<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(getDashboardUrl());

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fullName))  $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($username) || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    
    // Enhanced password validation using the new function
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        $passwordErrors = validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }
    }
    
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email or username already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role, phone) VALUES (?,?,?,?,'customer',?)");
        $stmt->bind_param('sssss', $fullName, $email, $username, $hash, $phone);
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            logActivity('Register', "New customer registered: $username", $userId);
            setFlash('success', 'Account created! You can now log in.');
            redirect(APP_URL . '/login.php');
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body class="auth-body">
<div class="auth-box" style="max-width:460px;">
  <div class="auth-logo">
    <div class="logo-icon">&#127801;</div>
    <h1><?= APP_NAME ?></h1>
    <p>Create your customer account</p>
  </div>

  <?php if ($errors): ?>
  <div class="flash flash-error" style="margin-bottom:16px;flex-direction:column;align-items:flex-start;gap:4px;">
    <?php foreach ($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-control" placeholder="e.g. Jane Wanjiku"
             value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="07XX XXX XXX"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Username *</label>
      <input type="text" name="username" class="form-control" placeholder="Choose a username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" id="password" class="form-control" 
           placeholder="Create a strong password" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
      <i class="fa fa-user-plus"></i> Create Account
    </button>

    <div class="form-group">
    <small class="form-text" style="font-size:0.75rem; color:#6b7280; margin-top:4px; display:block;">
        Password must contain:
        <span id="lengthCheck" style="color:#dc2626;">8+ characters</span> |
        <span id="upperCheck" style="color:#dc2626;">Uppercase</span> |
        <span id="lowerCheck" style="color:#dc2626;">Lowercase</span> |
        <span id="numberCheck" style="color:#dc2626;">Number</span> |
        <span id="specialCheck" style="color:#dc2626;">Special char</span>
    </small>
</div>
  </form>

  <div class="auth-footer">
    Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in</a>
  </div>
</div>
</body>

<script>
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    
    // Check each requirement
    const lengthValid = password.length >= 8;
    const upperValid = /[A-Z]/.test(password);
    const lowerValid = /[a-z]/.test(password);
    const numberValid = /[0-9]/.test(password);
    const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    // Update UI
    updateCheck('lengthCheck', lengthValid);
    updateCheck('upperCheck', upperValid);
    updateCheck('lowerCheck', lowerValid);
    updateCheck('numberCheck', numberValid);
    updateCheck('specialCheck', specialValid);
});

function updateCheck(elementId, isValid) {
    const element = document.getElementById(elementId);
    if (isValid) {
        element.style.color = '#059669';
        element.innerHTML = '✓ ' + element.innerHTML.substring(2);
    } else {
        element.style.color = '#dc2626';
        element.innerHTML = '✗ ' + element.innerHTML.substring(2);
    }
}
</script>
</html>
