<?php
// ============================================================
//  SL JetSpot — Admin Login (LOCAL VERSION)
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::start();

// If already logged in → go to dashboard
if (Auth::check()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $error = 'Security token mismatch. Please try again.';
    } else {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {

            // Redirect to admin dashboard
            header("Location: index.php");
            exit;

        } else {
            sleep(1);
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= e(SITE_NAME) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-body">

<div class="login-card">

    <div class="login-logo">
        <span>SL <strong>JetSpot</strong></span>
    </div>

    <h1>Admin Login</h1>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- IMPORTANT: action="" so it submits to itself -->
    <form method="POST" action="" autocomplete="off">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text"
                   name="username"
                   required
                   autofocus
                   value="<?= e($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password"
                   name="password"
                   required>
        </div>

        <button type="submit" class="btn-admin-primary">
            Sign In
        </button>
    </form>

    <!-- Go back to main site -->
    <p class="login-back">
        <a href="../index.php">← Back to site</a>
    </p>

    
</div>

</body>
</html>