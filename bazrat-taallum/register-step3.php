<?php
require_once __DIR__ . '/config/config.php';
header('Location: ' . rtrim(APP_URL, '/') . '/register.php');
exit;
