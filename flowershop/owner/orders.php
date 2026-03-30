<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Orders';

if (isset($_GET['status_update'], $_GET['oid'])) {
    $oid = (int)$_GET['oid'];
    $ns  = $conn->real_escape_string($_GET['status_update']);
    $allowed = ['pending','confirmed','processing','ready','delivered','cancelled'];
    if (in_array($ns, $allowed)) {
        $conn->query("UPDATE orders SET status='$ns' WHERE id=$oid");
        logActivity('Update Order Status', "Order $oid → $ns");
        setFlash('success', 'Order status updated.');
    }
    redirect(APP_URL.'/owner/orders.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE o.status='".addslashes($statusFilter)."'" : '';

$orders = $conn->query("
    SELECT o.*, u.full_name customer_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) item_count
    FROM orders o JOIN users u ON o.customer_id=u.id
    $where ORDER BY o.created_at DESC
");
include '../includes/header.php';
?>
<div class="page-header">
  <div><h1>Orders</h1><p>All customer orders</p></div>
  <div>
    <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','processing'=>'Processing','ready'=>'Ready','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="btn btn-sm <?= $statusFilter===$v?'btn-primary':'btn-outline' ?>" style="margin-left:4px;"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Order No.</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Update Status</th></tr></thead>
      <tbody>
        <?php $i=1; while ($o=$orders->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
          <td><?= htmlspecialchars($o['customer_name']) ?></td>
          <td><?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></td>
          <td><strong><?= formatCurrency($o['total_amount']) ?></strong></td>
          <td>
            <?php $bc=['pending'=>'badge-yellow','confirmed'=>'badge-blue','processing'=>'badge-purple','ready'=>'badge-green','delivered'=>'badge-green','cancelled'=>'badge-red']; ?>
            <span class="badge <?= $bc[$o['status']] ?? 'badge-gray' ?>"><?= ucfirst($o['status']) ?></span>
          </td>
          <td class="text-muted"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
          <td>
            <select onchange="location='?status_update='+this.value+'&oid=<?= $o['id'] ?>'" class="form-control" style="font-size:.8rem;padding:5px 8px;">
              <?php foreach (['pending','confirmed','processing','ready','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
