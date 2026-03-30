<?php

$user = currentUser();
$flash = getFlash();
$role = $user['role'];

// Role → colour accent
$roleColors = [
    'admin'    => '#7c3aed',
    'owner'    => '#059669',
    'staff'    => '#0284c7',
    'customer' => '#db2777',
];
$accent = $roleColors[$role] ?? '#059669';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> | <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
<style>:root { --accent: <?= $accent ?>; }</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">&#127801;</span>
    <div>
      <div class="brand-name"><?= APP_NAME ?></div>
      <div class="brand-role"><?= ucfirst($role) ?> Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php if ($role === 'admin'): ?>
      <a href="<?= APP_URL ?>/admin/dashboard.php"       class="nav-item"><i class="fa fa-chart-pie"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/admin/users.php"           class="nav-item"><i class="fa fa-users"></i> Manage Users</a>
      <a href="<?= APP_URL ?>/admin/add_user.php"        class="nav-item"><i class="fa fa-user-plus"></i> Add User</a>
      <a href="<?= APP_URL ?>/admin/activity_log.php"    class="nav-item"><i class="fa fa-list-alt"></i> Activity Log</a>
      <a href="<?= APP_URL ?>/admin/reports.php"         class="nav-item"><i class="fa fa-file-chart-column"></i> Reports</a>
      <a href="<?= APP_URL ?>/admin/enquiries.php"       class="nav-item"><i class="fa fa-envelope"></i> Enquiries</a>
      <a href="<?= APP_URL ?>/admin/settings.php"        class="nav-item"><i class="fa fa-gear"></i> Settings</a>

    <?php elseif ($role === 'owner'): ?>
      <a href="<?= APP_URL ?>/owner/dashboard.php"       class="nav-item"><i class="fa fa-chart-line"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/owner/inventory.php"       class="nav-item"><i class="fa fa-boxes-stacked"></i> Inventory</a>
      <a href="<?= APP_URL ?>/owner/add_flower.php"      class="nav-item"><i class="fa fa-plus-circle"></i> Add Flower</a>
      <a href="<?= APP_URL ?>/owner/sales.php"           class="nav-item"><i class="fa fa-receipt"></i> Sales</a>
      <a href="<?= APP_URL ?>/owner/orders.php"          class="nav-item"><i class="fa fa-bag-shopping"></i> Orders</a>
      <a href="<?= APP_URL ?>/owner/restock_history.php" class="nav-item"><i class="fa fa-rotate"></i> Restock History</a>
      <a href="<?= APP_URL ?>/owner/reports.php"         class="nav-item"><i class="fa fa-file-lines"></i> Reports</a>
      <a href="<?= APP_URL ?>/owner/staff.php"           class="nav-item"><i class="fa fa-id-badge"></i> My Staff</a>

    <?php elseif ($role === 'staff'): ?>
      <a href="<?= APP_URL ?>/staff/dashboard.php"       class="nav-item"><i class="fa fa-gauge"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/staff/process_sale.php"    class="nav-item"><i class="fa fa-cash-register"></i> Process Sale</a>
      <a href="<?= APP_URL ?>/staff/sales_history.php"   class="nav-item"><i class="fa fa-clock-rotate-left"></i> Sales History</a>
      <a href="<?= APP_URL ?>/staff/inventory.php"       class="nav-item"><i class="fa fa-warehouse"></i> View Inventory</a>
      <a href="<?= APP_URL ?>/staff/orders.php"          class="nav-item"><i class="fa fa-clipboard-list"></i> Orders Queue</a>
      <a href="<?= APP_URL ?>/staff/adjust_stock.php"    class="nav-item"><i class="fa fa-sliders"></i> Adjust Stock</a>

    <?php elseif ($role === 'customer'): ?>
      <a href="<?= APP_URL ?>/customer/dashboard.php"    class="nav-item"><i class="fa fa-house"></i> Home</a>
      <a href="<?= APP_URL ?>/customer/shop.php"         class="nav-item"><i class="fa fa-store"></i> Browse Flowers</a>
      <a href="<?= APP_URL ?>/customer/cart.php"         class="nav-item"><i class="fa fa-cart-shopping"></i> My Cart</a>
      <a href="<?= APP_URL ?>/customer/orders.php"       class="nav-item"><i class="fa fa-box"></i> My Orders</a>
      <a href="<?= APP_URL ?>/customer/enquiry.php"      class="nav-item"><i class="fa fa-comment-dots"></i> Make Enquiry</a>
      <a href="<?= APP_URL ?>/customer/profile.php"      class="nav-item"><i class="fa fa-circle-user"></i> My Profile</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/logout.php" class="logout-btn">
      <i class="fa fa-right-from-bracket"></i> Logout
    </a>
  </div>
</aside>

<!-- Main wrapper -->
<div class="main-wrapper">
  <!-- Top bar -->
  <header class="topbar">
    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
      <i class="fa fa-bars"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></div>
    <div class="topbar-user">
      <span class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
      <span><?= htmlspecialchars($user['name']) ?></span>
    </div>
  </header>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="flash flash-<?= $flash['type'] ?>">
    <i class="fa fa-<?= $flash['type'] === 'success' ? 'circle-check' : ($flash['type'] === 'error' ? 'circle-xmark' : 'circle-info') ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
  </div>
  <?php endif; ?>

  <main class="content">

  <script>
    // add image_url to stored items
const Cart = {
    get: function() {
        const cart = localStorage.getItem('flowerCart');
        return cart ? JSON.parse(cart) : [];
    },
    
    save: function(cart) {
        localStorage.setItem('flowerCart', JSON.stringify(cart));
        this.updateBadge();
        window.dispatchEvent(new Event('cartUpdated'));
    },
    
    add: function(id, name, price, maxQty, imageUrl) {
        let cart = this.get();
        const existing = cart.find(item => item.id === id);
        
        if (existing) {
            if (existing.qty + 1 <= maxQty) {
                existing.qty++;
            } else {
                showToast(`Only ${maxQty} ${name} available!`);
                return false;
            }
        } else {
            cart.push({
                id: id,
                name: name,
                price: price,
                qty: 1,
                image_url: imageUrl || ''  // Store the image URL
            });
        }
        this.save(cart);
        showToast(`${name} added to cart!`);
        return true;
    },
    
    updateQtyByDelta: function(id, delta) {
        let cart = this.get();
        const item = cart.find(i => i.id === id);
        if (item) {
            const newQty = item.qty + delta;
            if (newQty <= 0) {
                this.remove(id);
            } else {
                item.qty = newQty;
                this.save(cart);
            }
        }
    },
    
    remove: function(id) {
        let cart = this.get();
        cart = cart.filter(item => item.id !== id);
        this.save(cart);
    },
    
    clear: function() {
        localStorage.removeItem('flowerCart');
        this.updateBadge();
        window.dispatchEvent(new Event('cartUpdated'));
    },
    
    updateBadge: function() {
        const cart = this.get();
        const total = cart.reduce((sum, item) => sum + item.qty, 0);
        const badges = document.querySelectorAll('.cart-badge, .cart-count');
        badges.forEach(badge => {
            if (badge) badge.textContent = total;
        });
    }
};
  </script>