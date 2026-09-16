<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$msg = '';
$courseFilter = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

function admin_table_exists_quiz(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return (bool)($r && $r->num_rows > 0);
}

if (!admin_table_exists_quiz($conn, 'course_quizzes')) {
    render_admin_layout_start('اختبارات الدورات', 'course-quizzes.php');
    echo '<div class="alert" style="background:#fef2f2;color:#991b1b">جدول الاختبارات غير موجود. نفّذ migration: <code>php database/migrations/roadmap_phases_123_2026.php</code></div>';
    render_admin_layout_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $st = $conn->prepare('DELETE FROM course_quizzes WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        $msg = 'تم حذف الاختبار.';
    } elseif (isset($_POST['save_quiz'])) {
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $titleAr = trim((string)($_POST['title_ar'] ?? ''));
        $titleEn = trim((string)($_POST['title_en'] ?? ''));
        $questions = trim((string)($_POST['questions_json'] ?? ''));
        $pass = max(0, min(100, (int)($_POST['pass_score'] ?? 70)));
        $attempts = max(1, min(20, (int)($_POST['max_attempts'] ?? 3)));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($titleAr !== '' && $courseId > 0 && $questions !== '') {
            if ($id > 0) {
                $st = $conn->prepare('UPDATE course_quizzes SET course_id=?, title_ar=?, title_en=?, questions_json=?, pass_score=?, max_attempts=?, is_active=? WHERE id=?');
                $st->bind_param('isssiiii', $courseId, $titleAr, $titleEn, $questions, $pass, $attempts, $active, $id);
                $st->execute();
                $msg = 'تم تحديث الاختبار.';
            } else {
                $st = $conn->prepare('INSERT INTO course_quizzes (course_id, title_ar, title_en, questions_json, pass_score, max_attempts, is_active) VALUES (?,?,?,?,?,?,?)');
                $st->bind_param('isssiii', $courseId, $titleAr, $titleEn, $questions, $pass, $attempts, $active);
                $st->execute();
                $msg = 'تم إنشاء الاختبار.';
            }
        }
        $courseFilter = $courseId > 0 ? $courseId : $courseFilter;
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = $conn->prepare('SELECT * FROM course_quizzes WHERE id = ? LIMIT 1');
    $st->bind_param('i', $editId);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
}

$courses = $conn->query("SELECT id, title_ar FROM courses ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$where = $courseFilter > 0 ? 'WHERE cq.course_id = ' . $courseFilter : '';
$rows = $conn->query("SELECT cq.*, c.title_ar AS course_title_ar
                      FROM course_quizzes cq
                      INNER JOIN courses c ON c.id = cq.course_id
                      {$where}
                      ORDER BY cq.id DESC")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('اختبارات الدورات', 'course-quizzes.php');
?>
<?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

<div class="filters-card">
  <form method="get" class="filter-row" style="grid-template-columns:2fr 1fr">
    <select name="course_id" class="form-input">
      <option value="0">كل الدورات</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $courseFilter === (int)$c['id'] ? 'selected' : '' ?>>#<?= (int)$c['id'] ?> - <?= e((string)$c['title_ar']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">تصفية</button>
  </form>
</div>

<div class="form-card">
  <h3><?= $editRow ? 'تعديل اختبار' : 'اختبار جديد' ?></h3>
  <form method="post">
    <input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>الدورة *</label>
        <?php $sel = (int)($editRow['course_id'] ?? $courseFilter); ?>
        <select name="course_id" class="form-input" required>
          <option value="">اختر الدورة</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $sel === (int)$c['id'] ? 'selected' : '' ?>>#<?= (int)$c['id'] ?> - <?= e((string)$c['title_ar']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>درجة النجاح (%)</label>
        <input type="number" name="pass_score" min="0" max="100" class="form-input" value="<?= e((string)($editRow['pass_score'] ?? 70)) ?>">
      </div>
      <div class="form-group">
        <label>عدد المحاولات</label>
        <input type="number" name="max_attempts" min="1" max="20" class="form-input" value="<?= e((string)($editRow['max_attempts'] ?? 3)) ?>">
      </div>
      <div class="form-group full">
        <label>العنوان (عربي) *</label>
        <input type="text" name="title_ar" class="form-input" required value="<?= e((string)($editRow['title_ar'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>العنوان (إنجليزي)</label>
        <input type="text" name="title_en" class="form-input" value="<?= e((string)($editRow['title_en'] ?? '')) ?>">
      </div>
      <div class="form-group full">
        <label>الأسئلة JSON *</label>
        <textarea name="questions_json" rows="7" class="form-input" required><?= e((string)($editRow['questions_json'] ?? '[{"q":"سؤال","choices":["أ","ب"],"answer":0}]')) ?></textarea>
      </div>
      <div class="form-group full">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editRow['is_active']) || (int)$editRow['is_active'] === 1 ? 'checked' : '' ?>> نشط</label>
      </div>
    </div>
    <button class="btn btn-primary" type="submit" name="save_quiz" value="1">حفظ</button>
    <?php if ($editRow): ?><a class="btn btn-outline" href="course-quizzes.php<?= $courseFilter ? '?course_id=' . $courseFilter : '' ?>">إلغاء التعديل</a><?php endif; ?>
  </form>
</div>

<div class="table-container">
  <div class="table-header"><strong>الاختبارات (<?= count($rows) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>الدورة</th><th>العنوان</th><th>نجاح</th><th>محاولات</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td>#<?= (int)$r['course_id'] ?> - <?= e((string)$r['course_title_ar']) ?></td>
          <td><?= e((string)$r['title_ar']) ?></td>
          <td><?= (int)$r['pass_score'] ?>%</td>
          <td><?= (int)$r['max_attempts'] ?></td>
          <td><span class="status <?= (int)$r['is_active'] ? 'active' : 'cancelled' ?>"><?= (int)$r['is_active'] ? 'نشط' : 'موقوف' ?></span></td>
          <td class="action-buttons">
            <a class="btn btn-sm btn-info" href="course-quizzes.php?edit=<?= (int)$r['id'] ?><?= $courseFilter ? '&course_id=' . $courseFilter : '' ?>">تعديل</a>
            <form method="post" onsubmit="return confirm('حذف الاختبار؟')">
              <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
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

