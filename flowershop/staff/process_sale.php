<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'Process Sale';

$flowers = $conn->query("SELECT f.*, c.name cat_name FROM flowers f LEFT JOIN categories c ON f.category_id=c.id WHERE f.is_active=1 AND f.quantity>0 ORDER BY f.name");

$errors = [];
$success = false;
$saleNumber = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $items     = $_POST['items'] ?? [];     // array of flower_id
    $qtys      = $_POST['qtys'] ?? [];      // array of quantities
    $payment   = $_POST['payment_method'] ?? 'cash';
    $notes     = trim($_POST['notes'] ?? '');
    $staffId   = (int)$_SESSION['user_id'];

    // Filter empty
    $cartItems = [];
    foreach ($items as $k=>$fid) {
        $fid = (int)$fid;
        $qty = (int)($qtys[$k] ?? 0);
        if ($fid > 0 && $qty > 0) $cartItems[] = ['fid'=>$fid,'qty'=>$qty];
    }

    if (empty($cartItems)) {
        $errors[] = 'Add at least one item to the sale.';
    }

    // Validate stock
    if (empty($errors)) {
        foreach ($cartItems as &$ci) {
            $stmt = $conn->prepare("SELECT id,name,price,quantity FROM flowers WHERE id=? AND is_active=1");
            $stmt->bind_param('i',$ci['fid']); $stmt->execute();
            $frow = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$frow) { $errors[] = "Flower ID {$ci['fid']} not found."; break; }
            if ($frow['quantity'] < $ci['qty']) { $errors[] = "Only {$frow['quantity']} {$frow['name']} available."; break; }
            $ci['price'] = $frow['price'];
            $ci['name']  = $frow['name'];
            $ci['stock'] = $frow['quantity'];
        }
    }

    if (empty($errors)) {
        // Create order
        $saleNumber = generateNumber('SLE');
        $total = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $cartItems));

        $orderNum = generateNumber('ORD');
        $allowed  = ['cash','mpesa','card','bank'];
        $payment  = in_array($payment,$allowed)?$payment:'cash';

        // Create order first
        $stmt = $conn->prepare("INSERT INTO orders (customer_id,order_number,status,total_amount,notes) VALUES (?,?,'delivered',?,?)");
        $dummy = $staffId; // staff-created direct sale uses staff as placeholder
        $stmt->bind_param('isds',$dummy,$orderNum,$total,$notes);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        // Insert order items & reduce stock
        foreach ($cartItems as $ci) {
            $sub = $ci['price']*$ci['qty'];
            $stmt = $conn->prepare("INSERT INTO order_items (order_id,flower_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iiidd',$orderId,$ci['fid'],$ci['qty'],$ci['price'],$sub);
            $stmt->execute(); $stmt->close();

            // Reduce stock
            $newQty = $ci['stock'] - $ci['qty'];
            $conn->query("UPDATE flowers SET quantity=$newQty WHERE id={$ci['fid']}");
            // Log adjustment
            $conn->query("INSERT INTO stock_adjustments (flower_id,adjusted_by,adjustment_type,quantity_change,quantity_before,quantity_after,reason)
                VALUES ({$ci['fid']},$staffId,'correction',{$ci['qty']},{$ci['stock']},$newQty,'Sale: $saleNumber')");
        }

        // Record sale
        $stmt = $conn->prepare("INSERT INTO sales (order_id,staff_id,sale_number,total_amount,payment_method,payment_status) VALUES (?,?,?,?,'$payment','paid')");
        $stmt->bind_param('iisd',$orderId,$staffId,$saleNumber,$total);
        $stmt->execute(); $stmt->close();

        logActivity('Process Sale', "Sale $saleNumber, total: ".formatCurrency($total));
        setFlash('success', "Sale $saleNumber recorded successfully! Total: ".formatCurrency($total));
        redirect(APP_URL.'/staff/process_sale.php');
    }
}
include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>Process Sale</h1><p>Record a walk-in customer purchase</p></div>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2" style="flex-direction:column;gap:4px;">
  <?php foreach($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">
  <!-- Flower Selector -->
  <div class="card">
    <div class="card-header"><span class="card-title">Available Flowers</span></div>
    <div class="card-body" style="padding:0;">
      <table id="flowerTable">
        <thead><tr><th>Flower</th><th>Price</th><th>Stock</th><th>Add</th></tr></thead>
        <tbody>
          <?php $flowers->data_seek(0); while ($f=$flowers->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($f['name']) ?></div>
              <div style="font-size:.75rem;color:#6b7280;"><?= htmlspecialchars($f['cat_name']??'') ?> · <?= htmlspecialchars($f['color']??'') ?></div>
            </td>
            <td><?= formatCurrency($f['price']) ?></td>
            <td><?= $f['quantity'] ?></td>
            <td>
              <button type="button" class="btn btn-sm btn-primary"
                onclick="addToCart(<?= $f['id'] ?>, '<?= addslashes(htmlspecialchars($f['name'])) ?>', <?= $f['price'] ?>, <?= $f['quantity'] ?>)">
                <i class="fa fa-plus"></i>
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Cart / Sale Form -->
  <div class="card" style="position:sticky;top:80px;">
    <div class="card-header"><span class="card-title">Current Sale</span></div>
    <div class="card-body">
      <form method="POST" id="saleForm">
        <div id="cartItems">
          <div class="empty-state" id="emptyCart"><div class="empty-icon">&#128722;</div><p>No items added yet.<br>Click <strong>+</strong> to add flowers.</p></div>
        </div>

        <div id="cartSummary" style="display:none;margin-top:16px;border-top:2px solid var(--border);padding-top:14px;">
          <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;">
            <span>Total</span><span id="cartTotal" style="color:var(--accent);">KES 0.00</span>
          </div>
        </div>

        <div class="form-group mt-2">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-control">
            <option value="cash">Cash</option>
            <option value="mpesa">M-Pesa</option>
            <option value="card">Card</option>
            <option value="bank">Bank Transfer</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Customer name, special instructions..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;" id="submitBtn" disabled>
          <i class="fa fa-cash-register"></i> Complete Sale
        </button>
      </form>
    </div>
  </div>
</div>

<script>
let cart = {};

function addToCart(id, name, price, maxStock) {
  if (cart[id]) {
    if (cart[id].qty < maxStock) cart[id].qty++;
    else { alert('Max stock reached for ' + name); return; }
  } else {
    cart[id] = { name, price, qty: 1, max: maxStock };
  }
  renderCart();
}

function removeFromCart(id) {
  delete cart[id];
  renderCart();
}

function changeQty(id, val) {
  cart[id].qty = Math.max(1, Math.min(parseInt(val)||1, cart[id].max));
  renderCart();
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const empty     = document.getElementById('emptyCart');
  const summary   = document.getElementById('cartSummary');
  const submitBtn = document.getElementById('submitBtn');
  const totalEl   = document.getElementById('cartTotal');
  const form      = document.getElementById('saleForm');

  // Remove old hidden inputs
  form.querySelectorAll('.cart-input').forEach(e=>e.remove());

  const keys = Object.keys(cart);
  if (!keys.length) {
    container.innerHTML = '';
    container.appendChild(empty);
    summary.style.display = 'none';
    submitBtn.disabled = true;
    return;
  }
  empty.style.display = 'none';
  summary.style.display = 'block';
  submitBtn.disabled = false;

  let html = '', total = 0, i = 0;
  for (const id of keys) {
    const item = cart[id];
    const sub = item.price * item.qty;
    total += sub;
    html += `<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
      <div style="flex:1;">
        <div style="font-weight:600;font-size:.88rem;">${item.name}</div>
        <div style="font-size:.75rem;color:#6b7280;">KES ${item.price.toFixed(2)} × <input type="number" min="1" max="${item.max}" value="${item.qty}" onchange="changeQty('${id}',this.value)" style="width:50px;border:1px solid #ddd;border-radius:4px;padding:2px 5px;font-size:.82rem;"> = <strong>KES ${sub.toFixed(2)}</strong></div>
      </div>
      <button type="button" onclick="removeFromCart('${id}')" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:1.1rem;">&#10005;</button>
    </div>`;
    // Add hidden inputs
    const inp1 = document.createElement('input'); inp1.type='hidden'; inp1.name='items[]'; inp1.value=id; inp1.className='cart-input'; form.appendChild(inp1);
    const inp2 = document.createElement('input'); inp2.type='hidden'; inp2.name='qtys[]'; inp2.value=item.qty; inp2.className='cart-input'; form.appendChild(inp2);
    i++;
  }
  container.innerHTML = html;
  totalEl.textContent = 'KES ' + total.toFixed(2);
}
</script>

<?php include '../includes/footer.php'; ?>
