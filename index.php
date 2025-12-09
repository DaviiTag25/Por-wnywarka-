<?php
session_start();

// Sprawdzenie, czy aplikacja jest zainstalowana
if (!file_exists('includes/config.local.php')) {
    header('Location: install.php');
    exit();
}

require_once 'includes/config.local.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Sprawdzenie czy użytkownik jest zalogowany
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: login.php');
    exit();
}

// Routing podstawowy
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'dashboard';

// Dołączanie odpowiedniego pliku
$allowed_pages = ['dashboard', 'products', 'product_edit', 'settings', 'profile', 'reviews'];
if (in_array($page, $allowed_pages) && file_exists("views/$page.php")) {
    include 'includes/header.php';
    include "views/$page.php";
    include 'includes/footer.php';
} else {
    header('HTTP/1.0 404 Not Found');
    include 'views/404.php';
}
