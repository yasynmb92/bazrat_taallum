<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
$conn = db();
?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> إحصائيات الدورات</h1>
        <a href="index.php" class="btn btn-secondary">← العودة</a>
    </div>

    <div class="stats-grid">
        <div class="chart-container">
            <h3>توزيع المستويات</h3>
            <canvas id="levelsChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>الدورات حسب الفئات</h3>
            <canvas id="categoriesChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>حالة النشر</h3>
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php
$levels = $conn->query("SELECT level, COUNT(*) as count FROM courses GROUP BY level")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT cat.name_ar, COUNT(*) as count FROM courses c LEFT JOIN categories cat ON c.category_id = cat.id GROUP BY cat.id")->fetch_all(MYSQLI_ASSOC);
$status = $conn->query("SELECT is_published, COUNT(*) as count FROM courses GROUP BY is_published")->fetch_all(MYSQLI_ASSOC);
?>

const levelsData = <?= json_encode($levels) ?>;
const categoriesData = <?= json_encode($categories) ?>;
const statusData = <?= json_encode($status) ?>;

new Chart(document.getElementById('levelsChart'), {
    type: 'doughnut',
    data: {
        labels: levelsData.map(l => ['beginner','intermediate','advanced'][l.level] || l.level),
        datasets: [{ data: levelsData.map(l => l.count), backgroundColor: ['#48bb78', '#ed8936', '#4299e1'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('categoriesChart'), {
    type: 'bar',
    data: {
        labels: categoriesData.map(c => c.name_ar || 'بدون فئة'),
        datasets: [{ label: 'عدد الدورات', data: categoriesData.map(c => c.count), backgroundColor: '#4299e1' }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: statusData.map(s => s.is_published ? 'منشور' : 'مسودة'),
        datasets: [{ data: statusData.map(s => s.count), backgroundColor: ['#48bb78', '#fed7d7'] }]
    }
});
</script>

<style>
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; }
.chart-container { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.chart-container h3 { margin-bottom: 1.5rem; color: #2d3748; text-align: center; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

