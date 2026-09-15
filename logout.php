<?php
/**
 * Plantora E-Commerce
 * User Logout Handler
 * File: logout.php
 */

require_once __DIR__ . '/includes/auth.php';

// Safely destroy session and remove authentication cookies
logoutUser();

// Output quick cleanup for client-side storage, then redirect to home
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out | Plantora</title>
    <meta http-equiv="refresh" content="0;url=index.php?logged_out=1">
    <script>
        // Clear client-side cart so another user on this device cannot see it
        try {
            localStorage.removeItem('plantora_cart');
        } catch(e) {}
        window.location.href = 'index.php?logged_out=1';
    </script>
</head>
<body style="font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f7faf8; color: #0b3d20;">
    <p>Signing out of Plantora... <a href="index.php?logged_out=1">Click here if not redirected</a></p>
</body>
</html>
