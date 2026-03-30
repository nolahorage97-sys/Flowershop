<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'My Orders';
$uid = (int)$_SESSION['user_id'];

// Cancel an order if it's still pending
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND customer_id=? AND status='pending'");
    $stmt->bind_param('ii', $oid, $uid); $stmt->execute(); $stmt->close();
    setFlash('info', 'Order cancelled.');
    redirect(APP_URL . '/customer/orders.php');
}

// View single order detail
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$orderDetail = null;
$orderItems  = null;
if ($viewId) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND customer_id=?");
    $stmt->bind_param('ii', $viewId, $uid); $stmt->execute();
    $orderDetail = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($orderDetail) {
        $orderItems = $conn->query("SELECT oi.*, f.name flower_name, f.color, f.unit
            FROM order_items oi JOIN flowers f ON oi.flower_id=f.id
            WHERE oi.order_id=$viewId");
    }
}

$orders = $conn->query("SELECT * FROM orders WHERE customer_id=$uid ORDER BY created_at DESC");

$statusColors = [
    'pending'    => 'badge-yellow',
    'confirmed'  => 'badge-blue',
    'processing' => 'badge-purple',
    'ready'      => 'badge-green',
    'delivered'  => 'badge-green',
    'cancelled'  => 'badge-red',
];
$statusIcons = [
    'pending'    => 'fa-clock',
    'confirmed'  => 'fa-circle-check',
    'processing' => 'fa-gear',
    'ready'      => 'fa-box',
    'delivered'  => 'fa-truck',
    'cancelled'  => 'fa-ban',
];

include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>My Orders</h1><p>Track and manage all your orders</p></div>
  <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-primary"><i class="fa fa-store"></i> Order More Flowers</a>
</div>

<?php if ($orderDetail): ?>
<!-- Single Order Detail View -->
<div class="card mb-2">
  <div class="card-header">
    <span class="card-title">Order: <code><?= htmlspecialchars($orderDetail['order_number']) ?></code></span>
    <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-sm btn-outline"><i class="fa fa-arrow-left"></i> All Orders</a>
  </div>
  <div class="card-body">
    <!-- Status progress bar -->
    <?php
    $steps = ['pending','confirmed','processing','ready','delivered'];
    $curIdx = array_search($orderDetail['status'], $steps);
    if ($orderDetail['status'] === 'cancelled') $curIdx = -1;
    ?>
    <?php if ($orderDetail['status'] !== 'cancelled'): ?>
    <div style="display:flex;justify-content:space-between;margin-bottom:28px;position:relative;">
      <div style="position:absolute;top:16px;left:0;right:0;height:3px;background:#e5e7eb;z-index:0;"></div>
      <div style="position:absolute;top:16px;left:0;height:3px;background:var(--accent);z-index:1;
           width:<?= $curIdx<0?0:min(100,($curIdx/4)*100) ?>%;transition:width .5s;"></div>
      <?php foreach ($steps as $i => $st): ?>
      <div style="text-align:center;z-index:2;flex:1;">
        <div style="width:34px;height:34px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;
          background:<?= $i<=$curIdx?'var(--accent)':'#e5e7eb' ?>;color:<?= $i<=$curIdx?'#fff':'#9ca3af' ?>;font-size:.9rem;">
          <i class="fa <?= $statusIcons[$st] ?>"></i></div>
        <div style="font-size:.72rem;margin-top:6px;color:<?= $i<=$curIdx?'var(--accent)':'#9ca3af' ?>;font-weight:<?= $i===$curIdx?'700':'400' ?>;"><?= ucfirst($st) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-danger mb-2"><i class="fa fa-ban"></i> This order was cancelled.</div>
    <?php endif; ?>

    <div class="grid-2" style="margin-bottom:20px;">
      <div>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:4px;">STATUS</p>
        <span class="badge <?= $statusColors[$orderDetail['status']] ?>" style="font-size:.85rem;padding:5px 12px;">
          <?= ucfirst($orderDetail['status']) ?>
        </span>
      </div>
      <div>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:4px;">ORDER TOTAL</p>
        <strong style="font-size:1.2rem;color:var(--accent);"><?= formatCurrency($orderDetail['total_amount']) ?></strong>
      </div>
      <div>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:4px;">ORDER DATE</p>
        <strong><?= date('d F Y, H:i', strtotime($orderDetail['created_at'])) ?></strong>
      </div>
      <div>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:4px;">DELIVERY ADDRESS</p>
        <span><?= nl2br(htmlspecialchars($orderDetail['delivery_address'] ?? '—')) ?></span>
      </div>
    </div>

    <?php if ($orderDetail['notes']): ?>
    <p class="text-muted" style="font-size:.85rem;margin-bottom:16px;"><strong>Notes:</strong> <?= htmlspecialchars($orderDetail['notes']) ?></p>
    <?php endif; ?>

    <!-- Items table -->
    <table>
      <thead><tr><th>Flower</th><th>Colour</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
      <tbody>
        <?php while ($oi = $orderItems->fetch_assoc()): ?>
        <tr>
          <td><strong>🌷 <?= htmlspecialchars($oi['flower_name']) ?></strong></td>
          <td><?= htmlspecialchars($oi['color'] ?? '—') ?></td>
          <td><?= formatCurrency($oi['unit_price']) ?></td>
          <td><?= $oi['quantity'] ?> <?= htmlspecialchars($oi['unit'] ?? '') ?>s</td>
          <td><strong><?= formatCurrency($oi['subtotal']) ?></strong></td>
        </tr>
        <?php endwhile; ?>
        <tr style="background:var(--sand);">
          <td colspan="4" class="text-right"><strong>ORDER TOTAL</strong></td>
          <td><strong style="color:var(--accent);font-size:1.05rem;"><?= formatCurrency($orderDetail['total_amount']) ?></strong></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- Orders list -->
<?php if ($orders->num_rows === 0): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state">
      <div class="empty-icon">🌸</div>
      <p>You haven't placed any orders yet.</p>
      <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-primary" style="margin-top:16px;"><i class="fa fa-store"></i> Browse Flowers</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Order No.</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php while ($o = $orders->fetch_assoc()):
          $itemCount = $conn->query("SELECT SUM(quantity) v FROM order_items WHERE order_id={$o['id']}")->fetch_assoc()['v'] ?? 0;
        ?>
        <tr>
          <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
          <td class="text-muted"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
          <td><?= $itemCount ?> stems/items</td>
          <td><strong><?= formatCurrency($o['total_amount']) ?></strong></td>
          <td>
            <span class="badge <?= $statusColors[$o['status']] ?? 'badge-gray' ?>">
              <i class="fa <?= $statusIcons[$o['status']] ?? 'fa-circle' ?>"></i>
              <?= ucfirst($o['status']) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="?view=<?= $o['id'] ?>" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
              <?php if ($o['status'] === 'pending'): ?>
              <a href="?cancel=<?= $o['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Cancel this order?">
                <i class="fa fa-ban"></i> Cancel
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
