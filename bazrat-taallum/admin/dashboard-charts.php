<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$conn = db();
?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> لوحة الإحصائيات المتقدمة</h1>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> العودة</a>
    </div>

    <div class="charts-dashboard">
        <div class="chart-row">
            <div class="chart-card">
                <h3>توزيع الدورات حسب المستوى</h3>
                <canvas id="levelPieChart" height="300"></canvas>
            </div>
            <div class="chart-card">
                <h3>الدورات حسب الفئات (عمودي)</h3>
                <canvas id="categoryBarChart" height="300"></canvas>
            </div>
        </div>
        <div class="chart-row">
            <div class="chart-card">
                <h3>حالة نشر الدورات</h3>
                <canvas id="statusDoughnutChart" height="300"></canvas>
            </div>
            <div class="chart-card">
                <h3>نمو التسجيلات الشهري</h3>
                <canvas id="enrollmentsLineChart" height="300"></canvas>
            </div>
        </div>
        <div class="chart-row">
            <div class="chart-card full-width">
                <h3>إجمالي الإيرادات حسب الشهر</h3>
                <canvas id="revenueLineChart" height="350"></canvas>
            </div>
        </div>
    </div>

    <div class="quick-stats">
        <div class="stat-item">
            <i class="fas fa-fire"></i>
            <span>أكثر دورة شعبية: <?= $conn->query("SELECT title_ar FROM courses ORDER BY enrolled_count DESC LIMIT 1")->fetch_assoc()['title_ar'] ?? 'لا توجد' ?></span>
        </div>
        <div class="stat-item">
            <i class="fas fa-trophy"></i>
            <span>أعلى إيراد: $<?= $conn->query("SELECT total FROM orders WHERE status='paid' ORDER BY total DESC LIMIT 1")->fetch_assoc()['total'] ?? 0 ?></span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php
// Course levels
$levels = $conn->query("SELECT level, COUNT(*) as count FROM courses GROUP BY level")->fetch_all(MYSQLI_ASSOC);
$levelLabels = array_column($levels, 'level');
$levelData = array_column($levels, 'count');

// Categories
$categories = $conn->query("SELECT COALESCE(cat.name_ar, 'بدون فئة') as name, COUNT(*) as count FROM courses c LEFT JOIN categories cat ON c.category_id = cat.id GROUP BY cat.id, name ORDER BY count DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$catLabels = array_column($categories, 'name');
$catData = array_column($categories, 'count');

// Status
$status = $conn->query("SELECT is_published, COUNT(*) as count FROM courses GROUP BY is_published")->fetch_all(MYSQLI_ASSOC);
$statusLabels = array_map(fn($s) => $s['is_published'] ? 'منشور' : 'مسودة', $status);
$statusData = array_column($status, 'count');

// Monthly enrollments (last 6 months)
$months = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM enrollments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month")->fetch_all(MYSQLI_ASSOC);
$monthLabels = array_column($months, 'month');
$monthData = array_column($months, 'count');

// Revenue
$revenue = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue FROM orders WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month")->fetch_all(MYSQLI_ASSOC);
$revenueLabels = array_column($revenue, 'month');
$revenueData = array_column($revenue, 'revenue');
?>

// Level Pie Chart
new Chart(document.getElementById('levelPieChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($levelLabels) ?>,
        datasets: [{
            data: <?= json_encode($levelData) ?>,
            backgroundColor: ['#48bb78', '#ed8936', '#4299e1', '#f56565']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Category Bar Chart
new Chart(document.getElementById('categoryBarChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            label: 'عدد الدورات',
            data: <?= json_encode($catData) ?>,
            backgroundColor: 'linear-gradient(45deg, #4299e1, #3182ce)'
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Status Doughnut
new Chart(document.getElementById('statusDoughnutChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: ['#48bb78', '#fed7d7']
        }]
    },
    options: { responsive: true, cutout: '60%' }
});

// Enrollments Line
new Chart(document.getElementById('enrollmentsLineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'التسجيلات',
            data: <?= json_encode($monthData) ?>,
            borderColor: '#4299e1',
            backgroundColor: 'rgba(66, 153, 225, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Revenue Line
new Chart(document.getElementById('revenueLineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($revenueLabels) ?>,
        datasets: [{
            label: 'الإيرادات $',
            data: <?= json_encode($revenueData) ?>,
            borderColor: '#48bb78',
            backgroundColor: 'rgba(72, 187, 120, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { callback: value => '$' + value.toLocaleString() }
            }
        }
    }
});
</script>

<style>
.charts-dashboard { display: flex; flex-direction: column; gap: 2rem; }
.chart-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem; }
.chart-card, .chart-card.full-width { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
.chart-card.full-width { grid-column: 1 / -1; }
.chart-card h3 { margin-bottom: 1.5rem; color: #2d3748; text-align: center; font-size: 1.3rem; }
canvas { max-height: 350px; width: 100% !important; height: auto !important; }
.quick-stats { background: white; border-radius: 16px; padding: 2rem; display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; }
.stat-item { display: flex; align-items: center; gap: 1rem; color: #4a5568; font-size: 1.1rem; }
.stat-item i { font-size: 2rem; color: #4299e1; }
@media (max-width: 768px) { .chart-row { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
