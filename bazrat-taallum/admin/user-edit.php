<?php
require_once __DIR__ . '/_layout.php';

$id = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
$conn = db();
$message = '';
$error = '';

$user = null;
if ($id > 0) {
    $st = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->bind_param('i', $id);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
}

if (!$user || ($user['role'] ?? '') === 'admin') {
    http_response_code(404);
    render_admin_layout_start('مستخدم غير موجود', 'users.php');
    echo '<p>لا يمكن تعديل هذا المستخدم.</p>';
    render_admin_layout_end();
    exit;
}

$countries = countries_dial_sorted_for_lang('ar');
$permissionsSupported = users_table_has_permissions_json();
$permissionLabels = assignable_user_permissions();
$currentPermissions = user_permissions_list($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === $id && $user['role'] !== 'admin') {
        $del = $conn->prepare('DELETE FROM users WHERE id = ? AND role != ?');
        $adm = 'admin';
        $del->bind_param('is', $uid, $adm);
        $del->execute();
        header('Location: users.php?deleted=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = (string)($_POST['role'] ?? 'student');
    if (!in_array($role, ['student', 'instructor'], true)) {
        $role = 'student';
    }
    $birth_year = ($_POST['birth_year'] ?? '') === '' ? null : (int)$_POST['birth_year'];
    $birth_month = ($_POST['birth_month'] ?? '') === '' ? null : (int)$_POST['birth_month'];
    $phone_country_code = trim((string)($_POST['phone_country_code'] ?? ''));
    $phone_number = trim((string)($_POST['phone_number'] ?? ''));
    $whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $state = trim((string)($_POST['state'] ?? ''));
    $parent_name = trim((string)($_POST['parent_name'] ?? ''));
    $parent_phone = trim((string)($_POST['parent_phone'] ?? ''));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = (string)($_POST['new_password'] ?? '');
    $unlink_fb = isset($_POST['unlink_facebook']) && users_table_has_facebook_id();
    $selectedPermissions = [];
    if ($permissionsSupported) {
        $posted = $_POST['permissions'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        $allowedPerms = array_keys($permissionLabels);
        foreach ($posted as $perm) {
            $perm = (string)$perm;
            if (in_array($perm, $allowedPerms, true)) {
                $selectedPermissions[] = $perm;
            }
        }
        $selectedPermissions = array_values(array_unique($selectedPermissions));
    }

    $form = [
        'full_name' => $full_name,
        'email' => $email,
        'phone_country_code' => $phone_country_code,
        'phone_number' => $phone_number,
    ];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد غير صالح.';
    } else {
        $errVal = validate_registration_fields($conn, $form, $id);
        if ($errVal !== '') {
            $error = $errVal;
        }
    }

    if ($error === '') {
        $chk = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $chk->bind_param('si', $email, $id);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $error = 'البريد مستخدم لمستخدم آخر.';
        }
    }

    if ($error === '') {
        $hashPart = '';
        if (trim($new_password) !== '') {
            if (strlen($new_password) < 8) {
                $error = 'كلمة المرور يجب أن لا تقل عن 8 أحرف.';
            } else {
                $hashPart = ', password_hash = ?';
            }
        }

        if ($error === '') {
            $sql = 'UPDATE users SET full_name = ?, email = ?, role = ?, birth_year = ?, birth_month = ?,
                phone_country_code = ?, phone_number = ?, whatsapp = ?, city = ?, state = ?,
                parent_name = ?, parent_phone = ?, is_active = ?';
            if ($unlink_fb) {
                $sql .= ', facebook_id = NULL';
            }
            if ($permissionsSupported) {
                $sql .= ', permissions_json = ?';
            }
            $sql .= $hashPart ? $hashPart : '';
            $sql .= ' WHERE id = ? AND role != ?';

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = 'تعذر حفظ التعديلات.';
            } else {
                $adm = 'admin';
                $permJson = $permissionsSupported ? json_encode($selectedPermissions, JSON_UNESCAPED_UNICODE) : '';
                if ($hashPart !== '') {
                    $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                    if ($permissionsSupported) {
                        $stmt->bind_param(
                            'sssii' . str_repeat('s', 7) . 'issis',
                            $full_name,
                            $email,
                            $role,
                            $birth_year,
                            $birth_month,
                            $phone_country_code,
                            $phone_number,
                            $whatsapp,
                            $city,
                            $state,
                            $parent_name,
                            $parent_phone,
                            $is_active,
                            $permJson,
                            $newHash,
                            $id,
                            $adm
                        );
                    } else {
                        $stmt->bind_param(
                            'sssii' . str_repeat('s', 7) . 'isis',
                            $full_name,
                            $email,
                            $role,
                            $birth_year,
                            $birth_month,
                            $phone_country_code,
                            $phone_number,
                            $whatsapp,
                            $city,
                            $state,
                            $parent_name,
                            $parent_phone,
                            $is_active,
                            $newHash,
                            $id,
                            $adm
                        );
                    }
                } else {
                    if ($permissionsSupported) {
                        $stmt->bind_param(
                            'sssii' . str_repeat('s', 7) . 'isis',
                            $full_name,
                            $email,
                            $role,
                            $birth_year,
                            $birth_month,
                            $phone_country_code,
                            $phone_number,
                            $whatsapp,
                            $city,
                            $state,
                            $parent_name,
                            $parent_phone,
                            $is_active,
                            $permJson,
                            $id,
                            $adm
                        );
                    } else {
                        $stmt->bind_param(
                            'sssii' . str_repeat('s', 7) . 'iis',
                            $full_name,
                            $email,
                            $role,
                            $birth_year,
                            $birth_month,
                            $phone_country_code,
                            $phone_number,
                            $whatsapp,
                            $city,
                            $state,
                            $parent_name,
                            $parent_phone,
                            $is_active,
                            $id,
                            $adm
                        );
                    }
                }
                if ($stmt->execute()) {
                    $message = 'تم حفظ التعديلات.';
                    admin_audit_log('update', 'user', $id, ['email' => $email, 'role' => $role, 'is_active' => $is_active]);
                    $st = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                    $st->bind_param('i', $id);
                    $st->execute();
                    $user = $st->get_result()->fetch_assoc() ?: $user;
                    $currentPermissions = user_permissions_list($user);
                } else {
                    $error = 'تعذر حفظ التعديلات.';
                }
            }
        }
    }
}

