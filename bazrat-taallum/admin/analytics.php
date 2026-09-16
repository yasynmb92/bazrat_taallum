<?php
require_once __DIR__ . '/_layout.php';
$conn = db();

function table_exists_analytics(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $r = $conn->query("SHOW TABLES LIKE '{$t}'");
  return (bool)($r && $r->num_rows > 0);
}

$enrollCol = 'enrolled_at';
$r = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'enrolled_at'");
if (!$r || $r->num_rows === 0) {
    $r2 = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'created_at'");
    $enrollCol = ($r2 && $r2->num_rows > 0) ? 'created_at' : 'id';
}

$monthsEnroll = [];
if ($enrollCol !== 'id') {
    $q = "SELECT DATE_FORMAT(`$enrollCol`, '%Y-%m') AS m, COUNT(*) AS c FROM enrollments
          WHERE `$enrollCol` >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m ORDER BY m";
    $monthsEnroll = $conn->query($q)->fetch_all(MYSQLI_ASSOC) ?: [];
} else {
    $one = $conn->query('SELECT COUNT(*) AS c FROM enrollments')->fetch_assoc();
    $monthsEnroll = [['m' => date('Y-m'), 'c' => (int)($one['c'] ?? 0)]];
}

$revenue = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COALESCE(SUM(total),0) AS s FROM orders
    WHERE status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m ORDER BY m")->fetch_all(MYSQLI_ASSOC) ?: [];

$newUsers = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m ORDER BY m")->fetch_all(MYSQLI_ASSOC) ?: [];

$ordersByStatus = $conn->query("SELECT status, COUNT(*) AS c FROM orders GROUP BY status")->fetch_all(MYSQLI_ASSOC) ?: [];

