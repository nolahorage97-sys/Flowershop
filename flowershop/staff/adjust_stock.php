<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'Adjust Stock';

$flowers = $conn->query("SELECT id,name,quantity,unit FROM flowers WHERE is_active=1 ORDER BY name");
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fid    = (int)$_POST['flower_id'];
    $qty    = (int)$_POST['quantity'];
    $type   = $_POST['type'] ?? 'wastage';
    $reason = trim($_POST['reason'] ?? '');
    $uid    = (int)$_SESSION['user_id'];
    $allowed = ['wastage','damage','correction'];

    if (!in_array($type, $allowed)) $errors[] = 'Invalid type.';
    if ($qty <= 0) $errors[] = 'Quantity must be > 0.';

    if (empty($errors)) {
        $frow = $conn->query("SELECT quantity,name FROM flowers WHERE id=$fid")->fetch_assoc();
        if (!$frow) { $errors[] = 'Flower not found.'; }
        else {
            $before = $frow['quantity'];
            $change = -$qty; // reductions only for staff
            $after  = max(0, $before + $change);
            $esc    = addslashes($reason);
            $conn->query("UPDATE flowers SET quantity=$after WHERE id=$fid");
            $conn->query("INSERT INTO stock_adjustments (flower_id,adjusted_by,adjustment_type,quantity_change,quantity_before,quantity_after,reason) VALUES ($fid,$uid,'$type',$change,$before,$after,'$esc')");
            logActivity('Stock Adjust', "$type on {$frow['name']}: $change");
            setFlash('success', "Stock adjusted for {$frow['name']}. New quantity: $after");
            redirect(APP_URL.'/staff/adjust_stock.php');
        }
    }
}
include '../includes/header.php';
?>
<div class="page-header"><div><h1>Adjust Stock</h1><p>Report wastage, damage, or stock corrections</p></div></div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2"><?php foreach($errors as $e) echo "<div>$e</div>"; ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px;">
  <div class="card-header"><span class="card-title">Stock Adjustment Form</span></div>
  <div class="card-body">
    <div class="alert alert-info mb-2"><i class="fa fa-circle-info"></i> <span>Staff adjustments <strong>reduce</strong> stock only. Contact the shop owner to add stock.</span></div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Flower *</label>
        <select name="flower_id" class="form-control" required>
          <option value="">— Select Flower —</option>
          <?php while ($f=$flowers->fetch_assoc()): ?>
          <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> (<?= $f['quantity'] ?> in stock)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Adjustment Type *</label>
          <select name="type" class="form-control">
            <option value="wastage">Wastage (Wilted/unsold)</option>
            <option value="damage">Damage (Physical damage)</option>
            <option value="correction">Correction (Count error)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity to Remove *</label>
          <input type="number" name="quantity" class="form-control" min="1" placeholder="e.g. 5" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Notes *</label>
        <textarea name="reason" class="form-control" placeholder="e.g. Flowers wilted due to heat, damaged in delivery..." required></textarea>
      </div>
      <button type="submit" class="btn btn-warning"><i class="fa fa-sliders"></i> Submit Adjustment</button>
    </form>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
