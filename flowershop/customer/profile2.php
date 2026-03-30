<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'My Profile';
$uid = (int)$_SESSION['user_id'];

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i', $uid); 
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc(); 
$stmt->close();

if (!$user) {
    setFlash('error', 'User account not found. Please login again.');
    session_destroy();
    redirect(APP_URL . '/login.php');
}

$errors = []; 
$pwErrors = [];
$uploadErrors = [];

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExt, $allowed)) {
            $uploadErrors[] = 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.';
        }
        
        if ($fileSize > 5242880) {
            $uploadErrors[] = 'File size must be less than 5MB.';
        }
        
        if (empty($uploadErrors)) {
            // Create unique filename
            $newFileName = 'user_' . $uid . '_' . time() . '.' . $fileExt;
            
            // FIXED: Use correct path
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/flowershop/uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $uploadPath = $uploadDir . $newFileName;
            $dbPath = 'uploads/profiles/' . $newFileName; // Relative path for database
            
            // Delete old profile image if exists
            if (!empty($user['profile_image'])) {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/flowershop/' . $user['profile_image'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Upload file
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $stmt = $conn->prepare("UPDATE users SET profile_image=? WHERE id=?");
                $stmt->bind_param('si', $dbPath, $uid);
                if ($stmt->execute()) {
                    $user['profile_image'] = $dbPath;
                    setFlash('success', 'Profile picture updated successfully!');
                    redirect(APP_URL . '/customer/profile.php');
                } else {
                    $uploadErrors[] = 'Failed to update database.';
                }
                $stmt->close();
            } else {
                $uploadErrors[] = 'Failed to upload image. Check folder permissions.';
            }
        }
    } else {
        $uploadErrors[] = 'Please select an image to upload.';
    }
}

// Handle remove profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_photo'])) {
    if (!empty($user['profile_image'])) {
        $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/flowershop/' . $user['profile_image'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET profile_image=NULL WHERE id=?");
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
        $user['profile_image'] = null;
        setFlash('success', 'Profile picture removed.');
        redirect(APP_URL . '/customer/profile.php');
    }
    $stmt->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if (empty($fullName)) $errors[] = 'Full name is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param('sssi', $fullName, $phone, $address, $uid);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $fullName;
            $user['full_name'] = $fullName; 
            $user['phone'] = $phone; 
            $user['address'] = $address;
            setFlash('success', 'Profile updated successfully.');
            redirect(APP_URL . '/customer/profile.php');
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $pwErrors[] = 'Current password is incorrect.';
    } else {
        // Validate new password strength
        $passwordErrors = validatePasswordStrength($newPass);
        if (!empty($passwordErrors)) {
            $pwErrors = array_merge($pwErrors, $passwordErrors);
        }
        
        if (strlen($newPass) < 8) {
            $pwErrors[] = 'New password must be at least 8 characters.';
        }
        
        if ($newPass !== $confirm) {
            $pwErrors[] = 'New passwords do not match.';
        }
    }

    if (empty($pwErrors)) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $uid); 
        $stmt->execute(); 
        $stmt->close();
        setFlash('success', 'Password changed successfully.');
        redirect(APP_URL . '/customer/profile.php');
    }
}

include '../includes/header.php';
?>

<div class="page-header">
  <div><h1>My Profile</h1><p>Manage your account information</p></div>
</div>

