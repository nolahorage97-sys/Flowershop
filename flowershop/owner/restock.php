<?php
require_once '../includes/config.php';
requireRole('owner');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid flower.'); redirect(APP_URL.'/owner/inventory.php'); }
$stmt = $conn->prepare("SELECT * FROM flowers WHERE id=? AND is_active=1");
$stmt->bind_param('i',$id); $stmt->execute();
$f = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$f) { setFlash('error','Flower not found.'); redirect(APP_URL.'/owner/inventory.php'); }

$pageTitle = 'Restock: '.$f['name'];
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $qty    = (int)($_POST['quantity'] ?? 0);
    $type   = $_POST['type'] ?? 'restock';
    $reason = trim($_POST['reason'] ?? '');

    if ($qty <= 0) $errors[] = 'Quantity must be greater than 0.';

    if (empty($errors)) {
        $before = $f['quantity'];
        $change = in_array($type,['wastage','damage']) ? -$qty : $qty;
        $after  = max(0, $before + $change);
        $uid    = (int)$_SESSION['user_id'];

        $conn->query("UPDATE flowers SET quantity=$after WHERE id=$id");
        $esc = $conn->real_escape_string($reason);
        $conn->query("INSERT INTO stock_adjustments (flower_id,adjusted_by,adjustment_type,quantity_change,quantity_before,quantity_after,reason)
            VALUES ($id,$uid,'$type',$change,$before,$after,'$esc')");
        logActivity('Stock Adjustment', "$type on {$f['name']}: $change (before:$before after:$after)");
        setFlash('success', "Stock updated for \"{$f['name']}\". New quantity: $after");
        redirect(APP_URL.'/owner/inventory.php');
    }
}
include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Stock Adjustment</h1><p>Update quantity for: <strong><?= htmlspecialchars($f['name']) ?></strong></p></div>
  <a href="<?= APP_URL ?>/owner/inventory.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2"><?php foreach($errors as $e) echo "<div>$e</div>"; ?></div>
<?php endif; ?>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Current Status</span></div>
    <div class="card-body">
      <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <div style="text-align:center;padding:16px;">
          <div style="font-size:2.5rem;font-weight:700;color:var(--accent);"><?= $f['quantity'] ?></div>
          <div class="text-muted">Current Stock (<?= htmlspecialchars($f['unit']) ?>s)</div>
        </div>
        <div style="text-align:center;padding:16px;">
          <div style="font-size:2.5rem;font-weight:700;color:var(--warning);"><?= $f['reorder_level'] ?></div>
          <div class="text-muted">Reorder Level</div>
        </div>
        <div style="text-align:center;padding:16px;">
          <div style="font-size:2.5rem;font-weight:700;"><?= formatCurrency($f['price']) ?></div>
          <div class="text-muted">Unit Price</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Adjust Stock</span></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Adjustment Type *</label>
          <select name="type" class="form-control" required>
            <option value="restock">Restock (Add to stock)</option>
            <option value="wastage">Wastage (Flowers wilted/unsold)</option>
            <option value="damage">Damage (Damaged on delivery)</option>
            <option value="correction">Correction (Inventory count fix)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity *</label>
          <input type="number" name="quantity" min="1" class="form-control" placeholder="Enter number of <?= htmlspecialchars($f['unit']) ?>s" required>
        </div>
        <div class="form-group">
          <label class="form-label">Reason / Notes</label>
          <textarea name="reason" class="form-control" placeholder="Optional note about this adjustment..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-rotate"></i> Apply Adjustment</button>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
