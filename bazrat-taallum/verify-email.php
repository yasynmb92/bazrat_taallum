<?php
require_once __DIR__ . '/includes/functions.php';
csrf_enforce_on_post();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (verify_email($email, $code)) {
        $message = '✅ تم تأكيد البريد! يمكنك الآن تسجيل الدخول.';
    } else {
        $error = '❌ الكود خاطئ أو منتهي الصلاحية';
    }
}

$pending_email = $_GET['email'] ?? $_SESSION['pending_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد - <?= APP_NAME_AR ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .container { background: white; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); padding: 3rem; max-width: 500px; width: 100%; text-align: center; }
        .logo { font-size: 2.5rem; color: #667eea; margin-bottom: 1rem; }
        h1 { color: #2d3748; margin-bottom: 1rem; font-weight: 700; font-size: 1.8rem; }
        .message { padding: 1rem 2rem; border-radius: 12px; margin: 1.5rem 0; font-weight: 500; }
        .success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .error { background: #fed7d7; color: #742a2a; border: 1px solid #fc8181; }
        .form-group { margin-bottom: 1.5rem; text-align: right; }
        label { display: block; color: #4a5568; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="email"], input[type="text"] { width: 100%; padding: 1rem 1.5rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; font-family: 'Cairo', sans-serif; transition: all 0.3s; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .btn { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 1rem 2.5rem; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; width: 100%; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
        .resend { background: #48bb78; margin-top: 1rem; }
        .login-link { margin-top: 2rem; }
        .login-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        @media (max-width: 480px) { .container { padding: 2rem 1.5rem; margin: 1rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-seedling"></i>
            <span style="display: block; font-size: 1.2rem; font-weight: 700;">بذرة تعلم</span>
        </div>
        
        <h1>تأكيد البريد الإلكتروني</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= $message ?></div>
            <a href="login.php" class="btn">تسجيل الدخول</a>
        <?php elseif ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <form method="POST">
                            <?= csrf_field() ?>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($pending_email) ?>" required>
                </div>
                <div class="form-group">
                    <label>كود التأكيد (6 أرقام)</label>
                    <input type="text" name="code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required>
                </div>
                <button type="submit" class="btn">تأكيد</button>
            </form>
            <button class="btn resend" onclick="resendCode('<?= htmlspecialchars($pending_email) ?>')">
                إعادة إرسال الكود
            </button>
        <?php endif; ?>
        
        <div class="login-link">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> لديك حساب؟ تسجيل الدخول</a>
        </div>
    </div>

    <script>
        function resendCode(email) {
            fetch('api/resend-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': <?= json_encode(csrf_token()) ?>
                },
                body: JSON.stringify({email: email})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ تم إرسال الكود الجديد!');
                } else {
                    alert('❌ خطأ: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>

