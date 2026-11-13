<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ../Project/admin%20dashboard/admin_dashboard.php');
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    header('Location: ../customer%20dashboard/customer_dashboard.php');
    exit();
}

header('Location: ../login%20form/index.php');
exit();
