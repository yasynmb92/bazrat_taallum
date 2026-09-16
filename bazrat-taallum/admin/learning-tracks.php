<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$msg = '';

function track_table_exists(mysqli $conn): bool {
    $r = $conn->query("SHOW TABLES LIKE 'learning_tracks'");
    return (bool)($r && $r->num_rows > 0);
}

if (!track_table_exists($conn)) {
    render_admin_layout_start('المسارات التعليمية', 'learning-tracks.php');
    echo '<div class="alert" style="background:#fef2f2;color:#991b1b">جدول المسارات غير موجود. نفّذ migration: <code>php database/migrations/roadmap_phases_123_2026.php</code></div>';
    render_admin_layout_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $st = $conn->prepare('DELETE FROM learning_tracks WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $msg = 'تم حذف المسار.';
    } elseif (isset($_POST['save_track'])) {
        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));
        $nameAr = trim((string)($_POST['name_ar'] ?? ''));
        $nameEn = trim((string)($_POST['name_en'] ?? ''));
        $descAr = trim((string)($_POST['description_ar'] ?? ''));
        $descEn = trim((string)($_POST['description_en'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($slug !== '' && $nameAr !== '' && $nameEn !== '') {
            if ($id > 0) {
                $st = $conn->prepare('UPDATE learning_tracks SET slug=?, name_ar=?, name_en=?, description_ar=?, description_en=?, sort_order=?, is_active=? WHERE id=?');
                $st->bind_param('sssssiii', $slug, $nameAr, $nameEn, $descAr, $descEn, $sortOrder, $active, $id);
                $st->execute();
                $msg = 'تم تحديث المسار.';
            } else {
                $st = $conn->prepare('INSERT INTO learning_tracks (slug, name_ar, name_en, description_ar, description_en, sort_order, is_active) VALUES (?,?,?,?,?,?,?)');
                $st->bind_param('sssssii', $slug, $nameAr, $nameEn, $descAr, $descEn, $sortOrder, $active);
                $st->execute();
                $msg = 'تم إضافة المسار.';
            }
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $conn->prepare('SELECT * FROM learning_tracks WHERE id = ? LIMIT 1');
    $st->bind_param('i', $editId);
    $st->execute();
    $edit = $st->get_result()->fetch_assoc();
}
$tracks = $conn->query('SELECT * FROM learning_tracks ORDER BY sort_order, id')->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('المسارات التعليمية', 'learning-tracks.php');
?>
<?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

<div class="form-card">
  <h3><?= $edit ? 'تعديل مسار' : 'مسار جديد' ?></h3>
  <form method="post">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Slug *</label>
        <input type="text" name="slug" class="form-input" required value="<?= e((string)($edit['slug'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>الترتيب</label>
        <input type="number" name="sort_order" class="form-input" value="<?= e((string)($edit['sort_order'] ?? 0)) ?>">
      </div>
      <div class="form-group full">
        <label>الاسم (عربي) *</label>
        <input type="text" name="name_ar" class="form-input" required value="<?= e((string)($edit['name_ar'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>الاسم (إنجليزي) *</label>
        <input type="text" name="name_en" class="form-input" required value="<?= e((string)($edit['name_en'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>الوصف (عربي)</label>
        <textarea name="description_ar" class="form-input" rows="3"><?= e((string)($edit['description_ar'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>الوصف (إنجليزي)</label>
        <textarea name="description_en" class="form-input" rows="3"><?= e((string)($edit['description_en'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($edit['is_active']) || (int)$edit['is_active'] === 1 ? 'checked' : '' ?>> نشط</label>
      </div>
    </div>
    <button class="btn btn-primary" name="save_track" value="1" type="submit">حفظ</button>
    <?php if ($edit): ?><a class="btn btn-outline" href="learning-tracks.php">إلغاء التعديل</a><?php endif; ?>
  </form>
</div>

<div class="table-container">
  <div class="table-header"><strong>المسارات (<?= count($tracks) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Slug</th><th>الاسم</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($tracks as $t): ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td><code><?= e((string)$t['slug']) ?></code></td>
          <td><?= e((string)$t['name_ar']) ?><br><small class="muted"><?= e((string)$t['name_en']) ?></small></td>
          <td><?= (int)$t['sort_order'] ?></td>
          <td><span class="status <?= (int)$t['is_active'] ? 'active' : 'cancelled' ?>"><?= (int)$t['is_active'] ? 'نشط' : 'موقوف' ?></span></td>
          <td class="action-buttons">
            <a class="btn btn-sm btn-info" href="learning-tracks.php?edit=<?= (int)$t['id'] ?>">تعديل</a>
            <form method="post" onsubmit="return confirm('حذف المسار؟')">
              <input type="hidden" name="delete_id" value="<?= (int)$t['id'] ?>">
              <button class="btn btn-sm btn-danger" type="submit">حذف</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php render_admin_layout_end(); ?>

