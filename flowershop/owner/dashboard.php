<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Owner Dashboard';

// KPIs
$totalFlowers  = $conn->query("SELECT COUNT(*) c FROM flowers WHERE is_active=1")->fetch_assoc()['c'];
$totalStock    = $conn->query("SELECT COALESCE(SUM(quantity),0) c FROM flowers WHERE is_active=1")->fetch_assoc()['c'];
$todaySales    = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['c'];
$monthRevenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM sales WHERE MONTH(sale_date)=MONTH(NOW()) AND YEAR(sale_date)=YEAR(NOW())")->fetch_assoc()['c'];
$pendingOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE status IN ('pending','confirmed')")->fetch_assoc()['c'];
$lowStockItems = $conn->query("SELECT COUNT(*) c FROM flowers WHERE quantity<=reorder_level AND is_active=1")->fetch_assoc()['c'];

// Low stock list
$lowStock = $conn->query("SELECT name, quantity, reorder_level FROM flowers WHERE quantity<=reorder_level AND is_active=1 ORDER BY quantity ASC LIMIT 8");

// Recent sales
$recentSales = $conn->query("SELECT s.sale_number, s.total_amount, s.sale_date, u.full_name staff_name
    FROM sales s LEFT JOIN users u ON s.staff_id=u.id
    ORDER BY s.sale_date DESC LIMIT 6");

// Monthly revenue (last 6 months) for chart
$chart = $conn->query("
    SELECT DATE_FORMAT(sale_date,'%b') mo, SUM(total_amount) rev, COUNT(*) cnt
    FROM sales WHERE sale_date>=DATE_SUB(NOW(),INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sale_date,'%Y-%m') ORDER BY MIN(sale_date)
");
$cLabels=[]; $cRevs=[]; $cCounts=[];
while ($r=$chart->fetch_assoc()) { $cLabels[]=$r['mo']; $cRevs[]=(float)$r['rev']; $cCounts[]=(int)$r['cnt']; }

// Top 5 flowers by sales
$topFive = $conn->query("
    SELECT f.name, SUM(oi.quantity) qty
    FROM order_items oi JOIN flowers f ON oi.flower_id=f.id
    JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled'
    GROUP BY f.id ORDER BY qty DESC LIMIT 5
");
$fNames=[]; $fQtys=[];
while ($r=$topFive->fetch_assoc()) { $fNames[]=$r['name']; $fQtys[]=(int)$r['qty']; }

include '../includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="page-header">
  <div><h1>Shop Dashboard</h1><p>Welcome back, <?= htmlspecialchars(explode(' ',$_SESSION['user_name'])[0]) ?>! Here's your shop overview.</p></div>
  <a href="<?= APP_URL ?>/owner/add_flower.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Flower</a>
</div>

<?php if ($lowStockItems > 0): ?>
<div class="alert alert-warning mb-2">
  <i class="fa fa-triangle-exclamation"></i>
  <div><strong><?= $lowStockItems ?> flower<?= $lowStockItems>1?'s':'' ?> running low on stock.</strong> Check your inventory to reorder before stock runs out.</div>
</div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-sun"></i></div>
    <div><div class="stat-value"><?= formatCurrency($todaySales) ?></div><div class="stat-label">Today's Sales</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-calendar"></i></div>
    <div><div class="stat-value"><?= formatCurrency($monthRevenue) ?></div><div class="stat-label">This Month</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-boxes-stacked"></i></div>
    <div><div class="stat-value"><?= number_format($totalStock) ?></div><div class="stat-label">Total Stems in Stock</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-seedling"></i></div>
    <div><div class="stat-value"><?= $totalFlowers ?></div><div class="stat-label">Flower Varieties</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fa fa-bag-shopping"></i></div>
    <div><div class="stat-value"><?= $pendingOrders ?></div><div class="stat-label">Pending Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa fa-triangle-exclamation"></i></div>
    <div><div class="stat-value"><?= $lowStockItems ?></div><div class="stat-label">Low Stock Alerts</div></div>
  </div>
</div>

<div class="grid-2 mb-2">
  <!-- Revenue Chart -->
  <div class="card">
    <div class="card-header"><span class="card-title">Revenue — Last 6 Months</span></div>
    <div class="card-body"><canvas id="revenueChart" height="230"></canvas></div>
  </div>
  <!-- Top Flowers -->
  <div class="card">
    <div class="card-header"><span class="card-title">Top Selling Flowers</span></div>
    <div class="card-body"><canvas id="topChart" height="230"></canvas></div>
  </div>
</div>

<div class="grid-2">
  <!-- Low Stock -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">&#9888; Low Stock Alerts</span>
      <a href="<?= APP_URL ?>/owner/inventory.php" class="btn btn-sm btn-outline">Full Inventory</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Flower</th><th>In Stock</th><th>Reorder At</th><th>Status</th></tr></thead>
        <tbody>
          <?php while ($f=$lowStock->fetch_assoc()):
            $pct = $f['reorder_level'] ? min(100, round($f['quantity']/$f['reorder_level']*100)) : 100;
            $cls = $f['quantity']==0 ? 'stock-critical' : 'stock-low';
          ?>
          <tr class="<?= $f['quantity']<=0?'out-of-stock-row':'low-stock-row' ?>">
            <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
            <td><?= $f['quantity'] ?></td>
            <td><?= $f['reorder_level'] ?></td>
            <td>
              <?php if ($f['quantity']==0): ?>
                <span class="badge badge-red">Out of Stock</span>
              <?php else: ?>
                <span class="badge badge-yellow">Low</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if (!$lowStockItems): ?>
          <tr><td colspan="4" class="text-center text-muted" style="padding:24px;">&#10003; All stock levels healthy</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Sales -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Sales</span>
      <a href="<?= APP_URL ?>/owner/sales.php" class="btn btn-sm btn-outline">All Sales</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Sale #</th><th>Staff</th><th>Amount</th><th>Date</th></tr></thead>
        <tbody>
          <?php while ($s=$recentSales->fetch_assoc()): ?>
          <tr>
            <td><code><?= htmlspecialchars($s['sale_number']) ?></code></td>
            <td><?= htmlspecialchars($s['staff_name'] ?? '—') ?></td>
            <td><strong><?= formatCurrency($s['total_amount']) ?></strong></td>
            <td class="text-muted"><?= date('d M, H:i', strtotime($s['sale_date'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
renderLineChart('revenueChart', <?= json_encode($cLabels) ?>, [{
  label:'Revenue (KES)', data: <?= json_encode($cRevs) ?>,
  borderColor:'#059669', backgroundColor:'#05966920', tension:0.4, fill:true
}]);
renderBarChart('topChart', <?= json_encode($fNames) ?>, <?= json_encode($fQtys) ?>, '#059669');
</script>

<?php include '../includes/footer.php'; ?>
