<?php
/**
 * Plantora E-Commerce
 * Authentication & Session Management Library
 * File: includes/auth.php
 */

if (!defined('PLANTORA_AUTH_LOADED')) {
    define('PLANTORA_AUTH_LOADED', true);
}

/**
 * Safely start PHP session if not already active.
 */
function startSessionIfNeeded() {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            $lifetime = 60 * 60 * 24 * 7; // 7 days
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        @session_start();
    }
}

// Automatically ensure session is initialized when auth.php is included
startSessionIfNeeded();

/**
 * Check whether the current visitor is logged in.
 *
 * @return bool
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user ID.
 *
 * @return int|null
 */
function currentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Fetch the current user record.
 * If a mysqli connection is provided, fetches the fresh profile from DB (excluding password).
 * Otherwise returns basic session metadata.
 *
 * @param mysqli|null $conn
 * @return array|null
 */
function currentUser($conn = null) {
    if (!isLoggedIn()) {
        return null;
    }

    $userId = currentUserId();

    if ($conn instanceof mysqli) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT user_id, name, email, phone, address, shipping_address, billing_address, role, created_at FROM users WHERE user_id = ? LIMIT 1'
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if ($user) {
                return $user;
            }
        }
    }

    return [
        'user_id' => $userId,
        'name' => $_SESSION['user_name'] ?? 'Customer',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer'
    ];
}

/**
 * Protect routes by requiring an active session.
 * If not authenticated, redirects to login.php with a safe return URL.
 *
 * @param string|null $returnUrl
 */
function requireLogin($returnUrl = null) {
    if (!isLoggedIn()) {
        if ($returnUrl === null && isset($_SERVER['REQUEST_URI'])) {
            $returnUrl = $_SERVER['REQUEST_URI'];
        }

        // Clean return URL to prevent open redirect vulnerabilities
        $safeUrl = 'login.php';
        if (!empty($returnUrl)) {
            // Remove any domain/host if present, keep only relative path
            $parsed = parse_url($returnUrl);
            $path = $parsed['path'] ?? '';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $cleanPath = basename($path);
            if (!empty($cleanPath) && strpos($cleanPath, 'login.php') === false && strpos($cleanPath, 'register.php') === false) {
                $safeUrl = 'login.php?return_url=' . urlencode($cleanPath . $query);
            }
        }

        header('Location: ' . $safeUrl);
        exit;
    }
}

/**
 * Authenticate and log in a user.
 * Regenerates session ID to prevent session fixation.
 * Stores only non-sensitive identifiers in session.
 *
 * @param mysqli $conn
 * @param array $user User database record
 */
function loginUser($conn, array $user) {
    startSessionIfNeeded();
    // Regenerate session ID to mitigate session fixation attacks
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_regenerate_id(true);
    }

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'customer';

    // Ensure user has a cart in the database
    if ($conn instanceof mysqli) {
        getUserCartId($conn, (int)$user['user_id']);
    }
}

/**
 * Safely log out the user, destroy session, and expire session cookies.
 */
function logoutUser() {
    startSessionIfNeeded();

    $_SESSION = [];

    if (!headers_sent() && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    @session_destroy();
}

/**
 * Set a one-time flash message in the session.
 *
 * @param string $type 'success', 'error', 'warning', or 'info'
 * @param string $message
 */
function setFlashMessage($type, $message) {
    startSessionIfNeeded();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message.
 *
 * @return array|null
 */
function getFlashMessage() {
    startSessionIfNeeded();
    if (!empty($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Retrieve or create a cart_id for the specified user_id.
 *
 * @param mysqli $conn
 * @param int $userId
 * @return int
 */
function getUserCartId($conn, $userId) {
    $userId = (int)$userId;
    $stmt = mysqli_prepare($conn, 'SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row) {
        return (int)$row['cart_id'];
    }

    // Create a new cart for this user
    $insertStmt = mysqli_prepare($conn, 'INSERT INTO cart (user_id) VALUES (?)');
    mysqli_stmt_bind_param($insertStmt, 'i', $userId);
    mysqli_stmt_execute($insertStmt);
    $cartId = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($insertStmt);

    return $cartId;
}

/**
 * Retrieve all items in the user's database cart with product details.
 *
 * @param mysqli $conn
 * @param int $userId
 * @return array
 */
function getUserCartItems($conn, $userId) {
    $cartId = getUserCartId($conn, $userId);
    $sql = '
        SELECT 
            ci.cart_item_id,
            ci.cart_id,
            ci.variation_id,
            ci.quantity,
            pv.product_id,
            pv.color,
            pv.size,
            pv.pot_option,
            pv.price,
            pv.stock_quantity,
            p.product_name,
            p.image,
            c.category_name AS category
        FROM cart_items ci
        JOIN product_variations pv ON ci.variation_id = pv.variation_id
        JOIN products p ON pv.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE ci.cart_id = ?
        ORDER BY ci.cart_item_id ASC
    ';

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'product_id' => (int)$row['product_id'],
            'variation_id' => (int)$row['variation_id'],
            'product_name' => $row['product_name'],
            'category' => $row['category'] ?? '',
            'color' => $row['color'] ?? '',
            'size' => $row['size'] ?? '',
            'pot_option' => $row['pot_option'] ?? '',
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'stock_quantity' => (int)$row['stock_quantity'],
            'image' => $row['image'] ?? 'images/logo.png'
        ];
    }
    mysqli_stmt_close($stmt);

    return $items;
}

/**
 * Merge guest cart items (from client localStorage) into user's database cart.
 *
 * @param mysqli $conn
 * @param int $userId
 * @param array $guestItems
 * @return array Updated merged cart items
 */
function mergeGuestCartItems($conn, $userId, array $guestItems) {
    if (empty($guestItems)) {
        return getUserCartItems($conn, $userId);
    }

    $cartId = getUserCartId($conn, $userId);

    foreach ($guestItems as $item) {
        $varId = isset($item['variation_id']) ? (int)$item['variation_id'] : 0;
        $qty = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;

        if ($varId <= 0) {
            continue;
        }

        // Check if variation exists and get stock
        $checkVar = mysqli_prepare($conn, 'SELECT stock_quantity FROM product_variations WHERE variation_id = ? LIMIT 1');
        mysqli_stmt_bind_param($checkVar, 'i', $varId);
        mysqli_stmt_execute($checkVar);
        $res = mysqli_stmt_get_result($checkVar);
        $varData = mysqli_fetch_assoc($res);
        mysqli_stmt_close($checkVar);

        if (!$varData) {
            continue; // Invalid variation
        }

        $stock = (int)$varData['stock_quantity'];

        // Insert or update cart_items
        $upsertSql = '
            INSERT INTO cart_items (cart_id, variation_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)
        ';
        $upsertStmt = mysqli_prepare($conn, $upsertSql);
        $maxAllowed = max(1, $stock > 0 ? $stock : 99);
        mysqli_stmt_bind_param($upsertStmt, 'iiii', $cartId, $varId, $qty, $maxAllowed);
        mysqli_stmt_execute($upsertStmt);
        mysqli_stmt_close($upsertStmt);
    }

    return getUserCartItems($conn, $userId);
}
