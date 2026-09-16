<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = db();
$type = $_GET['type'] ?? 'courses';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير - <?= ['courses' => 'الدورات', 'users' => 'المستخدمين', 'orders' => 'الطلبات', 'enrollments' => 'التسجيلات'][$type] ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: white; color: #2d3748; line-height: 1.6; padding: 2rem; }
        .print-header { text-align: center; padding: 2rem 1rem; border-bottom: 3px solid #667eea; margin-bottom: 2rem; }
        .print-header h1 { color: #2d3748; font-size: 2rem; margin-bottom: 0.5rem; }
        .print-header p { color: #718096; font-size: 1.1rem; }
        .print-date { font-size: 0.95rem; color: #a0aec0; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.9rem; }
        tr:hover { background: #f7fafc; }
        .status { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status.paid, .status.published { background: #c6f6d5; color: #22543d; }
        .status.pending { background: #fed7d7; color: #742a2a; }
        .total { font-size: 1.4rem; font-weight: 800; color: #48bb78; text-align: center; padding: 2rem; background: #f0fff4; border-radius: 12px; margin-top: 2rem; }
        .btn { background: #4299e1; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 0.5rem; cursor: pointer; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
        @page { margin: 0.5in; }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>تقرير <?= ['courses' => 'الدورات', 'users' => 'المستخدمين', 'orders' => 'الطلبات', 'enrollments' => 'التسجيلات'][$type] ?></h1>
        <p>بذرة تعلم - منصة التعليم الإلكتروني</p>
        <div class="print-date">تاريخ الطباعة: <?= date('Y-m-d H:i:s') ?></div>
        <button onclick="window.print()" class="btn no-print">🖨️ طباعة التقرير</button>
        <a href="javascript:window.close()" class="btn no-print">✕ إغلاق</a>
    </div>

    <?php
    try {
        switch ($type) {
            case 'courses':
                $courses = $conn->query("SELECT c.*, COALESCE(cat.name_ar, 'بدون فئة') as category FROM courses c LEFT JOIN categories cat ON c.category_id = cat.id ORDER BY c.created_at DESC")->fetch_all(MYSQLI_ASSOC);
                echo '<table><thead><tr><th>العنوان</th><th>الفئة</th><th>المستوى</th><th>السعر</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody>';
                foreach ($courses as $c) {
                    $level = ['beginner' => 'مبتدئ', 'intermediate' => 'متوسط', 'advanced' => 'متقدم'][$c['level'] ?? 'beginner'];
                    $status_class = ($c['is_published'] ?? 0) ? 'published' : 'draft';
                    $status = ($c['is_published'] ?? 0) ? 'منشور' : 'مسودة';
                    echo '<tr><td>' . htmlspecialchars($c['title_ar'] ?? '') . '</td><td>' . htmlspecialchars($c['category'] ?? '') . '</td><td>' . $level . '</td><td>$' . number_format($c['price'] ?? 0, 2) . '</td><td><span class="status ' . $status_class . '">' . $status . '</span></td><td>' . date('Y-m-d', strtotime($c['created_at'] ?? 'now')) . '</td></tr>';
                }
                echo '</tbody></table>';
                break;
            
            case 'users':
                $users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
                echo '<table><thead><tr><th>الاسم الكامل</th><th>البريد الإلكتروني</th><th>الدور</th><th>تاريخ التسجيل</th></tr></thead><tbody>';
                foreach ($users as $u) {
                    $role_ar = ['student' => 'طالب', 'instructor' => 'مدرب'][$u['role'] ?? 'student'];
                    echo '<tr><td>' . htmlspecialchars($u['full_name'] ?? '') . '</td><td>' . htmlspecialchars($u['email'] ?? '') . '</td><td>' . $role_ar . '</td><td>' . date('Y-m-d', strtotime($u['created_at'] ?? 'now')) . '</td></tr>';
                }
                echo '</tbody></table>';
                break;
            
            case 'orders':
                $orders = $conn->query("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);
                echo '<table><thead><tr><th>العميل</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody>';
                $total_revenue = 0;
                foreach ($orders as $o) {
                    $status_ar = ['pending' => 'معلق', 'paid' => 'مدفوع', 'failed' => 'فشل', 'cancelled' => 'ملغى'][$o['status'] ?? 'pending'];
                    $status_class = $o['status'] == 'paid' ? 'paid' : 'pending';
                    echo '<tr><td>' . htmlspecialchars($o['full_name'] ?? 'غير معروف') . '</td><td>$' . number_format($o['total'] ?? 0, 2) . '</td><td><span class="status ' . $status_class . '">' . $status_ar . '</span></td><td>' . date('Y-m-d H:i', strtotime($o['created_at'] ?? 'now')) . '</td></tr>';
                    if (($o['status'] ?? '') == 'paid') $total_revenue += $o['total'] ?? 0;
                }
                echo '</tbody></table><div class="total">إجمالي الإيرادات: $' . number_format($total_revenue, 2) . '</div>';
                break;
            
            case 'enrollments':
                $enrollments = $conn->query("SELECT e.*, u.full_name as student_name, c.title_ar as course_title FROM enrollments e LEFT JOIN users u ON e.student_id = u.id LEFT JOIN courses c ON e.course_id = c.id ORDER BY e.created_at DESC")->fetch_all(MYSQLI_ASSOC);
                echo '<table><thead><tr><th>الطالب</th><th>الدورة</th><th>تاريخ التسجيل</th></tr></thead><tbody>';
                foreach ($enrollments as $e) {
                    echo '<tr><td>' . htmlspecialchars($e['student_name'] ?? 'غير معروف') . '</td><td>' . htmlspecialchars($e['course_title'] ?? 'غير معروف') . '</td><td>' . date('Y-m-d', strtotime($e['created_at'] ?? 'now')) . '</td></tr>';
                }
                echo '</tbody></table>';
                break;
            
            default:
                echo '<div style="text-align:center; padding:3rem; color:#718096;"><h2>نوع التقرير غير مدعوم</h2><p>الخيارات المتاحة: courses, users, orders, enrollments</p></div>';
        }
    } catch (Exception $e) {
        echo '<div style="text-align:center; padding:3rem; color:#e53e3e;"><h2>خطأ في التقرير</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    ?>
</body>
</html>

