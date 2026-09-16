<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$conn = db();

$coupon = $conn->query("SELECT * FROM coupons WHERE id = $id")->fetch_assoc();
if (!$coupon) die('الكوبون غير موجود');

if ($_POST) {
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $is_active = (int)$_POST['is_active'];
    $expires_at = $_POST['expires_at'] ?: null;
    
    $stmt = $conn->prepare("UPDATE coupons SET discount_type = ?, discount_value = ?, is_active = ?, expires_at = ? WHERE id = ?");
    $stmt->bind_param('sdisi', $discount_type, $discount_value, $is_active, $expires_at, $id);
    if ($stmt->execute()) {
        header('Location: coupons.php?success=1');
        exit;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> تعديل الكوبون: <?= e($coupon['code']) ?></h1>
        <a href="coupons.php" class="btn btn-secondary">← العودة</a>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>نوع الخصم</label>
                    <select name="discount_type" class="form-input">
                        <option value="percent" <?= $coupon['discount_type'] == 'percent' ? 'selected' : '' ?>>نسبة مئوية %</option>
                        <option value="fixed" <?= $coupon['discount_type'] == 'fixed' ? 'selected' : '' ?>>قيمة ثابتة $</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>قيمة الخصم</label>
                    <input type="number" step="0.01" name="discount_value" value="<?= $coupon['discount_value'] ?>" required class="form-input">
                </div>
                <div class="form-group">
                    <label>الحالة</label>
                    <select name="is_active" class="form-input">
                        <option value="1" <?= $coupon['is_active'] ? 'selected' : '' ?>>نشط</option>
                        <option value="0" <?= !$coupon['is_active'] ? 'selected' : '' ?>>معطل</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>انتهاء الصلاحية</label>
                    <input type="datetime-local" name="expires_at" value="<?= $coupon['expires_at'] ?>" class="form-input">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                <a href="coupons.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
