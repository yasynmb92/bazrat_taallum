<?php
require_once __DIR__ . '/_layout.php';
$conn = db();

function month_range_bounds(int $offsetMonths = 0): array {
  $base = new DateTime('first day of this month 00:00:00');
  if ($offsetMonths !== 0) {
    $base->modify(($offsetMonths > 0 ? '+' : '') . $offsetMonths . ' month');
  }
  $start = $base->format('Y-m-d H:i:s');
  $endObj = clone $base;
  $endObj->modify('+1 month');
  $end = $endObj->format('Y-m-d H:i:s');
  return [$start, $end];
}

function month_metric_sum(mysqli $conn, string $table, string $dateCol, string $sumExpr = 'COUNT(*)', string $where = '1=1'): float {
  [$start, $end] = month_range_bounds(0);
  $st = $conn->prepare("SELECT COALESCE($sumExpr,0) AS v FROM {$table} WHERE {$where} AND {$dateCol} >= ? AND {$dateCol} < ?");
  $st->bind_param('ss', $start, $end);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  return (float)($row['v'] ?? 0);
}

function prev_month_metric_sum(mysqli $conn, string $table, string $dateCol, string $sumExpr = 'COUNT(*)', string $where = '1=1'): float {
  [$start, $end] = month_range_bounds(-1);
  $st = $conn->prepare("SELECT COALESCE($sumExpr,0) AS v FROM {$table} WHERE {$where} AND {$dateCol} >= ? AND {$dateCol} < ?");
  $st->bind_param('ss', $start, $end);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  return (float)($row['v'] ?? 0);
}

function build_trend(float $current, float $previous): array {
  $delta = $current - $previous;
  if ($previous <= 0.00001) {
    if ($current > 0.00001) {
      return ['dir' => 'up', 'pct' => 100.0, 'delta' => $delta];
    }
    return ['dir' => 'flat', 'pct' => 0.0, 'delta' => 0.0];
  }
  $pct = ($delta / $previous) * 100.0;
  $dir = 'flat';
  if ($pct > 0.0001) {
    $dir = 'up';
  } elseif ($pct < -0.0001) {
    $dir = 'down';
  }
  return ['dir' => $dir, 'pct' => round(abs($pct), 1), 'delta' => round($delta, 2)];
}

function render_trend_chip(array $t): string {
  if (($t['dir'] ?? 'flat') === 'up') {
    return '<span class="trend-chip trend-up"><i class="fa-solid fa-arrow-trend-up"></i> ' . e((string)$t['pct']) . '%</span>';
  }
  if (($t['dir'] ?? 'flat') === 'down') {
    return '<span class="trend-chip trend-down"><i class="fa-solid fa-arrow-trend-down"></i> ' . e((string)$t['pct']) . '%</span>';
  }
  return '<span class="trend-chip trend-flat"><i class="fa-solid fa-minus"></i> 0%</span>';
}

