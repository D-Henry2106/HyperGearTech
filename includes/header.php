<?php

/**
 * ============================================
 * Hyper Gear Tech - Header Include
 * ============================================
 * Included at the top of every page.
 * Contains <head> section, CSS imports, and opening <body>.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config if not already included
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}

// Calculate cart count for navbar badge
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $cart_query = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    if ($cart_query && $row = $cart_query->fetch_assoc()) {
        $cart_count = $row['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hyper Gear Tech - Premium electronic devices, keyboards, mice, monitors, headsets and accessories.">
    <title><?= isset($page_title) ? $page_title . ' | ' . SITE_NAME : SITE_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>

<body>