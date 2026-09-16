<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$message = '';
$id = (int)($_GET['id'] ?? 0);
$maxVideoMb = (int)max(1, floor(effective_upload_max_bytes(512 * 1024 * 1024) / (1024 * 1024)));

$lesson = $id ? $conn->query('SELECT * FROM lessons WHERE id = ' . $id)->fetch_assoc() : null;
if (!$lesson) {
    render_admin_layout_start('تعديل حصة', 'courses.php');
    echo '<div class="alert">الحصة غير موجودة.</div><p><a href="courses.php" class="btn btn-primary">الدورات</a></p>';
    render_admin_layout_end();
    exit;
}

$course_id = (int)$lesson['course_id'];
$course = $conn->query('SELECT * FROM courses WHERE id = ' . $course_id)->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_ar = trim((string)($_POST['title_ar'] ?? ''));
    $title_en = trim((string)($_POST['title_en'] ?? ''));
    $video_url = trim((string)($_POST['video_url'] ?? ''));
    if (isset($_FILES['video_file']) && (int)($_FILES['video_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $up = admin_store_uploaded_video($_FILES['video_file']);
        if (!empty($up['ok'])) {
            $video_url = (string)$up['url'];
        } else {
            $map = [
                'size_limit' => 'حجم الفيديو أكبر من الحد المسموح.',
                'bad_type' => 'نوع الفيديو غير مدعوم.',
                'dir_fail' => 'تعذر إنشاء مجلد الرفع على السيرفر.',
                'move_fail' => 'تعذر نقل ملف الفيديو بعد الرفع.',
                'upload_error' => 'حدث خطأ أثناء رفع الفيديو.',
            ];
            $message = $map[$up['error'] ?? ''] ?? 'تعذر رفع الفيديو.';
        }
    }
    $content_ar = trim((string)($_POST['content_ar'] ?? ''));
    $content_en = trim((string)($_POST['content_en'] ?? ''));
    $duration = trim((string)($_POST['duration'] ?? ''));
    $timed_json = trim((string)($_POST['timed_transcript_json'] ?? ''));
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if ($title_ar === '') {
        $message = 'العنوان بالعربية مطلوب.';
    } elseif ($message === '' && $video_url === '' && $content_ar === '' && $content_en === '') {
        $message = 'أدخل فيديو أو مادة قراءة.';
    } elseif ($message === '' && $timed_json !== '' && json_decode($timed_json, true) === null) {
        $message = 'JSON النص المتزامن غير صالح.';
    } elseif ($message === '') {
        $stmt = $conn->prepare('UPDATE lessons SET title_ar=?, title_en=?, content_ar=?, content_en=?, video_url=?, duration=?, timed_transcript_json=?, sort_order=? WHERE id=?');
        $stmt->bind_param('sssssssii', $title_ar, $title_en, $content_ar, $content_en, $video_url, $duration, $timed_json, $sort_order, $id);
        if ($stmt->execute()) {
            $message = 'تم الحفظ.';
            admin_audit_log('update', 'lesson', $id, ['course_id' => $course_id, 'title_ar' => $title_ar]);
            $lesson = $conn->query('SELECT * FROM lessons WHERE id = ' . $id)->fetch_assoc();
        } else {
            $message = 'تعذر الحفظ.';
        }
    }
}

render_admin_layout_start('تعديل حصة', 'courses.php');
?>
<?php if ($message !== ''): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>

<p class="muted"><a href="lessons.php?course=<?= $course_id ?>">← <?= e($course['title_ar'] ?? '') ?></a></p>

<form method="post" class="form-card" enctype="multipart/form-data">
  <div class="form-group">
    <label>العنوان (عربي) *</label>
    <input class="form-input" type="text" name="title_ar" required value="<?= e($lesson['title_ar'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>العنوان (إنجليزي)</label>
    <input class="form-input" type="text" name="title_en" value="<?= e($lesson['title_en'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>رابط الفيديو</label>
    <input class="form-input" type="url" name="video_url" value="<?= e($lesson['video_url'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>أو رفع فيديو جديد</label>
    <input class="form-input" type="file" name="video_file" accept="video/*">
    <p class="muted small">في حال رفع فيديو جديد سيتم استبدال رابط الفيديو الحالي بهذا الملف. الحد التقريبي: <?= (int)$maxVideoMb ?>MB.</p>
  </div>
  <div class="form-group">
    <label>مادة القراءة (عربي)</label>
    <textarea class="form-input" name="content_ar" rows="8"><?= e($lesson['content_ar'] ?? '') ?></textarea>
  </div>
  <div class="form-group">
    <label>مادة القراءة (إنجليزي)</label>
    <textarea class="form-input" name="content_en" rows="8"><?= e($lesson['content_en'] ?? '') ?></textarea>
  </div>
  <div class="form-group">
    <label>المدة / وقت القراءة التقريبي</label>
    <input class="form-input" type="text" name="duration" value="<?= e($lesson['duration'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>نص متزامن مع الفيديو (JSON)</label>
    <textarea class="form-input" name="timed_transcript_json" rows="6"><?= e($lesson['timed_transcript_json'] ?? '') ?></textarea>
    <p class="muted small">مثال: [{"start":0.5,"end":1.2,"text":"مرحبا"}]</p>
  </div>
  <div class="form-group">
    <label>ترتيب العرض</label>
    <input class="form-input" type="number" name="sort_order" value="<?= (int)($lesson['sort_order'] ?? 0) ?>">
  </div>
  <button type="submit" class="btn btn-primary">حفظ</button>
</form>
<?php render_admin_layout_end(); ?>
