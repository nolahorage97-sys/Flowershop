<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'System Reports';

$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) v FROM sales WHERE payment_status='paid'")->fetch_assoc()['v'];
$totalOrders    = $conn->query("SELECT COUNT(*) v FROM orders")->fetch_assoc()['v'];
$totalSales     = $conn->query("SELECT COUNT(*) v FROM sales")->fetch_assoc()['v'];
$totalCustomers = $conn->query("SELECT COUNT(*) v FROM users WHERE role='customer'")->fetch_assoc()['v'];

$monthlyRev = $conn->query("SELECT DATE_FORMAT(sale_date,'%b %Y') mo, SUM(total_amount) rev
    FROM sales WHERE payment_status='paid' AND sale_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sale_date,'%Y-%m') ORDER BY sale_date ASC");
$months=[]; $revenues=[];
while ($r=$monthlyRev->fetch_assoc()){ $months[]=$r['mo']; $revenues[]=(float)$r['rev']; }

$topFlowers = $conn->query("SELECT f.name, SUM(oi.quantity) total_sold, SUM(oi.subtotal) revenue
    FROM order_items oi JOIN flowers f ON oi.flower_id=f.id
    GROUP BY f.id ORDER BY total_sold DESC LIMIT 8");

$orderStatus = $conn->query("SELECT status, COUNT(*) cnt FROM orders GROUP BY status");
$statusData=[]; while($r=$orderStatus->fetch_assoc()) $statusData[$r['status']]=$r['cnt'];

include '../includes/header.php';
?>
<div class="page-header"><div><h1>System Reports</h1><p>Business intelligence overview</p></div></div>
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-coins"></i></div>
    <div><div class="stat-value"><?= formatCurrency($totalRevenue) ?></div><div class="stat-label">Total Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-receipt"></i></div>
    <div><div class="stat-value"><?= $totalSales ?></div><div class="stat-label">Completed Sales</div></div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-bag-shopping"></i></div>
    <div><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-users"></i></div>
    <div><div class="stat-value"><?= $totalCustomers ?></div><div class="stat-label">Customers</div></div></div>
</div>
<div class="grid-2 mb-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Monthly Revenue (Last 6 Months)</span></div>
    <div class="card-body"><canvas id="revenueChart" height="200"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Orders by Status</span></div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:center;"><canvas id="statusChart" height="200"></canvas></div>
  </div>
</div>
<div class="card">
  <div class="card-header"><span class="card-title">Top Selling Flowers</span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Flower</th><th>Units Sold</th><th>Revenue</th></tr></thead>
    <tbody>
      <?php $i=1; while($f=$topFlowers->fetch_assoc()): ?>
      <tr><td><?=$i++?></td><td><strong><?=htmlspecialchars($f['name'])?></strong></td><td><?=$f['total_sold']?></td><td><?=formatCurrency($f['revenue'])?></td></tr>
      <?php endwhile; if($i===1): ?><tr><td colspan="4" class="text-center text-muted" style="padding:24px;">No sales data yet</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
renderLineChart('revenueChart',<?=json_encode($months)?>,
  [{label:'Revenue (KES)',data:<?=json_encode($revenues)?>,borderColor:'#059669',backgroundColor:'rgba(5,150,105,.1)',fill:true,tension:.4,pointRadius:5}]);
renderDoughnut('statusChart',<?=json_encode(array_keys($statusData))?>,<?=json_encode(array_values($statusData))?>,
  ['#059669','#0284c7','#d97706','#7c3aed','#dc2626','#6b7280']);
</script>
<?php include '../includes/footer.php'; ?>
