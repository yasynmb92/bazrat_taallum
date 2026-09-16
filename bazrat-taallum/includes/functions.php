<?php
/**
 * وظائف مشتركة للمنصة: الجلسة، الدورات، السلة، التسجيل، البريد، التقدّم، فيسبوك، إلخ.
 * تُحمَّل من header وصفحات الإدارة؛ تجنّب وضع منطق عرض HTML هنا.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/system_settings.php';
require_once __DIR__ . '/smtp_mailer.php';
require_once __DIR__ . '/user_validation.php';
require_once __DIR__ . '/countries_dial.php';
require_once __DIR__ . '/facebook_oauth.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return !!current_user();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool {
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    $token = (string)($token ?? '');
    return $sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_enforce_on_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_validate(is_string($token) ? $token : '')) {
        http_response_code(419);
        exit('CSRF validation failed');
    }
}

function admin_csrf_enforce_on_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_validate(is_string($token) ? $token : '')) {
        http_response_code(419);
        die('CSRF validation failed');
    }
}

function upload_limit_bytes_from_ini(string $val): int {
    $val = trim($val);
    if ($val === '') {
        return 0;
    }
    $last = strtolower(substr($val, -1));
    $num = (int)$val;
    switch ($last) {
        case 'g': return $num * 1024 * 1024 * 1024;
        case 'm': return $num * 1024 * 1024;
        case 'k': return $num * 1024;
        default: return (int)$val;
    }
}

function effective_upload_max_bytes(int $appLimitBytes): int {
    $u = upload_limit_bytes_from_ini((string)ini_get('upload_max_filesize'));
    $p = upload_limit_bytes_from_ini((string)ini_get('post_max_size'));
    $candidates = array_filter([$appLimitBytes, $u, $p], static fn($x) => (int)$x > 0);
    if ($candidates === []) {
        return $appLimitBytes;
    }
    return (int)min($candidates);
}

function login_rate_limit_check(string $email, string $ip): array {
    $conn = db();
    $tableExists = $conn->query("SHOW TABLES LIKE 'auth_login_attempts'");
    if (!$tableExists || $tableExists->num_rows === 0) {
        return ['ok' => true, 'wait_seconds' => 0];
    }
    $windowMinutes = 15;
    $limit = 8;
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM auth_login_attempts WHERE email = ? AND ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL {$windowMinutes} MINUTE)");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    if ($count < $limit) {
        return ['ok' => true, 'wait_seconds' => 0];
    }
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(created_at), DATE_ADD(MAX(created_at), INTERVAL {$windowMinutes} MINUTE)) AS w FROM auth_login_attempts WHERE email = ? AND ip_address = ?");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $wait = max(1, (int)($stmt->get_result()->fetch_assoc()['w'] ?? 60));
    return ['ok' => false, 'wait_seconds' => $wait];
}

function login_rate_limit_record_failure(string $email, string $ip): void {
    $conn = db();
    $tableExists = $conn->query("SHOW TABLES LIKE 'auth_login_attempts'");
    if (!$tableExists || $tableExists->num_rows === 0) {
        return;
    }
    $stmt = $conn->prepare('INSERT INTO auth_login_attempts (email, ip_address) VALUES (?, ?)');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
}

function login_rate_limit_clear(string $email, string $ip): void {
    $conn = db();
    $tableExists = $conn->query("SHOW TABLES LIKE 'auth_login_attempts'");
    if (!$tableExists || $tableExists->num_rows === 0) {
        return;
    }
    $stmt = $conn->prepare('DELETE FROM auth_login_attempts WHERE email = ? AND ip_address = ?');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
}

function set_admin_student_preview_mode(bool $on): void {
    $_SESSION['admin_student_preview'] = $on ? 1 : 0;
}

function is_admin_student_preview_mode(): bool {
    return !empty($_SESSION['admin_student_preview']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (current_user()['role'] !== 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
}

function route_url($path = '') {
    $path = ltrim((string)$path, '/');
    if ($path === '') {
        return APP_URL . '/';
    }
    return APP_URL . '/' . $path;
}

function slugify($value) {
    $value = strtolower(trim((string)$value));
    $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
    $value = preg_replace('/[\s-]+/', '-', $value ?? '');
    return trim((string)$value, '-');
}

function course_slug(array $course) {
    $base = app_lang() === 'ar' ? ($course['title_en'] ?? $course['title_ar'] ?? '') : ($course['title_en'] ?? '');
    $slug = slugify($base);
    if ($slug === '') {
        $slug = 'course-' . (int)($course['id'] ?? 0);
    }
    return $slug . '-' . (int)($course['id'] ?? 0);
}

function course_url(array $course) {
    return route_url('course/' . course_slug($course));
}

function course_id_from_slug($slug) {
    $slug = trim((string)$slug);
    if (preg_match('/-(\d+)$/', $slug, $m)) {
        return (int)$m[1];
    }
    return 0;
}

function udemy_category_links() {
    return [
        'development' => ['label_ar' => 'البرمجة والتطوير', 'keywords' => ['development', 'programming', 'web', 'code']],
        'business' => ['label_ar' => 'الأعمال', 'keywords' => ['business']],
        'finance-and-accounting' => ['label_ar' => 'المالية والمحاسبة', 'keywords' => ['finance', 'accounting']],
        'design' => ['label_ar' => 'التصميم', 'keywords' => ['design']],
        'marketing' => ['label_ar' => 'التسويق', 'keywords' => ['marketing']],
        'personal-development' => ['label_ar' => 'تطوير الذات', 'keywords' => ['personal development', 'self']],
        'health-and-fitness' => ['label_ar' => 'الصحة واللياقة', 'keywords' => ['health', 'fitness']],
    ];
}

function resolve_category_id_by_slug($slug) {
    $conn = db();
    $slug = trim((string)$slug);
    if ($slug === '') {
        return 0;
    }
    $stmt = $conn->prepare("SELECT id, name_en, name_ar FROM categories");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (slugify($row['name_en']) === $slug || slugify($row['name_ar']) === $slug) {
            return (int)$row['id'];
        }
    }
    $map = udemy_category_links()[$slug]['keywords'] ?? [];
    if (!$map) {
        return 0;
    }
    $rows = fetch_categories();
    foreach ($rows as $row) {
        $nameEn = strtolower((string)$row['name_en']);
        $nameAr = strtolower((string)$row['name_ar']);
        foreach ($map as $kw) {
            if (strpos($nameEn, $kw) !== false || strpos($nameAr, $kw) !== false) {
                return (int)$row['id'];
            }
        }
    }
    return 0;
}

function table_has_column(string $table, string $column): bool {
    static $cache = [];
    $k = $table . '.' . $column;
    if (isset($cache[$k])) {
        return $cache[$k];
    }
    $conn = db();
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    $cache[$k] = (bool)($r && $r->num_rows > 0);
    return $cache[$k];
}

function fetch_learning_tracks(): array {
    $conn = db();
    $r = $conn->query("SHOW TABLES LIKE 'learning_tracks'");
    if (!$r || $r->num_rows === 0) {
        return [];
    }
    $res = $conn->query('SELECT * FROM learning_tracks WHERE is_active = 1 ORDER BY sort_order, id');
    $rows = [];
    while ($res && $row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function course_list_select_sql() {
    $trackSelect = '';
    if (table_has_column('courses', 'track_id')) {
        $trackSelect = ", c.track_id, tr.name_ar as track_name_ar, tr.name_en as track_name_en";
    }
    $ageSelect = table_has_column('courses', 'age_group') ? ", c.age_group" : '';
    return "c.*, u.full_name as instructor_name, cat.name_ar as category_ar, cat.name_en as category_en,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
            (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.course_id = c.id) AS rating_avg,
            (SELECT COUNT(*) FROM reviews r WHERE r.course_id = c.id) AS rating_count,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') AS students_count
            {$trackSelect}{$ageSelect}";
}

function fetch_all_courses() {
    $conn = db();
    $sel = course_list_select_sql();
    $trackJoin = table_has_column('courses', 'track_id')
        ? ' LEFT JOIN learning_tracks tr ON tr.id = c.track_id '
        : '';
    $sql = "SELECT $sel
            FROM courses c
            LEFT JOIN users u ON u.id = c.instructor_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            {$trackJoin}
            WHERE c.is_published = 1
            ORDER BY c.id DESC";
    $res = $conn->query($sql);
    $rows = [];
    while ($res && $r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

/**
 * @param array{q?:string,category_id?:int,category_slug?:string,sort?:string} $opts
 */
