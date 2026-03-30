<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Activity Log';

$logs = $conn->query("SELECT al.*, u.full_name, u.role FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 200");
include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Activity Log</h1><p>All system actions and events</p></div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
      <tbody>
        <?php $i=1; while ($l = $logs->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($l['full_name'] ?? 'Guest') ?></td>
          <td><?php if ($l['role']): ?><span class="badge badge-gray"><?= ucfirst($l['role']) ?></span><?php endif; ?></td>
          <td><strong><?= htmlspecialchars($l['action']) ?></strong></td>
          <td class="text-muted"><?= htmlspecialchars(substr($l['details'],0,80)) ?></td>
          <td class="text-muted"><code><?= htmlspecialchars($l['ip_address']) ?></code></td>
          <td class="text-muted"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
