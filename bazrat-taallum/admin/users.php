<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$message = '';
if (isset($_GET['deleted'])) {
  $message = 'تم حذف المستخدم.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  if (isset($_POST['activate'])) {
    $conn->query("UPDATE users SET is_active = 1 WHERE id = $id AND role != 'admin'");
    $message = 'تم تفعيل الحساب.';
  }
  if (isset($_POST['disable'])) {
    $conn->query("UPDATE users SET is_active = 0 WHERE id = $id AND role != 'admin'");
    $message = 'تم تعطيل الحساب.';
  }
}
$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('إدارة المستخدمين', 'users.php');
?>
<?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
<div class="table-container">
  <div class="table-header"><strong>المستخدمون (<?= count($users) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>تاريخ الإنشاء</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><?= (int)$user['id'] ?></td>
        <td><?= e($user['full_name']) ?></td>
        <td><?= e($user['email']) ?></td>
        <td><span class="badge badge-<?= e($user['role']) ?>"><?= $user['role'] === 'instructor' ? 'مدرب' : 'طالب' ?></span></td>
        <td><span class="badge <?= (int)$user['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= (int)$user['is_active'] ? 'نشط' : 'معطل' ?></span></td>
        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
        <td class="action-buttons">
          <a class="btn btn-sm btn-secondary" href="user-edit.php?id=<?= (int)$user['id'] ?>">تعديل</a>
          <a class="btn btn-sm btn-info" href="user-profile.php?id=<?= (int)$user['id'] ?>">الملف</a>
          <?php if ((int)$user['is_active']): ?>
            <form method="post" onsubmit="return confirm('تعطيل الحساب؟')"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><button type="submit" name="disable" class="btn btn-sm btn-danger">تعطيل</button></form>
          <?php else: ?>
            <form method="post"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><button type="submit" name="activate" class="btn btn-sm btn-success">تفعيل</button></form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_admin_layout_end(); ?>
