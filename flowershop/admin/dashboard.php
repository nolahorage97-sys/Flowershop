<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Admin Dashboard';

// Stats
$totalUsers    = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalFlowers  = $conn->query("SELECT COUNT(*) c FROM flowers WHERE is_active=1")->fetch_assoc()['c'];
$totalOrders   = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$totalSales    = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM sales")->fetch_assoc()['c'];
$openEnquiries = $conn->query("SELECT COUNT(*) c FROM enquiries WHERE status='open'")->fetch_assoc()['c'];
$lowStock      = $conn->query("SELECT COUNT(*) c FROM flowers WHERE quantity <= reorder_level AND is_active=1")->fetch_assoc()['c'];

// Recent users
$recentUsers = $conn->query("SELECT full_name, username, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Recent activity
$recentActivity = $conn->query("SELECT al.action, al.details, al.created_at, u.full_name
    FROM activity_log al LEFT JOIN users u ON al.user_id=u.id
    ORDER BY al.created_at DESC LIMIT 8");

// Users by role
$roleStats = [];
$roleRes = $conn->query("SELECT role, COUNT(*) cnt FROM users GROUP BY role");
while ($r = $roleRes->fetch_assoc()) $roleStats[$r['role']] = $r['cnt'];

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Admin Dashboard</h1>
    <p>System overview and management</p>
  </div>
  <a href="<?= APP_URL ?>/admin/add_user.php" class="btn btn-primary"><i class="fa fa-user-plus"></i> Add User</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-users"></i></div>
    <div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-seedling"></i></div>
    <div><div class="stat-value"><?= $totalFlowers ?></div><div class="stat-label">Flower Types</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-bag-shopping"></i></div>
    <div><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fa fa-coins"></i></div>
    <div><div class="stat-value"><?= formatCurrency($totalSales) ?></div><div class="stat-label">Total Revenue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa fa-envelope"></i></div>
    <div><div class="stat-value"><?= $openEnquiries ?></div><div class="stat-label">Open Enquiries</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fa fa-triangle-exclamation"></i></div>
    <div><div class="stat-value"><?= $lowStock ?></div><div class="stat-label">Low Stock Alerts</div></div>
  </div>
</div>

<div class="grid-2">
  <!-- Users by Role -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Users by Role</span>
      <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body">
      <?php
      $roles  = ['admin'=>'Admin','owner'=>'Shop Owner','staff'=>'Staff','customer'=>'Customer'];
      $colors = ['admin'=>'badge-purple','owner'=>'badge-green','staff'=>'badge-blue','customer'=>'badge-yellow'];
      foreach ($roles as $k => $v):
        $cnt = $roleStats[$k] ?? 0;
      ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #e5e7eb;">
        <span class="badge <?= $colors[$k] ?>"><?= $v ?></span>
        <strong><?= $cnt ?> user<?= $cnt != 1 ? 's' : '' ?></strong>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Recent Users -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Registrations</span>
    </div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody>
          <?php while ($u = $recentUsers->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
            <td><span class="badge badge-green"><?= ucfirst($u['role']) ?></span></td>
            <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Activity Log Preview -->
<div class="card mt-2">
  <div class="card-header">
    <span class="card-title">Recent Activity</span>
    <a href="<?= APP_URL ?>/admin/activity_log.php" class="btn btn-sm btn-outline">Full Log</a>
  </div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead><tr><th>User</th><th>Action</th><th>Details</th><th>Time</th></tr></thead>
      <tbody>
        <?php while ($a = $recentActivity->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($a['full_name'] ?? 'Guest') ?></td>
          <td><?= htmlspecialchars($a['action']) ?></td>
          <td class="text-muted"><?= htmlspecialchars(substr($a['details'],0,60)) ?></td>
          <td class="text-muted"><?= date('d M, H:i', strtotime($a['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
