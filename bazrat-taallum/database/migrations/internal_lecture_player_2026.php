<?php
/** Keep seeded lectures playable through the platform's native player. */
require_once __DIR__ . '/../../config/db.php';

$conn = db();
$internalVideo = 'assets/video/Python.mp4';
$stmt = $conn->prepare('UPDATE lessons SET video_url = ? WHERE video_url LIKE "https://www.youtube.com/@%" OR video_url LIKE "https://www.youtube.com/channel/%"');
$stmt->bind_param('s', $internalVideo);
$stmt->execute();