function fetch_courses_filtered(array $opts = []) {
    $conn = db();
    $q = trim($opts['q'] ?? '');
    $catId = isset($opts['category_id']) ? (int)$opts['category_id'] : 0;
    if ($catId <= 0 && !empty($opts['category_slug'])) {
        $catId = resolve_category_id_by_slug((string)$opts['category_slug']);
    }
    $sort = $opts['sort'] ?? 'newest';

    $orders = [
        'newest' => 'c.id DESC',
        'price_low' => 'c.price ASC',
        'price_high' => 'c.price DESC',
        'title' => app_lang() === 'ar' ? 'c.title_ar ASC' : 'c.title_en ASC',
    ];
    $orderSql = $orders[$sort] ?? $orders['newest'];

    $where = ['c.is_published = 1'];
    $types = '';
    $params = [];

    if ($catId > 0) {
        $where[] = 'c.category_id = ?';
        $types .= 'i';
        $params[] = $catId;
    }
    if ($q !== '') {
        $where[] = '(c.title_ar LIKE ? OR c.title_en LIKE ? OR c.description_ar LIKE ? OR c.description_en LIKE ?)';
        $types .= 'ssss';
        $term = '%' . $q . '%';
        array_push($params, $term, $term, $term, $term);
    }

    $sel = course_list_select_sql();
    $trackJoin = table_has_column('courses', 'track_id')
        ? ' LEFT JOIN learning_tracks tr ON tr.id = c.track_id '
        : '';
    $sql = "SELECT $sel
            FROM courses c
            LEFT JOIN users u ON u.id = c.instructor_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            {$trackJoin}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $orderSql";

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function fetch_categories() {
    $conn = db();
    $res = $conn->query('SELECT * FROM categories ORDER BY name_ar ASC');
    $rows = [];
    while ($res && $r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function fetch_course_detail($id) {
    $conn = db();
    $id = (int)$id;
    $sel = course_list_select_sql();
    $trackJoin = table_has_column('courses', 'track_id')
        ? ' LEFT JOIN learning_tracks tr ON tr.id = c.track_id '
        : '';
    $stmt = $conn->prepare("SELECT $sel
            FROM courses c
            LEFT JOIN users u ON u.id = c.instructor_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            {$trackJoin}
            WHERE c.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetch_course_by_slug($slug) {
    $id = course_id_from_slug($slug);
    if ($id <= 0) {
        return null;
    }
    return fetch_course_detail($id);
}

function platform_stats() {
    $conn = db();
    $courses = (int)$conn->query('SELECT COUNT(*) c FROM courses WHERE is_published = 1')->fetch_assoc()['c'];
    $students = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role = 'student'")->fetch_assoc()['c'];
    $instructors = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role IN ('instructor','admin')")->fetch_assoc()['c'];
    $learningMinutes = published_platform_learning_minutes();
    $learningHours = $learningMinutes > 0 ? round($learningMinutes / 60.0, 1) : 0.0;
    return [
        'courses' => $courses,
        'students' => $students,
        'instructors' => $instructors,
        'learning_minutes' => $learningMinutes,
        'learning_hours' => $learningHours,
    ];
}

/**
 * تنسيق تاريخ آخر تعديل لعرضه في الواجهة.
 */
function course_last_updated_label(array $course): string {
    $raw = trim((string)($course['updated_at'] ?? ''));
    if ($raw === '' || $raw === '0000-00-00 00:00:00') {
        $raw = trim((string)($course['created_at'] ?? ''));
    }
    if ($raw === '' || $raw === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false || $ts <= 0) {
        return '';
    }
    return date('d/m/Y', $ts);
}

function fetch_course_reviews($courseId, $limit = 12) {
    $conn = db();
    $courseId = (int)$courseId;
    $limit = max(1, min(50, (int)$limit));
    $sql = "SELECT r.*, u.full_name AS reviewer_name
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.course_id = $courseId
            ORDER BY r.id DESC
            LIMIT $limit";
    $res = $conn->query($sql);
    $rows = [];
    while ($res && $r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function fetch_active_course_project(int $courseId): ?array {
    $conn = db();
    $r = $conn->query("SHOW TABLES LIKE 'course_projects'");
    if (!$r || $r->num_rows === 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM course_projects WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function fetch_active_course_quiz(int $courseId): ?array {
    $conn = db();
    $r = $conn->query("SHOW TABLES LIKE 'course_quizzes'");
    if (!$r || $r->num_rows === 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM course_quizzes WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function latest_quiz_attempt(int $quizId, int $userId): ?array {
    $conn = db();
    $r = $conn->query("SHOW TABLES LIKE 'quiz_attempts'");
    if (!$r || $r->num_rows === 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('ii', $quizId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function video_embed_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return $url;
}

function get_first_lesson_id($courseId) {
    $lessons = fetch_lessons((int)$courseId);
    return isset($lessons[0]['id']) ? (int)$lessons[0]['id'] : null;
}

/** عدد الدروس المجانية المعروضة قبل الشراء (الأولى والثانية). */
if (!defined('FREE_PREVIEW_LESSON_COUNT')) {
    define('FREE_PREVIEW_LESSON_COUNT', 3);
}

function has_access($userId, $courseId) {
    $userId = (int)$userId;
    $courseId = (int)$courseId;
    if ($userId <= 0 || $courseId <= 0) {
        return false;
    }
    $conn = db();
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('ii', $userId, $courseId);
    $stmt->execute();
    $hasEnrollment = $stmt->get_result()->fetch_assoc() !== null;
    if (!$hasEnrollment) {
        return false;
    }
    // لا تربط الوصول بأي عملية دفع غير مرتبطة بنفس الدورة.
    return enrollment_is_payment_confirmed_or_free($userId, $courseId);
}

/**
 * هل يوجد دفع ناجح فعلي لهذه الدورة للمستخدم (أو هي دورة مجانية/تسجيل يدوي بلا طلبات)؟
 */
function enrollment_is_payment_confirmed_or_free(int $userId, int $courseId): bool {
    $conn = db();
    $st = $conn->prepare('SELECT is_free FROM courses WHERE id = ? LIMIT 1');
    $st->bind_param('i', $courseId);
    $st->execute();
    $course = $st->get_result()->fetch_assoc();
    if ($course && (int)($course['is_free'] ?? 0) === 1) {
        return true;
    }

    // إن لم توجد طلبات لهذه الدورة للمستخدم نعتبره تسجيل مجاني/يدوي (توافق خلفي).
    $st = $conn->prepare('SELECT 1 FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? AND oi.course_id = ? LIMIT 1');
    $st->bind_param('ii', $userId, $courseId);
    $st->execute();
    $hasAnyOrder = $st->get_result()->fetch_assoc() !== null;
    if (!$hasAnyOrder) {
        return true;
    }

    // الوصول مدفوع فقط عند وجود طلب Paid + دفعة Success لنفس الدورة.
    $st = $conn->prepare(
        "SELECT 1
         FROM orders o
         INNER JOIN order_items oi ON oi.order_id = o.id
         INNER JOIN payments p ON p.order_id = o.id
         WHERE o.user_id = ?
           AND oi.course_id = ?
           AND o.status = 'paid'
           AND p.status = 'success'
         LIMIT 1"
    );
    $st->bind_param('ii', $userId, $courseId);
    $st->execute();
    return $st->get_result()->fetch_assoc() !== null;
}

/**
 * مزامنة تسجيلات المستخدم لدورات طلب محدد بحسب حالته.
 * paid => active، غير ذلك => cancelled (إلا لو هناك طلب آخر ناجح لنفس الدورة).
 */
function sync_enrollments_for_order(int $orderId): void {
    $conn = db();
    $order = $conn->query('SELECT id, user_id, status FROM orders WHERE id = ' . (int)$orderId . ' LIMIT 1')->fetch_assoc();
    if (!$order) {
        return;
    }
    $userId = (int)$order['user_id'];
    $status = (string)$order['status'];
    $items = $conn->query('SELECT course_id FROM order_items WHERE order_id = ' . (int)$orderId);
    if (!$items) {
        return;
    }
    while ($it = $items->fetch_assoc()) {
        $cid = (int)$it['course_id'];
        if ($status === 'paid' && enrollment_is_payment_confirmed_or_free($userId, $cid)) {
            $ins = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress, status) VALUES (?, ?, 0, 'active') ON DUPLICATE KEY UPDATE status = 'active'");
            $ins->bind_param('ii', $userId, $cid);
            $ins->execute();
            continue;
        }
        if (!enrollment_is_payment_confirmed_or_free($userId, $cid)) {
            $up = $conn->prepare("UPDATE enrollments SET status = 'cancelled' WHERE user_id = ? AND course_id = ?");
            $up->bind_param('ii', $userId, $cid);
            $up->execute();
        }
    }
}

function fetch_course($id) {
    $conn = db();
    $stmt = $conn->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetch_lessons($courseId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order, id");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

function lesson_index_by_id(array $lessons, int $lessonId): ?int {
    foreach ($lessons as $i => $l) {
        if ((int)$l['id'] === $lessonId) {
            return (int)$i;
        }
    }
    return null;
}

function fetch_completed_lesson_ids(int $userId, int $courseId): array {
    $conn = db();
    $stmt = $conn->prepare('SELECT lesson_id FROM lesson_completions WHERE user_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $userId, $courseId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($res && $r = $res->fetch_assoc()) {
        $ids[] = (int)$r['lesson_id'];
    }
    return $ids;
}

function enrolled_lesson_unlocked(array $lessons, int $lessonIndex, array $completedLessonIds): bool {
    if ($lessonIndex <= 0) {
        return true;
    }
    for ($j = 0; $j < $lessonIndex; $j++) {
        if (!in_array((int)$lessons[$j]['id'], $completedLessonIds, true)) {
            return false;
        }
    }
    return true;
}

/**
 * @return array{ok:bool, mode?:string, reason?:string}
 */
function learn_lesson_access_state(?array $viewer, array $course, int $lessonId, array $lessons, array $completedLessonIds): array {
    $courseId = (int)$course['id'];
    $published = (int)($course['is_published'] ?? 0);
    $idx = lesson_index_by_id($lessons, $lessonId);
    if ($idx === null) {
        return ['ok' => false, 'reason' => 'not_found'];
    }

    $isAdmin = $viewer && ($viewer['role'] ?? '') === 'admin';
    $isAdminPreviewAsStudent = $isAdmin && is_admin_student_preview_mode();
    $userId = $viewer ? (int)$viewer['id'] : 0;

    if (!$published && !$isAdmin) {
        return ['ok' => false, 'reason' => 'unpublished'];
    }
    if ($isAdmin && !$isAdminPreviewAsStudent) {
        return ['ok' => true, 'mode' => 'admin_preview'];
    }

    $enrolled = $userId > 0 && has_access($userId, $courseId);
    if ($enrolled) {
        if (enrolled_lesson_unlocked($lessons, $idx, $completedLessonIds)) {
            return ['ok' => true, 'mode' => 'enrolled'];
        }
        return ['ok' => false, 'reason' => 'locked'];
    }

    if ($idx < FREE_PREVIEW_LESSON_COUNT) {
        return ['ok' => true, 'mode' => 'preview'];
    }

    return ['ok' => false, 'reason' => 'purchase_required'];
}

function mark_lesson_completed(int $userId, int $lessonId, int $courseId): bool {
    $conn = db();
    $stmt = $conn->prepare('INSERT IGNORE INTO lesson_completions (user_id, lesson_id, course_id) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $userId, $lessonId, $courseId);
    $ok = (bool)$stmt->execute();
    enrollment_progress_recalculate($userId, $courseId);
    return $ok;
}

/**
 * ─── تقدّم التسجيل في الدورة ───
 * يحدّث عمود enrollments.progress كنسبة مئوية من الدروس المكتملة / إجمالي الدروس.
 */
function enrollment_progress_recalculate(int $userId, int $courseId): void {
    $conn = db();
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM lessons WHERE course_id = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    if ($total <= 0) {
        $u = $conn->prepare("UPDATE enrollments SET progress = 0 WHERE user_id = ? AND course_id = ?");
        $u->bind_param('ii', $userId, $courseId);
        $u->execute();
        return;
    }
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM lesson_completions WHERE user_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $userId, $courseId);
    $stmt->execute();
    $done = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $pct = (int)round(min(100, ($done / $total) * 100));
    $u = $conn->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ? AND status = 'active'");
    $u->bind_param('iii', $pct, $userId, $courseId);
    $u->execute();
}

/** نسبة التقدّم المحفوظة للمستخدم في دورة (للعرض في صفحة التعلّم). */
function enrollment_progress_percent(int $userId, int $courseId): int {
    $conn = db();
    $stmt = $conn->prepare("SELECT progress FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('ii', $userId, $courseId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ? (int)$r['progress'] : 0;
}

/**
 * ─── شريط التنقل: دورات المستخدم المسجّل ───
 * يُستخدم في الهيدر لعرض روابط سريعة لآخر الدورات النشطة.
 *
 * @return list<array{course_id:int,title_ar:string,title_en:string,progress:int,first_lesson_id:?int}>
 */
function fetch_user_nav_courses(int $userId, int $limit = 6): array {
    $lim = max(1, min(12, $limit));
    $conn = db();
    $sql = "SELECT e.progress, c.id AS course_id, c.title_ar, c.title_en
            FROM enrollments e
            INNER JOIN courses c ON c.id = e.course_id
            WHERE e.user_id = " . (int)$userId . " AND e.status = 'active'
            ORDER BY e.enrolled_at DESC
            LIMIT {$lim}";
    $res = $conn->query($sql);
    $rows = [];
    while ($res && $r = $res->fetch_assoc()) {
        $cid = (int)$r['course_id'];
        $rows[] = [
            'course_id' => $cid,
            'title_ar' => (string)$r['title_ar'],
            'title_en' => (string)$r['title_en'],
            'progress' => (int)$r['progress'],
            'first_lesson_id' => get_first_lesson_id($cid),
        ];
    }
    return $rows;
}

/** إعادة تحميل بيانات المستخدم في الجلسة بعد تعديل البروفايل. */
function refresh_session_user(int $userId): void {
    if (!is_logged_in() || (int)(current_user()['id'] ?? 0) !== $userId) {
        return;
    }
    $conn = db();
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) {
        $_SESSION['user'] = $u;
    }
}

/** التحقق من وجود عمود في جدول المستخدمين (قواعد قديمة بدون ترقية). */
function users_table_has_column(string $columnName): bool {
    static $cache = [];
    if (isset($cache[$columnName])) {
        return $cache[$columnName];
    }
    $conn = db();
    $c = $conn->real_escape_string($columnName);
    $r = $conn->query("SHOW COLUMNS FROM users LIKE '{$c}'");
    $cache[$columnName] = $r && $r->num_rows > 0;
    return $cache[$columnName];
}

function users_table_has_permissions_json(): bool {
    return users_table_has_column('permissions_json');
}

function admin_audit_log(string $action, string $entity, int $entityId = 0, array $meta = []): void {
    $conn = db();
    $tableExists = $conn->query("SHOW TABLES LIKE 'admin_audit_logs'");
    if (!$tableExists || $tableExists->num_rows === 0) {
        return;
    }
    $admin = current_user();
    $adminId = (int)($admin['id'] ?? 0);
    if ($adminId <= 0) {
        return;
    }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare('INSERT INTO admin_audit_logs (admin_user_id, action, entity_type, entity_id, meta_json, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $adminId, $action, $entity, $entityId, $metaJson, $ip);
    $stmt->execute();
}

/**
 * الصلاحيات القابلة للإسناد من لوحة الإدارة.
 * @return array<string,string>
 */
function assignable_user_permissions(): array {
    return [
        'manage_courses' => 'إدارة الدورات',
        'manage_lessons' => 'إدارة الدروس',
        'manage_orders' => 'إدارة الطلبات',
        'manage_payments' => 'إدارة المدفوعات',
        'manage_users' => 'إدارة المستخدمين',
        'view_reports' => 'عرض التقارير',
    ];
}

/**
 * @return list<string>
 */
function user_permissions_list(array $userRow): array {
    if (!users_table_has_permissions_json()) {
        return [];
    }
    $raw = trim((string)($userRow['permissions_json'] ?? ''));
    if ($raw === '') {
        return [];
    }
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        return [];
    }
    $allowed = array_keys(assignable_user_permissions());
    $out = [];
    foreach ($arr as $p) {
        $p = (string)$p;
        if (in_array($p, $allowed, true)) {
            $out[] = $p;
        }
    }
    return array_values(array_unique($out));
}

/** هل يحق للمستخدم تغيير الاسم الظاهر من البروفايل (مرة واحدة فقط بعد أول تعديل ناجح). */
function user_may_change_display_name(array $userRow): bool {
    if (!users_table_has_column('full_name_change_used')) {
        return true;
    }
    return (int)($userRow['full_name_change_used'] ?? 0) === 0;
}

function is_direct_video_file_url(string $url): bool {
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    return (bool)preg_match('/\.(mp4|webm|ogg)(\?|#|$)/i', $url);
}

/**
 * رفع فيديو من لوحة الأدمن وإرجاع رابط عام داخل المنصة.
 * @return array{ok:bool,url?:string,error?:string}
 */
function admin_store_uploaded_video(array $file, int $maxMb = 512): array {
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'no_file'];
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'upload_error'];
    }
    $size = (int)($file['size'] ?? 0);
    $effectiveMax = effective_upload_max_bytes($maxMb * 1024 * 1024);
    if ($size <= 0 || $size > $effectiveMax) {
        return ['ok' => false, 'error' => 'size_limit'];
    }
    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['mp4', 'webm', 'ogg', 'mov', 'm4v'];
    if (!in_array($ext, $allowed, true)) {
        return ['ok' => false, 'error' => 'bad_type'];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    $mime = '';
    if ($tmp !== '' && function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $mime = (string)finfo_file($fi, $tmp);
            finfo_close($fi);
        }
    }
    $allowedMimes = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'application/octet-stream', // بعض الاستضافات تُعيد هذا رغم صحة الملف
    ];
    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'bad_mime'];
    }
    $targetDir = __DIR__ . '/../assets/uploads/videos';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['ok' => false, 'error' => 'dir_fail'];
    }
    $base = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $targetAbs = $targetDir . '/' . $base . '.' . $ext;
    if (!move_uploaded_file((string)$file['tmp_name'], $targetAbs)) {
        return ['ok' => false, 'error' => 'move_fail'];
    }
    $url = APP_URL . '/assets/uploads/videos/' . basename($targetAbs);
    return ['ok' => true, 'url' => $url];
}

/** تسعير الدورة: السعر القائمة، السعر بعد الخصم/المجاني. */
function course_effective_pricing(array $c): array {
    $list = (float)($c['price'] ?? 0);
    if (!empty($c['is_free'])) {
        return ['list_price' => $list, 'final_price' => 0.0, 'has_discount' => $list > 0.00001];
    }
    $type = $c['course_discount_type'] ?? 'none';
    if ($type === '' || $type === null) {
        $type = 'none';
    }
    $amt = (float)($c['course_discount_value'] ?? 0);
    $final = $list;
    if ($type === 'percent' && $amt > 0) {
        $final = round(max(0, $list * (1 - min(100, $amt) / 100)), 2);
    } elseif ($type === 'fixed' && $amt > 0) {
        $final = round(max(0, $list - $amt), 2);
    }
    return [
        'list_price' => $list,
        'final_price' => $final,
        'has_discount' => $final < $list - 0.00001,
    ];
}

function youtube_video_id_from_url(string $url): ?string {
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return $m[1];
    }
    return null;
}

/** مصفوفة عناصر {start,end,text} بالثواني لمزامنة النص مع الفيديو. */
function parse_lesson_timed_transcript($json): array {
    $json = trim((string)($json ?? ''));
    if ($json === '') {
        return [];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    if (isset($data['words']) && is_array($data['words'])) {
        $data = $data['words'];
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $start = isset($row['start']) ? (float)$row['start'] : (isset($row['t']) ? (float)$row['t'] : null);
        $end = isset($row['end']) ? (float)$row['end'] : null;
        if ($end === null && isset($row['d']) && $start !== null) {
            $end = $start + (float)$row['d'];
        }
        $text = trim((string)($row['text'] ?? $row['word'] ?? ''));
        if ($text === '' || $start === null) {
            continue;
        }
        if ($end === null || $end <= $start) {
            $end = $start + 0.4;
        }
        $out[] = ['start' => $start, 'end' => $end, 'text' => $text];
    }
    usort($out, function ($a, $b) {
        return $a['start'] <=> $b['start'];
    });
    return $out;
}

/**
 * يحوّل نص المدة (عربي/إنجليزي، MM:SS، H:MM:SS، رقم صريح) إلى دقائق، أو null إن تعذّر التفسير.
 */
function parse_duration_string_to_minutes(string $raw): ?float {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $norm = str_replace(['٫', '٬', '،'], ['.', ',', ','], $raw);
    if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $norm, $m)) {
        return (int)$m[1] * 60 + (int)$m[2] + (int)$m[3] / 60.0;
    }
    if (preg_match('/^(\d+):(\d{2})$/', $norm, $m)) {
        return (int)$m[1] + (int)$m[2] / 60.0;
    }
    $sum = 0.0;
    $any = false;
    if (preg_match('/ساعتين/u', $norm)) {
        $sum += 120.0;
        $any = true;
    }
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*ساعات?/u', $norm, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mm) {
            $sum += (float)str_replace(',', '.', $mm[1]) * 60.0;
            $any = true;
        }
    }
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:دقيقة|دقائق)/u', $norm, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mm) {
            $sum += (float)str_replace(',', '.', $mm[1]);
            $any = true;
        }
    }
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*h(?:ours?)?\b/iu', $norm, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mm) {
            $sum += (float)str_replace(',', '.', $mm[1]) * 60.0;
            $any = true;
        }
    }
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:mins?|minutes?)\b/iu', $norm, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mm) {
            $sum += (float)str_replace(',', '.', $mm[1]);
            $any = true;
        }
    }
    if ($any) {
        return $sum;
    }
    if (preg_match('/^(\d+(?:[.,]\d+)?)\s*$/u', $norm, $m)) {
        return (float)str_replace(',', '.', $m[1]);
    }
    return null;
}

