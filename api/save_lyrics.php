<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


$songsDir = __DIR__ . '/../songs';
$cacheFile = __DIR__ . '/../songs/.cache_library.json';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || empty($body['filename']) || empty($body['lyrics'])) {
    echo json_encode(['status' => 'error', 'message' => 'Nama file dan konten lirik diperlukan']);
    exit;
}

$filename = basename($body['filename']);
$lyricsContent = trim($body['lyrics']);
$baseName = pathinfo($filename, PATHINFO_FILENAME);
$lrcFilePath = $songsDir . '/' . $baseName . '.lrc';

$saved = @file_put_contents($lrcFilePath, $lyricsContent);

if ($saved === false) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file lirik ke server']);
    exit;
}

require_once __DIR__ . '/cache_helper.php';

// Invalidate library cache so next scan detects the new .lrc file
if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}
AuraCache::delete('library_scan');

echo json_encode([
    'status' => 'success',
    'message' => 'Lirik sinkron berhasil disimpan ke lagu!',
    'lyrics_url' => 'songs/' . $baseName . '.lrc',
    'lyrics' => $lyricsContent
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
