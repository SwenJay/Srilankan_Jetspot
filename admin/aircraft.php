<?php
// ============================================================
//  SL JetSpot — Admin Aircraft List
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::require();

// Handle publish toggle
if ($_POST['action'] ?? '' === 'toggle' && isset($_POST['id'])) {
    if (!csrf_verify()) { redirect('aircraft.php'); }
    DB::query('UPDATE aircraft SET is_published = NOT is_published WHERE id = ?', [(int)$_POST['id']]);
    redirect('aircraft.php');
}

// Handle delete
if ($_POST['action'] ?? '' === 'delete' && isset($_POST['id'])) {
    if (!csrf_verify()) { redirect('aircraft.php'); }
    $row = DB::query('SELECT image_path FROM aircraft WHERE id = ?', [(int)$_POST['id']])->fetch();
    if ($row) {
        $full = UPLOAD_DIR . basename($row['image_path']);
        if (file_exists($full) && strpos($row['image_path'], 'uploads/') !== false) {
            @unlink($full);
        }
        DB::query('DELETE FROM aircraft WHERE id = ?', [(int)$_POST['id']]);
    }
    flash('success', 'Aircraft deleted.');
    redirect('aircraft.php');
}

$aircraft = DB::query(
    'SELECT * FROM aircraft ORDER BY sort_order ASC, created_at DESC'
)->fetchAll();

require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-plane"></i> Aircraft (<?= count($aircraft) ?>)</h1>
    <a href="aircraft-add.php" class="btn-admin-primary"><i class="fas fa-plus"></i> Add Aircraft</a>
</div>

<?php if ($msg = flash('success')): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-wrap">
    <table class="admin-table sortable">
        <thead>
            <tr>
                <th style="width:80px">Image</th>
                <th>Airline</th>
                <th>Aircraft</th>
                <th>Reg</th>
                <th>Location</th>
                <th>Date</th>
                <th>Status</th>
                <th style="width:120px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($aircraft as $ac): ?>
        <tr>
            <td>
                <img src="../<?= e($ac['image_path']) ?>" alt="" class="thumb"
                    style="width:70px;height:50px;object-fit:cover;border-radius:6px;">
            </td>
            <td><strong><?= e($ac['airline']) ?></strong></td>
            <td><?= e($ac['variant'] ?: $ac['aircraft_type']) ?></td>
            <td><code><?= e($ac['registration']) ?></code></td>
            <td><?= e($ac['location']) ?></td>
            <td><?= date('d M Y', strtotime($ac['date_captured'])) ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $ac['id'] ?>">
                    <input type="hidden" name="action" value="toggle">
                    <button type="submit" class="badge-status <?= $ac['is_published'] ? 'published' : 'draft' ?>"
                        style="cursor:pointer;border:none;background:none">
                        <?= $ac['is_published'] ? 'Published' : 'Draft' ?>
                    </button>
                </form>
            </td>
            <td>
                <div class="action-btns">
                    <a href="aircraft-edit.php?id=<?= $ac['id'] ?>" class="btn-icon edit" title="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" onsubmit="return confirm('Delete this aircraft entry?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $ac['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn-icon delete" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
