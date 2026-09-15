<?php
/**
 * Verification of Login and Register Page Improvements
 */

$loginHtml = file_get_contents('http://127.0.0.1/plantora/login.php');

$checksLogin = [
    'Logo removed from login card' => strpos($loginHtml, 'auth-brand-badge') === false,
    'Welcome Back! heading present' => strpos($loginHtml, 'Welcome Back!') !== false,
    'Login to your Plantora account subtitle' => strpos($loginHtml, 'Login to your Plantora account') !== false,
    'Enter your email placeholder' => strpos($loginHtml, 'placeholder="Enter your email"') !== false,
    'Enter your password placeholder' => strpos($loginHtml, 'placeholder="Enter your password"') !== false,
    'Show/hide password toggle present' => strpos($loginHtml, 'pwd-toggle') !== false,
    'Login button text' => strpos($loginHtml, '<span>Login</span>') !== false,
    'Forgot password link present' => strpos($loginHtml, 'Forgot password?') !== false,
    'Don\'t have an account? Sign up present' => strpos($loginHtml, "Don't have an account?") !== false && strpos($loginHtml, 'href="register.php"') !== false,
    'Login background section present' => strpos($loginHtml, 'login-bg-section') !== false,
    'Navbar does NOT have Register button' => strpos($loginHtml, 'register-nav-btn') === false && strpos($loginHtml, '>Register<') === false,
    'Navbar retains main logo' => strpos($loginHtml, 'images/logo.png') !== false,
    'Navbar retains cart link' => strpos($loginHtml, 'cart.php') !== false,
];

echo "LOGIN PAGE & NAVBAR CHECKS:\n";
$allPass = true;
foreach ($checksLogin as $k => $v) {
    echo ($v ? ' [PASS] ' : ' [FAIL] ') . $k . "\n";
    if (!$v) $allPass = false;
}

$regHtml = file_get_contents('http://127.0.0.1/plantora/register.php');
$checksReg = [
    'Create Account heading' => strpos($regHtml, 'Create Account') !== false,
    'Join Plantora and bring nature home' => strpos($regHtml, 'Join Plantora and bring nature home') !== false,
    'Enter your full name' => strpos($regHtml, 'placeholder="Enter your full name"') !== false,
    'Enter your email' => strpos($regHtml, 'placeholder="Enter your email"') !== false,
    'Enter your phone number' => strpos($regHtml, 'placeholder="Enter your phone number"') !== false,
    'Enter your shipping address' => strpos($regHtml, 'placeholder="Enter your shipping address"') !== false,
    'Enter your billing address' => strpos($regHtml, 'placeholder="Enter your billing address"') !== false,
    'Create a password' => strpos($regHtml, 'placeholder="Create a password"') !== false,
    'Confirm your password' => strpos($regHtml, 'placeholder="Confirm your password"') !== false,
    'Terms and Conditions checkbox' => strpos($regHtml, 'Terms and Conditions') !== false,
    'Privacy Policy link' => strpos($regHtml, 'Privacy Policy') !== false,
    'Create Account button' => strpos($regHtml, '<span>Create Account</span>') !== false,
    'Already have an account? Login link' => strpos($regHtml, 'Already have an account?') !== false && strpos($regHtml, 'href="login.php"') !== false,
    'Navbar on register does NOT have Register button' => strpos($regHtml, 'register-nav-btn') === false && strpos($regHtml, '>Register<') === false
];

echo "\nREGISTER PAGE CHECKS:\n";
foreach ($checksReg as $k => $v) {
    echo ($v ? ' [PASS] ' : ' [FAIL] ') . $k . "\n";
    if (!$v) $allPass = false;
}

$bgExists = file_exists(__DIR__ . '/../images/auth/login-background.jpg');
echo "\nBACKGROUND IMAGE FILE CHECK:\n";
echo ($bgExists ? " [PASS] " : " [FAIL] ") . "images/auth/login-background.jpg exists (" . filesize(__DIR__ . '/../images/auth/login-background.jpg') . " bytes)\n";

echo "\nFINAL RESULT: " . ($allPass && $bgExists ? "ALL REQUIREMENTS MET & VERIFIED" : "VERIFICATION FAILED") . "\n";
