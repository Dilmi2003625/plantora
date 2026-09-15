<?php
/**
 * Plantora E-Commerce
 * User Profile Dashboard (My Account)
 * File: profile.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Protection: Requires active session
requireLogin('profile.php');

$pageTitle = 'My Account | Plantora';
$userId = currentUserId();

// Fetch fresh user data from database (excluding passwords/hashes)
$user = currentUser($conn);
if (!$user) {
    // If user deleted or session invalid, log out
    logoutUser();
    header('Location: login.php');
    exit;
}

// Fetch user's cart item count from database
$cartItems = getUserCartItems($conn, $userId);
$totalCartCount = 0;
foreach ($cartItems as $cItem) {
    $totalCartCount += $cItem['quantity'];
}

// Fetch order count if any
$orderCount = 0;
$orderStmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?');
if ($orderStmt) {
    mysqli_stmt_bind_param($orderStmt, 'i', $userId);
    mysqli_stmt_execute($orderStmt);
    $res = mysqli_stmt_get_result($orderStmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $orderCount = (int)$row['cnt'];
    }
    mysqli_stmt_close($orderStmt);
}

$flash = getFlashMessage();
$regDate = !empty($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$initial = !empty($user['name']) ? strtoupper(mb_substr(trim($user['name']), 0, 1)) : 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div>🌿 Bring Nature Into Your Home with Plantora</div>
    <div>🚚 Free Delivery on orders over Rs. 5000</div>
    <div>Help Center &nbsp; | &nbsp; Track Order &nbsp; | &nbsp; FAQs</div>
</div>

<!-- HEADER -->
<header class="header">
    <a href="index.php" class="logo">
        <img src="images/logo.png" alt="Plantora - Bring Nature Home">
    </a>

    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/header_actions.php'; ?>
</header>

<!-- PROFILE DASHBOARD HERO -->
<section class="profile-hero">
    <div class="profile-hero-inner">
        <div class="profile-avatar-large">
            <span><?php echo htmlspecialchars($initial); ?></span>
        </div>
        <div class="profile-hero-text">
            <span class="profile-role-badge">
                <i class="fa-solid fa-leaf"></i> <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'Customer')); ?> Account
            </span>
            <h1 class="profile-welcome">Hello, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="profile-meta">
                <span><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                <span><i class="fa-regular fa-calendar-check"></i> Member since <?php echo htmlspecialchars($regDate); ?></span>
                <span class="status-pill active"><i class="fa-solid fa-circle"></i> Active</span>
            </p>
        </div>
        <div class="profile-hero-actions">
            <a href="logout.php" class="profile-logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</section>

<!-- MAIN PROFILE DASHBOARD CONTENT -->
<main class="profile-dashboard-wrap">

    <?php if ($flash): ?>
        <div class="auth-alert <?php echo htmlspecialchars($flash['type']); ?>" role="alert" style="margin-bottom: 25px;">
            <i class="<?php echo $flash['type'] === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    <?php endif; ?>

    <!-- STATS OVERVIEW CARDS -->
    <div class="profile-stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-data">
                <div class="stat-number"><?php echo $totalCartCount; ?></div>
                <div class="stat-label">Items in Cart</div>
            </div>
            <a href="cart.php" class="stat-link">View Cart →</a>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            <div class="stat-data">
                <div class="stat-number"><?php echo $orderCount; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <span class="stat-link muted">Order History</span>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="stat-data">
                <div class="stat-number">2</div>
                <div class="stat-label">Saved Addresses</div>
            </div>
            <a href="#addresses" class="stat-link">View Addresses →</a>
        </div>
    </div>

    <!-- MAIN TWO COLUMN LAYOUT -->
    <div class="profile-main-layout">

        <!-- LEFT COLUMN: DETAILS & ADDRESSES -->
        <div class="profile-content-left">

            <!-- 1. PERSONAL DETAILS CARD -->
            <div class="profile-card" id="personal-details">
                <div class="card-header-row">
                    <h2><i class="fa-regular fa-id-card"></i> Personal Information</h2>
                    <button type="button" class="edit-toggle-btn" onclick="toggleEditMode()">
                        <i class="fa-solid fa-pen-to-square"></i> <span id="edit-btn-text">Edit Details</span>
                    </button>
                </div>

                <!-- VIEW MODE -->
                <div id="details-view-mode" class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email Address</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?> <span class="badge-verified">Verified</span></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Account Status</span>
                        <span class="detail-value"><span class="badge-status-active">Active Customer</span></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Registration Date</span>
                        <span class="detail-value"><?php echo htmlspecialchars($regDate); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">User ID Reference</span>
                        <span class="detail-value">#USR-<?php echo str_pad($user['user_id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>

                <!-- EDIT FORM (Hidden by default, toggled via JS) -->
                <div id="details-edit-mode" style="display: none;">
                    <form action="update-profile.php" method="POST" class="auth-form" id="update-profile-form">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="edit_name" class="form-label">Full Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="edit_name" 
                                    name="name" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($user['name']); ?>" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address (Read-only)</label>
                                <input 
                                    type="email" 
                                    class="form-input readonly" 
                                    value="<?php echo htmlspecialchars($user['email']); ?>" 
                                    readonly 
                                    disabled
                                    title="Email cannot be changed directly for security reasons"
                                >
                            </div>

                            <div class="form-group">
                                <label for="edit_phone" class="form-label">Phone Number <span class="required">*</span></label>
                                <input 
                                    type="tel" 
                                    id="edit_phone" 
                                    name="phone" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                    required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label for="edit_shipping_address" class="form-label">Default Shipping Address <span class="required">*</span></label>
                                <textarea 
                                    id="edit_shipping_address" 
                                    name="shipping_address" 
                                    rows="2" 
                                    class="form-textarea" 
                                    required><?php echo htmlspecialchars($user['shipping_address'] ?? $user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="edit_billing_address" class="form-label">Default Billing Address <span class="required">*</span></label>
                                <textarea 
                                    id="edit_billing_address" 
                                    name="billing_address" 
                                    rows="2" 
                                    class="form-textarea" 
                                    required><?php echo htmlspecialchars($user['billing_address'] ?? $user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions-row">
                            <button type="submit" class="auth-submit-btn compact">
                                <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                            </button>
                            <button type="button" class="btn-cancel" onclick="toggleEditMode()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. ADDRESS BOOK CARD -->
            <div class="profile-card" id="addresses">
                <div class="card-header-row">
                    <h2><i class="fa-solid fa-map-location-dot"></i> Address Book</h2>
                    <span class="badge-address-note">Used for rapid checkout</span>
                </div>

                <div class="address-grid">
                    <!-- Shipping Address Card -->
                    <div class="address-box">
                        <div class="address-box-header">
                            <span class="address-tag shipping"><i class="fa-solid fa-truck"></i> Shipping Address</span>
                        </div>
                        <div class="address-box-content">
                            <p class="address-recipient"><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
                            <p class="address-text">
                                <?php echo nl2br(htmlspecialchars($user['shipping_address'] ?? $user['address'] ?? 'No shipping address specified yet.')); ?>
                            </p>
                            <p class="address-phone"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <button type="button" class="address-edit-link" onclick="openEditAddresses()">
                            <i class="fa-solid fa-pencil"></i> Edit Shipping Address
                        </button>
                    </div>

                    <!-- Billing Address Card -->
                    <div class="address-box">
                        <div class="address-box-header">
                            <span class="address-tag billing"><i class="fa-solid fa-receipt"></i> Billing Address</span>
                        </div>
                        <div class="address-box-content">
                            <p class="address-recipient"><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
                            <p class="address-text">
                                <?php echo nl2br(htmlspecialchars($user['billing_address'] ?? $user['address'] ?? 'No billing address specified yet.')); ?>
                            </p>
                            <p class="address-phone"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <button type="button" class="address-edit-link" onclick="openEditAddresses()">
                            <i class="fa-solid fa-pencil"></i> Edit Billing Address
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: SECURITY & QUICK ACTIONS -->
        <div class="profile-content-right">

            <!-- 3. CHANGE PASSWORD CARD -->
            <div class="profile-card" id="change-password">
                <div class="card-header-row">
                    <h2><i class="fa-solid fa-shield-halved"></i> Security & Password</h2>
                </div>

                <form action="update-password.php" method="POST" class="auth-form" id="password-form">
                    <div class="form-group full-width">
                        <label for="current_password" class="form-label">Current Password <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-input" 
                                placeholder="Enter current password" 
                                required
                            >
                            <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('current_password', this)" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="new_password" class="form-label">New Password <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-input" 
                                placeholder="Min 8 characters (letters & numbers)" 
                                required
                            >
                            <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('new_password', this)" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-circle-check input-icon"></i>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                placeholder="Re-enter new password" 
                                required
                            >
                            <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('confirm_password', this)" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit-btn compact">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- 4. QUICK ACTIONS CARD -->
            <div class="profile-card">
                <div class="card-header-row">
                    <h2><i class="fa-solid fa-compass"></i> Quick Links</h2>
                </div>
                <div class="quick-links-list">
                    <a href="shop.php" class="quick-link-item">
                        <div class="ql-icon"><i class="fa-solid fa-plant-wilt"></i></div>
                        <div class="ql-text">
                            <strong>Browse Plants</strong>
                            <span>Explore indoor greenery & pots</span>
                        </div>
                        <i class="fa-solid fa-chevron-right ql-arrow"></i>
                    </a>
                    <a href="cart.php" class="quick-link-item">
                        <div class="ql-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="ql-text">
                            <strong>My Shopping Cart</strong>
                            <span><?php echo $totalCartCount; ?> items currently saved</span>
                        </div>
                        <i class="fa-solid fa-chevron-right ql-arrow"></i>
                    </a>
                    <a href="logout.php" class="quick-link-item logout">
                        <div class="ql-icon"><i class="fa-solid fa-power-off"></i></div>
                        <div class="ql-text">
                            <strong>Log Out</strong>
                            <span>End current session securely</span>
                        </div>
                        <i class="fa-solid fa-chevron-right ql-arrow"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>

</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-leaf watermark"><i class="fa-solid fa-leaf"></i></div>
    <div class="footer-grid">
        <div class="footer-brand">
            <img src="images/logo.png" alt="Plantora Logo" class="footer-logo">
            <p>Beautiful indoor plants, stylish pots and thoughtful gift packages to greenify your space.</p>
        </div>
        <div class="footer-column">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a><a href="shop.php">Indoor Plants</a><a href="shop.php?category=pots">Pots</a><a href="shop.php?category=packages">Gift Packages</a>
        </div>
        <div class="footer-column">
            <h3>Customer Service</h3>
            <a href="#">Help Center</a><a href="#">Track Order</a><a href="#">FAQs</a><a href="#">Returns & Refunds</a>
        </div>
        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>📍 Colombo, Sri Lanka</p><p>📞 +94 71 234 5678</p><p>✉ support@plantora.com</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Plantora. All Rights Reserved.</p>
        <div><a href="#">Privacy Policy</a><a href="#">Terms & Conditions</a></div>
    </div>
</footer>

<script src="js/cart.js?v=2"></script>
<script>
function toggleEditMode() {
    const viewDiv = document.getElementById('details-view-mode');
    const editDiv = document.getElementById('details-edit-mode');
    const btnText = document.getElementById('edit-btn-text');

    if (viewDiv.style.display === 'none') {
        viewDiv.style.display = 'grid';
        editDiv.style.display = 'none';
        btnText.textContent = 'Edit Details';
    } else {
        viewDiv.style.display = 'none';
        editDiv.style.display = 'block';
        btnText.textContent = 'Close Editor';
    }
}

function openEditAddresses() {
    const viewDiv = document.getElementById('details-view-mode');
    const editDiv = document.getElementById('details-edit-mode');
    const btnText = document.getElementById('edit-btn-text');

    viewDiv.style.display = 'none';
    editDiv.style.display = 'block';
    btnText.textContent = 'Close Editor';

    document.getElementById('edit_shipping_address').focus();
    document.getElementById('personal-details').scrollIntoView({ behavior: 'smooth' });
}

function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }
}

// Client-side validation on password change
document.getElementById('password-form').addEventListener('submit', function(e) {
    const newPwd = document.getElementById('new_password').value;
    const confirmPwd = document.getElementById('confirm_password').value;

    if (newPwd !== confirmPwd) {
        e.preventDefault();
        alert('New password and confirmation password do not match.');
        document.getElementById('confirm_password').focus();
        return;
    }

    if (newPwd.length < 8) {
        e.preventDefault();
        alert('New password must be at least 8 characters long.');
        document.getElementById('new_password').focus();
        return;
    }
});

// Check if URL hash indicates editing or password change
window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#edit-profile') {
        toggleEditMode();
    }
});
</script>
</body>
</html>
