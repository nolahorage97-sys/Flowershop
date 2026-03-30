<?php
require_once 'includes/config.php';

// Already logged in → redirect to dashboard
if (isLoggedIn()) redirect(getDashboardUrl());

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, username, password, role, is_active FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            logActivity('Login', "User {$user['username']} logged in", $user['id']);
            redirect(getDashboardUrl($user['role']));
        } elseif ($user && !$user['is_active']) {
            $error = 'Your account has been deactivated. Contact the administrator.';
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body class="auth-body">
<div class="auth-box">
  <div class="auth-logo">
    <div class="logo-icon">&#127801;</div>
    <h1><?= APP_NAME ?></h1>
    <p><?= APP_TAGLINE ?></p>
  </div>

  <?php if ($error): ?>
  <div class="flash flash-error" style="margin-bottom:16px;">
    <i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label class="form-label"><i class="fa fa-user"></i> Username or Email</label>
      <input type="text" name="username" class="form-control"
             placeholder="Enter your username or email"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label class="form-label"><i class="fa fa-lock"></i> Password</label>
      <div style="position:relative;">
        <input type="password" name="password" id="passwordField" class="form-control"
               placeholder="Enter your password" required>
        <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280;">
          <i class="fa fa-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
      <i class="fa fa-right-to-bracket"></i> Sign In
    </button>
  </form>

  <div class="auth-footer">
    Don't have an account? <a href="<?= APP_URL ?>/register.php">Register here</a>
  </div>
  
</div>

<script>
function togglePass() {
  const f = document.getElementById('passwordField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type = 'text'; i.className = 'fa fa-eye-slash'; }
  else { f.type = 'password'; i.className = 'fa fa-eye'; }
}
</script>
</body>
</html>
