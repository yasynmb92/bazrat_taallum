<?php
require_once __DIR__ . '/../includes/functions.php';

if (!facebook_app_configured()) {
    header('Location: ' . route_url('login.php?fb=off'));
    exit;
}

$state = (string)($_GET['state'] ?? '');
$sess = (string)($_SESSION['oauth_fb_state'] ?? '');
unset($_SESSION['oauth_fb_state']);

if ($state === '' || $sess === '' || !hash_equals($sess, $state)) {
    header('Location: ' . route_url('login.php?fb=state'));
    exit;
}

$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    header('Location: ' . route_url('login.php?fb=cancel'));
    exit;
}

$tok = facebook_exchange_code($code);
if (!$tok || empty($tok['access_token'])) {
    header('Location: ' . route_url('login.php?fb=token'));
    exit;
}

$me = facebook_graph_me($tok['access_token']);
if (!$me || empty($me['id'])) {
    header('Location: ' . route_url('login.php?fb=profile'));
    exit;
}

$fbId = (string)$me['id'];
$conn = db();

if (!users_table_has_facebook_id()) {
    header('Location: ' . route_url('login.php?fb=migrate'));
    exit;
}

$stmt = $conn->prepare('SELECT * FROM users WHERE facebook_id = ? LIMIT 1');
$stmt->bind_param('s', $fbId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $email = isset($me['email']) ? trim((string)$me['email']) : '';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
        $email = 'fb.' . $fbId . '@' . preg_replace('/^www\./', '', $host);
    }

    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $byEmail = $stmt->get_result()->fetch_assoc();
    if ($byEmail) {
        if (!empty($byEmail['facebook_id']) && (string)$byEmail['facebook_id'] !== $fbId) {
            header('Location: ' . route_url('login.php?fb=linked'));
            exit;
        }
        $uid = (int)$byEmail['id'];
        $up = $conn->prepare('UPDATE users SET facebook_id = ? WHERE id = ?');
        $up->bind_param('si', $fbId, $uid);
        $up->execute();
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $fullName = trim((string)($me['name'] ?? ''));
        if ($fullName === '' || full_name_part_count($fullName) < 3) {
            $fullName = 'مستخدم فيسبوك ' . $fbId;
        }
        if (is_full_name_taken($conn, $fullName, 0)) {
            $fullName .= ' ' . substr($fbId, 0, 6);
        }

        $tryEmail = $email;
        for ($i = 0; $i < 15; $i++) {
            $chk = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->bind_param('s', $tryEmail);
            $chk->execute();
            if (!$chk->get_result()->fetch_assoc()) {
                break;
            }
            $tryEmail = 'fb.' . $fbId . '.' . ($i + 1) . '@' . preg_replace('/^www\./', '', parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost');
        }

        $user = register_user_from_facebook($fbId, $tryEmail, $fullName);
        if (!$user) {
            header('Location: ' . route_url('login.php?fb=create'));
            exit;
        }
    }
}

if (!$user || (int)$user['is_active'] !== 1) {
    header('Location: ' . route_url('login.php?fb=inactive'));
    exit;
}

$_SESSION['user'] = $user;
header('Location: ' . route_url('index.php'));
exit;
