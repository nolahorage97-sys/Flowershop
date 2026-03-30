<?php
require_once '../includes/config.php';
requireRole('staff');
$pageTitle = 'Orders Queue';

// Update status
if (isset($_GET['update'], $_GET['oid'])) {
    $oid = (int)$_GET['oid'];
    $ns  = addslashes($_GET['update']);
    $allowed = ['confirmed','processing','ready','delivered','cancelled'];
    if (in_array($_GET['update'], $allowed)) {
        $conn->query("UPDATE orders SET status='$ns' WHERE id=$oid");
        // If delivered, record a sale
        if ($_GET['update'] === 'delivered') {
            $o = $conn->query("SELECT * FROM orders WHERE id=$oid")->fetch_assoc();
            if ($o) {
                $sn = generateNumber('SLE');
                $sid = (int)$_SESSION['user_id'];
                $amt = $o['total_amount'];
                $conn->query("INSERT INTO sales (order_id,staff_id,sale_number,total_amount,payment_method,payment_status) VALUES ($oid,$sid,'$sn',$amt,'cash','paid')");
                // Reduce stock for each item
                $items = $conn->query("SELECT * FROM order_items WHERE order_id=$oid");
                while ($it=$items->fetch_assoc()) {
                    $fid=$it['flower_id']; $qty=$it['quantity'];
                    $cur=$conn->query("SELECT quantity FROM flowers WHERE id=$fid")->fetch_assoc()['quantity'];
                    $newq=max(0,$cur-$qty);
                    $conn->query("UPDATE flowers SET quantity=$newq WHERE id=$fid");
                    $conn->query("INSERT INTO stock_adjustments (flower_id,adjusted_by,adjustment_type,quantity_change,quantity_before,quantity_after,reason) VALUES ($fid,$sid,'correction',-$qty,$cur,$newq,'Delivered order $oid')");
                }
            }
        }
        logActivity('Order Update', "Order $oid → $ns");
        setFlash('success','Order status updated.');
    }
    redirect(APP_URL.'/staff/orders.php');
}

$orders = $conn->query("
    SELECT o.*, u.full_name cust, u.phone cust_phone,
      (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) items
    FROM orders o JOIN users u ON o.customer_id=u.id
    WHERE o.status NOT IN ('delivered','cancelled')
    ORDER BY o.created_at
");
include '../includes/header.php';
?>
<div class="page-header"><div><h1>Orders Queue</h1><p>Pending and in-progress orders to fulfill</p></div></div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Order No.</th><th>Customer</th><th>Phone</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php $i=1; $has=false; while ($o=$orders->fetch_assoc()): $has=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
          <td><?= htmlspecialchars($o['cust']) ?></td>
          <td><?= htmlspecialchars($o['cust_phone']??'—') ?></td>
          <td><?= $o['items'] ?></td>
          <td><strong><?= formatCurrency($o['total_amount']) ?></strong></td>
          <td>
            <?php $bc=['pending'=>'badge-yellow','confirmed'=>'badge-blue','processing'=>'badge-purple','ready'=>'badge-green']; ?>
            <span class="badge <?= $bc[$o['status']]??'badge-gray' ?>"><?= ucfirst($o['status']) ?></span>
          </td>
          <td class="text-muted"><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
          <td>
            <select onchange="location='?update='+this.value+'&oid=<?= $o['id'] ?>'" class="form-control" style="font-size:.8rem;padding:5px 8px;">
              <?php foreach (['confirmed','processing','ready','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$has): ?><tr><td colspan="9"><div class="empty-state"><div class="empty-icon">&#10003;</div><p>No pending orders!</p></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
