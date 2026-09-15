<?php
/**
 * Plantora E-Commerce
 * Update Profile Details Handler
 * File: update-profile.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Protected action: must be logged in
requireLogin('profile.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

// User ID is strictly taken from the session to prevent unauthorized tampering
$userId = currentUserId();

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$billingAddress = trim($_POST['billing_address'] ?? '');

$errors = [];

// Validate Name
if (empty($name)) {
    $errors[] = 'Full Name cannot be empty.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Full Name must not exceed 100 characters.';
}

// Validate Phone
if (empty($phone)) {
    $errors[] = 'Phone number cannot be empty.';
} elseif (!preg_match('/^[0-9+\s\-()]{7,20}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}

// Validate Addresses
if (empty($shippingAddress)) {
    $errors[] = 'Shipping address cannot be empty.';
}

if (empty($billingAddress)) {
    $errors[] = 'Billing address cannot be empty.';
}

if (!empty($errors)) {
    setFlashMessage('error', implode(' ', $errors));
    header('Location: profile.php#edit-profile');
    exit;
}

// General address kept consistent with shipping address
$generalAddress = $shippingAddress;

$updateSql = '
    UPDATE users 
    SET name = ?, phone = ?, address = ?, shipping_address = ?, billing_address = ?
    WHERE user_id = ?
';
$stmt = mysqli_prepare($conn, $updateSql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt, 
        'sssssi', 
        $name, 
        $phone, 
        $generalAddress, 
        $shippingAddress, 
        $billingAddress, 
        $userId
    );

    if (mysqli_stmt_execute($stmt)) {
        // Update user name in session so navbar immediately updates
        $_SESSION['user_name'] = $name;
        mysqli_stmt_close($stmt);

        setFlashMessage('success', 'Your profile details have been successfully updated.');
        header('Location: profile.php?updated=1');
        exit;
    } else {
        mysqli_stmt_close($stmt);
        setFlashMessage('error', 'Unable to update profile at this time. Please try again.');
        header('Location: profile.php#edit-profile');
        exit;
    }
} else {
    setFlashMessage('error', 'Database system error. Please try again later.');
    header('Location: profile.php');
    exit;
}
