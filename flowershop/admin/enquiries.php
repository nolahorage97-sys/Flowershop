<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Enquiries';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $eid = (int)$_POST['reply_id']; $reply = trim($_POST['reply'] ?? '');
    if ($reply) { $uid=(int)$_SESSION['user_id'];
        $stmt=$conn->prepare("UPDATE enquiries SET reply=?,status='replied',replied_by=?,replied_at=NOW() WHERE id=?");
        $stmt->bind_param('sii',$reply,$uid,$eid); $stmt->execute(); $stmt->close();
        logActivity('Reply Enquiry',"Replied #$eid"); setFlash('success','Reply sent.'); }
    redirect(APP_URL.'/admin/enquiries.php');
}
if (isset($_GET['close'])) { $eid=(int)$_GET['close'];
    $conn->query("UPDATE enquiries SET status='closed' WHERE id=$eid");
    setFlash('info','Enquiry closed.'); redirect(APP_URL.'/admin/enquiries.php'); }
$filter=$_GET['status']??''; $where=$filter?"WHERE e.status='".$conn->real_escape_string($filter)."'":'';
$enquiries=$conn->query("SELECT e.*,u.full_name FROM enquiries e LEFT JOIN users u ON e.customer_id=u.id $where ORDER BY e.created_at DESC");
include '../includes/header.php'; ?>
<div class="page-header"><div><h1>Enquiries</h1><p>Customer support messages</p></div>
  <div style="display:flex;gap:8px;">
    <?php foreach([''=>'All','open'=>'Open','replied'=>'Replied','closed'=>'Closed'] as $k=>$v): ?>
    <a href="?status=<?=$k?>" class="btn btn-sm <?=$filter===$k?'btn-primary':'btn-outline'?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php while($e=$enquiries->fetch_assoc()): ?>
<div class="card mb-2">
  <div class="card-header">
    <div><strong><?=htmlspecialchars($e['name']??$e['full_name']??'Guest')?></strong>
      <span class="text-muted" style="margin-left:10px;"><?=htmlspecialchars($e['email']??'')?></span>
      <span class="badge <?=$e['status']==='open'?'badge-yellow':($e['status']==='replied'?'badge-green':'badge-gray')?>" style="margin-left:10px;"><?=ucfirst($e['status'])?></span></div>
    <span class="text-muted" style="font-size:.82rem;"><?=date('d M Y H:i',strtotime($e['created_at']))?></span>
  </div>
  <div class="card-body">
    <p style="font-weight:600;margin-bottom:8px;"><?=htmlspecialchars($e['subject']??'(No subject)')?></p>
    <p><?=nl2br(htmlspecialchars($e['message']))?></p>
    <?php if($e['reply']): ?>
    <div style="margin-top:16px;padding:14px;background:#f0fdf4;border-radius:8px;border-left:4px solid #059669;">
      <div style="font-size:.78rem;color:#059669;font-weight:600;margin-bottom:6px;">REPLY · <?=date('d M Y',strtotime($e['replied_at']))?></div>
      <p><?=nl2br(htmlspecialchars($e['reply']))?></p></div>
    <?php endif; ?>
    <?php if($e['status']!=='closed'): ?>
    <form method="POST" style="margin-top:16px;">
      <input type="hidden" name="reply_id" value="<?=$e['id']?>">
      <div class="form-group"><textarea name="reply" class="form-control" rows="3" placeholder="Type reply..."><?=htmlspecialchars($e['reply']??'')?></textarea></div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-reply"></i> Send Reply</button>
        <a href="?close=<?=$e['id']?>" class="btn btn-sm btn-outline">Close</a>
      </div></form>
    <?php endif; ?>
  </div>
</div>
<?php endwhile; include '../includes/footer.php'; ?>
