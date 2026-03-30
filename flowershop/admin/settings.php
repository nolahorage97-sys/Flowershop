<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Settings';
include '../includes/header.php'; ?>
<div class="page-header"><div><h1>System Settings</h1><p>Administrator configuration</p></div></div>
<div class="card" style="max-width:540px;">
  <div class="card-header"><span class="card-title">Change My Password</span></div>
  <div class="card-body">
    <?php
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $cur=$_POST['current']??''; $new=$_POST['new_pass']??''; $con=$_POST['confirm']??'';
        $stmt=$conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param('i',$_SESSION['user_id']); $stmt->execute();
        $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!password_verify($cur,$row['password'])) { echo '<div class="flash flash-error mb-2">Current password is wrong.</div>'; }
        elseif ($new!==$con)                          { echo '<div class="flash flash-error mb-2">New passwords do not match.</div>'; }
        elseif (strlen($new)<6)                       { echo '<div class="flash flash-error mb-2">Password must be 6+ characters.</div>'; }
        else {
            $hash=password_hash($new,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si',$hash,$_SESSION['user_id']); $stmt->execute(); $stmt->close();
            echo '<div class="flash flash-success mb-2">Password updated successfully.</div>';
        }
    } ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current" class="form-control" required></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_pass" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" name="confirm" class="form-control" required></div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-key"></i> Update Password</button>
    </form>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
