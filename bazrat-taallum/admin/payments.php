<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$payments = $conn->query("SELECT p.*, u.full_name FROM payments p JOIN orders o ON o.id=p.order_id JOIN users u ON u.id=o.user_id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$totalRevenue = (float)$conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status='success'")->fetch_assoc()['total'];
render_admin_layout_start('إدارة المدفوعات', 'payments.php');
?>
<div class="grid-4">
  <div class="stat-card"><span class="icon"><i class="fa-solid fa-sack-dollar"></i></span><div><h3>$<?= number_format($totalRevenue,2) ?></h3><p>إجمالي الإيرادات</p></div></div>
</div>
<div class="table-container">
  <div class="table-header"><strong>سجل المدفوعات</strong></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>#</th><th>العميل</th><th>البوابة</th><th>المبلغ</th><th>الحالة</th><th>مرجع العملية</th><th>وقت الدفع</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= e($p['full_name']) ?></td>
          <td><?= e($p['gateway']) ?></td>
          <td>$<?= number_format((float)$p['amount'],2) ?></td>
          <td><span class="status <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
          <td><?= e($p['transaction_ref']) ?></td>
          <td><?= $p['paid_at'] ? date('Y-m-d H:i', strtotime($p['paid_at'])) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php render_admin_layout_end(); ?>
