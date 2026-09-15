<?php
/**
 * Plantora E-Commerce
 * Update Password Handler
 * File: update-password.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Protected action: must be logged in
requireLogin('profile.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$userId = currentUserId();
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    setFlashMessage('error', 'All password fields are required.');
    header('Location: profile.php#change-password');
    exit;
}

if ($newPassword !== $confirmPassword) {
    setFlashMessage('error', 'New password and confirmation password do not match.');
    header('Location: profile.php#change-password');
    exit;
}

if (strlen($newPassword) < 8) {
    setFlashMessage('error', 'New password must be at least 8 characters long.');
    header('Location: profile.php#change-password');
    exit;
}

if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    setFlashMessage('error', 'New password must contain both letters and numbers.');
    header('Location: profile.php#change-password');
    exit;
}

// Retrieve current password hash from database
$stmt = mysqli_prepare($conn, 'SELECT password FROM users WHERE user_id = ? LIMIT 1');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        setFlashMessage('error', 'Your current password is incorrect.');
        header('Location: profile.php#change-password');
        exit;
    }

    // Hash the new password using password_hash()
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update in database using prepared statement
    $updateStmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE user_id = ?');
    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, 'si', $newHash, $userId);
        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            setFlashMessage('success', 'Your password has been changed successfully.');
            header('Location: profile.php?pwd_changed=1');
            exit;
        } else {
            mysqli_stmt_close($updateStmt);
            setFlashMessage('error', 'Unable to update password. Please try again.');
            header('Location: profile.php#change-password');
            exit;
        }
    }
}

setFlashMessage('error', 'Database system error. Please try again later.');
header('Location: profile.php#change-password');
exit;
