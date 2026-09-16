<?php
if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function load_env_file(string $path): void {
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . trim($value, " \t\"'"));
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');

function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : trim((string)$value);
}

define('APP_NAME_AR', env_value('APP_NAME_AR', 'بذرة تعلم'));
define('APP_NAME_EN', env_value('APP_NAME_EN', 'Learning Seeds'));
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost/bazrat-taallum'), '/'));

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS'));
define('DB_NAME', env_value('DB_NAME', 'bazrat_taallum'));
define('APP_KEY', env_value('APP_KEY'));
define('APP_ENV', env_value('APP_ENV', 'development'));

if (APP_ENV === 'production' && (strlen(APP_KEY) < 32 || parse_url(APP_URL, PHP_URL_SCHEME) !== 'https')) {
    error_log('Production requires APP_KEY (32+ characters) and an HTTPS APP_URL.');
    http_response_code(503);
    exit('Production configuration is incomplete.');
}

function encrypt_app_secret(string $value): string {
    if ($value === '' || APP_KEY === '') {
        return $value;
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($value, 'aes-256-cbc', hash('sha256', APP_KEY, true), OPENSSL_RAW_DATA, $iv);
    return $cipher === false ? $value : 'enc:' . base64_encode($iv . $cipher);
}

function decrypt_app_secret(string $value): string {
    if (!str_starts_with($value, 'enc:') || APP_KEY === '') {
        return $value;
    }
    $raw = base64_decode(substr($value, 4), true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }
    $plain = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', hash('sha256', APP_KEY, true), OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $plain === false ? '' : $plain;
}

if (session_status() === PHP_SESSION_NONE) {
    $https = parse_url(APP_URL, PHP_URL_SCHEME) === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

date_default_timezone_set('Asia/Riyadh');

function app_lang() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'ar';
}

function app_dir() {
    return app_lang() === 'ar' ? 'rtl' : 'ltr';
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
