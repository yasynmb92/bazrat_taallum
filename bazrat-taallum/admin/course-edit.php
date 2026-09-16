<?php
require_once __DIR__ . '/_layout.php';

$id = (int)($_GET['id'] ?? 0);
$conn = db();
$hasTrackCol = table_has_column('courses', 'track_id');
$hasAgeGroupCol = table_has_column('courses', 'age_group');
$tracks = $hasTrackCol ? fetch_learning_tracks() : [];

if ($id) {
    $stmt = $conn->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    if (!$course) {
        render_admin_layout_start('دورة غير موجودة', 'courses.php');
        echo '<div class="alert" style="background:#fee;color:#991b1b">الدورة غير موجودة.</div>';
        echo '<p><a href="courses.php" class="btn btn-primary">← العودة للدورات</a></p>';
        render_admin_layout_end();
        exit;
    }
} else {
    $course = [
        'price' => 0,
        'level' => 'beginner',
        'track_id' => 0,
        'age_group' => '13-15',
        'is_free' => 0,
        'course_discount_type' => 'none',
        'course_discount_value' => 0,
        'is_published' => 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'title_ar' => $_POST['title_ar'] ?? '',
        'title_en' => $_POST['title_en'] ?? '',
        'description_ar' => $_POST['description_ar'] ?? '',
        'description_en' => $_POST['description_en'] ?? '',
        'thumbnail' => $_POST['thumbnail'] ?? '',
        'instructor_id' => (int)($_POST['instructor_id'] ?? 0) ?: null,
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
        'level' => $_POST['level'] ?? 'beginner',
        'price' => (float)($_POST['price'] ?? 0),
        'is_free' => isset($_POST['is_free']) ? 1 : 0,
        'course_discount_type' => $_POST['course_discount_type'] ?? 'none',
        'course_discount_value' => (float)($_POST['course_discount_value'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];
    if ($hasTrackCol) {
        $fields['track_id'] = (int)($_POST['track_id'] ?? 0);
    }
    if ($hasAgeGroupCol) {
        $fields['age_group'] = $_POST['age_group'] ?? '13-15';
    }

    $typeMap = [
        'price' => 'd',
        'instructor_id' => 'i',
        'category_id' => 'i',
        'is_free' => 'i',
        'course_discount_value' => 'd',
        'is_published' => 'i',
    ];
    if ($hasTrackCol) {
        $typeMap['track_id'] = 'i';
    }

    $set = [];
    $types = '';
    $values = [];
    foreach ($fields as $key => $val) {
        $placeholder = in_array($key, ['category_id', 'instructor_id', 'track_id'], true) ? 'NULLIF(?, 0)' : '?';
        $set[] = "$key = $placeholder";
        $types .= $typeMap[$key] ?? 's';
        $values[] = $val;
    }

    $values[] = $id;
    $types .= 'i';

    if ($id) {
        $sql = 'UPDATE courses SET ' . implode(', ', $set) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            admin_audit_log('update', 'course', $id, ['title_ar' => (string)$fields['title_ar']]);
            header('Location: courses.php?success=1');
            exit;
        }
    } else {
        array_pop($values);
        $types = substr($types, 0, -1);
        $insertValues = [];
        foreach (array_keys($fields) as $key) {
            $insertValues[] = in_array($key, ['category_id', 'instructor_id', 'track_id'], true) ? 'NULLIF(?, 0)' : '?';
        }
        $sql = 'INSERT INTO courses (' . implode(', ', array_keys($fields)) . ') VALUES (' . implode(', ', $insertValues) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            admin_audit_log('create', 'course', (int)$new_id, ['title_ar' => (string)$fields['title_ar']]);
            header('Location: lessons.php?course=' . $new_id);
            exit;
        }
    }
}

$layoutTitle = $id ? 'تعديل دورة' : 'دورة جديدة';
render_admin_layout_start($layoutTitle, 'courses.php');
?>

<div class="page-header" style="margin-bottom:16px">
    <h1 style="margin:0 0 8px"><?= $id ? 'تعديل' : 'دورة جديدة' ?> <?= e($course['title_ar'] ?? '') ?></h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <a href="courses.php" class="btn btn-secondary">← الدورات</a>
        <?php if ($id): ?><a href="lessons.php?course=<?= (int)$id ?>" class="btn btn-primary">إدارة الحصص والفيديو</a><?php endif; ?>
        <?php if ($id): ?><a href="course-projects.php?course_id=<?= (int)$id ?>" class="btn btn-outline">مشاريع الدورة</a><?php endif; ?>
        <?php if ($id): ?><a href="course-quizzes.php?course_id=<?= (int)$id ?>" class="btn btn-outline">اختبارات الدورة</a><?php endif; ?>
    </div>
</div>

