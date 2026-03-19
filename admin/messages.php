<?php
// ============================================================
//  SL JetSpot — Admin Messages
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::require();

// Only process POST actions on actual POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // BUG 1 FIX — parentheses around null coalesce so === binds correctly
    if (($_POST['action'] ?? '') === 'delete' && isset($_POST['id'])) {
        // BUG 2 FIX — redirect and stop if CSRF fails, don't silently skip
        if (!csrf_verify()) {
            flash('error', 'Security token invalid.');
            redirect('admin/messages.php');
        }
        DB::query('DELETE FROM messages WHERE id = ?', [(int)$_POST['id']]);
        flash('success', 'Message deleted.');
        redirect('admin/messages.php');
    }

    if (($_POST['action'] ?? '') === 'mark_read' && isset($_POST['id'])) {
        if (!csrf_verify()) {
            flash('error', 'Security token invalid.');
            redirect('admin/messages.php');
        }
        DB::query('UPDATE messages SET is_read = 1 WHERE id = ?', [(int)$_POST['id']]);
        redirect('admin/messages.php?id=' . (int)$_POST['id']);
    }
}

// View single message — auto mark as read
$viewMsg = null;
if (isset($_GET['id'])) {
    $viewMsg = DB::query('SELECT * FROM messages WHERE id = ?', [(int)$_GET['id']])->fetch();
    if ($viewMsg && !$viewMsg['is_read']) {
        DB::query('UPDATE messages SET is_read = 1 WHERE id = ?', [(int)$_GET['id']]);
        $viewMsg['is_read'] = 1;
    }
}

$messages = DB::query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();

require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-envelope"></i> Messages (<?= count($messages) ?>)</h1>
</div>

<?php if ($err = flash('error')): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
<?php endif; ?>
<?php if ($ok = flash('success')): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($ok) ?></div>
<?php endif; ?>

<div class="messages-layout">

    <!-- SIDEBAR LIST -->
    <div class="msg-sidebar">
        <?php if (empty($messages)): ?>
            <p class="empty-state" style="padding:24px">No messages yet.</p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
        <a href="messages.php?id=<?= (int)$msg['id'] ?>"
           class="msg-sidebar-item <?= !$msg['is_read'] ? 'unread' : '' ?> <?= (isset($viewMsg) && $viewMsg['id'] == $msg['id']) ? 'active' : '' ?>">
            <div class="msg-sb-head">
                <strong><?= e($msg['name']) ?></strong>
                <span><?= date('d M', strtotime($msg['created_at'])) ?></span>
            </div>
            <div class="msg-sb-subject"><?= e($msg['subject']) ?></div>
            <div class="msg-sb-preview"><?= e(substr($msg['message'], 0, 80)) ?>...</div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- DETAIL VIEW -->
    <div class="msg-detail">
        <?php if ($viewMsg): ?>
        <div class="admin-card">
            <div class="msg-detail-header">
                <div>
                    <h2><?= e($viewMsg['subject']) ?></h2>
                    <p class="msg-meta-line">
                        From <strong><?= e($viewMsg['name']) ?></strong>
                        &lt;<a href="mailto:<?= e($viewMsg['email']) ?>"><?= e($viewMsg['email']) ?></a>&gt;
                        &middot; <?= date('d M Y, H:i', strtotime($viewMsg['created_at'])) ?>
                    </p>
                </div>
                <!-- Delete form -->
                <form method="POST" onsubmit="return confirm('Delete this message? This cannot be undone.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id"     value="<?= (int)$viewMsg['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn-icon delete" title="Delete message">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>

            <div class="msg-body"><?= nl2br(e($viewMsg['message'])) ?></div>

            <div class="msg-actions">
                <!-- BUG 4 FIX — urlencode the email address, not just the subject -->
                <a href="mailto:<?= rawurlencode($viewMsg['email']) ?>?subject=Re:+<?= rawurlencode($viewMsg['subject']) ?>"
                   class="btn-admin-primary">
                    <i class="fas fa-reply"></i> Reply via Email
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="admin-card msg-empty">
            <i class="fas fa-envelope-open-text"></i>
            <p>Select a message to read it</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require 'partials/footer.php'; ?>