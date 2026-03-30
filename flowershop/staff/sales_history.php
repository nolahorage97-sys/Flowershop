<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'My Sales History';
$uid = (int)$_SESSION['user_id'];

$sales = $conn->query("SELECT s.*, o.order_number FROM sales s LEFT JOIN orders o ON s.order_id=o.id WHERE s.staff_id=$uid ORDER BY s.sale_date DESC LIMIT 100");
include '../includes/header.php';
?>
<div class="page-header"><div><h1>My Sales History</h1><p>All sales you have processed</p></div></div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Sale No.</th><th>Order No.</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
      <tbody>
        <?php $i=1; while ($s=$sales->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><code><?= htmlspecialchars($s['sale_number']) ?></code></td>
          <td><?= $s['order_number'] ? '<code>'.htmlspecialchars($s['order_number']).'</code>' : '—' ?></td>
          <td><strong><?= formatCurrency($s['total_amount']) ?></strong></td>
          <td><span class="badge badge-blue"><?= ucfirst($s['payment_method']) ?></span></td>
          <td class="text-muted"><?= date('d M Y H:i', strtotime($s['sale_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
