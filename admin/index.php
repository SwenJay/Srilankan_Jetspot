<?php
// ============================================================
//  SL JetSpot — Admin Dashboard
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::require();

// --- Stats ---
$totalAircraft  = (int) DB::query('SELECT COUNT(*) FROM aircraft')->fetchColumn();
$publishedCount = (int) DB::query('SELECT COUNT(*) FROM aircraft WHERE is_published = 1')->fetchColumn();
$unreadMessages = (int) DB::query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$totalVisitors  = (int) DB::query('SELECT COUNT(*) FROM visitors WHERE visited_at > DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();

// --- Recent activity ---
$recentAircraft = DB::query('SELECT * FROM aircraft ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentMessages = DB::query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5')->fetchAll();

require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <a href="aircraft-add.php" class="btn-admin-primary"><i class="fas fa-plus"></i> Add Aircraft</a>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-plane"></i></div>
        <div class="stat-body">
            <span class="stat-num"><?= $totalAircraft ?></span>
            <span class="stat-label">Total Aircraft</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-eye"></i></div>
        <div class="stat-body">
            <span class="stat-num"><?= $publishedCount ?></span>
            <span class="stat-label">Published</span>
        </div>
    </div>
    <div class="stat-card <?= $unreadMessages > 0 ? 'has-badge' : '' ?>">
        <div class="stat-icon red"><i class="fas fa-envelope"></i></div>
        <div class="stat-body">
            <span class="stat-num"><?= $unreadMessages ?></span>
            <span class="stat-label">Unread Messages</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <span class="stat-num"><?= number_format($totalVisitors) ?></span>
            <span class="stat-label">Visitors (30d)</span>
        </div>
    </div>
</div>

<!-- TWO COLUMN -->
<div class="admin-two-col">

    <!-- RECENT AIRCRAFT -->
    <div class="admin-card">
        <div class="card-head">
            <h2><i class="fas fa-plane"></i> Recent Aircraft</h2>
            <a href="aircraft.php">View all</a>
        </div>
        <table class="admin-table">
            <thead><tr><th>Airline</th><th>Aircraft</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentAircraft as $ac): ?>
            <tr>
                <td><?= e($ac['airline']) ?></td>
                <td><?= e($ac['variant'] ?: $ac['aircraft_type']) ?></td>
                <td><?= date('d M Y', strtotime($ac['date_captured'])) ?></td>
                <td>
                    <span class="badge-status <?= $ac['is_published'] ? 'published' : 'draft' ?>">
                        <?= $ac['is_published'] ? 'Published' : 'Draft' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- RECENT MESSAGES -->
    <div class="admin-card">
        <div class="card-head">
            <h2><i class="fas fa-envelope"></i> Recent Messages</h2>
            <a href="messages.php">View all</a>
        </div>
        <?php if (empty($recentMessages)): ?>
        <p class="empty-state">No messages yet.</p>
        <?php else: ?>
        <div class="message-list">
        <?php foreach ($recentMessages as $msg): ?>
            <div class="message-item <?= !$msg['is_read'] ? 'unread' : '' ?>">
                <div class="msg-meta">
                    <strong><?= e($msg['name']) ?></strong>
                    <?php if (!$msg['is_read']): ?><span class="dot-new">New</span><?php endif; ?>
                    <span class="msg-date"><?= date('d M', strtotime($msg['created_at'])) ?></span>
                </div>
                <div class="msg-subject"><?= e($msg['subject']) ?></div>
                <a href="messages.php?id=<?= $msg['id'] ?>" class="msg-link">Read →</a>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require 'partials/footer.php'; ?>
