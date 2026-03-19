<?php
// ============================================================
//  SL JetSpot — Configuration
//  Edit these values to match your InfinityFree settings
// ============================================================

define('DB_HOST',     'sql306.infinityfree.com');  // from InfinityFree panel
define('DB_NAME',     'if0_41201889_srilankan_jetspot');      // your DB name
define('DB_USER',     'if0_41201889');              // your DB username
define('DB_PASS',     'OoOIvBBPARr');          // your DB password

define('SITE_NAME',   'Sri Lankan JetSpot');
define('SITE_URL',    'https://swetech.ct.ws/jetspot-php'); // no trailing slash
define('ADMIN_EMAIL', 'suwencj1@gmail.com');           // receives contact form emails
define('BASE_URL', '/jetspot-php');

define('UPLOAD_DIR',  __DIR__ . '/../uploads/');   // absolute path
define('UPLOAD_URL',  SITE_URL . '/uploads/');     // public URL

// Allowed image types
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_MB', 8);

// Session security
define('SESSION_NAME', 'sljetspot_sess');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);

//app password = uecb rggr uote zhcm
