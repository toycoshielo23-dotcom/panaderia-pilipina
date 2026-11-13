<?php
session_start();
require_once __DIR__ . '/account_store.php';
require_once __DIR__ . '/app_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: /login%20form/login.php');
    exit();
}

// Simple per-session throttle to blunt online password guessing.
require_once __DIR__ . '/login_guard.php';
if (!loginGuardAllowsAttempt('login')) {
    header('Location: /login%20form/login.php?error=' . rawurlencode('Too many attempts. Please try again in a few minutes.'));
    exit();
}

$role = strtolower(trim((string)($_POST['role'] ?? '')));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$validRole = in_array($role, ['', 'admin', 'customer'], true);

if ($validRole && in_array($role, ['', 'admin'], true) && appAdminLoginSucceeds($email, $password)) {
    loginGuardReset('login');
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    header('Location: /Project/admin%20dashboard/admin_dashboard.php');
    exit();
}

if ($validRole && in_array($role, ['', 'customer'], true)) {
    foreach (loadCustomerAccounts() as $account) {
        if (strtolower((string)($account['email'] ?? '')) === $email && password_verify($password, $account['password'] ?? '')) {
            loginGuardReset('login');
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['role'] = 'customer';
            $_SESSION['username'] = trim((string)($account['full_name'] ?? $account['username'] ?? $email));
            $_SESSION['customer_email'] = $email;
            header('Location: /customer%20dashboard/customer_dashboard.php');
            exit();
        }
    }
}

// Customer demo accounts are only enabled when explicitly turned on, so a
// public deployment does not ship a known-good login.
if ($validRole && in_array($role, ['', 'customer'], true) && appEnv('ENABLE_DEMO_ACCOUNTS', '0') === '1') {
    $demoCustomerEmails = ['customer@gmail.com', 'customer@panaderia.com'];
    if (in_array($role, ['', 'customer'], true) && in_array($email, $demoCustomerEmails, true) && $password === 'customer123') {
        loginGuardReset('login');
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['role'] = 'customer';
        $_SESSION['username'] = 'customer';
        $_SESSION['customer_email'] = $email;
        header('Location: /customer%20dashboard/customer_dashboard.php');
        exit();
    }
}

// Recorded AFTER clearing the session above, so $_SESSION = [] cannot wipe it.
loginGuardRecordFailure('login');
header('Location: /login%20form/login.php?error=Invalid+email+or+password');
exit();