render_admin_layout_start('تعديل مستخدم', 'users.php');
?>
<?php if ($message): ?><div class="alert" style="background:#ecfdf5;color:#166534;padding:12px;border-radius:8px;margin-bottom:16px"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert" style="background:#fef2f2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>

<p><a href="users.php" class="btn btn-outline btn-sm">← المستخدمون</a>
   <a href="user-profile.php?id=<?= (int)$id ?>" class="btn btn-outline btn-sm">الملف</a></p>

<form method="post" class="settings-form-admin">
  <input type="hidden" name="user_id" value="<?= (int)$id ?>">
  <div class="form-section">
    <h3>البيانات الأساسية</h3>
    <div class="form-group">
      <label>الاسم الكامل (ثلاثي أو أكثر)</label>
      <input type="text" name="full_name" class="form-input" required value="<?= e($user['full_name']) ?>">
    </div>
    <div class="form-group">
      <label>البريد الإلكتروني (فريد)</label>
      <input type="email" name="email" class="form-input" required value="<?= e($user['email']) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>الدور</label>
        <select name="role" class="form-input">
          <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>طالب</option>
          <option value="instructor" <?= $user['role'] === 'instructor' ? 'selected' : '' ?>>مدرب</option>
        </select>
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= (int)$user['is_active'] ? 'checked' : '' ?>> حساب نشط</label>
      </div>
    </div>
    <?php if ($permissionsSupported): ?>
    <div class="form-group">
      <label>صلاحيات إضافية</label>
      <div class="permissions-grid">
        <?php foreach ($permissionLabels as $permKey => $permLabel): ?>
          <label class="perm-item">
            <input type="checkbox" name="permissions[]" value="<?= e($permKey) ?>" <?= in_array($permKey, $currentPermissions, true) ? 'checked' : '' ?>>
            <span><?= e($permLabel) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="muted small">يمكن تعيين صلاحيات تفصيلية إضافية للمستخدم بدون منحه دور مدير كامل.</p>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label>كلمة مرور جديدة (اختياري)</label>
      <input type="password" name="new_password" class="form-input" autocomplete="new-password" placeholder="اتركه فارغاً إن لم ترد التغيير">
    </div>
    <?php if (users_table_has_facebook_id() && !empty($user['facebook_id'])): ?>
    <div class="form-group">
      <label><input type="checkbox" name="unlink_facebook" value="1"> إلغاء ربط فيسبوك (المعرف الحالي: <?= e((string)$user['facebook_id']) ?>)</label>
    </div>
    <?php endif; ?>
  </div>

  <div class="form-section">
    <h3>الهاتف والموقع</h3>
    <p class="muted small">يُسمح بتكرار رقم الهاتف بين مستخدمين مختلفين؛ البريد يبقى فريداً.</p>
    <div class="form-group">
      <label>رمز الدولة</label>
      <select name="phone_country_code" class="form-input" required>
        <option value="">—</option>
        <?php foreach ($countries as $c): ?>
          <option value="<?= e($c['dial']) ?>" <?= ($user['phone_country_code'] ?? '') === $c['dial'] ? 'selected' : '' ?>>
            <?= e($c['name_ar'] . ' (' . $c['dial'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>رقم الهاتف (6–12 رقماً بدون المفتاح)</label>
      <input type="tel" name="phone_number" class="form-input" value="<?= e((string)($user['phone_number'] ?? '')) ?>"
        maxlength="12" pattern="[0-9]{6,12}">
    </div>
    <div class="form-group">
      <label>واتساب</label>
      <input type="text" name="whatsapp" class="form-input" value="<?= e((string)($user['whatsapp'] ?? '')) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>سنة الميلاد</label>
        <input type="number" name="birth_year" class="form-input" value="<?= $user['birth_year'] !== null ? (int)$user['birth_year'] : '' ?>">
      </div>
      <div class="form-group">
        <label>شهر الميلاد</label>
        <input type="number" name="birth_month" min="1" max="12" class="form-input" value="<?= $user['birth_month'] !== null ? (int)$user['birth_month'] : '' ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>المدينة</label>
        <input type="text" name="city" class="form-input" value="<?= e((string)($user['city'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>المنطقة</label>
        <input type="text" name="state" class="form-input" value="<?= e((string)($user['state'] ?? '')) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>اسم ولي الأمر</label>
        <input type="text" name="parent_name" class="form-input" value="<?= e((string)($user['parent_name'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>هاتف ولي الأمر</label>
        <input type="text" name="parent_phone" class="form-input" value="<?= e((string)($user['parent_phone'] ?? '')) ?>">
      </div>
    </div>
  </div>

  <button type="submit" name="save_user" value="1" class="btn btn-primary">حفظ</button>
</form>

<hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb">

<form method="post" onsubmit="return confirm('حذف المستخدم نهائياً؟ لا يمكن التراجع. سيتم حذف تسجيلاته وطلباته المرتبطة.');">
  <input type="hidden" name="user_id" value="<?= (int)$id ?>">
  <button type="submit" name="delete_user" value="1" class="btn btn-danger">حذف المستخدم</button>
</form>

<style>
.settings-form-admin { max-width: 720px; }
.form-section { margin-bottom: 1.5rem; }
.permissions-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 14px; margin-top: 8px; }
.perm-item { display: flex; align-items: center; gap: 8px; font-size: .95rem; }
@media (max-width: 680px) { .permissions-grid { grid-template-columns: 1fr; } }
</style>
<?php render_admin_layout_end(); ?>
