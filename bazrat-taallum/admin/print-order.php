<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$order_id = (int)($_GET['id'] ?? 0);
$conn = db();

if (!$order_id) {
    die('معرف الطلب مطلوب');
}

$order = $conn->query("SELECT o.*, u.full_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = $order_id")->fetch_assoc();
if (!$order) die('الطلب غير موجود');

$items = $conn->query("SELECT oi.*, c.title_ar FROM order_items oi LEFT JOIN courses c ON oi.course_id = c.id WHERE oi.order_id = $order_id")->fetch_all(MYSQLI_ASSOC);
$payment = $conn->query("SELECT * FROM payments WHERE order_id = $order_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة الطلب #<?= $order['id'] ?> - <?= APP_NAME_AR ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: white; color: #2d3748; line-height: 1.6; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; padding: 2rem; border-bottom: 4px solid #667eea; margin-bottom: 2rem; }
        .header h1 { color: #2d3748; font-size: 2.2rem; margin-bottom: 0.5rem; }
        .header p { color: #718096; font-size: 1.2rem; }
        .order-info { background: #f8fafc; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .info-item { padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .info-label { font-weight: 600; color: #4a5568; margin-bottom: 0.5rem; display: block; }
        .info-value { font-size: 1.1rem; color: #2d3748; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .status-paid { background: #c6f6d5; color: #22543d; }
        .status-pending { background: #fed7d7; color: #742a2a; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .items-table th, .items-table td { padding: 1rem; text-align: right; border-bottom: 1px solid #e2e8f0; }
        .items-table th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        .total-section { text-align: center; padding: 2rem; background: linear-gradient(135deg, #f0fff4, #c6f6d5); border-radius: 12px; margin-top: 2rem; }
        .grand-total { font-size: 2rem; font-weight: 800; color: #38a169; margin-top: 0.5rem; }
        .print-btn { background: #4299e1; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1.1rem; cursor: pointer; margin: 1rem; }
        @media print { .no-print { display: none !important; } body { padding: 0.5rem; } }
        @page { margin: 0.75in; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>فاتورة الطلب #<?= $order['id'] ?></h1>
        <p><?= APP_NAME_AR ?> - بذرة تعلم</p>
        <p style="font-size: 0.9rem; color: #a0aec0;"><?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="order-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">العميل</span>
                <span class="info-value"><?= htmlspecialchars($order['full_name'] ?? 'غير معروف') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">البريد الإلكتروني</span>
                <span class="info-value"><?= htmlspecialchars($order['email'] ?? '') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">رقم الطلب</span>
                <span class="info-value">#<?= $order['id'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ الطلب</span>
                <span class="info-value"><?= date('Y-m-d H:i', strtotime($order['created_at'] ?? 'now')) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">حالة الطلب</span>
                <span class="info-value"><span class="status-badge status-<?= $order['status'] ?? 'pending' ?>">
                    <?= ['pending' => 'معلق', 'paid' => 'مدفوع', 'failed' => 'فشل'][$order['status'] ?? 'pending'] ?>
                </span></span>
            </div>
            <?php if ($payment): ?>
            <div class="info-item">
                <span class="info-label">طريقة الدفع</span>
                <span class="info-value"><?= htmlspecialchars($payment['gateway']) ?> #<?= htmlspecialchars($payment['transaction_ref']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>الدورة</th>
                <th>السعر</th>
                <th>الكمية</th>
                <th>المجموع</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $order_subtotal = 0;
            foreach ($items as $item): 
                $item_total = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                $order_subtotal += $item_total;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['title_ar'] ?? 'غير معروف') ?></td>
                <td>$<?= number_format($item['price'] ?? 0, 2) ?></td>
                <td><?= $item['quantity'] ?? 1 ?></td>
                <td>$<?= number_format($item_total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">المجموع النهائي</div>
        <div class="grand-total">$<?= number_format($order['total'] ?? $order_subtotal, 2) ?></div>
        <div style="font-size: 0.9rem; color: #718096; margin-top: 1rem;">شكراً لك على ثقتك بنا</div>
    </div>

    <div style="text-align: center; margin-top: 3rem; font-size: 0.8rem; color: #a0aec0;">
        <?= APP_NAME_AR ?> | جميع الحقوق محفوظة © <?= date('Y') ?>
    </div>
</body>
</html>
