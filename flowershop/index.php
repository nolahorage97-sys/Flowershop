<?php
require_once 'includes/config.php';
if (isLoggedIn()) {
    redirect(getDashboardUrl());
} else {
    redirect(APP_URL . '/login.php');
}
