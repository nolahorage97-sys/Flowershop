<?php

define('DB_HOST',     'localhost');
define('DB_USER',     'root');         // XAMPP default
define('DB_PASS',     '');             // XAMPP default (empty)
define('DB_NAME',     'flowershop_db');
define('DB_PORT',     3306);

define('APP_NAME',    'BloomTrack');
define('APP_TAGLINE', 'Flower Shop Inventory System');
define('APP_URL',     'http://localhost/flowershop');

// Session lifetime in seconds (2 hours)
define('SESSION_LIFETIME', 7200);

// Low stock warning threshold multiplier (0 = use flower's own reorder_level)
define('LOW_STOCK_MULTIPLIER', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}


// Database Connection (MySQLi)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2 style="color:#e74c3c;">&#9888; Database Connection Failed</h2>
        <p>Could not connect to MySQL. Please ensure XAMPP is running and the database exists.</p>
        <p><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
        <p><a href="' . APP_URL . '/setup.php">Run Setup</a></p>
    </div>');
}

$conn->set_charset('utf8mb4');

// Helper Functions

/**
 * Sanitize user input
 */
function clean($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login — redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Require a specific role
 */
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        redirect(APP_URL . '/unauthorized.php');
    }
}

/**
 * Get current user info from session
 */
function currentUser() {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'name'     => $_SESSION['user_name'] ?? '',
        'username' => $_SESSION['username']  ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
        'email'    => $_SESSION['user_email']?? '',
    ];
}

/**
 * Log an activity
 */
function logActivity($action, $details = '', $userId = null) {
    global $conn;
    $userId  = $userId ?? ($_SESSION['user_id'] ?? null);
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
    $action  = $conn->real_escape_string($action);
    $details = $conn->real_escape_string($details);
    $ip      = $conn->real_escape_string($ip);
    $uId     = $userId ? (int)$userId : 'NULL';
    $conn->query("INSERT INTO activity_log (user_id, action, details, ip_address)
                  VALUES ($uId, '$action', '$details', '$ip')");
}

/**
 * Format currency (KES)
 */
function formatCurrency($amount) {
    return 'KES ' . number_format((float)$amount, 2);
}

/**
 * Generate order / sale number
 */
function generateNumber($prefix = 'ORD') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Flash message: set
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash message: get & clear
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get dashboard URL for role
 */
function getDashboardUrl($role = null) {
    $role = $role ?? ($_SESSION['user_role'] ?? '');
    $map = [
        'admin'    => APP_URL . '/admin/dashboard.php',
        'owner'    => APP_URL . '/owner/dashboard.php',
        'staff'    => APP_URL . '/staff/dashboard.php',
        'customer' => APP_URL . '/customer/dashboard.php',
    ];
    return $map[$role] ?? APP_URL . '/login.php';
}

function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter (A-Z)';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter (a-z)';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number (0-9)';
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)';
    }
    
    return $errors;
}


