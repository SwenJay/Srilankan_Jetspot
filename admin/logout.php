<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::start();
Auth::logout();
header('Location: login.php');
exit;
