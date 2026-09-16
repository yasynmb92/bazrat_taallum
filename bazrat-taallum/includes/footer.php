<footer class="footer site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <strong><?= e(t('platform_name')) ?></strong>
      <p><?= e(t('footer_tagline')) ?></p>
    </div>
    <div>
      <h4><?= e(t('footer_courses')) ?></h4>
      <ul class="footer-links">
        <li><a href="<?= APP_URL ?>/courses.php"><?= e(t('courses')) ?></a></li>
        <li><a href="<?= APP_URL ?>/index.php"><?= e(t('featured_courses')) ?></a></li>
      </ul>
    </div>
    <div>
      <h4><?= e(t('footer_help')) ?></h4>
      <ul class="footer-links">
        <li><a href="<?= APP_URL ?>/login.php"><?= e(t('login')) ?></a></li>
        <li><a href="<?= APP_URL ?>/register.php"><?= e(t('register')) ?></a></li>
      </ul>
    </div>
    <div>
      <h4><?= e(t('footer_contact')) ?></h4>
      <p class="muted small"><?= e(t('footer_rights')) ?></p>
    </div>
  </div>
  <div class="footer-bar">
    <div class="container">
      © <?= date('Y') ?> <?= e(t('platform_name')) ?> — <?= e(t('all_rights')) ?>
    </div>
  </div>
</footer>
</body>
</html>
