<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Sales Records';

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');

$sales = $conn->query("
    SELECT s.*, u.full_name staff_name, o.order_number
    FROM sales s
    LEFT JOIN users u ON s.staff_id=u.id
    LEFT JOIN orders o ON s.order_id=o.id
    WHERE DATE(s.sale_date) BETWEEN '$dateFrom' AND '$dateTo'
    ORDER BY s.sale_date DESC
");

$totals = $conn->query("
    SELECT COUNT(*) cnt, COALESCE(SUM(total_amount),0) revenue
    FROM sales WHERE DATE(sale_date) BETWEEN '$dateFrom' AND '$dateTo'
")->fetch_assoc();

include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Sales Records</h1><p>All processed sales transactions</p></div>
</div>

<div class="card mb-2">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group mb-0">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= $dateFrom ?>">
      </div>
      <div class="form-group mb-0">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= $dateTo ?>">
      </div>
      <div class="form-group mb-0" style="align-self:flex-end;">
        <button class="btn btn-primary">Filter</button>
      </div>
      <div style="margin-left:auto;display:flex;gap:16px;align-items:center;">
        <div style="text-align:right;">
          <div style="font-size:.8rem;color:#6b7280;">Total Transactions</div>
          <div style="font-weight:700;font-size:1.2rem;"><?= $totals['cnt'] ?></div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.8rem;color:#6b7280;">Total Revenue</div>
          <div style="font-weight:700;font-size:1.2rem;color:var(--success);"><?= formatCurrency($totals['revenue']) ?></div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Sale No.</th><th>Order #</th><th>Staff</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php $i=1; while ($s=$sales->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><code><?= htmlspecialchars($s['sale_number']) ?></code></td>
          <td><?= $s['order_number'] ? '<code>'.htmlspecialchars($s['order_number']).'</code>' : '<span class="text-muted">Direct</span>' ?></td>
          <td><?= htmlspecialchars($s['staff_name'] ?? '—') ?></td>
          <td><strong><?= formatCurrency($s['total_amount']) ?></strong></td>
          <td><span class="badge badge-blue"><?= ucfirst($s['payment_method']) ?></span></td>
          <td>
            <?php $bc=['paid'=>'badge-green','pending'=>'badge-yellow','refunded'=>'badge-red']; ?>
            <span class="badge <?= $bc[$s['payment_status']] ?>"><?= ucfirst($s['payment_status']) ?></span>
          </td>
          <td class="text-muted"><?= date('d M Y H:i', strtotime($s['sale_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