function lesson_video_minutes_from_transcript(array $lesson): float {
    $steps = parse_lesson_timed_transcript($lesson['timed_transcript_json'] ?? null);
    $max = 0.0;
    foreach ($steps as $s) {
        $max = max($max, (float)$s['end']);
    }
    return $max > 1 ? round($max / 60.0, 2) : 0.0;
}

function lesson_reading_minutes_estimate(array $lesson): float {
    $isArUi = app_lang() === 'ar';
    $primary = trim(strip_tags((string)($isArUi ? ($lesson['content_ar'] ?? '') : ($lesson['content_en'] ?? ''))));
    $ar = trim(strip_tags((string)($lesson['content_ar'] ?? '')));
    $en = trim(strip_tags((string)($lesson['content_en'] ?? '')));
    $text = $primary !== '' ? $primary : ($ar !== '' ? $ar : $en);
    $text = trim($text);
    if ($text === '') {
        return 0.0;
    }
    $n = 0;
    if (preg_match_all('/\p{L}[\p{L}\p{M}]*/u', $text, $m)) {
        $n = count($m[0]);
    } else {
        $n = str_word_count($text);
    }
    if ($n <= 0) {
        return 0.0;
    }
    return max(1.0, round($n / 200.0, 1));
}

/**
 * تقدير مدة الدرس بالدقائق: الحقل duration إن وُجد، وإلا طول الفيديو من النص المتزامن، وإلا وقت قراءة من المحتوى النصي.
 */