$topCourses = $conn->query("SELECT c.title_ar AS t, COUNT(e.id) AS cnt FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    GROUP BY e.course_id, c.title_ar ORDER BY cnt DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC) ?: [];

$paySum = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE status='success'")->fetch_assoc();
$totalRevenueAll = (float)($paySum['s'] ?? 0);
$cntCourses = (int)($conn->query('SELECT COUNT(*) AS c FROM courses')->fetch_assoc()['c'] ?? 0);
$cntStudents = (int)($conn->query("SELECT COUNT(*) AS c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0);
$cntEnroll = (int)($conn->query('SELECT COUNT(*) AS c FROM enrollments')->fetch_assoc()['c'] ?? 0);
$cntCertificates = table_exists_analytics($conn, 'certificates')
  ? (int)($conn->query('SELECT COUNT(*) AS c FROM certificates')->fetch_assoc()['c'] ?? 0)
  : 0;
$cntActiveChallenges = table_exists_analytics($conn, 'weekly_challenges')
  ? (int)($conn->query("SELECT COUNT(*) AS c FROM weekly_challenges WHERE is_active = 1")->fetch_assoc()['c'] ?? 0)
  : 0;
$completionRate = 0.0;
if ($cntEnroll > 0) {
  $completedEnroll = (int)($conn->query("SELECT COUNT(*) AS c FROM enrollments WHERE progress >= 100 OR status = 'completed'")->fetch_assoc()['c'] ?? 0);
  $completionRate = round(($completedEnroll / max(1, $cntEnroll)) * 100, 1);
}

$labelsE = array_column($monthsEnroll, 'm');
$dataE = array_map('intval', array_column($monthsEnroll, 'c'));
if (!$labelsE) {
    $labelsE = [date('Y-m')];
    $dataE = [0];
}

$labelsR = array_column($revenue, 'm');
$dataR = array_map('floatval', array_column($revenue, 's'));
if (!$labelsR) {
    $labelsR = [date('Y-m')];
    $dataR = [0];
}

$labelsU = array_column($newUsers, 'm');
$dataU = array_map('intval', array_column($newUsers, 'c'));
if (!$labelsU) {
    $labelsU = [date('Y-m')];
    $dataU = [0];
}

$osLabels = array_column($ordersByStatus, 'status');
$osData = array_map('intval', array_column($ordersByStatus, 'c'));
if (!$osLabels) {
    $osLabels = ['—'];
    $osData = [0];
}

$tcLabels = array_map(function ($row) {
    $t = (string)($row['t'] ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($t, 0, 22, 'UTF-8') . (mb_strlen($t, 'UTF-8') > 22 ? '…' : '');
    }
    return strlen($t) > 22 ? substr($t, 0, 22) . '…' : $t;
}, $topCourses);
$tcData = array_map('intval', array_column($topCourses, 'cnt'));
if (!$tcLabels) {
    $tcLabels = ['—'];
    $tcData = [0];
}

render_admin_layout_start('التقارير والإحصائيات', 'analytics.php');
?>
<div class="analytics-kpis" style="margin-bottom:18px">
  <div class="analytics-kpi"><strong>إجمالي المدفوعات (نجاح)</strong><span style="color:#16a34a">$<?= number_format($totalRevenueAll, 2) ?></span></div>
  <div class="analytics-kpi"><strong>الدورات</strong><span><?= $cntCourses ?></span></div>
  <div class="analytics-kpi"><strong>الطلاب</strong><span><?= $cntStudents ?></span></div>
  <div class="analytics-kpi"><strong>التسجيلات في الدورات</strong><span><?= $cntEnroll ?></span></div>
  <div class="analytics-kpi"><strong>نسبة الإكمال</strong><span><?= number_format($completionRate, 1) ?>%</span></div>
  <div class="analytics-kpi"><strong>الشهادات الممنوحة</strong><span><?= $cntCertificates ?></span></div>
  <div class="analytics-kpi"><strong>تحديات أسبوعية نشطة</strong><span><?= $cntActiveChallenges ?></span></div>
</div>

<div class="analytics-grid" style="margin-bottom:16px">
  <div class="chart-card"><div class="head">التسجيلات في الدورات (6 أشهر)</div><div class="body"><canvas id="enrollChart" height="220"></canvas></div></div>
  <div class="chart-card"><div class="head">إيرادات الطلبات المدفوعة (6 أشهر)</div><div class="body"><canvas id="revChart" height="220"></canvas></div></div>
</div>

<div class="analytics-grid" style="margin-bottom:16px">
  <div class="chart-card"><div class="head">مستخدمون جدد (6 أشهر)</div><div class="body"><canvas id="usersChart" height="220"></canvas></div></div>
  <div class="chart-card"><div class="head">الطلبات حسب الحالة</div><div class="body"><canvas id="ordersPie" height="220"></canvas></div></div>
</div>

<div class="chart-card" style="margin-bottom:24px">
  <div class="head">أكثر الدورات تسجيلاً</div>
  <div class="body"><canvas id="topCoursesChart" height="260"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'IBM Plex Sans Arabic', sans-serif";
new Chart(document.getElementById('enrollChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labelsE, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'التسجيلات', data: <?= json_encode($dataE) ?>, backgroundColor: '#0f766e', borderRadius: 6 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('revChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labelsR, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      label: 'الإيراد ($)',
      data: <?= json_encode($dataR) ?>,
      borderColor: '#0f766e',
      backgroundColor: 'rgba(15,118,110,.12)',
      fill: true,
      tension: 0.35
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('usersChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labelsU, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'مستخدمون', data: <?= json_encode($dataU) ?>, backgroundColor: '#14b8a6', borderRadius: 6 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('ordersPie'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($osLabels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      data: <?= json_encode($osData) ?>,
      backgroundColor: ['#0f766e', '#14b8a6', '#ef4444', '#f59e0b', '#94a3b8', '#64748b']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('topCoursesChart'), {
  type: 'bar',
  indexAxis: 'y',
  data: {
    labels: <?= json_encode($tcLabels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'عدد التسجيلات', data: <?= json_encode($tcData) ?>, backgroundColor: '#0f766e', borderRadius: 4 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
});
</script>
<?php render_admin_layout_end(); ?>
