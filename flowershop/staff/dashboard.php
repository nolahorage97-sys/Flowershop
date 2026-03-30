<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'Staff Dashboard';
$uid = (int)$_SESSION['user_id'];

$mySales     = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) rev FROM sales WHERE staff_id=$uid AND DATE(sale_date)=CURDATE()")->fetch_assoc();
$myWeek      = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) rev FROM sales WHERE staff_id=$uid AND sale_date>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc();
$pendingQ    = $conn->query("SELECT COUNT(*) c FROM orders WHERE status IN ('confirmed','processing')")->fetch_assoc()['c'];
$lowStock    = $conn->query("SELECT COUNT(*) c FROM flowers WHERE quantity<=reorder_level AND is_active=1")->fetch_assoc()['c'];

// My recent sales
$recentSales = $conn->query("SELECT s.sale_number, s.total_amount, s.payment_method, s.sale_date FROM sales s WHERE s.staff_id=$uid ORDER BY s.sale_date DESC LIMIT 6");

// Pending orders
$pendingOrders = $conn->query("SELECT o.order_number, o.total_amount, o.status, u.full_name cust FROM orders o JOIN users u ON o.customer_id=u.id WHERE o.status IN ('confirmed','processing') ORDER BY o.created_at LIMIT 5");

include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Staff Dashboard</h1><p>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(explode(' ',$_SESSION['user_name'])[0]) ?>!</p></div>
  <a href="<?= APP_URL ?>/staff/process_sale.php" class="btn btn-primary"><i class="fa fa-cash-register"></i> Process Sale</a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-sun"></i></div><div><div class="stat-value"><?= $mySales['c'] ?></div><div class="stat-label">My Sales Today</div></div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-coins"></i></div><div><div class="stat-value"><?= formatCurrency($mySales['rev']) ?></div><div class="stat-label">Today's Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa fa-calendar-week"></i></div><div><div class="stat-value"><?= formatCurrency($myWeek['rev']) ?></div><div class="stat-label">This Week Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-clipboard-list"></i></div><div><div class="stat-value"><?= $pendingQ ?></div><div class="stat-label">Orders to Process</div></div></div>
  <?php if ($lowStock): ?>
  <div class="stat-card"><div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa fa-triangle-exclamation"></i></div><div><div class="stat-value"><?= $lowStock ?></div><div class="stat-label">Low Stock Items</div></div></div>
  <?php endif; ?>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">My Recent Sales</span><a href="<?= APP_URL ?>/staff/sales_history.php" class="btn btn-sm btn-outline">View All</a></div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Sale No.</th><th>Amount</th><th>Payment</th><th>Time</th></tr></thead>
        <tbody>
          <?php $hasSales=false; while ($s=$recentSales->fetch_assoc()): $hasSales=true; ?>
          <tr>
            <td><code><?= htmlspecialchars($s['sale_number']) ?></code></td>
            <td><strong><?= formatCurrency($s['total_amount']) ?></strong></td>
            <td><span class="badge badge-blue"><?= ucfirst($s['payment_method']) ?></span></td>
            <td class="text-muted"><?= date('H:i', strtotime($s['sale_date'])) ?></td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$hasSales): ?><tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No sales recorded today.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Orders Queue</span><a href="<?= APP_URL ?>/staff/orders.php" class="btn btn-sm btn-outline">Full Queue</a></div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Order No.</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php $hasOrders=false; while ($o=$pendingOrders->fetch_assoc()): $hasOrders=true; ?>
          <tr>
            <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
            <td><?= htmlspecialchars($o['cust']) ?></td>
            <td><?= formatCurrency($o['total_amount']) ?></td>
            <td><span class="badge badge-yellow"><?= ucfirst($o['status']) ?></span></td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$hasOrders): ?><tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No pending orders.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
