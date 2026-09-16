<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$msg = '';

function admin_table_exists_ch(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return (bool)($r && $r->num_rows > 0);
}

if (!admin_table_exists_ch($conn, 'weekly_challenges')) {
    render_admin_layout_start('التحديات الأسبوعية', 'weekly-challenges.php');
    echo '<div class="alert" style="background:#fef2f2;color:#991b1b">جدول التحديات غير موجود. نفّذ migration: <code>php database/migrations/roadmap_phases_123_2026.php</code></div>';
    render_admin_layout_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $st = $conn->prepare('DELETE FROM weekly_challenges WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $msg = 'تم حذف التحدي.';
    } elseif (isset($_POST['save_challenge'])) {
        $id = (int)($_POST['id'] ?? 0);
        $titleAr = trim((string)($_POST['title_ar'] ?? ''));
        $titleEn = trim((string)($_POST['title_en'] ?? ''));
        $descAr = trim((string)($_POST['description_ar'] ?? ''));
        $descEn = trim((string)($_POST['description_en'] ?? ''));
        $type = (string)($_POST['challenge_type'] ?? 'project');
        if (!in_array($type, ['hackathon', 'project', 'quiz'], true)) {
            $type = 'project';
        }
        $startAt = trim((string)($_POST['start_at'] ?? ''));
        $endAt = trim((string)($_POST['end_at'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($titleAr !== '') {
            if ($id > 0) {
                $st = $conn->prepare('UPDATE weekly_challenges SET title_ar=?, title_en=?, description_ar=?, description_en=?, challenge_type=?, start_at=?, end_at=?, is_active=? WHERE id=?');
                $st->bind_param('sssssssii', $titleAr, $titleEn, $descAr, $descEn, $type, $startAt, $endAt, $active, $id);
                $st->execute();
                $msg = 'تم تحديث التحدي.';
            } else {
                $st = $conn->prepare('INSERT INTO weekly_challenges (title_ar, title_en, description_ar, description_en, challenge_type, start_at, end_at, is_active) VALUES (?,?,?,?,?,?,?,?)');
                $st->bind_param('sssssssi', $titleAr, $titleEn, $descAr, $descEn, $type, $startAt, $endAt, $active);
                $st->execute();
                $msg = 'تم إنشاء التحدي.';
            }
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = $conn->prepare('SELECT * FROM weekly_challenges WHERE id = ? LIMIT 1');
    $st->bind_param('i', $editId);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
}

$rows = $conn->query('SELECT * FROM weekly_challenges ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
render_admin_layout_start('التحديات الأسبوعية', 'weekly-challenges.php');
?>
<?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

<div class="form-card">
  <h3><?= $editRow ? 'تعديل تحدي' : 'تحدي جديد' ?></h3>
  <form method="post">
    <input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group full">
        <label>العنوان (عربي) *</label>
        <input type="text" name="title_ar" class="form-input" required value="<?= e((string)($editRow['title_ar'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>العنوان (إنجليزي)</label>
        <input type="text" name="title_en" class="form-input" value="<?= e((string)($editRow['title_en'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>نوع التحدي</label>
        <?php $typ = (string)($editRow['challenge_type'] ?? 'project'); ?>
        <select name="challenge_type" class="form-input">
          <option value="project" <?= $typ === 'project' ? 'selected' : '' ?>>مشروع</option>
          <option value="quiz" <?= $typ === 'quiz' ? 'selected' : '' ?>>اختبار</option>
          <option value="hackathon" <?= $typ === 'hackathon' ? 'selected' : '' ?>>هاكاثون</option>
        </select>
      </div>
      <div class="form-group">
        <label>بداية التحدي</label>
        <input type="datetime-local" name="start_at" class="form-input" value="<?= e(isset($editRow['start_at']) && $editRow['start_at'] ? date('Y-m-d\TH:i', strtotime((string)$editRow['start_at'])) : '') ?>">
      </div>
      <div class="form-group">
        <label>نهاية التحدي</label>
        <input type="datetime-local" name="end_at" class="form-input" value="<?= e(isset($editRow['end_at']) && $editRow['end_at'] ? date('Y-m-d\TH:i', strtotime((string)$editRow['end_at'])) : '') ?>">
      </div>
      <div class="form-group full">
        <label>الوصف (عربي)</label>
        <textarea name="description_ar" rows="3" class="form-input"><?= e((string)($editRow['description_ar'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>الوصف (إنجليزي)</label>
        <textarea name="description_en" rows="3" class="form-input"><?= e((string)($editRow['description_en'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editRow['is_active']) || (int)$editRow['is_active'] === 1 ? 'checked' : '' ?>> نشط</label>
      </div>
    </div>
    <button class="btn btn-primary" type="submit" name="save_challenge" value="1">حفظ</button>
    <?php if ($editRow): ?><a class="btn btn-outline" href="weekly-challenges.php">إلغاء التعديل</a><?php endif; ?>
  </form>
</div>

<div class="table-container">
  <div class="table-header"><strong>التحديات (<?= count($rows) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>العنوان</th><th>النوع</th><th>الفترة</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e((string)$r['title_ar']) ?></td>
          <td><?= e((string)$r['challenge_type']) ?></td>
          <td><?= e((string)($r['start_at'] ?: '-')) ?> → <?= e((string)($r['end_at'] ?: '-')) ?></td>
          <td><span class="status <?= (int)$r['is_active'] ? 'active' : 'cancelled' ?>"><?= (int)$r['is_active'] ? 'نشط' : 'موقوف' ?></span></td>
          <td class="action-buttons">
            <a class="btn btn-sm btn-info" href="weekly-challenges.php?edit=<?= (int)$r['id'] ?>">تعديل</a>
            <form method="post" onsubmit="return confirm('حذف التحدي؟')">
              <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
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

