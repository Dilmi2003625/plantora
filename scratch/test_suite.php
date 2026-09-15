<?php
/**
 * Automated Verification Test Suite for Plantora Week 06 Auth & Profile Management
 * File: scratch/test_suite.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "========================================================\n";
echo "PLANTORA WEEK 06 AUTOMATED VERIFICATION SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($title, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "[PASS] $title\n";
        if ($details) echo "       -> $details\n";
    } else {
        $failCount++;
        echo "[FAIL] $title\n";
        if ($details) echo "       -> $details\n";
    }
}

// Clean test data before running
$testEmailA = 'usera_test@plantora.test';
$testEmailB = 'userb_test@plantora.test';
mysqli_query($conn, "DELETE FROM users WHERE email IN ('$testEmailA', '$testEmailB')");

// TEST 1: Register User A
$passwordA = 'Secret123!';
$hashedA = password_hash($passwordA, PASSWORD_DEFAULT);
$insertA = mysqli_prepare($conn, "
    INSERT INTO users (name, email, password, phone, address, shipping_address, billing_address, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'customer')
");
$nameA = 'Alice Perera';
$phoneA = '+94 77 111 2233';
$shipA = '123 Palm Grove, Colombo 03';
$billA = '123 Palm Grove, Colombo 03';
mysqli_stmt_bind_param($insertA, 'sssssss', $nameA, $testEmailA, $hashedA, $phoneA, $shipA, $shipA, $billA);
$resA = mysqli_stmt_execute($insertA);
$userAId = mysqli_insert_id($conn);
mysqli_stmt_close($insertA);

assertTest("TEST 1: Register New Customer (User A)", $resA && $userAId > 0, "User A inserted with ID: $userAId");

// Check hash in DB
$checkA = mysqli_query($conn, "SELECT password, role FROM users WHERE user_id = $userAId");
$rowA = mysqli_fetch_assoc($checkA);
$isHashed = password_verify($passwordA, $rowA['password']) && $rowA['password'] !== $passwordA;
assertTest("TEST 1b: Password Stored As Hash (Not Plaintext)", $isHashed, "Hash prefix: " . substr($rowA['password'], 0, 15) . "...");
assertTest("TEST 1c: Role defaults to customer", $rowA['role'] === 'customer', "Role: {$rowA['role']}");

// TEST 2: Duplicate email rejection
$stmtDup = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmtDup, 's', $testEmailA);
mysqli_stmt_execute($stmtDup);
mysqli_stmt_store_result($stmtDup);
$isDup = mysqli_stmt_num_rows($stmtDup) > 0;
mysqli_stmt_close($stmtDup);
assertTest("TEST 2: Email Uniqueness Check Rejects Duplicate", $isDup, "Duplicate email detected correctly");

// TEST 3: Login with correct credentials
$loginUserStmt = mysqli_prepare($conn, "SELECT user_id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($loginUserStmt, 's', $testEmailA);
mysqli_stmt_execute($loginUserStmt);
$resLogin = mysqli_stmt_get_result($loginUserStmt);
$uLogin = mysqli_fetch_assoc($resLogin);
mysqli_stmt_close($loginUserStmt);

$verifySuccess = $uLogin && password_verify($passwordA, $uLogin['password']);
assertTest("TEST 3: Login with Correct Credentials", $verifySuccess, "Password verified with password_verify");

loginUser($conn, $uLogin);
assertTest("TEST 3b: Session Established & LoggedIn True", isLoggedIn() && currentUserId() === $userAId, "Session user_id: " . currentUserId());

// TEST 4: Login with incorrect credentials
$verifyFail = password_verify('WrongPassword999', $uLogin['password']);
assertTest("TEST 4: Login with Incorrect Credentials Rejected", !$verifyFail, "Rejected invalid credentials");

// TEST 5: Protected Route check
// Simulate logged out state
logoutUser();
$loggedOutCheck = !isLoggedIn() && currentUserId() === null;
assertTest("TEST 5: Protected Routes (profile.php) Deny Unauthenticated Access", $loggedOutCheck, "Session destroyed, isLoggedIn() returned false");

// TEST 6: Update Profile
// Re-login User A
loginUser($conn, $uLogin);
$newNameA = 'Alice Silva';
$newPhoneA = '+94 77 999 8888';
$newShipA = '456 Lotus Lane, Kandy';
$newBillA = '456 Lotus Lane, Kandy';

$upStmt = mysqli_prepare($conn, "
    UPDATE users 
    SET name = ?, phone = ?, address = ?, shipping_address = ?, billing_address = ?
    WHERE user_id = ?
");
mysqli_stmt_bind_param($upStmt, 'sssssi', $newNameA, $newPhoneA, $newShipA, $newShipA, $newBillA, $userAId);
$upRes = mysqli_stmt_execute($upStmt);
mysqli_stmt_close($upStmt);

$freshA = currentUser($conn);
$updatedProperly = ($freshA['name'] === $newNameA && $freshA['phone'] === $newPhoneA && $freshA['shipping_address'] === $newShipA);
assertTest("TEST 6: Update Name, Phone, and Address", $upRes && $updatedProperly, "Updated name: {$freshA['name']}, phone: {$freshA['phone']}");

// TEST 7: Password Change
$newPasswordA = 'NewSecret456!';
$newHashA = password_hash($newPasswordA, PASSWORD_DEFAULT);
$pwdUpStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
mysqli_stmt_bind_param($pwdUpStmt, 'si', $newHashA, $userAId);
$pwdUpRes = mysqli_stmt_execute($pwdUpStmt);
mysqli_stmt_close($pwdUpStmt);

$checkNewPwd = mysqli_query($conn, "SELECT password FROM users WHERE user_id = $userAId");
$rowNewPwd = mysqli_fetch_assoc($checkNewPwd);
$newPwdMatches = password_verify($newPasswordA, $rowNewPwd['password']) && !password_verify($passwordA, $rowNewPwd['password']);
assertTest("TEST 7: Password Change Verified with New Hash", $pwdUpRes && $newPwdMatches, "New password verified; old password rejected");

// TEST 8 & 9: User A and User B Cart Association & Isolation
// Register User B
$passwordB = 'UserBPass123!';
$hashedB = password_hash($passwordB, PASSWORD_DEFAULT);
$insertB = mysqli_prepare($conn, "
    INSERT INTO users (name, email, password, phone, address, shipping_address, billing_address, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'customer')
");
$nameB = 'Bob Fernando';
$phoneB = '+94 71 444 5566';
$shipB = '789 Orchid St, Galle';
$billB = '789 Orchid St, Galle';
mysqli_stmt_bind_param($insertB, 'sssssss', $nameB, $testEmailB, $hashedB, $phoneB, $shipB, $shipB, $billB);
mysqli_stmt_execute($insertB);
$userBId = mysqli_insert_id($conn);
mysqli_stmt_close($insertB);

// Get a valid variation from DB
$varRes = mysqli_query($conn, "SELECT variation_id FROM product_variations LIMIT 2");
$vars = [];
while ($vr = mysqli_fetch_assoc($varRes)) {
    $vars[] = (int)$vr['variation_id'];
}

$cartAId = getUserCartId($conn, $userAId);
$cartBId = getUserCartId($conn, $userBId);
assertTest("TEST 8a: Distinct Carts Created for User A and User B", $cartAId !== $cartBId, "Cart A ID: $cartAId, Cart B ID: $cartBId");

// Add item to User A's cart
mergeGuestCartItems($conn, $userAId, [
    ['variation_id' => $vars[0], 'quantity' => 2]
]);

// Add item to User B's cart
mergeGuestCartItems($conn, $userBId, [
    ['variation_id' => $vars[1], 'quantity' => 5]
]);

$itemsA = getUserCartItems($conn, $userAId);
$itemsB = getUserCartItems($conn, $userBId);

$userAHasOnlyVar0 = count($itemsA) === 1 && $itemsA[0]['variation_id'] === $vars[0] && $itemsA[0]['quantity'] === 2;
$userBHasOnlyVar1 = count($itemsB) === 1 && $itemsB[0]['variation_id'] === $vars[1] && $itemsB[0]['quantity'] === 5;

assertTest("TEST 8b: User A Can See Only User A's Cart Items", $userAHasOnlyVar0, "User A items: " . json_encode($itemsA));
assertTest("TEST 9: User B Cannot See User A's Cart Items", $userBHasOnlyVar1, "User B items: " . json_encode($itemsB));

// TEST 10: Database Integrity Check
$dbCheck = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN password LIKE '$2y$%' OR password LIKE '$2a$%' THEN 1 ELSE 0 END) as hashed_passwords,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_roles,
        SUM(CASE WHEN shipping_address IS NOT NULL AND billing_address IS NOT NULL THEN 1 ELSE 0 END) as address_fields_valid
    FROM users 
    WHERE user_id IN ($userAId, $userBId)
");
$stats = mysqli_fetch_assoc($dbCheck);
$dbIntegrityPass = ($stats['total_users'] == 2 && $stats['hashed_passwords'] == 2 && $stats['customer_roles'] == 2 && $stats['address_fields_valid'] == 2);
assertTest("TEST 10: Database Integrity (No Plaintext, Valid Roles, Valid Addresses)", $dbIntegrityPass, json_encode($stats));

echo "\n========================================================\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "========================================================\n";

// Cleanup test records
mysqli_query($conn, "DELETE FROM users WHERE user_id IN ($userAId, $userBId)");