<div class="grid-2" style="align-items:start;">
  <!-- Profile Info -->
  <div class="card">
    <div class="card-header"><span class="card-title">Personal Information</span></div>
    <div class="card-body">
      <?php if ($errors): ?>
      <div class="flash flash-error mb-2">
        <?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>
      
      <?php if ($uploadErrors): ?>
      <div class="flash flash-error mb-2">
        <?php foreach($uploadErrors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Profile Picture Section -->
      <div style="text-align:center;margin-bottom:24px;">
        <?php 
        // FIXED: Correct URL construction
        $profileImageUrl = !empty($user['profile_image']) ? APP_URL . '/' . $user['profile_image'] : null;
        "<!-- Profile image path in DB: " . ($user['profile_image'] ?? 'NULL') . " -->";
        "<!-- Full URL: " . ($profileImageUrl ?? 'NULL') . " -->";
        ?>
        <div style="position:relative;display:inline-block;">
          <?php if ($profileImageUrl && file_exists($_SERVER['DOCUMENT_ROOT'] . '/flowershop/' . $user['profile_image'])): ?>
            <img src="<?= $profileImageUrl . '?t=' . time() ?>" 
                 alt="Profile Picture" 
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);">
          <?php else: ?>
            <div style="width:100px;height:100px;border-radius:50%;background:var(--accent);color:#fff;
                 display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;margin:0 auto;border:3px solid var(--accent);">
              <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
            </div>
          <?php endif; ?>
          
          <!-- Upload button -->
          <form method="POST" enctype="multipart/form-data" style="margin-top:10px;" id="uploadForm">
            <input type="hidden" name="upload_photo" value="1">
            <label for="profile_image" style="cursor:pointer;background:var(--accent);color:white;padding:6px 12px;border-radius:20px;font-size:0.8rem;display:inline-flex;align-items:center;gap:5px;">
              <i class="fa fa-camera"></i> Change Photo
            </label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display:none;" onchange="this.form.submit();">
          </form>
          
          <?php if ($profileImageUrl): ?>
          <form method="POST" style="margin-top:5px;">
            <input type="hidden" name="remove_photo" value="1">
            <button type="submit" style="background:none;border:none;color:#dc2626;font-size:0.75rem;cursor:pointer;margin-top:5px;">
              <i class="fa fa-trash"></i> Remove
            </button>
          </form>
          <?php endif; ?>
        </div>
        
        <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;margin-top:10px;"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
        <div class="text-muted" style="font-size:.85rem;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        <span class="badge badge-yellow" style="margin-top:6px;"><?= ucfirst($user['role'] ?? 'customer') ?></span>
      </div>

      <!-- Rest of your form remains the same -->
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control"
                 value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="text-muted">(cannot be changed)</span></label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="07XX XXX XXX"
                 value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Default Delivery Address</label>
          <textarea name="address" class="form-control" rows="3"
                    placeholder="Your default delivery address..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          <span class="form-text">This will pre-fill when you place an order.</span>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Password change and Account info  -->
  <div>
    <div class="card mb-2">
      <div class="card-header"><span class="card-title">Change Password</span></div>
      <div class="card-body">
        <?php if ($pwErrors): ?>
        <div class="flash flash-error mb-2">
          <?php foreach($pwErrors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="change_password" value="1">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning"><i class="fa fa-key"></i> Change Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Account Summary</span></div>
      <div class="card-body">
        <?php
        $totalOrders = $conn->query("SELECT COUNT(*) as v FROM orders WHERE customer_id=$uid")->fetch_assoc()['v'] ?? 0;
        $totalSpent = $conn->query("SELECT COALESCE(SUM(total_amount),0) as v FROM orders WHERE customer_id=$uid AND status!='cancelled'")->fetch_assoc()['v'] ?? 0;
        $enquiries = $conn->query("SELECT COUNT(*) as v FROM enquiries WHERE customer_id=$uid")->fetch_assoc()['v'] ?? 0;
        
        $memberSince = 'N/A';
        if (!empty($user['created_at']) && $user['created_at'] !== '0000-00-00 00:00:00') {
            $memberSince = date('M Y', strtotime($user['created_at']));
        }
        ?>
        <div style="display:grid;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--sand);border-radius:8px;">
            <span class="text-muted">Total Orders</span>
            <strong><?= $totalOrders ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--sand);border-radius:8px;">
            <span class="text-muted">Total Spent</span>
            <strong style="color:var(--accent);"><?= formatCurrency($totalSpent) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--sand);border-radius:8px;">
            <span class="text-muted">Enquiries</span>
            <strong><?= $enquiries ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--sand);border-radius:8px;">
            <span class="text-muted">Member Since</span>
            <strong><?= $memberSince ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>