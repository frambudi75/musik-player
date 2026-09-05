<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/cache_helper.php';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || empty($body['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID lagu wajib disertakan']);
    exit;
}

$trackId = $body['id'];
$songsDir = realpath(__DIR__ . '/../songs');
$cacheFile = __DIR__ . '/../songs/.cache_library.json';

if (!file_exists($cacheFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Library database belum tersedia']);
    exit;
}

$cacheData = json_decode(file_get_contents($cacheFile), true);
if (!$cacheData || !isset($cacheData['songs'])) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca library']);
    exit;
}

$songToDelete = null;
$newSongList = [];

foreach ($cacheData['songs'] as $s) {
    if ($s['id'] === $trackId) {
        $songToDelete = $s;
    } else {
        $newSongList[] = $s;
    }
}

if (!$songToDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Lagu tidak ditemukan di library']);
    exit;
}

// Safely delete actual audio file on disk
$filePath = $songToDelete['file_path'] ?? '';
if (!empty($filePath)) {
    $realPath = realpath($filePath);
    if ($realPath && strpos($realPath, $songsDir) === 0 && file_exists($realPath)) {
        @unlink($realPath);
    }
}

// Delete matching .lrc file if exists
$lrcPath = $songToDelete['lrc_path'] ?? '';
if (!empty($lrcPath)) {
    $realLrc = realpath($lrcPath);
    if ($realLrc && strpos($realLrc, $songsDir) === 0 && file_exists($realLrc)) {
        @unlink($realLrc);
    }
}

// Update cache file and memory cache
$cacheData['songs'] = $newSongList;
$cacheData['timestamp'] = time();
file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES));
AuraCache::set('library_scan', $cacheData, 3600);

echo json_encode([
    'status' => 'success',
    'message' => 'Lagu berhasil dihapus dari koleksi server',
    'deleted_id' => $trackId
], JSON_UNESCAPED_SLASHES);
