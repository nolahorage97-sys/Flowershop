<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'My Dashboard';
$uid = (int)$_SESSION['user_id'];

$myOrders   = $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid")->fetch_assoc()['c'];
$pendingOrd = $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid AND status NOT IN ('delivered','cancelled')")->fetch_assoc()['c'];
$totalSpent = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE customer_id=$uid AND status!='cancelled'")->fetch_assoc()['c'];
$myEnq      = $conn->query("SELECT COUNT(*) c FROM enquiries WHERE customer_id=$uid")->fetch_assoc()['c'];

$recentOrders = $conn->query("SELECT * FROM orders WHERE customer_id=$uid ORDER BY created_at DESC LIMIT 5");

include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Welcome, <?= htmlspecialchars(explode(' ',$_SESSION['user_name'])[0]) ?>! &#127801;</h1><p>Manage your orders and explore our fresh flowers.</p></div>
  <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-primary"><i class="fa fa-store"></i> Browse Flowers</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-bag-shopping"></i></div><div><div class="stat-value"><?= $myOrders ?></div><div class="stat-label">Total Orders</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-clock"></i></div><div><div class="stat-value"><?= $pendingOrd ?></div><div class="stat-label">Active Orders</div></div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-coins"></i></div><div><div class="stat-value"><?= formatCurrency($totalSpent) ?></div><div class="stat-label">Total Spent</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa fa-comment-dots"></i></div><div><div class="stat-value"><?= $myEnq ?></div><div class="stat-label">My Enquiries</div></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Recent Orders</span><a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-sm btn-outline">All Orders</a></div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Order No.</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php $has=false; while ($o=$recentOrders->fetch_assoc()): $has=true; ?>
          <tr>
            <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
            <td><strong><?= formatCurrency($o['total_amount']) ?></strong></td>
            <td>
              <?php $bc=['pending'=>'badge-yellow','confirmed'=>'badge-blue','processing'=>'badge-purple','ready'=>'badge-green','delivered'=>'badge-green','cancelled'=>'badge-red']; ?>
              <span class="badge <?= $bc[$o['status']]??'badge-gray' ?>"><?= ucfirst($o['status']) ?></span>
            </td>
            <td class="text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$has): ?><tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No orders yet. <a href="<?= APP_URL ?>/customer/shop.php">Shop now!</a></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Quick Actions</span></div>
    <div class="card-body" style="display:grid;gap:12px;">
      <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-primary" style="justify-content:center;padding:14px;"><i class="fa fa-store"></i> Browse & Order Flowers</a>
      <a href="<?= APP_URL ?>/customer/cart.php" class="btn btn-outline" style="justify-content:center;padding:14px;"><i class="fa fa-cart-shopping"></i> View My Cart</a>
      <a href="<?= APP_URL ?>/customer/enquiry.php" class="btn btn-outline" style="justify-content:center;padding:14px;"><i class="fa fa-comment-dots"></i> Make an Enquiry</a>
      <a href="<?= APP_URL ?>/customer/profile.php" class="btn btn-outline" style="justify-content:center;padding:14px;"><i class="fa fa-circle-user"></i> Update My Profile</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
