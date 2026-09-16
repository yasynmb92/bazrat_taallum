<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$conn = db();

$user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
if (!$user) die('المستخدم غير موجود');

$enrollments_count = $conn->query("SELECT COUNT(*) c FROM enrollments WHERE user_id = $id")->fetch_assoc()['c'];
$orders_count = $conn->query("SELECT COUNT(*) c FROM orders WHERE user_id = $id")->fetch_assoc()['c'];
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-user"></i> <?= e($user['full_name']) ?></h1>
        <p><a class="btn btn-outline btn-sm" href="user-edit.php?id=<?= (int)$id ?>">تعديل البيانات</a></p>
    </div>

    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar"> <?= substr($user['full_name'], 0, 2) ?></div>
                <h2><?= e($user['full_name']) ?></h2>
                <span class="role"><?= $user['role'] === 'student' ? 'طالب' : 'مدرب' ?></span>
            </div>
            <div class="profile-stats">
                <div class="stat">
                    <h3><?= $enrollments_count ?></h3>
                    <p>دورات مُسجل</p>
                </div>
                <div class="stat">
                    <h3><?= $orders_count ?></h3>
                    <p>طلبات</p>
                </div>
                <div class="stat">
                    <h3><?= $user['is_active'] ? 'نشط' : 'معطل' ?></h3>
                    <p>الحالة</p>
                </div>
            </div>
            <div class="profile-info">
                <p><strong>البريد:</strong> <?= e($user['email']) ?></p>
                <p><strong>اللغة:</strong> <?= $user['language_pref'] === 'ar' ? 'العربية' : 'English' ?></p>
                <p><strong>التسجيل:</strong> <?= date('Y-m-d', strtotime($user['created_at'])) ?></p>
            </div>
        </div>

        <div class="recent-activity">
            <h3>آخر التسجيلات</h3>
            <?php
            $recent = $conn->query("SELECT c.title_ar, e.enrolled_at, e.status 
                                   FROM enrollments e JOIN courses c ON e.course_id = c.id 
                                   WHERE e.user_id = $id ORDER BY e.enrolled_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
            ?>
            <ul>
                <?php foreach ($recent as $enroll): ?>
                <li>
                    <strong><?= e($enroll['title_ar']) ?></strong> 
                    <span><?= date('M d', strtotime($enroll['enrolled_at'])) ?></span>
                    <small><?= $enroll['status'] === 'completed' ? 'مكتمل' : 'نشط' ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<style>
.profile-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
.profile-card { background: white; border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.profile-header { text-align: center; margin-bottom: 2rem; }
.avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: 0 auto 1rem; }
.profile-header h2 { color: #2d3748; margin-bottom: 0.5rem; }
.role { background: linear-gradient(135deg, #4299e1, #3182ce); color: white; padding: 0.5rem 1.5rem; border-radius: 25px; font-weight: 600; display: inline-block; }
.profile-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat { text-align: center; padding: 1rem; }
.stat h3 { color: #2d3748; font-size: 1.8rem; margin-bottom: 0.25rem; }
.stat p { color: #718096; font-size: 0.9rem; }
.profile-info p { margin-bottom: 0.75rem; color: #4a5568; }
.recent-activity { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.recent-activity h3 { margin-bottom: 1.5rem; color: #2d3748; }
.recent-activity ul { list-style: none; }
.recent-activity li { padding: 1rem 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.recent-activity li:last-child { border-bottom: none; }
.recent-activity strong { color: #2d3748; flex: 1; }
.recent-activity span { color: #718096; font-size: 0.9rem; }
@media (max-width: 768px) { 
    .profile-grid { grid-template-columns: 1fr; } 
    .profile-stats { grid-template-columns: 1fr; } 
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
