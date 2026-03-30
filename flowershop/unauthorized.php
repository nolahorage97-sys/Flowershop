<?php
require_once 'includes/config.php';
$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Denied | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body class="auth-body">
<div class="auth-box" style="text-align:center;max-width:440px;">
  <div style="font-size:4rem;margin-bottom:16px;">🚫</div>
  <h1 style="font-family:'Playfair Display',serif;color:#dc2626;font-size:1.8rem;margin-bottom:12px;">Access Denied</h1>
  <p style="color:#6b7280;margin-bottom:24px;">You don't have permission to view that page. Please return to your dashboard.</p>
  <?php if (isLoggedIn()): ?>
    <a href="<?= getDashboardUrl() ?>" class="btn btn-primary" style="justify-content:center;width:100%;padding:12px;">
      <i class="fa fa-house"></i> Return to My Dashboard
    </a>
  <?php else: ?>
    <a href="<?= APP_URL ?>/login.php" class="btn btn-primary" style="justify-content:center;width:100%;padding:12px;">
      <i class="fa fa-right-to-bracket"></i> Sign In
    </a>
  <?php endif; ?>
</div>
</body>
</html>
