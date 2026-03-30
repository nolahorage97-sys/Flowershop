<?php
require_once '../includes/config.php';
requireRole('owner');
$pageTitle = 'Add Flower';
$errors = [];

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $catId       = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $reorder     = (int)($_POST['reorder_level'] ?? 10);
    $unit        = trim($_POST['unit'] ?? 'stem');
    $color       = trim($_POST['color'] ?? '');
    $season      = trim($_POST['season'] ?? '');
    $imageUrl    = trim($_POST['image_url'] ?? '');
    $ownerId     = (int)$_SESSION['user_id'];
    
    // Handle file upload
    $uploadedImagePath = '';
    if (isset($_FILES['flower_image']) && $_FILES['flower_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['flower_image'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($fileExt, $allowed)) {
            $errors[] = 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.';
        }
        
        if ($fileSize > 5242880) { // 5MB limit
            $errors[] = 'File size must be less than 5MB.';
        }
        
        if (empty($errors)) {
            // Create unique filename
            $newFileName = 'flower_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/flowershop/uploads/flowers/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $uploadPath = $uploadDir . $newFileName;
            $uploadedImagePath = 'uploads/flowers/' . $newFileName;
            
            // Upload file
            if (!move_uploaded_file($fileTmp, $uploadPath)) {
                $errors[] = 'Failed to upload image. Please try again.';
                $uploadedImagePath = '';
            }
        }
    }
    
    // Use uploaded image if available, otherwise use URL
    $finalImagePath = !empty($uploadedImagePath) ? $uploadedImagePath : $imageUrl;

    if (empty($name))    $errors[] = 'Flower name is required.';
    if ($price <= 0)     $errors[] = 'Price must be greater than 0.';
    if ($quantity < 0)   $errors[] = 'Quantity cannot be negative.';

    // Validate image URL if provided and no uploaded file
    if (empty($uploadedImagePath) && !empty($imageUrl) && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid image URL (must start with http:// or https://)';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO flowers (category_id, name, description, price, quantity, reorder_level, unit, color, season, image_url, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param('issdiissssi', 
            $catId,      // 1. integer
            $name,       // 2. string
            $description,// 3. string
            $price,      // 4. double
            $quantity,   // 5. integer
            $reorder,    // 6. integer
            $unit,       // 7. string
            $color,      // 8. string
            $season,     // 9. string
            $finalImagePath,   // 10. string (either uploaded path or URL)
            $ownerId     // 11. integer
        );
        
        if ($stmt->execute()) {
            $fid = $conn->insert_id;
            // Log initial stock if > 0
            if ($quantity > 0) {
                $conn->query("INSERT INTO stock_adjustments (flower_id, adjusted_by, adjustment_type, quantity_change, quantity_before, quantity_after, reason)
                    VALUES ($fid, $ownerId, 'restock', $quantity, 0, $quantity, 'Initial stock on creation')");
            }
            logActivity('Add Flower', "Added flower: $name (qty: $quantity)");
            setFlash('success', "\"$name\" added to inventory.");
            redirect(APP_URL.'/owner/inventory.php');
        } else {
            $errors[] = 'Failed to save flower. '.$conn->error;
        }
        $stmt->close();
    }
}
include '../includes/header.php';
?>

<style>
/* Image upload styles */
.image-upload-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.image-upload-box {
    flex: 1;
    min-width: 250px;
}

.image-preview {
    margin-top: 10px;
    max-width: 200px;
    display: none;
}

.image-preview img {
    width: 100%;
    border-radius: 8px;
    border: 2px solid var(--accent);
    padding: 4px;
}

.image-url-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 5px;
}

.image-url-hint a {
    color: var(--accent);
    text-decoration: none;
}

.image-url-hint a:hover {
    text-decoration: underline;
}

.file-upload-label {
    display: inline-block;
    padding: 10px 20px;
    background: var(--accent);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.file-upload-label:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.file-upload-label i {
    margin-right: 8px;
}

#file_name_display {
    margin-top: 8px;
    font-size: 0.8rem;
    color: #6b7280;
}

.or-divider {
    text-align: center;
    margin: 10px 0;
    position: relative;
    color: #9ca3af;
    font-size: 0.8rem;
}

.or-divider::before,
.or-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 45%;
    height: 1px;
    background: #e5e7eb;
}

.or-divider::before {
    left: 0;
}

.or-divider::after {
    right: 0;
}
</style>

