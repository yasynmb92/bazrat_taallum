<?php
require_once __DIR__ . '/_layout.php';
$conn = db();
$message = '';
$course_id = (int)($_GET['course'] ?? 0);
$maxVideoMb = (int)max(1, floor(effective_upload_max_bytes(512 * 1024 * 1024) / (1024 * 1024)));

if (!$course_id) {
    render_admin_layout_start('حصص الدورة', 'courses.php');
    echo '<div class="alert alert-danger">يرجى اختيار دورة من قائمة الدورات.</div>';
    echo '<p><a class="btn btn-primary" href="courses.php">الدورات</a></p>';
    render_admin_layout_end();
    exit;
}

$stCourse = $conn->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
$stCourse->bind_param('i', $course_id);
$stCourse->execute();
$course = $stCourse->get_result()->fetch_assoc();
if (!$course) {
    render_admin_layout_start('حصص الدورة', 'courses.php');
    echo '<div class="alert alert-danger">الدورة غير موجودة.</div>';
    render_admin_layout_end();
    exit;
}

$stList = $conn->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
$stList->bind_param('i', $course_id);
$stList->execute();
$lessons = $stList->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_lesson'])) {
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
            $message = 'عنوان الحصة بالعربية مطلوب.';
        } elseif ($message === '' && $video_url === '' && $content_ar === '' && $content_en === '') {
            $message = 'أدخل رابط فيديو أو مادة قراءة (عربي/إنجليزي) على الأقل.';
        } elseif ($message === '') {
            if ($timed_json !== '' && json_decode($timed_json, true) === null) {
                $message = 'JSON النص المتزامن غير صالح.';
            } else {
                if ($sort_order <= 0) {
                    $mx = $conn->query('SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM lessons WHERE course_id = ' . $course_id)->fetch_assoc();
                    $sort_order = (int)($mx['n'] ?? 1);
                }
                $stmt = $conn->prepare('INSERT INTO lessons (course_id, title_ar, title_en, content_ar, content_en, video_url, duration, timed_transcript_json, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssssssi', $course_id, $title_ar, $title_en, $content_ar, $content_en, $video_url, $duration, $timed_json, $sort_order);
                if ($stmt->execute()) {
                    $message = 'تم إضافة الحصة.';
                    admin_audit_log('create', 'lesson', (int)$conn->insert_id, ['course_id' => $course_id, 'title_ar' => $title_ar]);
                    $stList = $conn->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
                    $stList->bind_param('i', $course_id);
                    $stList->execute();
                    $lessons = $stList->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $message = 'تعذر الحفظ. شغّل ملفات الترقية في مجلد database/migrations إن لزم.';
                }
            }
        }
    } elseif (isset($_POST['delete'])) {
        $lesson_id = (int)($_POST['id'] ?? 0);
        $stDel = $conn->prepare('DELETE FROM lessons WHERE id = ?');
        $stDel->bind_param('i', $lesson_id);
        $stDel->execute();
        admin_audit_log('delete', 'lesson', $lesson_id, ['course_id' => $course_id]);
        $message = 'تم حذف الحصة.';
        $stList = $conn->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
        $stList->bind_param('i', $course_id);
        $stList->execute();
        $lessons = $stList->get_result()->fetch_all(MYSQLI_ASSOC);
    } elseif (isset($_POST['move_up']) || isset($_POST['move_down'])) {
        $lesson_id = (int)($_POST['id'] ?? 0);
        $direction = isset($_POST['move_up']) ? -1 : 1;
        $stOne = $conn->prepare('SELECT sort_order FROM lessons WHERE id = ? LIMIT 1');
        $stOne->bind_param('i', $lesson_id);
        $stOne->execute();
        $lesson = $stOne->get_result()->fetch_assoc();
        if ($lesson) {
            $op = $direction > 0 ? '>' : '<';
            $ord = $direction > 0 ? 'ASC' : 'DESC';
            $neighborSql = $direction > 0
              ? 'SELECT id, sort_order FROM lessons WHERE course_id = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1'
              : 'SELECT id, sort_order FROM lessons WHERE course_id = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1';
            $currSort = (int)$lesson['sort_order'];
            $stN = $conn->prepare($neighborSql);
            $stN->bind_param('ii', $course_id, $currSort);
            $stN->execute();
            $neighbor = $stN->get_result()->fetch_assoc();
            if ($neighbor) {
                $so = (int)$lesson['sort_order'];
                $nso = (int)$neighbor['sort_order'];
                $u1 = $conn->prepare('UPDATE lessons SET sort_order = ? WHERE id = ?');
                $u1->bind_param('ii', $nso, $lesson_id);
                $u1->execute();
                $nid = (int)$neighbor['id'];
                $u2 = $conn->prepare('UPDATE lessons SET sort_order = ? WHERE id = ?');
                $u2->bind_param('ii', $so, $nid);
                $u2->execute();
                admin_audit_log('reorder', 'lesson', $lesson_id, ['course_id' => $course_id, 'neighbor' => $nid]);
                $message = 'تم تغيير الترتيب.';
            }
        }
        $stList = $conn->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
        $stList->bind_param('i', $course_id);
        $stList->execute();
        $lessons = $stList->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

render_admin_layout_start('حصص: ' . $course['title_ar'], 'courses.php');
?>
<?php if ($message !== ''): ?>
  <div class="alert"><?= e($message) ?></div>
<?php endif; ?>

<div class="filters-card" style="margin-bottom:16px">
  <a href="courses.php" class="btn btn-outline">← الدورات</a>
  <a href="course-edit.php?id=<?= (int)$course['id'] ?>" class="btn btn-outline">تعديل الدورة</a>
</div>

<div class="form-card" style="margin-bottom:24px">
  <h3>حصة جديدة</h3>
  <form method="post" enctype="multipart/form-data">
    <div class="form-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
      <div class="form-group">
        <label>العنوان (عربي) *</label>
        <input type="text" name="title_ar" required class="form-input">
      </div>
      <div class="form-group">
        <label>العنوان (إنجليزي)</label>
        <input type="text" name="title_en" class="form-input" placeholder="English title">
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>رابط الفيديو (يوتيوب/فيميو/ملف mp4 — اختياري إن وُجدت مادة قراءة)</label>
        <input type="url" name="video_url" class="form-input" placeholder="https://...">
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>أو رفع فيديو من الجهاز/السيرفر (اختياري)</label>
        <input type="file" name="video_file" class="form-input" accept="video/*">
        <p class="muted small">إذا تم رفع ملف فيديو سيتم استخدامه بدلاً من رابط الفيديو. الحد التقريبي المتاح حالياً: <?= (int)$maxVideoMb ?>MB.</p>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>مادة القراءة (عربي) — HTML مسموح</label>
        <textarea name="content_ar" class="form-input" rows="5"></textarea>
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>مادة القراءة (إنجليزي)</label>
        <textarea name="content_en" class="form-input" rows="5"></textarea>
      </div>
      <div class="form-group">
        <label>المدة / وقت القراءة التقريبي (يظهر للطالب في دروس القراءة)</label>
        <input type="text" name="duration" class="form-input" placeholder="مثال: 15 دقيقة أو 12:30">
      </div>
      <div class="form-group" style="grid-column:1/-1">
        <label>نص متزامن مع الفيديو (JSON — كل مقطع: start/end بالثواني وtext)</label>
        <textarea name="timed_transcript_json" class="form-input" rows="4" placeholder='[{"start":0.5,"end":1.2,"text":"مرحبا"},{"start":1.2,"end":2,"text":"بكم"}]'></textarea>
        <p class="muted small">لليوتيوب أو mp4: يُبرز الموقع الكلمة أثناء التشغيل. اتركه فارغاً إن لم تُستخدم المزامنة.</p>
      </div>
      <div class="form-group">
        <label>ترتيب العرض (اتركه 0 للتعيين التلقائي)</label>
        <input type="number" name="sort_order" min="0" value="0" class="form-input">
      </div>
    </div>
    <button type="submit" name="add_lesson" value="1" class="btn btn-primary">إضافة الحصة</button>
  </form>
</div>

<div class="table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>العنوان</th>
        <th>الفيديو</th>
        <th>قراءة</th>
        <th>المدة</th>
        <th>الترتيب</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($lessons as $lesson): ?>
        <tr>
          <td><?= (int)$lesson['id'] ?></td>
          <td><strong><?= e($lesson['title_ar']) ?></strong></td>
          <td>
            <?php if (trim((string)($lesson['video_url'] ?? '')) !== ''): ?>
              <a href="<?= e($lesson['video_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-info">فتح</a>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= trim(strip_tags((string)($lesson['content_ar'] ?? ''))) !== '' || trim(strip_tags((string)($lesson['content_en'] ?? ''))) !== '' ? 'نعم' : '—' ?></td>
          <td><?= e($lesson['duration'] ?? '—') ?></td>
          <td><?= (int)$lesson['sort_order'] ?></td>
          <td>
            <div class="action-buttons">
              <a class="btn btn-sm btn-primary" href="lesson-edit.php?id=<?= (int)$lesson['id'] ?>">تعديل</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$lesson['id'] ?>">
                <button type="submit" name="move_up" class="btn btn-sm btn-secondary" title="أعلى"><i class="fas fa-arrow-up"></i></button>
                <button type="submit" name="move_down" class="btn btn-sm btn-secondary" title="أسفل"><i class="fas fa-arrow-down"></i></button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('حذف نهائي؟')">
                <input type="hidden" name="id" value="<?= (int)$lesson['id'] ?>">
                <button type="submit" name="delete" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($lessons)): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:2rem">لا توجد حصص بعد.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php render_admin_layout_end(); ?>
