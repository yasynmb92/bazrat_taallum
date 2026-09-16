<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
csrf_enforce_on_post();

$userId = (int)current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_course_id'])) {
        remove_from_cart($userId, (int)$_POST['remove_course_id']);
        header('Location: ' . route_url('cart.php'));
        exit;
    }
    if (isset($_POST['apply_coupon'])) {
        $code = strtoupper(trim((string)($_POST['coupon_code'] ?? '')));
        if ($code === '') {
            unset($_SESSION['cart_coupon_code']);
        } else {
            $_SESSION['cart_coupon_code'] = $code;
        }
        header('Location: ' . route_url('cart.php'));
        exit;
    }
}

$items = get_cart($userId);
$appliedCoupon = (string)($_SESSION['cart_coupon_code'] ?? '');
$summary = compute_cart_totals($userId, $appliedCoupon);
require_once __DIR__ . '/includes/header.php';
?>
<section class="container cart-page">
  <h1 class="page-title"><?= e(t('cart')) ?></h1>

  <?php if (!$items): ?>
    <div class="empty-learning">
      <p class="muted">السلة فارغة حالياً.</p>
      <a class="btn btn-primary" href="<?= e(route_url('courses')) ?>"><?= e(t('explore_courses')) ?></a>
    </div>
  <?php else: ?>
    <div class="cart-grid">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th><?= e(t('courses')) ?></th><th><?= e(t('price')) ?></th><th>إجراء</th></tr></thead>
          <tbody>
          <?php foreach ($summary['lines'] as $index => $line): ?>
            <?php $p = $line['pricing']; ?>
            <tr>
              <td><?= (int)$index + 1 ?></td>
              <td><?= e(app_lang() === 'ar' ? $line['title_ar'] : $line['title_en']) ?></td>
              <td>
                <?php if ($p['has_discount'] && $p['list_price'] > $p['final_price']): ?>
                  <span class="price-was">$<?= number_format($p['list_price'], 2) ?></span>
                  <strong class="price-now">$<?= number_format($p['final_price'], 2) ?></strong>
                <?php else: ?>
                  <strong>$<?= number_format($p['final_price'], 2) ?></strong>
                <?php endif; ?>
              </td>
              <td>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="remove_course_id" value="<?= (int)$line['course_id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit">حذف</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <aside class="cart-summary">
        <h2>ملخص الطلب</h2>
        <p class="cart-line"><span><?= e(t('subtotal_courses')) ?></span><strong>$<?= number_format($summary['subtotal'], 2) ?></strong></p>
        <?php if ($appliedCoupon !== ''): ?>
          <p class="cart-line muted small"><?= e(t('coupon_applied')) ?>: <code><?= e($appliedCoupon) ?></code>
            <?php if (!$summary['coupon_ok']): ?> — <?= e(t('coupon_invalid')) ?><?php endif; ?>
          </p>
        <?php endif; ?>
        <?php if ($summary['discount'] > 0): ?>
          <p class="cart-line cart-line--discount"><span><?= e(t('coupon_discount_line')) ?></span><strong>−$<?= number_format($summary['discount'], 2) ?></strong></p>
        <?php endif; ?>
        <p class="cart-line cart-line--total"><span><?= e(t('grand_total_pay')) ?></span><strong>$<?= number_format($summary['total'], 2) ?></strong></p>

        <form method="post" class="cart-coupon-form">
          <?= csrf_field() ?>
          <label><?= e(t('coupon_code')) ?></label>
          <input type="text" name="coupon_code" value="<?= e($appliedCoupon) ?>" placeholder="" autocomplete="off">
          <button class="btn btn-outline btn-block" type="submit" name="apply_coupon" value="1"><?= e(t('apply_coupon')) ?></button>
        </form>

        <form method="post" action="<?= e(route_url('checkout.php')) ?>" class="cart-checkout-form">
          <?= csrf_field() ?>
          <input type="hidden" name="pay" value="1">
          <?php if ($appliedCoupon !== ''): ?>
            <input type="hidden" name="coupon" value="<?= e($appliedCoupon) ?>">
          <?php endif; ?>
          <button class="btn btn-success btn-block" type="submit"><?= e(t('checkout')) ?> — $<?= number_format($summary['total'], 2) ?></button>
        </form>
      </aside>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