<div class="form-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>العنوان (عربي) *</label>
                <input type="text" name="title_ar" value="<?= e($course['title_ar'] ?? '') ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label>العنوان (إنجليزي)</label>
                <input type="text" name="title_en" value="<?= e($course['title_en'] ?? '') ?>" class="form-input">
            </div>
            <div class="form-group">
                <label>الفئة</label>
                <select name="category_id" class="form-input">
                    <option value="0">بدون فئة</option>
                    <?php
                    $cats = $conn->query('SELECT * FROM categories ORDER BY name_ar');
                    while ($cat = $cats->fetch_assoc()): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= (int)($course['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name_ar']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>المدرب</label>
                <select name="instructor_id" class="form-input">
                    <option value="0">اختر مدرب</option>
                    <?php
                    $insts = $conn->query("SELECT * FROM users WHERE role = 'instructor'");
                    while ($inst = $insts->fetch_assoc()): ?>
                    <option value="<?= (int)$inst['id'] ?>" <?= (int)($course['instructor_id'] ?? 0) === (int)$inst['id'] ? 'selected' : '' ?>>
                        <?= e($inst['full_name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if (!empty($tracks)): ?>
            <div class="form-group">
                <label>المسار التعليمي</label>
                <select name="track_id" class="form-input">
                    <option value="0">بدون مسار</option>
                    <?php foreach ($tracks as $tr):
                        $trLabel = app_lang() === 'ar' ? ($tr['name_ar'] ?? '') : ($tr['name_en'] ?? '');
                        ?>
                    <option value="<?= (int)$tr['id'] ?>" <?= (int)($course['track_id'] ?? 0) === (int)$tr['id'] ? 'selected' : '' ?>>
                        <?= e($trLabel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($hasAgeGroupCol): ?>
            <div class="form-group">
                <label>الفئة العمرية</label>
                <?php $age = (string)($course['age_group'] ?? '13-15'); ?>
                <select name="age_group" class="form-input">
                    <option value="8-12" <?= $age === '8-12' ? 'selected' : '' ?>>8-12 سنة</option>
                    <option value="13-15" <?= $age === '13-15' ? 'selected' : '' ?>>13-15 سنة</option>
                    <option value="16-18" <?= $age === '16-18' ? 'selected' : '' ?>>16-18 سنة</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>المستوى *</label>
                <select name="level" class="form-input">
                    <option value="beginner" <?= ($course['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>مبتدئ (7-12 سنة)</option>
                    <option value="intermediate" <?= ($course['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>متوسط (13-15 سنة)</option>
                    <option value="advanced" <?= ($course['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>متقدم (16-18 سنة)</option>
                </select>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_free" value="1" <?= !empty($course['is_free']) ? 'checked' : '' ?>> دورة مجانية بالكامل</label>
                <p class="muted small">عند التفعيل يُعرض السعر صفر ويتجاهل السعر أدناه.</p>
            </div>
            <div class="form-group">
                <label>السعر الأساسي ($)</label>
                <input type="number" step="0.01" min="0" name="price" value="<?= e((string)($course['price'] ?? 0)) ?>" class="form-input">
            </div>
            <div class="form-group">
                <label>خصم على الدورة</label>
                <select name="course_discount_type" class="form-input">
                    <option value="none" <?= ($course['course_discount_type'] ?? 'none') === 'none' ? 'selected' : '' ?>>بدون خصم على الدورة</option>
                    <option value="percent" <?= ($course['course_discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>نسبة مئوية من السعر</option>
                    <option value="fixed" <?= ($course['course_discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>مبلغ ثابت ($)</option>
                </select>
            </div>
            <div class="form-group">
                <label>قيمة خصم الدورة (نسبة أو مبلغ حسب الخيار)</label>
                <input type="number" step="0.01" min="0" name="course_discount_value" value="<?= e((string)($course['course_discount_value'] ?? 0)) ?>" class="form-input">
            </div>
            <div class="form-group">
                <label>صورة مصغرة (URL)</label>
                <input type="url" name="thumbnail" value="<?= e($course['thumbnail'] ?? '') ?>" class="form-input">
            </div>
            <div class="form-group full">
                <label>الوصف (عربي)</label>
                <textarea name="description_ar" rows="4" class="form-input"><?= e($course['description_ar'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>الوصف (إنجليزي)</label>
                <textarea name="description_en" rows="4" class="form-input"><?= e($course['description_en'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label><input type="checkbox" name="is_published" value="1" <?= !empty($course['is_published']) ? 'checked' : '' ?>> نشر الدورة على الموقع</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-large"><?= $id ? 'حفظ التعديلات' : 'إنشاء الدورة' ?></button>
    </form>
</div>

<?php render_admin_layout_end(); ?>
