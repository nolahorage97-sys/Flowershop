<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Restock History';

$history = $conn->query("
    SELECT sa.*, f.name flower_name, u.full_name adjusted_by_name
    FROM stock_adjustments sa
    JOIN flowers f ON sa.flower_id=f.id
    JOIN users u ON sa.adjusted_by=u.id
    ORDER BY sa.adjusted_at DESC LIMIT 200
");
include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Restock History</h1><p>All stock adjustments and restocking events</p></div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Flower</th><th>Type</th><th>Change</th><th>Before</th><th>After</th><th>Adjusted By</th><th>Reason</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($r=$history->fetch_assoc()):
          $positive = $r['quantity_change'] > 0;
          $typeBadge = ['restock'=>'badge-green','wastage'=>'badge-red','damage'=>'badge-yellow','correction'=>'badge-blue'];
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($r['flower_name']) ?></strong></td>
          <td><span class="badge <?= $typeBadge[$r['adjustment_type']] ?? 'badge-gray' ?>"><?= ucfirst($r['adjustment_type']) ?></span></td>
          <td style="font-weight:700;color:<?= $positive ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= $positive ? '+' : '' ?><?= $r['quantity_change'] ?>
          </td>
          <td><?= $r['quantity_before'] ?></td>
          <td><strong><?= $r['quantity_after'] ?></strong></td>
          <td><?= htmlspecialchars($r['adjusted_by_name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars(substr($r['reason'] ?? '—', 0, 50)) ?></td>
          <td class="text-muted"><?= date('d M Y H:i', strtotime($r['adjusted_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
