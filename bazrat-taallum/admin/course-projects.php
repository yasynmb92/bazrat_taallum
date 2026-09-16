<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$msg = '';
$courseFilter = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

function admin_table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return (bool)($r && $r->num_rows > 0);
}

if (!admin_table_exists($conn, 'course_projects')) {
    render_admin_layout_start('مشاريع الدورات', 'course-projects.php');
    echo '<div class="alert" style="background:#fef2f2;color:#991b1b">جدول المشاريع غير موجود. نفّذ migration: <code>php database/migrations/roadmap_phases_123_2026.php</code></div>';
    render_admin_layout_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $st = $conn->prepare('DELETE FROM course_projects WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $msg = 'تم حذف المشروع.';
    } elseif (isset($_POST['save_project'])) {
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $titleAr = trim((string)($_POST['title_ar'] ?? ''));
        $titleEn = trim((string)($_POST['title_en'] ?? ''));
        $descAr = trim((string)($_POST['description_ar'] ?? ''));
        $descEn = trim((string)($_POST['description_en'] ?? ''));
        $rubric = trim((string)($_POST['rubric_json'] ?? ''));
        $pass = max(0, min(100, (int)($_POST['pass_score'] ?? 60)));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($titleAr !== '' && $courseId > 0) {
            if ($id > 0) {
                $st = $conn->prepare('UPDATE course_projects SET course_id=?, title_ar=?, title_en=?, description_ar=?, description_en=?, rubric_json=?, pass_score=?, is_active=? WHERE id=?');
                $st->bind_param('isssssiii', $courseId, $titleAr, $titleEn, $descAr, $descEn, $rubric, $pass, $active, $id);
                $st->execute();
                $msg = 'تم تحديث المشروع.';
            } else {
                $st = $conn->prepare('INSERT INTO course_projects (course_id, title_ar, title_en, description_ar, description_en, rubric_json, pass_score, is_active) VALUES (?,?,?,?,?,?,?,?)');
                $st->bind_param('isssssii', $courseId, $titleAr, $titleEn, $descAr, $descEn, $rubric, $pass, $active);
                $st->execute();
                $msg = 'تم إنشاء المشروع.';
            }
        }
        $courseFilter = $courseId > 0 ? $courseId : $courseFilter;
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = $conn->prepare('SELECT * FROM course_projects WHERE id = ? LIMIT 1');
    $st->bind_param('i', $editId);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
}

$courses = $conn->query("SELECT id, title_ar, title_en FROM courses ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$where = $courseFilter > 0 ? 'WHERE cp.course_id = ' . $courseFilter : '';
$projects = $conn->query("SELECT cp.*, c.title_ar AS course_title_ar, c.title_en AS course_title_en
                          FROM course_projects cp
                          INNER JOIN courses c ON c.id = cp.course_id
                          {$where}
                          ORDER BY cp.id DESC")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('مشاريع الدورات', 'course-projects.php');
?>
<?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

<div class="filters-card">
  <form method="get" class="filter-row" style="grid-template-columns:2fr 1fr">
    <select name="course_id" class="form-input">
      <option value="0">كل الدورات</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $courseFilter === (int)$c['id'] ? 'selected' : '' ?>>
          #<?= (int)$c['id'] ?> - <?= e((string)$c['title_ar']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">تصفية</button>
  </form>
</div>

<div class="form-card">
  <h3><?= $editRow ? 'تعديل مشروع' : 'مشروع جديد' ?></h3>
  <form method="post">
    <input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>الدورة *</label>
        <select name="course_id" class="form-input" required>
          <option value="">اختر الدورة</option>
          <?php $selectedCourse = (int)($editRow['course_id'] ?? $courseFilter); ?>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $selectedCourse === (int)$c['id'] ? 'selected' : '' ?>>
              #<?= (int)$c['id'] ?> - <?= e((string)$c['title_ar']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>درجة النجاح (%)</label>
        <input type="number" name="pass_score" min="0" max="100" class="form-input" value="<?= e((string)($editRow['pass_score'] ?? 60)) ?>">
      </div>
      <div class="form-group full">
        <label>العنوان (عربي) *</label>
        <input type="text" name="title_ar" required class="form-input" value="<?= e((string)($editRow['title_ar'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>العنوان (إنجليزي)</label>
        <input type="text" name="title_en" class="form-input" value="<?= e((string)($editRow['title_en'] ?? '')) ?>">
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
        <label>Rubric JSON (اختياري)</label>
        <textarea name="rubric_json" rows="4" class="form-input"><?= e((string)($editRow['rubric_json'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editRow['is_active']) || (int)$editRow['is_active'] === 1 ? 'checked' : '' ?>> نشط</label>
      </div>
    </div>
    <button class="btn btn-primary" type="submit" name="save_project" value="1">حفظ</button>
    <?php if ($editRow): ?><a class="btn btn-outline" href="course-projects.php<?= $courseFilter ? '?course_id=' . $courseFilter : '' ?>">إلغاء التعديل</a><?php endif; ?>
  </form>
</div>

<div class="table-container">
  <div class="table-header"><strong>المشاريع (<?= count($projects) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>الدورة</th><th>العنوان</th><th>النجاح</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($projects as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td>#<?= (int)$p['course_id'] ?> - <?= e((string)$p['course_title_ar']) ?></td>
          <td><?= e((string)$p['title_ar']) ?></td>
          <td><?= (int)$p['pass_score'] ?>%</td>
          <td><span class="status <?= (int)$p['is_active'] ? 'active' : 'cancelled' ?>"><?= (int)$p['is_active'] ? 'نشط' : 'موقوف' ?></span></td>
          <td class="action-buttons">
            <a class="btn btn-sm btn-info" href="course-projects.php?edit=<?= (int)$p['id'] ?><?= $courseFilter ? '&course_id=' . $courseFilter : '' ?>">تعديل</a>
            <form method="post" onsubmit="return confirm('حذف المشروع؟')">
              <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="course_id" value="<?= (int)$courseFilter ?>">
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

