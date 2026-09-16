<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$message = '';
$allowed = ['pending','paid','failed','cancelled','refunded'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'pending';
  if (!in_array($status, $allowed, true)) { $status = 'pending'; }
  $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
  $stmt->bind_param('si', $status, $id);
  $stmt->execute();
  if ($id > 0) {
    sync_enrollments_for_order($id);
    admin_audit_log('status_change', 'order', $id, ['status' => $status]);
  }
  $message = 'تم تحديث حالة الطلب.';
}
$orders = $conn->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('إدارة الطلبات', 'orders.php');
?>
<?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
<div class="table-container">
  <div class="table-header"><strong>الطلبات (<?= count($orders) ?>)</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>العميل</th><th>الإجمالي</th><th>الخصم</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
      <tr>
        <td>#<?= (int)$order['id'] ?></td>
        <td><?= e($order['full_name']) ?></td>
        <td>$<?= number_format((float)$order['total'],2) ?></td>
        <td>$<?= number_format((float)$order['discount'],2) ?></td>
        <td>
          <form method="post">
            <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
            <select class="form-input" style="min-width:130px" name="status" onchange="this.form.submit()">
              <?php foreach ($allowed as $st): ?><option value="<?= e($st) ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?>
            </select>
          </form>
        </td>
        <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
        <td><a class="btn btn-sm btn-info" href="order-details.php?id=<?= (int)$order['id'] ?>">تفاصيل</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_admin_layout_end(); ?>
