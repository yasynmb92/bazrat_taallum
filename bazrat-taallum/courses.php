<?php
require_once __DIR__ . '/includes/header.php';
$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$categorySlug = trim((string)($_GET['category_slug'] ?? ''));
$sort = $_GET['sort'] ?? 'newest';
$allowedSort = ['newest', 'price_low', 'price_high', 'title'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'newest';
}
$courses = courses_attach_duration_minutes(fetch_courses_filtered([
    'q' => $q,
    'category_id' => $categoryId,
    'category_slug' => $categorySlug,
    'sort' => $sort,
]));
$categories = fetch_categories();
?>
<section class="container courses-page">
  <h1 class="page-title"><?= e(t('courses')) ?></h1>

  <form class="courses-toolbar" method="get" action="<?= e(route_url('courses/search')) ?>">
    <div class="toolbar-search">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search_placeholder')) ?>" aria-label="<?= e(t('search')) ?>">
      <?php if ($categoryId > 0): ?>
        <input type="hidden" name="category_id" value="<?= $categoryId ?>">
      <?php endif; ?>
      <button class="btn btn-primary" type="submit"><?= e(t('search')) ?></button>
    </div>
    <div class="toolbar-filters">
      <label class="sr-only" for="sort-courses"><?= e(t('sort_by')) ?></label>
      <select id="sort-courses" name="sort" onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e(t('sort_newest')) ?></option>
        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= e(t('sort_price_low')) ?></option>
        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= e(t('sort_price_high')) ?></option>
        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>><?= e(t('sort_title')) ?></option>
      </select>
    </div>
  </form>

  <div class="courses-layout">
    <aside class="courses-filters-aside">
      <h3><?= e(t('categories')) ?></h3>
      <ul class="filter-list">
        <li><a href="<?= e(route_url('courses' . ($q !== '' ? '/search?q=' . urlencode($q) : ''))) ?>" class="<?= $categoryId === 0 && $categorySlug === '' ? 'is-active' : '' ?>"><?= e(t('all_categories')) ?></a></li>
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= e(route_url('courses/' . slugify($cat['name_en']) . ($q !== '' ? '?q=' . urlencode($q) : ''))) ?>"
               class="<?= ($categoryId === (int)$cat['id'] || $categorySlug === slugify($cat['name_en'])) ? 'is-active' : '' ?>">
              <?= e(app_lang() === 'ar' ? $cat['name_ar'] : $cat['name_en']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>
    <div class="courses-results">
      <?php if (!$courses): ?>
        <p class="muted empty-courses"><?= e(t('no_courses_found')) ?></p>
      <?php else: ?>
        <p class="results-count"><?= count($courses) ?> <?= e(t('courses')) ?></p>
        <div class="course-grid">
          <?php foreach ($courses as $course): ?>
            <?php include __DIR__ . '/includes/course-card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
