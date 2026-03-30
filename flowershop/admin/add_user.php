<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Add User';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $role     = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    $address  = trim($_POST['address'] ?? '');

    if (empty($fullName))  $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($username) < 3) $errors[] = 'Username must be ≥ 3 characters.';
    if (strlen($password) < 6) $errors[] = 'Password must be ≥ 6 characters.';
    if (!in_array($role, ['admin','owner','staff','customer'])) $errors[] = 'Invalid role.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows) $errors[] = 'Email or username already taken.';
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name,email,username,password,role,phone,address) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssss', $fullName,$email,$username,$hash,$role,$phone,$address);
        if ($stmt->execute()) {
            logActivity('Add User', "Created $role: $username");
            setFlash('success', "User '$fullName' created successfully.");
            redirect(APP_URL . '/admin/users.php');
        } else {
            $errors[] = 'Failed to create user.';
        }
        $stmt->close();
    }
}
include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>Add New User</h1><p>Create an account with a specific role</p></div>
  <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2" style="flex-direction:column;align-items:flex-start;gap:4px;">
  <?php foreach ($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px;">
  <div class="card-header"><span class="card-title">User Information</span></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select name="role" class="form-control" required>
            <?php foreach (['admin','owner','staff','customer'] as $r): ?>
            <option value="<?= $r ?>" <?= ($_POST['role']??'customer')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($_POST['address']??'') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create User</button>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
