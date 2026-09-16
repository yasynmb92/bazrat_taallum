<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
csrf_enforce_on_post();

$userId = (int)current_user()['id'];
$appliedCoupon = trim((string)($_POST['coupon'] ?? $_SESSION['cart_coupon_code'] ?? ''));
$cartPreview = compute_cart_totals($userId, $appliedCoupon);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['pay'])) {
    $coupon = trim((string)($_POST['coupon'] ?? ''));
    if ($coupon === '') {
        $coupon = (string)($_SESSION['cart_coupon_code'] ?? '');
    }
    $orderId = create_order_from_cart($userId, $coupon !== '' ? $coupon : null);
    if ($orderId) {
        simulate_payment($orderId, true);
        $conn = db();
        $first = $conn->query('SELECT course_id FROM order_items WHERE order_id = ' . (int)$orderId . ' ORDER BY id ASC LIMIT 1')->fetch_assoc();
        if ($first) {
            $cid = (int)$first['course_id'];
            $lid = get_first_lesson_id($cid);
            if ($lid) {
                header('Location: ' . APP_URL . '/learn.php?course_id=' . $cid . '&lesson_id=' . $lid);
                exit;
            }
            header('Location: ' . route_url('course.php?id=' . $cid));
            exit;
        }
        header('Location: ' . route_url('my-learning.php'));
        exit;
    }
    $msg = 'تعذر إتمام الدفع. تأكد أن السلة تحتوي على دورات.';
}
  require_once __DIR__ . '/includes/header.php';
?>
<section class="container checkout-page">
  <h1 class="page-title"><?= e(t('checkout')) ?></h1>
  <?php if ($msg): ?>
    <div class="alert alert-danger"><?= e($msg) ?></div>
  <?php endif; ?>

  <div class="checkout-card">
    <h2>إتمام الطلب</h2>
    <?php if (empty($cartPreview['lines'])): ?>
      <p class="muted">السلة فارغة.</p>
      <a class="btn btn-primary" href="<?= e(route_url('cart.php')) ?>"><?= e(t('cart')) ?></a>
    <?php else: ?>
      <ul class="checkout-lines muted" style="list-style:none;padding:0;margin:0 0 16px">
        <?php foreach ($cartPreview['lines'] as $line): ?>
          <li style="display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-bottom:1px solid #eee">
            <span><?= e(app_lang() === 'ar' ? $line['title_ar'] : $line['title_en']) ?></span>
            <strong>$<?= number_format($line['pricing']['final_price'], 2) ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="checkout-total-line"><span><?= e(t('subtotal_courses')) ?></span> <strong>$<?= number_format($cartPreview['subtotal'], 2) ?></strong></p>
      <?php if ($cartPreview['discount'] > 0): ?>
        <p class="checkout-total-line checkout-discount"><span><?= e(t('coupon_discount_line')) ?></span> <strong>−$<?= number_format($cartPreview['discount'], 2) ?></strong></p>
      <?php endif; ?>
      <p class="checkout-total-line checkout-grand"><span><?= e(t('grand_total_pay')) ?></span> <strong>$<?= number_format($cartPreview['total'], 2) ?></strong></p>

      <form method="post" class="checkout-form">
        <?= csrf_field() ?>
        <input type="hidden" name="pay" value="1">
        <label class="muted small"><?= e(t('coupon_code')) ?> (<?= e(t('optional')) ?>)</label>
        <input type="text" name="coupon" value="<?= e($appliedCoupon) ?>" style="max-width:320px;margin-bottom:12px">
        <button class="btn btn-primary btn-lg" type="submit"><?= e(t('checkout')) ?> — $<?= number_format($cartPreview['total'], 2) ?></button>
      </form>
      <p class="muted small"><?= e(t('checkout_redirect_hint')) ?></p>
    <?php endif; ?>
    <div class="auth-actions-row" style="margin-top:16px">
      <a class="btn btn-outline" href="<?= e(route_url('cart.php')) ?>"><?= e(t('cart')) ?></a>
      <a class="btn btn-outline" href="<?= e(route_url('my-learning.php')) ?>"><?= e(t('my_learning')) ?></a>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
