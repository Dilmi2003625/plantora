<?php
/**
 * Plantora E-Commerce
 * Admin Authentication Guard
 * File: admin/auth.php
 */

require_once __DIR__ . '/../includes/auth.php';

// 1. Ensure the session is started using existing logic (already done by includes/auth.php)
// 2 & 4. Require the user to be logged in; if not, they are redirected to login.php
requireLogin('../login.php');

// 3 & 5. Check if the logged-in user's role is 'admin'
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // If logged in but NOT admin, deny access and redirect to customer home page
    header('Location: ../index.php');
    exit;
}
