<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'My Cart & Checkout';

//Order placement
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $cartData = json_decode($_POST['cart_json'] ?? '[]', true);
    $address  = trim($_POST['delivery_address'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');
    $custId   = (int)$_SESSION['user_id'];

    if (empty($cartData)) { $errors[] = 'Your cart is empty.'; }
    if (empty($address))  { $errors[] = 'Delivery address is required.'; }

    // Validate each item against live stock
    $validatedCart = [];
    if (empty($errors)) {
        foreach ($cartData as $item) {
            $fid = (int)$item['id'];
            $qty = (int)$item['qty'];
            $stmt = $conn->prepare("SELECT id, name, price, quantity, image_url FROM flowers WHERE id=? AND is_active=1");
            $stmt->bind_param('i',$fid); 
            $stmt->execute();
            $f = $stmt->get_result()->fetch_assoc(); 
            $stmt->close();
            if (!$f) { $errors[] = "Flower not found."; break; }
            if ($f['quantity'] < $qty) { $errors[] = "Sorry, only {$f['quantity']} {$f['name']} available."; break; }
            $validatedCart[] = [
                'id'=>$fid,
                'name'=>$f['name'],
                'price'=>$f['price'],
                'qty'=>$qty,
                'stock'=>$f['quantity'],
                'image_url'=>$f['image_url']
            ];
        }
    }

    if (empty($errors)) {
        $total = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $validatedCart));
        $orderNum = generateNumber('ORD');
        $esc_addr = $conn->real_escape_string($address);
        $esc_note = $conn->real_escape_string($notes);

        $stmt = $conn->prepare("INSERT INTO orders (customer_id,order_number,status,total_amount,delivery_address,notes) VALUES (?,?,'pending',?,?,?)");
        $stmt->bind_param('isdss', $custId, $orderNum, $total, $esc_addr, $esc_note);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        foreach ($validatedCart as $ci) {
            $sub = $ci['price']*$ci['qty'];
            $stmt = $conn->prepare("INSERT INTO order_items (order_id,flower_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iiidd', $orderId, $ci['id'], $ci['qty'], $ci['price'], $sub);
            $stmt->execute(); 
            $stmt->close();
            
            // Update stock
            $conn->query("UPDATE flowers SET quantity = quantity - {$ci['qty']} WHERE id = {$ci['id']}");
        }

        logActivity('Place Order', "Customer placed order $orderNum, total: $total");
        setFlash('success', "Order $orderNum placed successfully! We'll confirm it shortly. 🌸");
        // Clear cart signal
        echo "<script>Cart.clear(); window.location='".APP_URL."/customer/orders.php';</script>";
        exit;
    }
}
include '../includes/header.php';
?>

