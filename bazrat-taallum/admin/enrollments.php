<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$rows = $conn->query("SELECT e.*, u.full_name AS user_name, c.title_ar AS course_title
  FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id
  ORDER BY e.enrolled_at DESC")->fetch_all(MYSQLI_ASSOC);
render_admin_layout_start('إدارة التسجيلات', 'enrollments.php');
?>
<div class="table-container">
  <div class="table-header"><strong>التسجيلات (<?= count($rows) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>الطالب</th><th>الدورة</th><th>التقدم</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['user_name']) ?></td>
          <td><?= e($r['course_title']) ?></td>
          <td><?= (int)$r['progress'] ?>%</td>
          <td><span class="status <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td><?= date('Y-m-d H:i', strtotime($r['enrolled_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_admin_layout_end(); ?>
