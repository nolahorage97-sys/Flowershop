<?php
require_once '../includes/config.php';
requireRole('customer');
$pageTitle = 'Browse Flowers';

$search  = trim($_GET['q'] ?? '');
$catId   = (int)($_GET['cat'] ?? 0);
$where   = "WHERE f.is_active=1 AND f.quantity>0";
if ($search) $where .= " AND (f.name LIKE '%".addslashes($search)."%' OR f.description LIKE '%".addslashes($search)."%' OR f.color LIKE '%".addslashes($search)."%')";
if ($catId)  $where .= " AND f.category_id=$catId";

$flowers    = $conn->query("SELECT f.*, c.name cat_name FROM flowers f LEFT JOIN categories c ON f.category_id=c.id $where ORDER BY f.name");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include '../includes/header.php';
?>

<style>
/* Flower card styles with images */
.flower-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.flower-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e5e7eb;
}

.flower-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.flower-img-container {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #d1fae5, #fdf8f0);
}

.flower-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.flower-card:hover .flower-img {
    transform: scale(1.05);
}

.flower-emoji-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    background: linear-gradient(135deg, #d1fae5, #fdf8f0);
}

.flower-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ffd700;
    color: #1e3c2c;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 1;
}

.flower-info {
    padding: 16px;
}

.flower-name {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 6px;
    color: #1f2937;
}

.flower-meta {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 8px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.flower-description {
    font-size: 0.78rem;
    color: #9ca3af;
    margin-bottom: 12px;
    line-height: 1.4;
}

.flower-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 4px;
}

.flower-qty {
    font-size: 0.7rem;
    color: #6b7280;
    margin-bottom: 12px;
}

.add-to-cart-btn {
    width: 100%;
    background: var(--accent);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.add-to-cart-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Toast notification */
#toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1e3c2c;
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    display: none;
    z-index: 999;
    font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}
</style>

<div class="page-header">
  <div><h1>Browse Flowers</h1><p>Fresh flowers available for order today</p></div>
  <a href="<?= APP_URL ?>/customer/cart.php" class="btn btn-primary">
    <i class="fa fa-cart-shopping"></i> My Cart <span class="cart-badge" style="background:#fff;color:var(--accent);border-radius:20px;padding:1px 8px;font-size:.8rem;margin-left:4px;">0</span>
  </a>
</div>

<!-- Filters -->
<div class="card mb-2">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group mb-0" style="flex:2;min-width:180px;">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control" placeholder="Search flowers, colours..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="form-group mb-0">
        <label class="form-label">Category</label>
        <select name="cat" class="form-control">
          <option value="">All Flowers</option>
          <?php while ($c=$categories->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group mb-0">
        <button class="btn btn-primary">Search</button>
        <a href="<?= APP_URL ?>/customer/shop.php" class="btn btn-outline" style="margin-left:6px;">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="flower-grid">
  <?php $count=0; while ($f=$flowers->fetch_assoc()): $count++;
    // Determine image path
    $hasImage = !empty($f['image_url']);
    if ($hasImage) {
        $isLocalImage = strpos($f['image_url'], 'uploads/') === 0;
        $imagePath = $isLocalImage ? APP_URL . '/' . $f['image_url'] : $f['image_url'];
    }
  ?>
  <div class="flower-card">
    <div class="flower-img-container">
      <?php if ($hasImage): ?>
        <img src="<?= htmlspecialchars($imagePath) ?>" 
             alt="<?= htmlspecialchars($f['name']) ?>"
             class="flower-img"
             onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=\'flower-emoji-placeholder\'>🌸</div>';">
      <?php else: ?>
        <div class="flower-emoji-placeholder">🌸</div>
      <?php endif; ?>
      
      <?php if ($f['quantity'] <= $f['reorder_level'] && $f['quantity'] > 0): ?>
        <span class="flower-badge">Low Stock</span>
      <?php elseif ($f['quantity'] <= 5 && $f['quantity'] > 0): ?>
        <span class="flower-badge">Hurry!</span>
      <?php endif; ?>
    </div>
    
    <div class="flower-info">
      <div class="flower-name"><?= htmlspecialchars($f['name']) ?></div>
      
      <div class="flower-meta">
        <?php if ($f['color']): ?>
          <span><i class="fa fa-palette"></i> <?= htmlspecialchars($f['color']) ?></span>
        <?php endif; ?>
        <?php if ($f['season'] && $f['season'] != 'Year-round'): ?>
          <span><i class="fa fa-calendar"></i> <?= htmlspecialchars($f['season']) ?></span>
        <?php endif; ?>
      </div>
      
      <?php if ($f['description']): ?>
        <div class="flower-description">
          <?= htmlspecialchars(substr($f['description'], 0, 80)) ?>...
        </div>
      <?php endif; ?>
      
      <div class="flower-price"><?= formatCurrency($f['price']) ?> / <?= htmlspecialchars($f['unit']) ?></div>
      <div class="flower-qty">
        <?= $f['quantity'] ?> available
        <?php if ($f['quantity'] <= 5 && $f['quantity'] > 0): ?>
          <span style="color: #d97706;">⚠️ Only <?= $f['quantity'] ?> left!</span>
        <?php endif; ?>
      </div>
      
      <button class="add-to-cart-btn"
        onclick="addToCart(<?= $f['id'] ?>,'<?= addslashes(htmlspecialchars($f['name'])) ?>',<?= $f['price'] ?>,<?= $f['quantity'] ?>)">
        <i class="fa fa-cart-plus"></i> Add to Cart
      </button>
    </div>
  </div>
  <?php endwhile; ?>
  
  <?php if (!$count): ?>
  <div style="grid-column:1/-1;">
    <div class="empty-state">
      <div class="empty-icon">&#127801;</div>
      <p>No flowers found matching your search.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Toast notification -->
<div id="toast">
  <i class="fa fa-circle-check"></i> <span id="toastMsg">Added to cart!</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Cart !== 'undefined' && Cart.updateBadge) {
        Cart.updateBadge();
    }
});

function addToCart(id, name, price, max) {
    if (typeof Cart !== 'undefined' && Cart.add) {
        Cart.add(id, name, price, max);
        showToast(name + ' added to cart!');
    } else {
        showToast('Please login to add items to cart');
    }
}

function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.style.display = 'block';
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.style.display='none', 2500);
}
</script>

<?php include '../includes/footer.php'; ?>