function lesson_estimated_minutes(array $lesson): float {
    $parsed = parse_duration_string_to_minutes(trim((string)($lesson['duration'] ?? '')));
    if ($parsed !== null && $parsed > 0) {
        return $parsed;
    }
    $hasVideo = trim((string)($lesson['video_url'] ?? '')) !== '';
    if ($hasVideo) {
        $vm = lesson_video_minutes_from_transcript($lesson);
        if ($vm > 0) {
            return $vm;
        }
    }
    return lesson_reading_minutes_estimate($lesson);
}

function course_total_duration_minutes_from_lessons(array $lessons): float {
    $sum = 0.0;
    foreach ($lessons as $l) {
        $sum += lesson_estimated_minutes($l);
    }
    return round($sum, 1);
}

/**
 * إجمالي دقائق المحتوى التعليمي في جميع الدورات المنشورة (فيديو + قراءة حسب تقدير الدروس).
 */
function published_platform_learning_minutes(): float {
    $conn = db();
    $sql = 'SELECT l.video_url, l.duration, l.timed_transcript_json, l.content_ar, l.content_en
            FROM lessons l
            INNER JOIN courses c ON c.id = l.course_id AND c.is_published = 1';
    $res = $conn->query($sql);
    if (!$res) {
        return 0.0;
    }
    $sum = 0.0;
    while ($row = $res->fetch_assoc()) {
        $sum += lesson_estimated_minutes($row);
    }
    return round($sum, 1);
}