<style>
/* Cart page styles */
.cart-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #e5e7eb;
    align-items: center;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-thumb {
    width: 70px;
    height: 70px;
    flex-shrink: 0;
    border-radius: 10px;
    overflow: hidden;
    background: linear-gradient(135deg, #d1fae5, #fdf8f0);
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-thumb-emoji {
    font-size: 2rem;
}

.cart-details {
    flex: 1;
}

.cart-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.cart-price {
    font-size: 0.8rem;
    color: #6b7280;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.qty-value {
    min-width: 30px;
    text-align: center;
    font-weight: 700;
}

.remove-item-btn {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 5px;
    transition: all 0.2s;
}

.remove-item-btn:hover {
    color: #b91c1c;
    transform: scale(1.1);
}

.order-summary {
    background: var(--sand);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--accent);
    padding-top: 10px;
    border-top: 2px solid #e5e7eb;
    margin-top: 5px;
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-cart p {
    color: #6b7280;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .cart-item {
        flex-wrap: wrap;
    }
    
    .cart-thumb {
        width: 50px;
        height: 50px;
    }
    
    .qty-control {
        margin-left: auto;
    }
}
</style>

<div class="page-header">
  <div><h1>My Cart</h1><p>Review your items and place your order</p></div>
  <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Continue Shopping</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2">
  <?php foreach($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid-2" style="align-items:start; gap: 24px;">
  <!-- Cart Items -->
  <div class="card">
    <div class="card-header"><span class="card-title">Cart Items (<span id="itemCount">0</span>)</span></div>
    <div class="card-body" id="cartContainer">
      <div class="empty-cart" id="emptyMsg">
        <div class="empty-cart-icon">🛒</div>
        <p>Your cart is empty.</p>
        <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-primary">Browse Flowers</a>
      </div>
    </div>
  </div>

  <!-- Checkout Form -->
  <div class="card" style="position:sticky;top:80px;">
    <div class="card-header"><span class="card-title">Place Order</span></div>
    <div class="card-body">
      <form method="POST" id="checkoutForm">
        <input type="hidden" name="cart_json" id="cartJson">
        
        <div class="order-summary" id="orderSummary" style="display: none;">
          <div class="summary-row">
            <span>Subtotal</span>
            <span id="subtotal">KES 0.00</span>
          </div>
          <div class="summary-row">
            <span>Delivery Fee</span>
            <span>KES 0.00</span>
          </div>
          <div class="summary-total">
            <span>Total</span>
            <span id="orderTotal">KES 0.00</span>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Delivery Address *</label>
          <textarea name="delivery_address" class="form-control" rows="3" placeholder="Your full delivery address, estate, building, etc." required></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Additional Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Preferred delivery time, special requests..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" id="placeOrderBtn" style="width:100%;justify-content:center;padding:13px;" disabled>
          <i class="fa fa-check-circle"></i> Place Order
        </button>
        
        <button type="button" class="btn btn-danger" style="width:100%;justify-content:center;margin-top:8px;" onclick="clearCartUI()">
          <i class="fa fa-trash"></i> Clear Cart
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function getImageHtml(item) {
    if (item.image_url && item.image_url.trim() !== '') {
        // Check if it's a local uploaded image or external URL
        const isLocalImage = item.image_url.startsWith('uploads/');
        const imagePath = isLocalImage ? '<?= APP_URL ?>/' + item.image_url : item.image_url;
        return `<img src="${escapeHtml(imagePath)}" alt="${escapeHtml(item.name)}" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\"cart-thumb-emoji\">🌸</div>';">`;
    }
    return '<div class="cart-thumb-emoji">🌸</div>';
}

function renderCartPage() {
    const cart = Cart.get();
    const container = document.getElementById('cartContainer');
    const emptyMsg = document.getElementById('emptyMsg');
    const btn = document.getElementById('placeOrderBtn');
    const orderTotal = document.getElementById('orderTotal');
    const subtotalSpan = document.getElementById('subtotal');
    const itemCountSpan = document.getElementById('itemCount');
    const jsonInput = document.getElementById('cartJson');
    const orderSummary = document.getElementById('orderSummary');

    if (!cart || !cart.length) {
        if (container) {
            container.innerHTML = '';
            if (emptyMsg) container.appendChild(emptyMsg);
            if (emptyMsg) emptyMsg.style.display = 'block';
        }
        if (btn) btn.disabled = true;
        if (orderTotal) orderTotal.textContent = 'KES 0.00';
        if (subtotalSpan) subtotalSpan.textContent = 'KES 0.00';
        if (itemCountSpan) itemCountSpan.textContent = '0';
        if (jsonInput) jsonInput.value = '[]';
        if (orderSummary) orderSummary.style.display = 'none';
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';
    if (btn) btn.disabled = false;
    if (orderSummary) orderSummary.style.display = 'block';
    
    let html = '';
    let grandTotal = 0;
    let totalItems = 0;
    
    for (const item of cart) {
        const sub = item.price * item.qty;
        grandTotal += sub;
        totalItems += item.qty;
        
        html += `
            <div class="cart-item" data-id="${item.id}">
                <div class="cart-thumb">
                    ${getImageHtml(item)}
                </div>
                <div class="cart-details">
                    <div class="cart-name">${escapeHtml(item.name)}</div>
                    <div class="cart-price">KES ${item.price.toFixed(2)} / unit</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="qty-control">
                        <button class="qty-btn" onclick="decrementQty(${item.id})">−</button>
                        <span class="qty-value">${item.qty}</span>
                        <button class="qty-btn" onclick="incrementQty(${item.id})">+</button>
                    </div>
                    <strong style="min-width:80px;text-align:right;">KES ${sub.toFixed(2)}</strong>
                    <button class="remove-item-btn" onclick="removeItem(${item.id})">✕</button>
                </div>
            </div>
        `;
    }
    
    if (container) container.innerHTML = html;
    if (orderTotal) orderTotal.textContent = 'KES ' + grandTotal.toFixed(2);
    if (subtotalSpan) subtotalSpan.textContent = 'KES ' + grandTotal.toFixed(2);
    if (itemCountSpan) itemCountSpan.textContent = totalItems;
    if (jsonInput) jsonInput.value = JSON.stringify(cart);
}

function incrementQty(id) {
    Cart.updateQtyByDelta(id, 1);
    renderCartPage();
}

function decrementQty(id) {
    Cart.updateQtyByDelta(id, -1);
    renderCartPage();
}

function removeItem(id) {
    Cart.remove(id);
    renderCartPage();
}

function clearCartUI() {
    if (!confirm('Clear your entire cart?')) return;
    Cart.clear();
    renderCartPage();
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

document.addEventListener('DOMContentLoaded', renderCartPage);
window.addEventListener('cartUpdated', renderCartPage);
</script>

<?php include '../includes/footer.php'; ?>