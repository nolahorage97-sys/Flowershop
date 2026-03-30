<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Business Reports';

// Revenue last 6 months
$monthly = $conn->query("
    SELECT DATE_FORMAT(sale_date,'%b %Y') mo, SUM(total_amount) rev, COUNT(*) cnt
    FROM sales WHERE sale_date>=DATE_SUB(NOW(),INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sale_date,'%Y-%m') ORDER BY MIN(sale_date)
");
$mLabels=[]; $mRevs=[]; $mCnts=[];
while ($r=$monthly->fetch_assoc()) { $mLabels[]=$r['mo']; $mRevs[]=(float)$r['rev']; $mCnts[]=(int)$r['cnt']; }

// Top flowers
$topFlowers = $conn->query("
    SELECT f.name, f.color, f.season, SUM(oi.quantity) sold, SUM(oi.subtotal) revenue
    FROM order_items oi JOIN flowers f ON oi.flower_id=f.id
    JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled'
    GROUP BY f.id ORDER BY sold DESC LIMIT 10
");

// Seasonal demand
$seasonal = $conn->query("
    SELECT f.season, SUM(oi.quantity) qty
    FROM order_items oi JOIN flowers f ON oi.flower_id=f.id
    JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled' AND f.season IS NOT NULL
    GROUP BY f.season ORDER BY qty DESC
");
$sLabels=[]; $sQtys=[];
while ($r=$seasonal->fetch_assoc()) { if ($r['season']) { $sLabels[]=$r['season']; $sQtys[]=(int)$r['qty']; } }

// Wastage summary
$wastage = $conn->query("
    SELECT f.name, SUM(ABS(sa.quantity_change)) total_loss
    FROM stock_adjustments sa JOIN flowers f ON sa.flower_id=f.id
    WHERE sa.adjustment_type IN ('wastage','damage')
    GROUP BY f.id ORDER BY total_loss DESC LIMIT 6
");
$wLabels=[]; $wVals=[];
while ($r=$wastage->fetch_assoc()) { $wLabels[]=$r['name']; $wVals[]=(int)$r['total_loss']; }

// Summary
$summary = $conn->query("
    SELECT
      (SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE MONTH(sale_date)=MONTH(NOW())) thisMonth,
      (SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE MONTH(sale_date)=MONTH(NOW())-1) lastMonth,
      (SELECT COUNT(*) FROM orders WHERE status='delivered') delivered,
      (SELECT COUNT(*) FROM orders WHERE status='cancelled') cancelled,
      (SELECT COALESCE(SUM(ABS(quantity_change)),0) FROM stock_adjustments WHERE adjustment_type IN ('wastage','damage')) totalWastage
")->fetch_assoc();

include '../includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="page-header">
  <div><h1>Business Reports</h1><p>Sales performance, inventory analytics & seasonal trends</p></div>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fa fa-calendar"></i></div><div><div class="stat-value"><?= formatCurrency($summary['thisMonth']) ?></div><div class="stat-label">This Month Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#f3f4f6;color:#6b7280;"><i class="fa fa-calendar-minus"></i></div><div><div class="stat-value"><?= formatCurrency($summary['lastMonth']) ?></div><div class="stat-label">Last Month Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa fa-truck"></i></div><div><div class="stat-value"><?= $summary['delivered'] ?></div><div class="stat-label">Orders Delivered</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa fa-ban"></i></div><div><div class="stat-value"><?= $summary['cancelled'] ?></div><div class="stat-label">Orders Cancelled</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-trash"></i></div><div><div class="stat-value"><?= $summary['totalWastage'] ?></div><div class="stat-label">Total Units Wasted</div></div></div>
</div>

<div class="grid-2 mb-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Monthly Revenue Trend</span></div>
    <div class="card-body"><canvas id="revChart" height="230"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Seasonal Demand</span></div>
    <div class="card-body"><canvas id="seasonChart" height="230"></canvas></div>
  </div>
</div>

<div class="grid-2 mb-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Top Selling Flowers</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Rank</th><th>Flower</th><th>Colour</th><th>Season</th><th>Sold</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php $i=1; while ($f=$topFlowers->fetch_assoc()): ?>
          <tr>
            <td><span class="badge badge-green">#<?= $i++ ?></span></td>
            <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
            <td><?= htmlspecialchars($f['color']??'—') ?></td>
            <td><?= htmlspecialchars($f['season']??'—') ?></td>
            <td><?= number_format($f['sold']) ?></td>
            <td><?= formatCurrency($f['revenue']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Wastage / Damage by Flower</span></div>
    <div class="card-body"><canvas id="wastageChart" height="230"></canvas></div>
  </div>
</div>

<script>
renderLineChart('revChart', <?= json_encode($mLabels) ?>, [{
  label:'Revenue (KES)', data: <?= json_encode($mRevs) ?>,
  borderColor:'#059669', backgroundColor:'#05966920', tension:0.4, fill:true
}]);
renderBarChart('seasonChart', <?= json_encode($sLabels) ?>, <?= json_encode($sQtys) ?>, '#7c3aed');
renderBarChart('wastageChart', <?= json_encode($wLabels) ?>, <?= json_encode($wVals) ?>, '#dc2626');
</script>

<?php include '../includes/footer.php'; ?>