/** عرض مدة الدورة للواجهة (عربي/إنجليزي). */
function format_course_duration_display(float $totalMinutes, bool $isAr): string {
    if ($totalMinutes <= 0) {
        return '';
    }
    $h = (int)floor($totalMinutes / 60);
    $m = (int)round($totalMinutes - $h * 60);
    if ($m >= 60) {
        $h++;
        $m = 0;
    }
    if ($isAr) {
        if ($h > 0 && $m > 0) {
            return $h . ' س ' . $m . ' د';
        }
        if ($h > 0) {
            return $h . ($h === 1 ? ' ساعة' : ' ساعات');
        }
        return (string)(int)round($totalMinutes) . ' دقيقة';
    }
    if ($h > 0 && $m > 0) {
        return $h . 'h ' . $m . 'm';
    }
    if ($h > 0) {
        return $h . 'h';
    }
    return (string)(int)round($totalMinutes) . ' min';
}

/**
 * يضيف لكل عنصر total_duration_minutes و total_duration_label حسب دروس الدورة (استعلام واحد).
 *
 * @param list<array<string,mixed>> $courses
 * @return list<array<string,mixed>>
 */
function courses_attach_duration_minutes(array $courses): array {
    if ($courses === []) {
        return $courses;
    }
    $ids = [];
    foreach ($courses as $c) {
        $id = (int)($c['id'] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if ($ids === []) {
        return $courses;
    }
    $conn = db();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare(
        'SELECT course_id, video_url, duration, timed_transcript_json, content_ar, content_en FROM lessons WHERE course_id IN (' . $placeholders . ')'
    );
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $byCourse = [];
    while ($row = $res->fetch_assoc()) {
        $cid = (int)$row['course_id'];
        if (!isset($byCourse[$cid])) {
            $byCourse[$cid] = [];
        }
        $byCourse[$cid][] = $row;
    }
    $isAr = app_lang() === 'ar';
    foreach ($courses as &$c) {
        $cid = (int)($c['id'] ?? 0);
        $less = $byCourse[$cid] ?? [];
        $mins = course_total_duration_minutes_from_lessons($less);
        $c['total_duration_minutes'] = $mins;
        $c['total_duration_label'] = format_course_duration_display($mins, $isAr);
    }
    unset($c);
    return $courses;
}

function enroll_user_in_course(int $userId, int $courseId): bool {
    $conn = db();
    $stmt = $conn->prepare("INSERT IGNORE INTO enrollments (user_id, course_id, progress, status) VALUES (?, ?, 0, 'active')");
    $stmt->bind_param('ii', $userId, $courseId);
    return (bool)$stmt->execute();
}

/**
 * @return array{lines:array,subtotal:float,discount:float,total:float,coupon_id:?int,coupon_ok:bool,coupon_code:string}
 */
function compute_cart_totals(int $userId, ?string $couponCode): array {
    $cart = get_cart($userId);
    $lines = [];
    $subtotal = 0.0;
    foreach ($cart as $item) {
        $p = course_effective_pricing($item);
        $subtotal += $p['final_price'];
        $lines[] = array_merge($item, ['pricing' => $p]);
    }
    $couponCode = $couponCode !== null ? trim($couponCode) : '';
    $discount = 0.0;
    $couponId = null;
    $couponOk = false;
    if ($couponCode !== '' && $subtotal > 0) {
        $conn = db();
        $stmt = $conn->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('s', $couponCode);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        if ($coupon) {
            $couponOk = true;
            $couponId = (int)$coupon['id'];
            if ($coupon['discount_type'] === 'percent') {
                $discount = round($subtotal * ((float)$coupon['discount_value']) / 100, 2);
            } else {
                $discount = (float)$coupon['discount_value'];
            }
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
        }
    }
    $total = round(max(0, $subtotal - $discount), 2);
    return [
        'lines' => $lines,
        'subtotal' => round($subtotal, 2),
        'discount' => round($discount, 2),
        'total' => $total,
        'coupon_id' => $couponId,
        'coupon_ok' => $couponOk,
        'coupon_code' => $couponCode,
    ];
}

function register_user_v2($data) {
    $conn = db();
    if (isset($data['phone_number'], $data['phone_country_code'])) {
        $v = validate_registration_fields($conn, [
            'full_name' => $data['full_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone_country_code' => $data['phone_country_code'] ?? '',
            'phone_number' => $data['phone_number'] ?? '',
        ], 0);
        if ($v !== '') {
            return false;
        }
    }
    $temp_password = generate_temp_password();
    $hash = password_hash($temp_password, PASSWORD_DEFAULT);
    $role = 'student';
    $lang = app_lang();
    $code = sprintf('%06d', random_int(0, 999999));
    $verificationExpiresAt = date('Y-m-d H:i:s', time() + 900);
    $verificationSentAt = date('Y-m-d H:i:s');
    
$values = [];
    $placeholders = [];
    $param_types = '';
    $bind_params = [];

    // Basic fields
    $values[] = $data['full_name'] ?? '';
    $values[] = $data['email'] ?? '';
    $values[] = $hash;
    $values[] = $role;
    $values[] = $lang;
    $param_types .= 'sssss';
    
    // Optional fields from data
    $values[] = $data['birth_year'] ?? null;
    $values[] = $data['birth_month'] ?? null;
    $values[] = $data['phone_country_code'] ?? null;
    $values[] = $data['phone_number'] ?? null;
    $values[] = $data['whatsapp'] ?? null;
    $values[] = $data['city'] ?? null;
    $values[] = $data['state'] ?? null;
    $values[] = $data['parent_phone'] ?? null;
    $values[] = $data['parent_name'] ?? null;
    $param_types .= 'iisssssss';
    
    // Fixed fields
    $values[] = 0;
    $values[] = $code;
    $values[] = $verificationExpiresAt;
    $values[] = $verificationSentAt;
    $param_types .= 'isss';
    
    $fields_str = 'full_name, email, password_hash, role, language_pref, birth_year, birth_month, phone_country_code, phone_number, whatsapp, city, state, parent_phone, parent_name, email_verified, verification_code, verification_expires_at, verification_sent_at';
    $placeholders_str = str_repeat('?,', count($values) - 1) . '?';
    
    $sql = "INSERT INTO users ($fields_str) VALUES ($placeholders_str)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$values);
    
    if ($stmt->execute()) {
        send_verification_email($data['email'], $code, $temp_password, (string)($data['full_name'] ?? ''));
        $newId = (int)$conn->insert_id;
        return $newId > 0 ? $newId : true;
    }
    return false;
}

function generate_temp_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

function users_table_has_facebook_id(): bool {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $r = db()->query("SHOW COLUMNS FROM users LIKE 'facebook_id'");
    $cached = $r && $r->num_rows > 0;
    return $cached;
}

/**
 * إنشاء مستخدم جديد عبر فيسبوك (بريد مفعّل مسبقاً).
 * @return array<string,mixed>|null صف المستخدم بعد الإدراج أو null
 */
function register_user_from_facebook(string $fbId, string $email, string $fullName): ?array {
    if (!users_table_has_facebook_id()) {
        return null;
    }
    $conn = db();
    $hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
    $role = 'student';
    $lang = app_lang();
    $ev = 1;
    $vc = '';

        $sql = 'INSERT INTO users (full_name, email, password_hash, role, language_pref, email_verified, verification_code, facebook_id)
            VALUES (?,?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('sssssiss', $fullName, $email, $hash, $role, $lang, $ev, $vc, $fbId);
    if (!$stmt->execute()) {
        return null;
    }
    $newId = (int)$conn->insert_id;
    if ($newId <= 0) {
        return null;
    }
    $st2 = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st2->bind_param('i', $newId);
    $st2->execute();
    $row = $st2->get_result()->fetch_assoc();
    return $row ?: null;
}

function send_verification_email(string $email, string $code, string $password, string $fullName = '') {
    $lang = app_lang();
    $bund = build_verification_email_vars($email, $code, $password, $fullName);
    $varsHtml = $bund['html'];
    $varsPlain = $bund['plain'];

    $subject = $lang === 'ar'
        ? get_system_setting('email_verify_subject_ar', '')
        : get_system_setting('email_verify_subject_en', '');
    if ($subject === '') {
        $subject = $lang === 'ar'
            ? 'تم تسجيلك بنجاح في منصة {{site_name}}'
            : 'Welcome — you are registered on {{site_name}}';
    }
    $subject = apply_email_placeholders($subject, $varsPlain);

    $templateKey = $lang === 'ar' ? 'email_verify_body_html_ar' : 'email_verify_body_html_en';
    $template = get_system_setting($templateKey, '');
    if ($template === '') {
        $template = $lang === 'ar' ? default_verify_email_template_ar() : default_verify_email_template_en();
    }

    $html = apply_email_placeholders($template, $varsHtml);

    $outboxId = null;
    if (table_has_column('email_outbox', 'id')) {
        $conn = db();
        $pwdEsc = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $htmlPreview = str_replace($pwdEsc, '***', $html);
        $stOut = $conn->prepare('INSERT INTO email_outbox (email_to, subject, verification_code, html_preview, sent_ok, error_message) VALUES (?, ?, NULL, ?, 0, NULL)');
        $stOut->bind_param('sss', $email, $subject, $htmlPreview);
        $stOut->execute();
        $outboxId = (int)$conn->insert_id;
    }

    $fromEmail = get_system_setting('mail_from_email', '');
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = get_system_setting('notification_email', '');
    }
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
        $fromEmail = 'noreply@' . preg_replace('/^www\./', '', $host);
    }

    $fromName = get_system_setting('mail_from_name', platform_display_name());

    $r = smtp_send_html($email, $subject, $html, $fromEmail, $fromName);
    if ($r['ok']) {
        if ($outboxId) {
            $up = db()->prepare('UPDATE email_outbox SET sent_ok = 1, error_message = NULL WHERE id = ?');
            $up->bind_param('i', $outboxId);
            $up->execute();
        }
        return true;
    }

    error_log('SMTP mail failed: ' . $r['error']);

    if (get_system_setting('allow_php_mail_fallback', '0') === '1') {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
        $ok = @mail($email, $subject, $html, $headers);
        if ($outboxId) {
            $up = db()->prepare('UPDATE email_outbox SET sent_ok = ?, error_message = ? WHERE id = ?');
            $err = $ok ? null : (string)($r['error'] ?? 'PHP mail fallback failed');
            $sentOk = $ok ? 1 : 0;
            $up->bind_param('isi', $sentOk, $err, $outboxId);
            $up->execute();
        }
        return (bool)$ok;
    }

    if ($outboxId) {
        $up = db()->prepare('UPDATE email_outbox SET error_message = ? WHERE id = ?');
        $err = (string)($r['error'] ?? 'SMTP failed');
        $up->bind_param('si', $err, $outboxId);
        $up->execute();
    }
    return false;
}

function verify_email($email, $code) {
    $conn = db();
    $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires_at = NULL, verification_sent_at = NULL WHERE email = ? AND verification_code = ? AND email_verified = 0 AND verification_expires_at IS NOT NULL AND verification_expires_at >= NOW()");
    $stmt->bind_param('ss', $email, $code);
    return $stmt->execute();
}

function resend_verification_email($email) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND email_verified = 0 AND verification_code IS NOT NULL LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $sentAt = strtotime((string)($user['verification_sent_at'] ?? ''));
        if ($sentAt > 0 && (time() - $sentAt) < 60) {
            return false;
        }
        $oldCode = (string)($user['verification_code'] ?? '');
        $oldHash = (string)($user['password_hash'] ?? '');
        $oldExpiresAt = $user['verification_expires_at'] ?? null;
        $oldSentAt = $user['verification_sent_at'] ?? null;
        $newPassword = generate_temp_password();
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $code = sprintf('%06d', random_int(0, 999999));
        $expiresAt = date('Y-m-d H:i:s', time() + 900);
        $newSentAt = date('Y-m-d H:i:s');
        $uid = (int)$user['id'];
        $up = $conn->prepare('UPDATE users SET password_hash = ?, temp_password = NULL, verification_code = ?, verification_expires_at = ?, verification_sent_at = ? WHERE id = ?');
        $up->bind_param('ssssi', $newHash, $code, $expiresAt, $newSentAt, $uid);
        $up->execute();
        $sent = send_verification_email($email, $code, $newPassword, (string)($user['full_name'] ?? ''));
        if (!$sent) {
            $rb = $conn->prepare('UPDATE users SET password_hash = ?, verification_code = ?, verification_expires_at = ?, verification_sent_at = ? WHERE id = ?');
            $rb->bind_param('ssssi', $oldHash, $oldCode, $oldExpiresAt, $oldSentAt, $uid);
            $rb->execute();
            return false;
        }
        return true;
    }
    return false;
}

