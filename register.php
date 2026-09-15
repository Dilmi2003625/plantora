<?php
/**
 * Plantora E-Commerce
 * User Registration Page
 * File: register.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to profile
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$pageTitle = 'Create Account | Plantora';
$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'shipping_address' => '',
    'billing_address' => '',
    'same_address' => false,
    'terms' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = trim($_POST['full_name'] ?? '');
    $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $formData['same_address'] = isset($_POST['same_address']);
    $formData['terms'] = isset($_POST['terms']);
    $formData['shipping_address'] = trim($_POST['shipping_address'] ?? '');
    $formData['billing_address'] = $formData['same_address'] 
        ? $formData['shipping_address'] 
        : trim($_POST['billing_address'] ?? '');

    // 1. Validate Full Name
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($formData['full_name']) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters.';
    }

    // 2. Validate Email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // Uniqueness check using prepared statement
        $emailCheckStmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? LIMIT 1');
        if ($emailCheckStmt) {
            mysqli_stmt_bind_param($emailCheckStmt, 's', $formData['email']);
            mysqli_stmt_execute($emailCheckStmt);
            mysqli_stmt_store_result($emailCheckStmt);
            if (mysqli_stmt_num_rows($emailCheckStmt) > 0) {
                $errors['email'] = 'An account with this email already exists. Please login instead.';
            }
            mysqli_stmt_close($emailCheckStmt);
        } else {
            $errors['general'] = 'Registration service unavailable. Please try again.';
        }
    }

    // 3. Validate Phone Number
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9+\s\-()]{7,20}$/', $formData['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    // 4. Validate Addresses
    if (empty($formData['shipping_address'])) {
        $errors['shipping_address'] = 'Shipping address cannot be empty.';
    }

    if (empty($formData['billing_address'])) {
        $errors['billing_address'] = 'Billing address cannot be empty.';
    }

    // 5. Validate Password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain both letters and numbers.';
    }

    // 6. Confirm Password Match
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 7. Validate Terms & Conditions Checkbox
    if (!$formData['terms']) {
        $errors['terms'] = 'You must agree to the Terms and Conditions and Privacy Policy.';
    }

    // If validation passes, insert with password_hash
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer'; // Default and strictly enforced role

        $generalAddress = $formData['shipping_address'];

        $insertSql = '
            INSERT INTO users (name, email, password, phone, address, shipping_address, billing_address, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ';
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if ($insertStmt) {
            mysqli_stmt_bind_param(
                $insertStmt,
                'ssssssss',
                $formData['full_name'],
                $formData['email'],
                $passwordHash,
                $formData['phone'],
                $generalAddress,
                $formData['shipping_address'],
                $formData['billing_address'],
                $role
            );

            if (mysqli_stmt_execute($insertStmt)) {
                $newUserId = mysqli_insert_id($conn);
                mysqli_stmt_close($insertStmt);

                // Create initial cart record for user
                getUserCartId($conn, $newUserId);

                setFlashMessage('success', 'Registration successful! Please login with your credentials.');
                header('Location: login.php');
                exit;
            } else {
                mysqli_stmt_close($insertStmt);
                $errors['general'] = 'Registration failed. Please check your details and try again.';
            }
        } else {
            $errors['general'] = 'Database error. Please try again later.';
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

<!-- MAIN REGISTRATION SECTION WITH BOTANICAL BACKGROUND -->
<section class="auth-wrapper-section register-bg-section">
    <div class="auth-page-wrap">
        <div class="auth-card register-card">
            <div class="auth-header">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join Plantora and bring nature home</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="auth-alert error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($errors['general']); ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form" id="register-form" novalidate>
                <div class="form-grid">
                    <!-- 1. Full Name -->
                    <div class="form-group full-width">
                        <label for="full_name" class="form-label">Full Name <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-user input-icon"></i>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-input <?php echo isset($errors['full_name']) ? 'has-error' : ''; ?>" 
                                placeholder="Enter your full name" 
                                value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                                required
                                autofocus
                            >
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-envelope input-icon"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" 
                                placeholder="Enter your email" 
                                value="<?php echo htmlspecialchars($formData['email']); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Phone Number -->
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-input <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>" 
                                placeholder="Enter your phone number" 
                                value="<?php echo htmlspecialchars($formData['phone']); ?>"
                                required
                            >
                        </div>
                        <?php if (isset($errors['phone'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['phone']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 4. Shipping Address -->
                    <div class="form-group full-width">
                        <label for="shipping_address" class="form-label">Shipping Address <span class="required">*</span></label>
                        <textarea 
                            id="shipping_address" 
                            name="shipping_address" 
                            rows="2" 
                            class="form-textarea <?php echo isset($errors['shipping_address']) ? 'has-error' : ''; ?>" 
                            placeholder="Enter your shipping address" 
                            required><?php echo htmlspecialchars($formData['shipping_address']); ?></textarea>
                        <?php if (isset($errors['shipping_address'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['shipping_address']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Same Address Toggle Checkbox -->
                    <div class="form-group full-width checkbox-group">
                        <label class="checkbox-container">
                            <input 
                                type="checkbox" 
                                id="same_address" 
                                name="same_address" 
                                <?php echo $formData['same_address'] ? 'checked' : ''; ?>
                                onchange="syncBillingAddress()"
                            >
                            <span class="checkbox-checkmark"></span>
                            <span class="checkbox-label">Billing address is the same as shipping address</span>
                        </label>
                    </div>

                    <!-- 5. Billing Address -->
                    <div class="form-group full-width" id="billing_address_group" style="<?php echo $formData['same_address'] ? 'display:none;' : ''; ?>">
                        <label for="billing_address" class="form-label">Billing Address <span class="required">*</span></label>
                        <textarea 
                            id="billing_address" 
                            name="billing_address" 
                            rows="2" 
                            class="form-textarea <?php echo isset($errors['billing_address']) ? 'has-error' : ''; ?>" 
                            placeholder="Enter your billing address"><?php echo htmlspecialchars($formData['billing_address']); ?></textarea>
                        <?php if (isset($errors['billing_address'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['billing_address']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 6. Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input <?php echo isset($errors['password']) ? 'has-error' : ''; ?>" 
                                placeholder="Create a password"
                                required
                            >
                            <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('password', this)" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php else: ?>
                            <span class="field-hint">Min 8 characters (letters & numbers).</span>
                        <?php endif; ?>
                    </div>

                    <!-- 7. Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>" 
                                placeholder="Confirm your password"
                                required
                            >
                            <button type="button" class="pwd-toggle" onclick="togglePasswordVisibility('confirm_password', this)" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Terms and Conditions Checkbox & Privacy Policy -->
                    <div class="form-group full-width checkbox-group">
                        <label class="checkbox-container">
                            <input 
                                type="checkbox" 
                                id="terms" 
                                name="terms" 
                                <?php echo $formData['terms'] ? 'checked' : ''; ?>
                                required
                            >
                            <span class="checkbox-checkmark"></span>
                            <span class="checkbox-label">
                                I agree to the <a href="#" class="terms-link" onclick="alert('Plantora Terms and Conditions: All orders subject to botanical care standards and standard warranty.'); return false;">Terms and Conditions</a> and <a href="#" class="terms-link" onclick="alert('Plantora Privacy Policy: Your personal data is protected and never sold.'); return false;">Privacy Policy</a>.
                            </span>
                        </label>
                        <?php if (isset($errors['terms'])): ?>
                            <span class="field-error"><?php echo htmlspecialchars($errors['terms']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    <span>Create Account</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Bottom Navigation Link -->
            <div class="auth-footer">
                <p class="auth-bottom-text">
                    Already have an account? <a href="login.php" class="auth-switch-link">Login</a>
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

function syncBillingAddress() {
    const sameBox = document.getElementById('same_address');
    const billingGroup = document.getElementById('billing_address_group');
    const shipInput = document.getElementById('shipping_address');
    const billInput = document.getElementById('billing_address');

    if (sameBox.checked) {
        billingGroup.style.display = 'none';
        billInput.value = shipInput.value;
    } else {
        billingGroup.style.display = 'block';
    }
}

// Client-side UX validation
document.getElementById('register-form').addEventListener('submit', function(e) {
    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const shipAddress = document.getElementById('shipping_address').value.trim();
    const sameBox = document.getElementById('same_address');
    const billAddress = sameBox.checked ? shipAddress : document.getElementById('billing_address').value.trim();
    const pwd = document.getElementById('password').value;
    const confirmPwd = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;

    if (sameBox.checked) {
        document.getElementById('billing_address').value = shipAddress;
    }

    if (!fullName || !email || !phone || !shipAddress || !billAddress || !pwd || !confirmPwd) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return;
    }

    if (pwd !== confirmPwd) {
        e.preventDefault();
        alert('Password and Confirm Password do not match.');
        document.getElementById('confirm_password').focus();
        return;
    }

    if (pwd.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        document.getElementById('password').focus();
        return;
    }

    if (!terms) {
        e.preventDefault();
        alert('Please agree to the Terms and Conditions and Privacy Policy to create your account.');
        document.getElementById('terms').focus();
        return;
    }
});
</script>
</body>
</html>
