<?php
/**
 * Plantora E-Commerce
 * User Login Page
 * File: login.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to profile or return_url
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$pageTitle = 'Login | Plantora';
$error = '';
$flash = getFlashMessage();

// Sanitize return_url
$returnUrl = '';
if (!empty($_GET['return_url'])) {
    $parsed = parse_url($_GET['return_url']);
    $cleanPath = basename($parsed['path'] ?? '');
    if (!empty($cleanPath) && strpos($cleanPath, 'login.php') === false && strpos($cleanPath, 'register.php') === false) {
        $returnUrl = $cleanPath . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $postedReturnUrl = trim($_POST['return_url'] ?? '');
    $guestCartJson = trim($_POST['guest_cart_data'] ?? '');

    // Server-side validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email or password.';
    } else {
        // Query user by email using prepared statement
        $stmt = mysqli_prepare($conn, 'SELECT user_id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            // Verify password using password_verify()
            if ($user && password_verify($password, $user['password'])) {
                // Log in user: regenerates session ID and sets session variables
                loginUser($conn, $user);

                // If guest cart items were present in localStorage, merge into user's DB cart
                if (!empty($guestCartJson)) {
                    $guestItems = json_decode($guestCartJson, true);
                    if (is_array($guestItems) && !empty($guestItems)) {
                        mergeGuestCartItems($conn, (int)$user['user_id'], $guestItems);
                    }
                }

                // Redirect to intended page or index.php
                $destination = 'index.php';
                if (!empty($postedReturnUrl)) {
                    $parsed = parse_url($postedReturnUrl);
                    $cleanPath = basename($parsed['path'] ?? '');
                    if (!empty($cleanPath) && strpos($cleanPath, 'login.php') === false && strpos($cleanPath, 'register.php') === false) {
                        $destination = $cleanPath . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
                    }
                }

                // If AJAX request, respond with JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode([
                        'success' => true,
                        'redirect' => $destination,
                        'cart' => getUserCartItems($conn, (int)$user['user_id'])
                    ]);
                    exit;
                }

                header('Location: ' . $destination);
                exit;
            } else {
                // Generic error to avoid revealing account existence
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Authentication service is temporarily unavailable. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css?v=4">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="auth-body-bg">

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

<!-- MAIN LOGIN SECTION WITH BOTANICAL BACKGROUND -->
<section class="auth-wrapper-section login-bg-section">
    <div class="auth-page-wrap">
        <div class="auth-card login-card">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back!</h1>
                <p class="auth-subtitle">Login to your Plantora account</p>
            </div>

            <?php if ($flash): ?>
                <div class="auth-alert <?php echo htmlspecialchars($flash['type']); ?>" role="alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="auth-alert error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php<?php echo !empty($returnUrl) ? '?return_url=' . urlencode($returnUrl) : ''; ?>" method="POST" class="auth-form" id="login-form" novalidate>
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
                <input type="hidden" name="guest_cart_data" id="guest_cart_data" value="">

                <!-- Email Field -->
                <div class="form-group full-width">
                    <label for="email" class="form-label">Email <span class="required">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your email" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required 
                            autocomplete="email"
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group full-width">
                    <div class="label-row">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <a href="#" class="forgot-pwd-link" onclick="alert('Password reset link sent to your registered email if it exists.'); return false;">Forgot password?</a>
                    </div>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('password', this)" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    <span>Login</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Bottom Navigation Link -->
            <div class="auth-footer">
                <p class="auth-bottom-text">
                    Don't have an account? <a href="register.php" class="auth-switch-link">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</section>

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

// Client-side UX validation
document.getElementById('login-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {
        e.preventDefault();
        alert('Please enter both your email and password.');
        return;
    }

    // Attach guest cart data if present
    const cart = typeof window.readCart === 'function' ? window.readCart() : [];
    if (cart && cart.length > 0) {
        document.getElementById('guest_cart_data').value = JSON.stringify(cart);
    }
});
</script>
</body>
</html>
