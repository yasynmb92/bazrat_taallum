<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$message = isset($_GET['success']) ? 'تم حفظ تعديلات الدورة.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare('UPDATE courses SET is_published = NOT is_published WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    admin_audit_log('toggle_publish', 'course', $id);
    $message = 'تم تحديث حالة الدورة.';
  } elseif (isset($_POST['delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare('DELETE FROM courses WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    admin_audit_log('delete', 'course', $id);
    $message = 'تم حذف الدورة.';
  }
}

$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$status = $_GET['status'] ?? '';
$where = ['1=1'];
$params = [];
$types = '';
if ($q !== '') { $where[] = '(c.title_ar LIKE ? OR c.title_en LIKE ?)'; $like = "%$q%"; $params[] = $like; $params[] = $like; $types .= 'ss'; }
if ($categoryId > 0) { $where[] = 'c.category_id = ?'; $params[] = $categoryId; $types .= 'i'; }
if ($status !== '' && ($status === '0' || $status === '1')) { $where[] = 'c.is_published = ?'; $params[] = (int)$status; $types .= 'i'; }
$whereSql = implode(' AND ', $where);

$sql = "SELECT c.*, COALESCE(cat.name_ar, 'بدون فئة') AS category_name, COALESCE(u.full_name, '-') AS instructor_name,
 (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrollments_count
 FROM courses c
 LEFT JOIN categories cat ON cat.id = c.category_id
 LEFT JOIN users u ON u.id = c.instructor_id
 WHERE $whereSql
 ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT id, name_ar FROM categories ORDER BY name_ar")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('إدارة الدورات', 'courses.php');
?>
<?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>

<div class="filters-card">
  <form method="get" class="filter-row">
    <input class="form-input" type="search" name="q" value="<?= e($q) ?>" placeholder="بحث بعنوان الدورة...">
    <select class="form-input" name="category">
      <option value="">كل التصنيفات</option>
      <?php foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name_ar']) ?></option><?php endforeach; ?>
    </select>
    <select class="form-input" name="status">
      <option value="">كل الحالات</option>
      <option value="1" <?= $status === '1' ? 'selected' : '' ?>>منشور</option>
      <option value="0" <?= $status === '0' ? 'selected' : '' ?>>مسودة</option>
    </select>
    <button class="btn btn-primary" type="submit">تطبيق</button>
  </form>
</div>

<div class="table-container">
  <div class="table-header"><strong>الدورات (<?= count($courses) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>صورة</th><th>العنوان</th><th>المدرب</th><th>السعر</th><th>التسجيلات</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($courses as $course): ?>
      <tr>
        <td><img class="course-thumb" src="<?= e($course['thumbnail'] ?: 'https://via.placeholder.com/64x48') ?>" alt=""></td>
        <td><strong><?= e($course['title_ar']) ?></strong><br><small class="muted"><?= e($course['category_name']) ?></small></td>
        <td><?= e($course['instructor_name']) ?></td>
        <td>$<?= number_format((float)$course['price'],2) ?></td>
        <td><?= (int)$course['enrollments_count'] ?></td>
        <td><span class="status <?= (int)$course['is_published'] ? 'published' : 'draft' ?>"><?= (int)$course['is_published'] ? 'منشور' : 'مسودة' ?></span></td>
        <td>
          <div class="action-buttons">
            <a class="btn btn-sm btn-primary" href="course-edit.php?id=<?= (int)$course['id'] ?>">تعديل</a>
            <form method="post"><input type="hidden" name="id" value="<?= (int)$course['id'] ?>"><button class="btn btn-sm <?= (int)$course['is_published'] ? 'btn-warning' : 'btn-success' ?>" type="submit" name="toggle_status"><?= (int)$course['is_published'] ? 'إخفاء' : 'نشر' ?></button></form>
            <form method="post" onsubmit="return confirm('تأكيد الحذف؟')"><input type="hidden" name="id" value="<?= (int)$course['id'] ?>"><button class="btn btn-sm btn-danger" type="submit" name="delete">حذف</button></form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php render_admin_layout_end(); ?>
