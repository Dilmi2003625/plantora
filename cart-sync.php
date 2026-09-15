<?php
/**
 * Plantora E-Commerce
 * Cart Synchronization API Endpoint
 * Handles cart sync between frontend localStorage and MySQL cart / cart_items tables.
 * File: cart-sync.php
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$loggedIn = isLoggedIn();
$userId = currentUserId();

// Input JSON payload if any
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: [];

if ($action === 'get') {
    if (!$loggedIn) {
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'cart' => []
        ]);
        exit;
    }

    $items = getUserCartItems($conn, $userId);
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'cart' => $items
    ]);
    exit;
}

if ($action === 'merge') {
    if (!$loggedIn) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }

    $guestItems = $inputData['items'] ?? $_POST['items'] ?? [];
    if (is_string($guestItems)) {
        $guestItems = json_decode($guestItems, true) ?: [];
    }

    $merged = mergeGuestCartItems($conn, $userId, $guestItems);
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'cart' => $merged
    ]);
    exit;
}

if ($action === 'sync') {
    if (!$loggedIn) {
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'message' => 'Guest cart saved locally'
        ]);
        exit;
    }

    $cartItems = $inputData['items'] ?? $_POST['items'] ?? [];
    if (is_string($cartItems)) {
        $cartItems = json_decode($cartItems, true) ?: [];
    }

    $cartId = getUserCartId($conn, $userId);

    // Synchronize DB items:
    // If incoming cart is empty, delete all items for this cart
    if (empty($cartItems)) {
        $delStmt = mysqli_prepare($conn, 'DELETE FROM cart_items WHERE cart_id = ?');
        mysqli_stmt_bind_param($delStmt, 'i', $cartId);
        mysqli_stmt_execute($delStmt);
        mysqli_stmt_close($delStmt);

        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'cart' => []
        ]);
        exit;
    }

    // Otherwise, collect current variation IDs to delete removed ones
    $validVariationIds = [];
    foreach ($cartItems as $item) {
        $varId = isset($item['variation_id']) ? (int)$item['variation_id'] : 0;
        $qty = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;

        if ($varId <= 0) continue;

        $validVariationIds[] = $varId;

        // Upsert item into cart_items
        $upsertSql = '
            INSERT INTO cart_items (cart_id, variation_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ';
        $uStmt = mysqli_prepare($conn, $upsertSql);
        mysqli_stmt_bind_param($uStmt, 'iii', $cartId, $varId, $qty);
        mysqli_stmt_execute($uStmt);
        mysqli_stmt_close($uStmt);
    }

    // Remove any items not in the synced cart
    if (!empty($validVariationIds)) {
        $inClause = implode(',', array_map('intval', $validVariationIds));
        $delRemovedSql = "DELETE FROM cart_items WHERE cart_id = ? AND variation_id NOT IN ($inClause)";
        $delStmt = mysqli_prepare($conn, $delRemovedSql);
        mysqli_stmt_bind_param($delStmt, 'i', $cartId);
        mysqli_stmt_execute($delStmt);
        mysqli_stmt_close($delStmt);
    }

    $updatedItems = getUserCartItems($conn, $userId);
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'cart' => $updatedItems
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
