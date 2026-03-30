<?php
require_once '../includes/config.php';
requireRole('owner');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid flower.'); redirect(APP_URL.'/owner/inventory.php'); }
$stmt = $conn->prepare("SELECT * FROM flowers WHERE id=? AND is_active=1");
$stmt->bind_param('i',$id); $stmt->execute();
$f = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$f) { setFlash('error','Flower not found.'); redirect(APP_URL.'/owner/inventory.php'); }

$pageTitle = 'Edit Flower';
$errors = [];
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $catId       = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $newQty      = (int)($_POST['quantity'] ?? 0);
    $reorder     = (int)($_POST['reorder_level'] ?? 10);
    $unit        = trim($_POST['unit'] ?? 'stem');
    $color       = trim($_POST['color'] ?? '');
    $season      = trim($_POST['season'] ?? '');

    if (empty($name)) $errors[] = 'Flower name is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than 0.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE flowers SET category_id=?,name=?,description=?,price=?,quantity=?,reorder_level=?,unit=?,color=?,season=? WHERE id=?");
        $stmt->bind_param('issdiiissi', $catId,$name,$description,$price,$newQty,$reorder,$unit,$color,$season,$id);
        if ($stmt->execute()) {
            // Log stock change if quantity changed
            if ($newQty != $f['quantity']) {
                $diff = $newQty - $f['quantity'];
                $type = $diff > 0 ? 'restock' : 'correction';
                $uid  = (int)$_SESSION['user_id'];
                $conn->query("INSERT INTO stock_adjustments (flower_id,adjusted_by,adjustment_type,quantity_change,quantity_before,quantity_after,reason)
                    VALUES ($id,$uid,'$type',$diff,{$f['quantity']},$newQty,'Manual edit by owner')");
            }
            logActivity('Edit Flower', "Updated flower ID $id: $name");
            setFlash('success',"\"$name\" updated successfully.");
            redirect(APP_URL.'/owner/inventory.php');
        } else { $errors[] = 'Update failed.'; }
        $stmt->close();
    }
}
include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Edit Flower</h1><p>Update flower details and stock</p></div>
  <a href="<?= APP_URL ?>/owner/inventory.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2">
  <?php foreach($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:700px;">
  <div class="card-header"><span class="card-title">Editing: <?= htmlspecialchars($f['name']) ?></span></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Flower Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name']??$f['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-control">
            <option value="0">— Select —</option>
            <?php while ($c=$categories->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= (($_POST['category_id']??$f['category_id'])==$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description']??$f['description']??'') ?></textarea>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Price (KES) *</label>
          <input type="number" name="price" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars($_POST['price']??$f['price']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Current Quantity</label>
          <input type="number" name="quantity" min="0" class="form-control" value="<?= htmlspecialchars($_POST['quantity']??$f['quantity']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Reorder Level</label>
          <input type="number" name="reorder_level" min="1" class="form-control" value="<?= htmlspecialchars($_POST['reorder_level']??$f['reorder_level']) ?>">
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Unit</label>
          <select name="unit" class="form-control">
            <?php foreach(['stem','bunch','pot','piece','box'] as $u): ?>
            <option value="<?= $u ?>" <?= (($_POST['unit']??$f['unit'])===$u)?'selected':'' ?>><?= ucfirst($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Colour</label>
          <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($_POST['color']??$f['color']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Season</label>
          <input type="text" name="season" class="form-control" value="<?= htmlspecialchars($_POST['season']??$f['season']??'') ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
      <a href="<?= APP_URL ?>/owner/restock.php?id=<?= $f['id'] ?>" class="btn btn-outline" style="margin-left:8px;"><i class="fa fa-plus"></i> Quick Restock</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
