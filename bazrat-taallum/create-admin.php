<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/config/db.php';

$conn = db();

$name = getenv('ADMIN_NAME') ?: 'أدمن رئيسي';
$email = getenv('ADMIN_EMAIL') ?: ($argv[1] ?? '');
$password = getenv('ADMIN_PASSWORD') ?: ($argv[2] ?? '');
if ($email === '' || $password === '') {
    fwrite(STDERR, "Usage: ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='strong-password' php create-admin.php\n");
    exit(2);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $conn->prepare("INSERT IGNORE INTO users (full_name, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
$stmt->bind_param('ssss', $name, $email, $hash, $role);

if ($stmt->execute()) {
    $status = $conn->affected_rows > 0 ? '✅ تم إنشاء حساب أدمن جديد' : 'ℹ️ الحساب موجود مسبقاً';
} else {
    $status = '❌ خطأ: ' . $conn->error;
}

$stmt->close();
$conn->close();

echo $status . PHP_EOL;
echo 'Email: ' . $email . PHP_EOL;
echo "Admin account is ready." . PHP_EOL;
?>
