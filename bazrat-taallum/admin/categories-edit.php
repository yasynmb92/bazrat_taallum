<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$conn = db();

$category = $conn->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();
if (!$category) die('الفئة غير موجودة');

if ($_POST) {
    $name_ar = trim($_POST['name_ar']);
    $name_en = trim($_POST['name_en']);
    $stmt = $conn->prepare("UPDATE categories SET name_ar = ?, name_en = ? WHERE id = ?");
    $stmt->bind_param('ssi', $name_ar, $name_en, $id);
    if ($stmt->execute()) {
        header('Location: categories.php?success=1');
        exit;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> تعديل الفئة: <?= e($category['name_ar']) ?></h1>
        <a href="categories.php" class="btn btn-secondary">← العودة</a>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>الاسم بالعربية</label>
                    <input type="text" name="name_ar" value="<?= e($category['name_ar']) ?>" required class="form-input">
                </div>
                <div class="form-group">
                    <label>الاسم بالإنجليزية</label>
                    <input type="text" name="name_en" value="<?= e($category['name_en']) ?>" required class="form-input">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                <a href="categories.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
