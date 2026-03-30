<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'Inventory';

$flowers = $conn->query("SELECT f.*, c.name cat_name FROM flowers f LEFT JOIN categories c ON f.category_id=c.id WHERE f.is_active=1 ORDER BY f.name");
include '../includes/header.php';
?>
<div class="page-header"><div><h1>Inventory View</h1><p>Current stock levels</p></div></div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Flower</th><th>Category</th><th>Price (KES)</th><th>Stock</th><th>Reorder At</th><th>Status</th></tr></thead>
      <tbody>
        <?php $i=1; while ($f=$flowers->fetch_assoc()):
          if ($f['quantity']==0)                       { $badge='<span class="badge badge-red">Out of Stock</span>'; $rc='out-of-stock-row'; }
          elseif ($f['quantity']<=$f['reorder_level']) { $badge='<span class="badge badge-yellow">Low Stock</span>'; $rc='low-stock-row'; }
          else                                          { $badge='<span class="badge badge-green">OK</span>'; $rc=''; }
        ?>
        <tr class="<?= $rc ?>">
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($f['name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($f['color']??'') ?></small></td>
          <td><?= htmlspecialchars($f['cat_name']??'—') ?></td>
          <td><?= number_format($f['price'],2) ?></td>
          <td><strong><?= $f['quantity'] ?></strong> <?= htmlspecialchars($f['unit']) ?>s</td>
          <td><?= $f['reorder_level'] ?></td>
          <td><?= $badge ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
