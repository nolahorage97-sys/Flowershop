<?php
require_once '../includes/config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Invalid user.'); redirect(APP_URL.'/admin/users.php'); }

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i',$id); $stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$u) { setFlash('error','User not found.'); redirect(APP_URL.'/admin/users.php'); }

$pageTitle = 'Edit User';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $role     = $_POST['role'] ?? $u['role'];
    $address  = trim($_POST['address'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (empty($fullName)) $errors[] = 'Full name required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $stmt->bind_param('si',$email,$id); $stmt->execute();
        if ($stmt->get_result()->num_rows) $errors[] = 'Email already used by another account.';
        $stmt->close();
    }

    if (empty($errors)) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,role=?,phone=?,address=?,is_active=?,password=? WHERE id=?");
            $stmt->bind_param('sssssssi',$fullName,$email,$role,$phone,$address,$isActive,$hash,$id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?,email=?,role=?,phone=?,address=?,is_active=? WHERE id=?");
            $stmt->bind_param('sssssii',$fullName,$email,$role,$phone,$address,$isActive,$id);
        }
        if ($stmt->execute()) {
            logActivity('Edit User', "Updated user ID $id");
            setFlash('success','User updated successfully.');
            redirect(APP_URL.'/admin/users.php');
        }
        $stmt->close();
    }
}
include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>Edit User</h1><p>Modify account details and permissions</p></div>
  <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2">
  <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px;">
  <div class="card-header"><span class="card-title">Edit: <?= htmlspecialchars($u['full_name']) ?></span></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name']??$u['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" class="form-control">
            <?php foreach (['admin','owner','staff','customer'] as $r): ?>
            <option value="<?= $r ?>" <?= (($_POST['role']??$u['role'])===$r)?'selected':'' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??$u['email']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone']??$u['phone']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
        <input type="password" name="password" class="form-control" placeholder="New password...">
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($_POST['address']??$u['address']??'') ?></textarea>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="is_active" <?= ($u['is_active']?'checked':'') ?> style="width:18px;height:18px;">
          Account Active
        </label>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
