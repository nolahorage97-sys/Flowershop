<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Inventory';

// Delete flower
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $fid = (int)$_GET['delete'];
    $conn->query("UPDATE flowers SET is_active=0 WHERE id=$fid");
    logActivity('Delete Flower', "Deactivated flower ID $fid");
    setFlash('success','Flower removed from inventory.');
    redirect(APP_URL.'/owner/inventory.php');
}

$search   = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$stockFilter = $_GET['stock'] ?? '';

$where = "WHERE f.is_active=1";
if ($search)     $where .= " AND (f.name LIKE '%".addslashes($search)."%' OR f.color LIKE '%".addslashes($search)."%')";
if ($catFilter)  $where .= " AND f.category_id=$catFilter";
if ($stockFilter === 'low')  $where .= " AND f.quantity <= f.reorder_level AND f.quantity > 0";
if ($stockFilter === 'out')  $where .= " AND f.quantity = 0";
if ($stockFilter === 'ok')   $where .= " AND f.quantity > f.reorder_level";

$flowers = $conn->query("SELECT f.*, c.name cat_name FROM flowers f LEFT JOIN categories c ON f.category_id=c.id $where ORDER BY f.name");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include '../includes/header.php';
?>

<style>
/* Image styling for inventory */



.flower-placeholder {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #d1fae5, #fdf8f0);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    border: 1px solid var(--border);
}


.flower-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.flower-image-cell {
    width: 60px;
    height: 60px;
    flex-shrink: 0;
}

.flower-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--sand);
}

.flower-placeholder {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #d1fae5, #fdf8f0);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    border: 1px solid var(--border);
    flex-shrink: 0;
}

.flower-details {
    flex: 1;
}

.flower-name {
    font-weight: 600;
    margin-bottom: 4px;
    color: #1f2937;
}

.flower-color {
    font-size: .75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
}
</style>

<div class="page-header">
  <div><h1>Inventory</h1><p>Manage all flower stock levels</p></div>
  <a href="<?= APP_URL ?>/owner/add_flower.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Flower</a>
</div>

<!-- Filter bar -->
<div class="card mb-2">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group mb-0" style="flex:2;min-width:200px;">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control" placeholder="Flower name or colour..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="form-group mb-0">
        <label class="form-label">Category</label>
        <select name="cat" class="form-control">
          <option value="">All Categories</option>
          <?php $categories->data_seek(0); while ($cat=$categories->fetch_assoc()): ?>
          <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group mb-0">
        <label class="form-label">Stock Status</label>
        <select name="stock" class="form-control">
          <option value="">All</option>
          <option value="ok"  <?= $stockFilter==='ok'?'selected':'' ?>>In Stock</option>
          <option value="low" <?= $stockFilter==='low'?'selected':'' ?>>Low Stock</option>
          <option value="out" <?= $stockFilter==='out'?'selected':'' ?>>Out of Stock</option>
        </select>
      </div>
      <div class="form-group mb-0">
        <button class="btn btn-primary">Filter</button>
        <a href="<?= APP_URL ?>/owner/inventory.php" class="btn btn-outline" style="margin-left:6px;">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Flower</th>
          <th>Category</th>
          <th>Price (KES)</th>
          <th>Stock</th>
          <th>Level</th>
          <th>Season</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; $count=0; while ($f=$flowers->fetch_assoc()): $count++;
          $pct = $f['reorder_level'] ? min(100, round($f['quantity'] / ($f['reorder_level']*2) * 100)) : 100;
          if ($f['quantity'] == 0)                       { $cls='stock-critical'; $badge='<span class="badge badge-red">Out of Stock</span>'; $rowCls='out-of-stock-row'; }
          elseif ($f['quantity'] <= $f['reorder_level']) { $cls='stock-low';      $badge='<span class="badge badge-yellow">Low</span>';        $rowCls='low-stock-row'; }
          else                                            { $cls='stock-ok';       $badge='<span class="badge badge-green">OK</span>';           $rowCls=''; }
        ?>
        <tr class="<?= $rowCls ?>">
          <td><?= $i++ ?></td>
          
          <!-- Flower column with image -->
          <td>
              <div class="flower-info">
                  <?php 
                  // Check if image exists and is not empty
                  $hasImage = !empty($f['image_url']);
                  
                  if ($hasImage):
                      // Check if it's a local uploaded image or external URL
                      $isLocalImage = strpos($f['image_url'], 'uploads/') === 0;
                      $imagePath = $isLocalImage ? APP_URL . '/' . $f['image_url'] : $f['image_url'];
                  ?>
                      <div class="flower-image-cell">
                          <img src="<?= htmlspecialchars($imagePath) ?>" 
                              alt="<?= htmlspecialchars($f['name']) ?>"
                              class="flower-thumb"
                              onload="this.style.display='block'"
                              onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'flower-placeholder\' style=\'width:50px;height:50px;background:linear-gradient(135deg, #d1fae5, #fdf8f0);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;border:1px solid var(--border);\'>🌼</div>';">
                      </div>
                  <?php else: ?>
                      <div class="flower-placeholder">🌼</div>
                  <?php endif; ?>
                  
                  <div class="flower-details">
                      <div class="flower-name"><?= htmlspecialchars($f['name']) ?></div>
                      <?php if (!empty($f['color'])): ?>
                          <div class="flower-color">
                              <i class="fa fa-palette"></i> <?= htmlspecialchars($f['color']) ?>
                          </div>
                      <?php endif; ?>
                      <?php if (!empty($f['image_url'])): ?>
                          <div class="flower-color" style="font-size:0.7rem;">
                              <i class="fa fa-image"></i> Has Image
                          </div>
                      <?php endif; ?>
                  </div>
              </div>
          </td>
          
          <td><?= htmlspecialchars($f['cat_name'] ?? '—') ?></td>
          <td><strong><?= number_format($f['price'],2) ?></strong></td>
          <td><strong><?= $f['quantity'] ?></strong> <span class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($f['unit']) ?></span></td>
          <td style="min-width:120px;">
            <div class="<?= $cls ?>">
              <div class="stock-bar"><div class="stock-bar-fill" style="width:<?= $pct ?>%"></div></div>
              <div style="font-size:.72rem;color:#6b7280;margin-top:3px;">Reorder at: <?= $f['reorder_level'] ?></div>
            </div>
          </td>
          <td class="text-muted"><?= htmlspecialchars($f['season'] ?? '—') ?></td>
          <td><?= $badge ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= APP_URL ?>/owner/edit_flower.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-pen"></i></a>
              <a href="<?= APP_URL ?>/owner/restock.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-primary" title="Restock"><i class="fa fa-plus"></i></a>
              <a href="?delete=<?= $f['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Remove this flower from inventory?" title="Delete"><i class="fa fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$count): ?>
          <tr>
            <td colspan="9">
              <div class="empty-state">
                <div class="empty-icon">&#127801;</div>
                <p>No flowers found. <a href="<?= APP_URL ?>/owner/add_flower.php">Add one?</a></p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>