$stats = [];
$stats['courses'] = (int)$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'];
$stats['users'] = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$stats['enrollments'] = (int)$conn->query("SELECT COUNT(*) c FROM enrollments")->fetch_assoc()['c'];
$stats['orders'] = (int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$stats['revenue'] = (float)$conn->query("SELECT COALESCE(SUM(total), 0) c FROM orders WHERE status = 'paid'")->fetch_assoc()['c'];

$trends = [];
$trends['courses'] = build_trend(
  month_metric_sum($conn, 'courses', 'created_at'),
  prev_month_metric_sum($conn, 'courses', 'created_at')
);
$trends['users'] = build_trend(
  month_metric_sum($conn, 'users', 'created_at', 'COUNT(*)', "role != 'admin'"),
  prev_month_metric_sum($conn, 'users', 'created_at', 'COUNT(*)', "role != 'admin'")
);
$enrollDateCol = null;
$chkEnroll = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'enrolled_at'");
if ($chkEnroll && $chkEnroll->num_rows > 0) {
  $enrollDateCol = 'enrolled_at';
} else {
  $chk2 = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'created_at'");
  if ($chk2 && $chk2->num_rows > 0) {
    $enrollDateCol = 'created_at';
  }
}
if ($enrollDateCol !== null) {
  $trends['enrollments'] = build_trend(
    month_metric_sum($conn, 'enrollments', $enrollDateCol),
    prev_month_metric_sum($conn, 'enrollments', $enrollDateCol)
  );
} else {
  $trends['enrollments'] = ['dir' => 'flat', 'pct' => 0.0, 'delta' => 0.0];
}
$trends['orders'] = build_trend(
  month_metric_sum($conn, 'orders', 'created_at'),
  prev_month_metric_sum($conn, 'orders', 'created_at')
);
$trends['revenue'] = build_trend(
  month_metric_sum($conn, 'orders', 'created_at', 'SUM(total)', "status = 'paid'"),
  prev_month_metric_sum($conn, 'orders', 'created_at', 'SUM(total)', "status = 'paid'")
);

$courses = $conn->query("SELECT c.id, c.title_ar, c.level, c.price, c.is_published, c.created_at, COALESCE(cat.name_ar, 'بدون فئة') AS category_ar
  FROM courses c LEFT JOIN categories cat ON cat.id = c.category_id ORDER BY c.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$recentOrders = $conn->query("SELECT o.id, o.total, o.status, o.created_at, u.full_name
  FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

render_admin_layout_start('لوحة التحكم', 'index.php');
?>

<section class="dashboard-hero">
  <div class="hero-card">
    <h2>لوحة التحكم</h2>
    <p>متابعة شاملة لحالة المنصة: الدورات، المستخدمون، التسجيلات، الطلبات، والإيرادات مع وصول سريع لأحدث العناصر.</p>
  </div>
  <div class="hero-metrics">
    <div class="metric-pill"><span class="label">الطلبات</span><span class="value"><?= number_format($stats['orders']) ?></span></div>
    <div class="metric-pill"><span class="label">الإيرادات</span><span class="value">$<?= number_format($stats['revenue'],2) ?></span></div>
    <div class="metric-pill"><span class="label">الدورات</span><span class="value"><?= number_format($stats['courses']) ?></span></div>
    <div class="metric-pill"><span class="label">المستخدمون</span><span class="value"><?= number_format($stats['users']) ?></span></div>
  </div>
</section>

<section class="dashboard-kpis">
  <a class="kpi-card" href="courses.php"><span class="kpi-title">الدورات</span><strong class="kpi-value"><?= number_format($stats['courses']) ?></strong><?= render_trend_chip($trends['courses']) ?></a>
  <a class="kpi-card" href="users.php"><span class="kpi-title">المستخدمون</span><strong class="kpi-value"><?= number_format($stats['users']) ?></strong><?= render_trend_chip($trends['users']) ?></a>
  <a class="kpi-card" href="enrollments.php"><span class="kpi-title">التسجيلات</span><strong class="kpi-value"><?= number_format($stats['enrollments']) ?></strong><?= render_trend_chip($trends['enrollments']) ?></a>
  <a class="kpi-card" href="orders.php"><span class="kpi-title">الطلبات</span><strong class="kpi-value"><?= number_format($stats['orders']) ?></strong><?= render_trend_chip($trends['orders']) ?></a>
  <a class="kpi-card" href="payments.php"><span class="kpi-title">الإيرادات</span><strong class="kpi-value">$<?= number_format($stats['revenue'],2) ?></strong><?= render_trend_chip($trends['revenue']) ?></a>
</section>

<section class="grid-2">
  <div class="table-container">
    <div class="table-header"><strong>أحدث الدورات</strong></div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead><tr><th>العنوان</th><th>الفئة</th><th>السعر</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($courses as $course): ?>
            <tr>
              <td><?= e($course['title_ar']) ?></td>
              <td><?= e($course['category_ar']) ?></td>
              <td>$<?= number_format((float)$course['price'],2) ?></td>
              <td><span class="status <?= (int)$course['is_published'] ? 'published' : 'draft' ?>"><?= (int)$course['is_published'] ? 'منشور' : 'مسودة' ?></span></td>
              <td><a class="btn btn-sm btn-primary" href="course-edit.php?id=<?= (int)$course['id'] ?>">تعديل</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-container">
    <div class="table-header"><strong>أحدث الطلبات</strong></div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead><tr><th>#</th><th>العميل</th><th>الإجمالي</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td>#<?= (int)$order['id'] ?></td>
              <td><?= e($order['full_name']) ?></td>
              <td>$<?= number_format((float)$order['total'],2) ?></td>
              <td><span class="status <?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
              <td><a class="btn btn-sm btn-info" href="order-details.php?id=<?= (int)$order['id'] ?>">تفاصيل</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php render_admin_layout_end(); ?>
