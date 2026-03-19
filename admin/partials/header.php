<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= e(SITE_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

<?php
$unreadCount = (int) DB::query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <i class="fas fa-plane-departure"></i>
        <span>JetSpot <strong>Admin</strong></span>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="sn-link <?= $currentPage==='index.php'?'active':'' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="aircraft.php" class="sn-link <?= str_starts_with($currentPage,'aircraft')?'active':'' ?>">
            <i class="fas fa-plane"></i> Aircraft
        </a>
        <a href="messages.php" class="sn-link <?= $currentPage==='messages.php'?'active':'' ?>">
            <i class="fas fa-envelope"></i> Messages
            <?php if ($unreadCount > 0): ?>
            <span class="sn-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <div class="sn-divider"></div>
        <a href="change-password.php" class="sn-link <?= $currentPage==='change-password.php'?'active':'' ?>">
            <i class="fas fa-key"></i> Change Password
        </a>
        <a href="../index.php" class="sn-link" target="_blank">
            <i class="fas fa-external-link-alt"></i> View Site
        </a>
        <a href="logout.php" class="sn-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>

<!-- TOPBAR -->
<div class="admin-topbar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-right">
        <span class="topbar-user"><i class="fas fa-user-circle"></i> <?= e(Auth::user()) ?></span>
    </div>
</div>

<!-- MAIN -->
<main class="admin-main" id="adminMain">
