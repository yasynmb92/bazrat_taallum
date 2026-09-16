<?php
require_once __DIR__ . '/config/config.php';

$basePath = trim((string)parse_url(APP_URL, PHP_URL_PATH), '/');
$requestPath = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = trim(substr($requestPath, strlen($basePath)), '/');
}

if ($requestPath === '' || $requestPath === 'index.php') {
    require __DIR__ . '/index.php';
    exit;
}

$segments = explode('/', $requestPath);

if ($segments[0] === 'courses') {
    if (isset($segments[1]) && $segments[1] === 'search') {
        require __DIR__ . '/courses.php';
        exit;
    }
    if (isset($segments[1]) && $segments[1] !== '') {
        $_GET['category_slug'] = $segments[1];
        require __DIR__ . '/courses.php';
        exit;
    }
    require __DIR__ . '/courses.php';
    exit;
}

if ($segments[0] === 'course' && !empty($segments[1])) {
    $_GET['slug'] = $segments[1];
    require __DIR__ . '/course.php';
    exit;
}

if ($segments[0] === 'admin') {
    require __DIR__ . '/admin/index.php';
    exit;
}

// fallback to existing php file if explicitly requested
if ($requestPath !== 'router.php' && str_ends_with($requestPath, '.php')) {
    $candidate = __DIR__ . '/' . $requestPath;
    if (is_file($candidate)) {
        require $candidate;
        exit;
    }
}

http_response_code(404);
echo '404 Not Found';