// Legacy function
function register_user($name, $email, $password) {
    return register_user_v2(compact('name', 'email', 'password'));
}


function login_user($email, $password) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function logout_user() {
    unset($_SESSION['user']);
}

function add_to_cart($userId, $courseId) {
    $conn = db();
    $stmt = $conn->prepare("INSERT IGNORE INTO cart_items (user_id, course_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $userId, $courseId);
    return $stmt->execute();
}

function remove_from_cart($userId, $courseId) {
    $conn = db();
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param('ii', $userId, $courseId);
    return $stmt->execute();
}

function get_cart($userId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT ci.id, c.id AS course_id, c.title_ar, c.title_en, c.price,
                            COALESCE(c.is_free, 0) AS is_free,
                            IFNULL(NULLIF(TRIM(c.course_discount_type), ''), 'none') AS course_discount_type,
                            COALESCE(c.course_discount_value, 0) AS course_discount_value
                            FROM cart_items ci
                            JOIN courses c ON c.id = ci.course_id
                            WHERE ci.user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function create_order_from_cart($userId, $couponCode = null) {
    $conn = db();
    $cart = get_cart($userId);
    if (!$cart) {
        return false;
    }

    if ($couponCode === null || $couponCode === '') {
        $couponCode = $_SESSION['cart_coupon_code'] ?? '';
    }
    $couponCode = trim((string)$couponCode);

    $subtotal = 0.0;
    foreach ($cart as $item) {
        $subtotal += course_effective_pricing($item)['final_price'];
    }
    $subtotal = round($subtotal, 2);

    $discount = 0.0;
    $couponId = null;
    if ($couponCode !== '' && $subtotal > 0) {
        $stmt = $conn->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('s', $couponCode);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        if ($coupon) {
            $couponId = (int)$coupon['id'];
            if ($coupon['discount_type'] === 'percent') {
                $discount = round($subtotal * ((float)$coupon['discount_value']) / 100, 2);
            } else {
                $discount = (float)$coupon['discount_value'];
            }
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
        }
    }

    $total = round(max(0, $subtotal - $discount), 2);
    if ($couponId === null) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, discount, total, status, coupon_id) VALUES (?, ?, ?, ?, 'pending', NULL)");
        $stmt->bind_param('iddd', $userId, $subtotal, $discount, $total);
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, discount, total, status, coupon_id) VALUES (?, ?, ?, ?, 'pending', ?)");
        $cid = (int)$couponId;
        $stmt->bind_param('idddi', $userId, $subtotal, $discount, $total, $cid);
    }
    if (!$stmt->execute()) {
        return false;
    }
    $orderId = $conn->insert_id;

    $stmtItem = $conn->prepare('INSERT INTO order_items (order_id, course_id, price) VALUES (?, ?, ?)');
    foreach ($cart as $item) {
        $cid = (int)$item['course_id'];
        $price = course_effective_pricing($item)['final_price'];
        $stmtItem->bind_param('iid', $orderId, $cid, $price);
        $stmtItem->execute();
    }

    $conn->query('DELETE FROM cart_items WHERE user_id = ' . (int)$userId);
    unset($_SESSION['cart_coupon_code']);
    return $orderId;
}

