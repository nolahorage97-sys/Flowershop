<?php
require_once '../includes/config.php';
requireRole('admin');

// Handle toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $conn->query("UPDATE users SET is_active = 1 - is_active WHERE id=$uid AND id != " . (int)$_SESSION['user_id']);
    logActivity('Toggle User', "Toggled user ID $uid");
    setFlash('success', 'User status updated.');
    redirect(APP_URL . '/admin/users.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$uid");
        logActivity('Delete User', "Deleted user ID $uid");
        setFlash('success', 'User deleted.');
    } else {
        setFlash('error', 'You cannot delete your own account.');
    }
    redirect(APP_URL . '/admin/users.php');
}

$roleFilter = $_GET['role'] ?? '';
$search     = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($roleFilter) $where .= " AND role='" . $conn->real_escape_string($roleFilter) . "'";
if ($search)     $where .= " AND (full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";

$users = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC");
$pageTitle = 'Manage Users';
include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>Manage Users</h1><p>View, edit and control user accounts</p></div>
  <a href="<?= APP_URL ?>/admin/add_user.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add User</a>
</div>

<!-- Filters -->
<div class="card mb-2">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group mb-0" style="flex:1;min-width:200px;">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control" placeholder="Name, username or email..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="form-group mb-0">
        <label class="form-label">Role</label>
        <select name="role" class="form-control">
          <option value="">All Roles</option>
          <?php foreach (['admin','owner','staff','customer'] as $r): ?>
          <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group mb-0">
        <button class="btn btn-primary">Filter</button>
        <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline" style="margin-left:6px;">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($u = $users->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
          <td><code><?= htmlspecialchars($u['username']) ?></code></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td>
            <?php
            $badges = ['admin'=>'badge-purple','owner'=>'badge-green','staff'=>'badge-blue','customer'=>'badge-yellow'];
            ?>
            <span class="badge <?= $badges[$u['role']] ?? 'badge-gray' ?>"><?= ucfirst($u['role']) ?></span>
          </td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge badge-green">Active</span>
            <?php else: ?>
              <span class="badge badge-red">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= APP_URL ?>/admin/edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-pen"></i></a>
              <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                <i class="fa fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
              </a>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this user permanently?" title="Delete"><i class="fa fa-trash"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
