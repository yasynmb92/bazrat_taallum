<?php
/**
 * ترقية لمرة واحدة: قفل تغيير الاسم الظاهر بعد أول تعديل من البروفايل.
 * تشغيل: افتح الملف من المتصفح أو php user_profile_progress_2026.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name_change_used'");
if ($res && $res->num_rows === 0) {
    $fb = $conn->query("SHOW COLUMNS FROM users LIKE 'facebook_id'");
    $after = ($fb && $fb->num_rows > 0) ? ' AFTER facebook_id' : '';
    $conn->query("ALTER TABLE users ADD COLUMN full_name_change_used TINYINT(1) NOT NULL DEFAULT 0{$after}");
}
echo "OK: users.full_name_change_used\n";

// مزامنة نسب التقدّم من جدول إكمال الدروس (اختياري، يتطلب تحميل functions)
require_once __DIR__ . '/../../includes/functions.php';
$en = $conn->query("SELECT user_id, course_id FROM enrollments WHERE status = 'active'");
if ($en) {
    while ($row = $en->fetch_assoc()) {
        enrollment_progress_recalculate((int)$row['user_id'], (int)$row['course_id']);
    }
}
echo "OK: enrollment progress recalculated\n";