<div class="page-header">
  <div><h1>Add New Flower</h1><p>Add a new flower type to your inventory</p></div>
  <a href="<?= APP_URL ?>/owner/inventory.php" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="flash flash-error mb-2" style="flex-direction:column;gap:4px;">
  <?php foreach($errors as $e): ?><div><i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:800px;">
  <div class="card-header"><span class="card-title">Flower Details</span></div>
  <div class="card-body">
    <form method="POST" id="flowerForm" enctype="multipart/form-data">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Flower Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name']??'') ?>" placeholder="e.g. Red Roses" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-control">
            <option value="0">— Select Category —</option>
            <?php 
            $categories->data_seek(0);
            while ($c = $categories->fetch_assoc()): 
            ?>
            <option value="<?= $c['id'] ?>" <?= (($_POST['category_id']??0)==$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" placeholder="Brief description of this flower..."><?= htmlspecialchars($_POST['description']??'') ?></textarea>
      </div>
      
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Price (KES) *</label>
          <input type="number" name="price" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars($_POST['price']??'') ?>" placeholder="0.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Opening Quantity *</label>
          <input type="number" name="quantity" min="0" class="form-control" value="<?= htmlspecialchars($_POST['quantity']??'0') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Reorder Level</label>
          <input type="number" name="reorder_level" min="1" class="form-control" value="<?= htmlspecialchars($_POST['reorder_level']??'10') ?>">
          <div class="form-text">Alert when stock falls below this number</div>
        </div>
      </div>
      
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Unit</label>
          <select name="unit" class="form-control">
            <?php foreach(['stem', 'bunch', 'pot', 'piece', 'box'] as $u): ?>
            <option value="<?= $u ?>" <?= (($_POST['unit']??'stem')===$u)?'selected':'' ?>><?= ucfirst($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Colour</label>
          <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($_POST['color']??'') ?>" placeholder="e.g. Red, Pink, Mixed">
        </div>
        <div class="form-group">
          <label class="form-label">Season / Availability</label>
          <input type="text" name="season" class="form-control" value="<?= htmlspecialchars($_POST['season']??'') ?>" placeholder="e.g. Year-round, Spring">
        </div>
      </div>
      
      <!-- Image Upload Section - Both options -->
      <div class="form-group">
        <label class="form-label">Flower Image</label>
        
        <div class="image-upload-container">
          <!-- Option 1: Upload from device -->
          <div class="image-upload-box">
            <label class="file-upload-label">
              <i class="fa fa-upload"></i> Upload from Device
              <input type="file" name="flower_image" id="flower_image" accept="image/*" style="display: none;" onchange="previewUploadedImage(this)">
            </label>
            <div id="file_name_display"></div>
            <div class="image-preview" id="uploadPreview" style="display: none;">
              <img id="uploadPreviewImg" src="" alt="Upload Preview">
              <button type="button" onclick="clearUploadedImage()" style="margin-top: 5px; background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                <i class="fa fa-trash"></i> Remove
              </button>
            </div>
          </div>
          
          <div class="or-divider">OR</div>
          
          <!-- Option 2: Image URL -->
          <div class="image-upload-box">
            <input type="url" name="image_url" id="image_url" class="form-control" 
                   value="<?= htmlspecialchars($_POST['image_url']??'') ?>" 
                   placeholder="https://example.com/images/rose.jpg"
                   onchange="previewUrlImage()">
            <div class="image-url-hint">
              <i class="fa fa-info-circle"></i> 
              Example URLs:
              <a href="#" onclick="setExampleImage('https://images.unsplash.com/photo-1496062031456-07b8f162a322?w=300'); return false;">🌹 Rose</a> | 
              <a href="#" onclick="setExampleImage('https://images.unsplash.com/photo-1470509037663-253afd7f0f51?w=300'); return false;">🌻 Sunflower</a> | 
              <a href="#" onclick="setExampleImage('https://images.unsplash.com/photo-1493238792000-8113da705763?w=300'); return false;">🌸 Tulip</a>
            </div>
            <div class="image-preview" id="urlPreview" style="display: none;">
              <img id="urlPreviewImg" src="" alt="URL Preview">
            </div>
          </div>
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary"><i class="fa fa-seedling"></i> Add to Inventory</button>
    </form>
  </div>
</div>

<script>
function previewUploadedImage(input) {
    const fileDisplay = document.getElementById('file_name_display');
    const preview = document.getElementById('uploadPreview');
    const previewImg = document.getElementById('uploadPreviewImg');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        fileDisplay.innerHTML = `<i class="fa fa-file-image"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
        
        // Clear URL field when uploading file
        document.getElementById('image_url').value = '';
        document.getElementById('urlPreview').style.display = 'none';
    }
}

function clearUploadedImage() {
    document.getElementById('flower_image').value = '';
    document.getElementById('file_name_display').innerHTML = '';
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('uploadPreviewImg').src = '';
}

function previewUrlImage() {
    const urlInput = document.getElementById('image_url');
    const preview = document.getElementById('urlPreview');
    const previewImg = document.getElementById('urlPreviewImg');
    
    if (urlInput.value && (urlInput.value.startsWith('http://') || urlInput.value.startsWith('https://'))) {
        previewImg.src = urlInput.value;
        preview.style.display = 'block';
        
        // Clear uploaded file when using URL
        clearUploadedImage();
        
        // Handle image load error
        previewImg.onerror = function() {
            previewImg.src = 'https://via.placeholder.com/200x200?text=Invalid+Image+URL';
        };
    } else {
        preview.style.display = 'none';
    }
}

function setExampleImage(url) {
    document.getElementById('image_url').value = url;
    previewUrlImage();
}

// Preview on page load if there's a value
document.addEventListener('DOMContentLoaded', function() {
    previewUrlImage();
});
</script>

<?php include '../includes/footer.php'; ?>