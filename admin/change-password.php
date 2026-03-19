<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::require();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Token error.'; goto render; }

    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    $admin = DB::query('SELECT * FROM admins WHERE id = ?', [$_SESSION['admin_id']])->fetch();

    if (!password_verify($current, $admin['password_hash'])) $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 8)          $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $confirm)         $errors[] = 'New passwords do not match.';

    if (!$errors) {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        DB::query('UPDATE admins SET password_hash = ? WHERE id = ?', [$hash, $_SESSION['admin_id']]);
        $success = true;
    }
}

render:
require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-key"></i> Change Password</h1>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password changed successfully!</div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-card" style="max-width:480px">
    <form method="POST" class="admin-form" style="box-shadow:none;padding:0">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required autofocus>
        </div>
        <div class="form-group">
            <label>New Password <span style="font-size:.8rem;color:#888">(min. 8 chars)</span></label>
            <input type="password" name="new_password" required minlength="8">
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <div class="form-actions" style="margin-top:0">
            <button type="submit" class="btn-admin-primary"><i class="fas fa-save"></i> Update Password</button>
        </div>
    </form>
</div>

<?php require 'partials/footer.php'; ?>
