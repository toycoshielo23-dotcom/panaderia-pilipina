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

$password = (string)($_POST['password'] ?? '');
$adminMatches = $password !== '' && appAdminLoginSucceeds(appAdminEmail(), $password);
$customerMatches = [];
if ($password !== '') {
    foreach (loadCustomerAccounts() as $account) {
        $storedPassword = (string)($account['password'] ?? '');
        if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
            $customerMatches[] = $account;
            if (count($customerMatches) > 1) {
                break;
            }
        }
    }
}

if ($adminMatches && $customerMatches === []) {
    loginGuardReset('login');
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    header('Location: /Project/admin%20dashboard/admin_dashboard.php');
    exit();
}

if (!$adminMatches && count($customerMatches) === 1) {
    $account = $customerMatches[0];
    $email = strtolower(trim((string)($account['email'] ?? '')));
    loginGuardReset('login');
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['role'] = 'customer';
    $_SESSION['username'] = trim((string)($account['full_name'] ?? $account['username'] ?? $email));
    $_SESSION['customer_email'] = $email;
    header('Location: /customer%20dashboard/customer_dashboard.php');
    exit();
}

// Recorded AFTER clearing the session above, so $_SESSION = [] cannot wipe it.
loginGuardRecordFailure('login');
$error = $adminMatches || count($customerMatches) > 1
    ? 'This password is shared by multiple accounts. Each account needs a unique password.'
    : 'Invalid password.';
header('Location: /login%20form/login.php?error=' . rawurlencode($error));
exit();
