<?php
require_once __DIR__ . '/includes/header.php';
$stats = platform_stats();
$heroImg = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
$aboutImg = 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
?>
<main class="home-page">
  <section class="home-hero" aria-labelledby="home-hero-title">
    <div class="container home-hero-grid">
      <div class="home-hero-copy">
        <h1 id="home-hero-title"><?= e(t('hero_title')) ?></h1>
        <p class="home-hero-lead"><?= e(t('hero_desc')) ?></p>
        <form class="home-hero-search" method="get" action="<?= e(route_url('courses/search')) ?>" role="search">
          <input type="search" name="q" placeholder="<?= e(t('search_placeholder')) ?>" aria-label="<?= e(t('search')) ?>">
          <button class="btn" type="submit"><?= e(t('search')) ?></button>
        </form>
        <div class="home-hero-actions">
          <a class="btn btn-home-primary" href="<?= e(route_url('courses')) ?>"><?= e(t('explore_courses')) ?></a>
          <a class="btn btn-home-outline" href="#home-about"><?= e(t('learn_more')) ?></a>
          <?php if (!is_logged_in()): ?>
            <a class="btn btn-home-outline" href="<?= APP_URL ?>/register.php"><?= e(t('register')) ?></a>
          <?php endif; ?>
        </div>
        <div class="home-hero-stats" role="group" aria-label="<?= e(t('stats_hero_summary')) ?>">
          <div class="home-hero-stat">
            <strong><?= number_format($stats['courses']) ?></strong>
            <span><?= e(t('stats_courses')) ?></span>
          </div>
          <div class="home-hero-stat">
            <strong><?= number_format($stats['students']) ?></strong>
            <span><?= e(t('stats_students')) ?></span>
          </div>
          <div class="home-hero-stat">
            <strong><?= number_format($stats['instructors']) ?></strong>
            <span><?= e(t('stats_instructors')) ?></span>
          </div>
          <div class="home-hero-stat">
            <strong><?php
              $lh = (float)($stats['learning_hours'] ?? 0);
            echo e($lh >= 100 ? number_format($lh, 0) : number_format($lh, 1));
            ?></strong>
            <span><?= e(t('stats_learning_hours')) ?></span>
          </div>
        </div>
      </div>
      <div class="home-hero-media">
        <img src="<?= e($heroImg) ?>" alt="" width="800" height="600" loading="eager" decoding="async">
      </div>
    </div>
  </section>

  <section class="home-features" aria-labelledby="home-features-title">
    <div class="container">
      <div class="home-section-head">
        <h2 id="home-features-title"><?= e(t('home_why_title')) ?></h2>
        <p><?= e(t('home_why_sub')) ?></p>
      </div>
      <div class="home-features-grid">
        <article class="home-feature-card">
          <div class="home-feature-icon" aria-hidden="true"><i class="fa-solid fa-laptop-code"></i></div>
          <h3><?= e(t('home_feat_1_title')) ?></h3>
          <p><?= e(t('home_feat_1_desc')) ?></p>
        </article>
        <article class="home-feature-card">
          <div class="home-feature-icon" aria-hidden="true"><i class="fa-solid fa-infinity"></i></div>
          <h3><?= e(t('home_feat_2_title')) ?></h3>
          <p><?= e(t('home_feat_2_desc')) ?></p>
        </article>
        <article class="home-feature-card">
          <div class="home-feature-icon" aria-hidden="true"><i class="fa-solid fa-chalkboard-user"></i></div>
          <h3><?= e(t('home_feat_3_title')) ?></h3>
          <p><?= e(t('home_feat_3_desc')) ?></p>
        </article>
        <article class="home-feature-card">
          <div class="home-feature-icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></div>
          <h3><?= e(t('home_feat_4_title')) ?></h3>
          <p><?= e(t('home_feat_4_desc')) ?></p>
        </article>
      </div>
    </div>
  </section>

  <section class="home-about" id="home-about" aria-labelledby="home-about-title">
    <div class="container home-about-grid">
      <div class="home-about-media">
        <img src="<?= e($aboutImg) ?>" alt="" width="800" height="600" loading="lazy" decoding="async">
      </div>
      <div class="home-about-text">
        <h2 id="home-about-title"><?= e(t('home_about_title')) ?></h2>
        <p><?= e(t('home_about_p1')) ?></p>
        <p><?= e(t('home_about_p2')) ?></p>
        <ul class="home-about-list">
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e(t('home_about_li1')) ?></li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e(t('home_about_li2')) ?></li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e(t('home_about_li3')) ?></li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e(t('home_about_li4')) ?></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="container section-cats">
    <h2 class="section-title"><?= e(t('browse_categories')) ?></h2>
    <div class="category-pills">
      <a class="category-pill" href="<?= e(route_url('courses')) ?>"><?= e(t('all_categories')) ?></a>
      <?php foreach (fetch_categories() as $cat): ?>
        <a class="category-pill" href="<?= e(route_url('courses/' . slugify($cat['name_en']))) ?>">
          <?= e(app_lang() === 'ar' ? $cat['name_ar'] : $cat['name_en']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="container section-featured">
    <div class="section-head">
      <h2 class="section-title"><?= e(t('featured_courses')) ?></h2>
      <a class="section-link" href="<?= e(route_url('courses')) ?>"><?= e(t('view_all_courses')) ?> →</a>
    </div>
    <div class="course-grid">
      <?php foreach (array_slice(courses_attach_duration_minutes(fetch_all_courses()), 0, 6) as $course): ?>
        <?php include __DIR__ . '/includes/course-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="home-cta" aria-labelledby="home-cta-title">
    <div class="container">
      <h2 id="home-cta-title"><?= e(t('home_cta_title')) ?></h2>
      <p><?= e(t('home_cta_desc')) ?></p>
      <a class="btn" href="<?= e(route_url('courses')) ?>"><?= e(t('explore_courses')) ?></a>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
