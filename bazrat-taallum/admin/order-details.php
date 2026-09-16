<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$order_id = (int)($_GET['id'] ?? 0);
$conn = db();

$order = $conn->query("SELECT o.*, u.full_name, u.email 
                      FROM orders o JOIN users u ON o.user_id = u.id 
                      WHERE o.id = $order_id")->fetch_assoc();

if (!$order) die('الطلب غير موجود');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid']) && $order['status'] === 'pending') {
    simulate_payment($order_id, true);
    admin_audit_log('mark_paid', 'order', $order_id);
    header('Location: order-details.php?id=' . $order_id);
    exit;
}

$items = $conn->query("SELECT oi.*, c.title_ar 
                      FROM order_items oi JOIN courses c ON oi.course_id = c.id 
                      WHERE oi.order_id = $order_id")->fetch_all(MYSQLI_ASSOC);

$payment = $conn->query("SELECT * FROM payments WHERE order_id = $order_id")->fetch_assoc();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-file-invoice"></i> تفاصيل الطلب #<?= $order['id'] ?></h1>
        <a href="orders.php" class="btn btn-secondary">← جميع الطلبات</a>
    </div>

    <div class="order-details-grid">
        <div class="order-info">
            <h3>معلومات الطلب</h3>
            <div class="info-row">
                <span><strong>العميل:</strong> <?= e($order['full_name']) ?></span>
                <span><strong>البريد:</strong> <?= e($order['email']) ?></span>
            </div>
            <div class="info-row">
                <span><strong>رقم الطلب:</strong> #<?= $order['id'] ?></span>
                <span><strong>التاريخ:</strong> <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span><strong>الحالة:</strong> 
                    <span class="badge badge-<?= $order['status'] ?>">
                        <?= ['pending' => 'معلق', 'paid' => 'مدفوع', 'failed' => 'فشل', 'cancelled' => 'ملغى', 'refunded' => 'مسترد'][$order['status']] ?? e($order['status']) ?>
                    </span>
                </span>
                <span><strong>الإجمالي:</strong> $<span class="price-large"><?= number_format($order['total'], 2) ?></span></span>
            </div>
            <?php if ($payment): ?>
            <div class="info-row">
                <span><strong>الدفع:</strong> <?= $payment['gateway'] ?> (<?= $payment['transaction_ref'] ?>)</span>
                <span><strong>مدفوع في:</strong> <?= date('Y-m-d H:i', strtotime($payment['paid_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="order-items">
            <h3>عناصر الطلب (<?= count($items) ?>)</h3>
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <div class="item-info">
                    <h4><?= e($item['title_ar']) ?></h4>
                    <div>السعر: $<span class="price"><?= number_format($item['price'], 2) ?></span></div>
الكمية: <?= ($item['quantity'] ?? 1) ?>
                </div>
                <div class="item-total">
$<span class="price-large"><?= number_format(($item['total'] ?? ($item['price'] ?? 0)), 2) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="order-actions">
        <?php if ($order['status'] === 'pending'): ?>
        <form method="POST" style="display:inline;">
            <button type="submit" name="mark_paid" class="btn btn-success" onclick="return confirm('تأكيد الدفع؟')">علامة مدفوع</button>
        </form>
        <?php endif; ?>
        <a href="../user-profile.php?id=<?= $order['user_id'] ?>" class="btn btn-info" target="_blank">ملف العميل</a>
        <button class="btn btn-secondary" onclick="printOrder(<?= $order['id'] ?>)">طباعة</button>
    </div>
</div>

<script>
function printOrder(id) {
    window.open('print-order.php?id=' + id, '_blank');
}
</script>

<style>
.order-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
.order-info, .order-items { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.info-row { display: flex; gap: 2rem; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; }
.order-items h3 { margin-bottom: 1.5rem; }
.item-row { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 0; border-bottom: 1px solid #f1f5f9; }
.item-row:last-child { border-bottom: none; }
.item-info h4 { color: #2d3748; margin-bottom: 0.5rem; }
.price-large { font-size: 1.5rem; font-weight: 700; color: #48bb78; }
.order-actions { background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; gap: 1rem; flex-wrap: wrap; }
@media (max-width: 768px) { .order-details-grid { grid-template-columns: 1fr; } .info-row { flex-direction: column; gap: 0.5rem; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
