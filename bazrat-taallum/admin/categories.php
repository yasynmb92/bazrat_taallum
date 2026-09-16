<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$conn = db();
$message = isset($_GET['success']) ? 'تم حفظ التعديلات.' : '';

if ($_POST) {
    if (isset($_POST['add'])) {
        $name_ar = $_POST['name_ar'];
        $name_en = $_POST['name_en'];
        $stmt = $conn->prepare("INSERT INTO categories (name_ar, name_en) VALUES (?, ?)");
        $stmt->bind_param('ss', $name_ar, $name_en);
        if ($stmt->execute()) $message = 'تم إضافة الفئة';
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM categories WHERE id = $id");
        $message = 'تم حذف الفئة';
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name_ar")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> إدارة الفئات</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
    </div>

    <!-- Add Category Form -->
    <div class="form-card">
        <h3>إضافة فئة جديدة</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>الاسم بالعربية</label>
                    <input type="text" name="name_ar" required class="form-input">
                </div>
                <div class="form-group">
                    <label>الاسم بالإنجليزية</label>
                    <input type="text" name="name_en" required class="form-input">
                </div>
            </div>
            <button type="submit" name="add" class="btn btn-primary">إضافة</button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>الاسم العربي</th>
                    <th>الاسم الإنجليزي</th>
                    <th>عدد الدورات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td><?= e($cat['name_ar']) ?></td>
                    <td><?= e($cat['name_en']) ?></td>
                    <td>
                        <?= $conn->query("SELECT COUNT(*) c FROM courses WHERE category_id = {$cat['id']}")->fetch_assoc()['c'] ?>
                    </td>
                    <td>
<form method="POST" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟')">
    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
    <button type="submit" name="edit" formaction="categories-edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-primary">تعديل</button>
    <button type="submit" name="delete" class="btn btn-sm btn-danger">حذف</button>
</form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.form-card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
.form-group { display: flex; flex-direction: column; }
.form-group label { margin-bottom: 0.5rem; color: #4a5568; font-weight: 500; }
.form-input { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s; }
.form-input:focus { outline: none; border-color: #4299e1; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1); }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
