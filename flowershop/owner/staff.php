<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'My Staff';

// Toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE users SET is_active=1-is_active WHERE id=$id AND role='staff'");
    setFlash('success','Staff status updated.');
    redirect(APP_URL.'/owner/staff.php');
}

// Delete staff
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id AND role='staff'");
    logActivity('Delete Staff',"Removed staff ID $id");
    setFlash('success','Staff member removed.');
    redirect(APP_URL.'/owner/staff.php');
}

$errors = [];
// Add staff
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fullName = trim($_POST['full_name']??'');
    $email    = trim($_POST['email']??'');
    $username = trim($_POST['username']??'');
    $phone    = trim($_POST['phone']??'');
    $password = $_POST['password']??'';

    if (empty($fullName))  $errors[]='Full name required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Valid email required.';
    if (strlen($username)<3) $errors[]='Username must be 3+ chars.';
    if (strlen($password)<6) $errors[]='Password must be 6+ chars.';

    if (empty($errors)) {
        $stmt=$conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $stmt->bind_param('ss',$email,$username); $stmt->execute();
        if ($stmt->get_result()->num_rows) $errors[]='Email or username already taken.';
        $stmt->close();
    }
    if (empty($errors)) {
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $stmt=$conn->prepare("INSERT INTO users (full_name,email,username,password,role,phone) VALUES (?,?,?,?,'staff',?)");
        $stmt->bind_param('sssss',$fullName,$email,$username,$hash,$phone);
        if ($stmt->execute()) {
            logActivity('Add Staff',"Owner added staff: $username");
            setFlash('success',"Staff member '$fullName' added.");
            redirect(APP_URL.'/owner/staff.php');
        }
        $stmt->close();
    }
}

$staff = $conn->query("SELECT u.*, COUNT(s.id) sales_count, COALESCE(SUM(s.total_amount),0) total_sales
    FROM users u LEFT JOIN sales s ON s.staff_id=u.id
    WHERE u.role='staff' GROUP BY u.id ORDER BY u.created_at DESC");

include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>My Staff</h1><p>Manage staff accounts and view their performance</p></div>
</div>

<div class="grid-2" style="align-items:start;">
  <!-- Staff list -->
  <div class="card">
    <div class="card-header"><span class="card-title">Staff Members</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Username</th><th>Phone</th><th>Sales</th><th>Revenue</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php while($s=$staff->fetch_assoc()): ?>
          <tr>
            <td><strong><?=htmlspecialchars($s['full_name'])?></strong><br><small class="text-muted"><?=htmlspecialchars($s['email'])?></small></td>
            <td><code><?=htmlspecialchars($s['username'])?></code></td>
            <td><?=htmlspecialchars($s['phone']??'—')?></td>
            <td><?=$s['sales_count']?></td>
            <td style="color:var(--success);font-weight:600;"><?=formatCurrency($s['total_sales'])?></td>
            <td><span class="badge <?=$s['is_active']?'badge-green':'badge-red' ?>"><?=$s['is_active']?'Active':'Inactive'?></span></td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="?toggle=<?=$s['id']?>" class="btn btn-sm btn-warning" title="<?=$s['is_active']?'Deactivate':'Activate'?>">
                  <i class="fa fa-<?=$s['is_active']?'ban':'check'?>"></i></a>
                <a href="?delete=<?=$s['id']?>" class="btn btn-sm btn-danger" data-confirm="Remove this staff member?">
                  <i class="fa fa-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add staff form -->
  <div class="card">
    <div class="card-header"><span class="card-title">Add New Staff Member</span></div>
    <div class="card-body">
      <?php if ($errors): ?>
      <div class="flash flash-error mb-2">
        <?php foreach($errors as $e): ?><div><?=htmlspecialchars($e)?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group"><label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?=htmlspecialchars($_POST['full_name']??'')?>" required></div>
        <div class="form-group"><label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Username *</label>
            <input type="text" name="username" class="form-control" value="<?=htmlspecialchars($_POST['username']??'')?>" required></div>
          <div class="form-group"><label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($_POST['phone']??'')?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required></div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Add Staff Member</button>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
