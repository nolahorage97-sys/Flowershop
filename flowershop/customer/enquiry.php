<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'Make an Enquiry';
$uid = (int)$_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $name    = $_SESSION['user_name'];
    $email   = $_SESSION['user_email'];

    if (empty($subject)) $errors[] = 'Subject is required.';
    if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO enquiries (customer_id, name, email, subject, message) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $uid, $name, $email, $subject, $message);
        if ($stmt->execute()) {
            logActivity('Submit Enquiry', "Customer submitted enquiry: $subject");
            setFlash('success', 'Your enquiry has been submitted. We\'ll reply via your account.');
            redirect(APP_URL . '/customer/enquiry.php');
        }
        $stmt->close();
    }
}

// My past enquiries
$myEnquiries = $conn->query("SELECT * FROM enquiries WHERE customer_id=$uid ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>Make an Enquiry</h1><p>Send us a message and we'll get back to you</p></div>
</div>

<div class="grid-2" style="align-items:start;">
  <!-- Form -->
  <div class="card">
    <div class="card-header"><span class="card-title">New Enquiry</span></div>
    <div class="card-body">
      <?php if ($errors): ?>
      <div class="flash flash-error mb-2">
        <?php foreach($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <input type="text" name="subject" class="form-control"
                 placeholder="e.g. Do you have blue orchids? Delivery query..."
                 value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Your Message *</label>
          <textarea name="message" class="form-control" rows="6"
                    placeholder="Please describe your enquiry in detail..."
                    required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <div style="background:var(--sand);border-radius:8px;padding:12px;margin-bottom:16px;font-size:.85rem;color:var(--text-muted);">
          <i class="fa fa-circle-info"></i>
          Enquiry will be sent from <strong><?= htmlspecialchars($_SESSION['user_email']) ?></strong>.
          Replies will appear below.
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Submit Enquiry</button>
      </form>
    </div>
  </div>

  <!-- Common questions -->
  <div>
    <div class="card mb-2">
      <div class="card-header"><span class="card-title">Common Questions</span></div>
      <div class="card-body">
        <?php
        $faqs = [
          ['Do you deliver?', 'Yes! We deliver to most areas. Add your address when placing an order.'],
          ['Can I customise a bouquet?', 'Absolutely. Send us an enquiry with your preferences and budget.'],
          ['How fresh are the flowers?', 'All our flowers are sourced fresh daily from trusted farm suppliers.'],
          ['What payment methods do you accept?', 'We accept M-Pesa, Cash on Delivery, and bank transfer.'],
          ['Can I order for an event?', 'Yes — bulk event orders are welcome. Contact us well in advance.'],
        ];
        foreach ($faqs as $faq): ?>
        <details style="padding:10px 0;border-bottom:1px solid var(--border);cursor:pointer;">
          <summary style="font-weight:600;font-size:.9rem;"><?= $faq[0] ?></summary>
          <p style="color:var(--text-muted);font-size:.85rem;margin-top:8px;padding-left:4px;"><?= $faq[1] ?></p>
        </details>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card" style="background:var(--green);color:#fff;">
      <div class="card-body" style="text-align:center;padding:24px;">
        <div style="font-size:2rem;margin-bottom:8px;">📞</div>
        <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;margin-bottom:6px;">Call Us Directly</div>
        <div style="opacity:.8;font-size:.9rem;">Mon–Sat, 7am–7pm</div>
        <div style="font-size:1.2rem;font-weight:700;margin-top:10px;">+254 796 797 219</div>
      </div>
    </div>
  </div>
</div>

<!-- My past enquiries -->
<?php if ($myEnquiries->num_rows > 0): ?>
<div class="card mt-2">
  <div class="card-header"><span class="card-title">My Previous Enquiries</span></div>
  <div class="card-body" style="padding:0;">
    <?php while ($e = $myEnquiries->fetch_assoc()): ?>
    <div style="padding:18px 22px;border-bottom:1px solid var(--border);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <strong><?= htmlspecialchars($e['subject']) ?></strong>
        <div style="display:flex;gap:10px;align-items:center;">
          <span class="badge <?= $e['status']==='open'?'badge-yellow':($e['status']==='replied'?'badge-green':'badge-gray') ?>">
            <?= ucfirst($e['status']) ?>
          </span>
          <span class="text-muted" style="font-size:.78rem;"><?= date('d M Y', strtotime($e['created_at'])) ?></span>
        </div>
      </div>
      <p style="color:var(--text-muted);font-size:.88rem;"><?= nl2br(htmlspecialchars($e['message'])) ?></p>
      <?php if ($e['reply']): ?>
      <div style="margin-top:12px;padding:12px;background:#f0fdf4;border-radius:8px;border-left:3px solid var(--success);">
        <div style="font-size:.75rem;color:var(--success);font-weight:700;margin-bottom:5px;">
          <i class="fa fa-reply"></i> REPLY FROM SHOP · <?= date('d M Y', strtotime($e['replied_at'])) ?>
        </div>
        <p style="font-size:.88rem;"><?= nl2br(htmlspecialchars($e['reply'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
