<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$conn = db();
$message = isset($_GET['success']) ? 'تم حفظ تعديلات الكوبون.' : '';

if ($_POST) {
    $code = $_POST['code'];
    $type = $_POST['discount_type'];
    $value = (float)$_POST['discount_value'];
    $expires = $_POST['expires_at'] ?: null;
    
    $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, expires_at, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('ssds', $code, $type, $value, $expires);
    if ($stmt->execute()) $message = 'تم إضافة الكوبون';
}

$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-percent"></i> إدارة الكوبونات</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
    </div>

    <div class="form-card">
        <h3>كوبون جديد</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>كود الكوبون</label>
                    <input type="text" name="code" required class="form-input">
                </div>
                <div class="form-group">
                    <label>نوع الخصم</label>
                    <select name="discount_type" class="form-input">
                        <option value="percent">نسبة مئوية %</option>
                        <option value="fixed">قيمة ثابتة $</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>قيمة الخصم</label>
                    <input type="number" step="0.01" name="discount_value" required class="form-input">
                </div>
                <div class="form-group">
                    <label>انتهاء الصلاحية (اختياري)</label>
                    <input type="datetime-local" name="expires_at" class="form-input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">إضافة كوبون</button>
        </form>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>النوع</th>
                    <th>القيمة</th>
                    <th>الحالة</th>
                    <th>انتهاء</th>
                    <th>تاريخ الإنشاء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><code><?= e($coupon['code']) ?></code></td>
                    <td><?= $coupon['discount_type'] === 'percent' ? $coupon['discount_value'] . '%' : '$' . $coupon['discount_value'] ?></td>
                    <td><?= $coupon['discount_type'] ?></td>
<td>
    <span class="badge badge-<?= $coupon['is_active'] ? 'active' : 'inactive' ?>">
        <?= $coupon['is_active'] ? 'نشط' : 'معطل' ?>
    </span>
    <a href="coupons-edit.php?id=<?= $coupon['id'] ?>" class="btn btn-sm btn-primary ml-2">تعديل</a>
</td>
                    <td><?= $coupon['expires_at'] ?: 'غير محدود' ?></td>
                    <td><?= date('Y-m-d', strtotime($coupon['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
