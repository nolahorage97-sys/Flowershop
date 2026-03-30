<?php
require_once 'includes/config.php';
if (isLoggedIn()) {
    logActivity('Logout', 'User logged out');
}
session_destroy();
redirect(APP_URL . '/login.php');