function simulate_payment($orderId, $success = true) {
    $conn = db();
    $order = $conn->query("SELECT * FROM orders WHERE id = " . (int)$orderId)->fetch_assoc();
    if (!$order) return false;

    $status = $success ? 'success' : 'failed';
    $txn = 'SBX-' . strtoupper(bin2hex(random_bytes(4)));
    $amount = (float)$order['total'];

    $pending = $conn->query("SELECT id FROM payments WHERE order_id = " . (int)$orderId . " AND status = 'pending' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($pending) {
        $paymentId = (int)$pending['id'];
        $stmt = $conn->prepare('UPDATE payments SET gateway = \'sandbox\', transaction_ref = ?, amount = ?, status = ?, paid_at = NOW() WHERE id = ?');
        $stmt->bind_param('sdsi', $txn, $amount, $status, $paymentId);
    } else {
        $stmt = $conn->prepare("INSERT INTO payments (order_id, gateway, transaction_ref, amount, status, paid_at) VALUES (?, 'sandbox', ?, ?, ?, NOW())");
        $stmt->bind_param('isds', $orderId, $txn, $amount, $status);
    }
    $stmt->execute();

    if ($success) {
        $conn->query("UPDATE orders SET status = 'paid' WHERE id = " . (int)$orderId);
    } else {
        $conn->query("UPDATE orders SET status = 'failed' WHERE id = " . (int)$orderId);
    }
    sync_enrollments_for_order((int)$orderId);

    return true;
}

function my_enrollments($userId) {
    $conn = db();
    $sql = "SELECT e.*, c.title_ar, c.title_en, c.thumbnail
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.user_id = " . (int)$userId . "
              AND e.status = 'active'
            ORDER BY e.id DESC";
    $res = $conn->query($sql);
    $rows = [];
    while ($res && $r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}
