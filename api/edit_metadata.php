<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


$cacheFile = __DIR__ . '/../songs/.cache_library.json';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || empty($body['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data lagu tidak lengkap']);
    exit;
}

$trackId = $body['id'];
$title = trim($body['title'] ?? '');
$artist = trim($body['artist'] ?? '');
$album = trim($body['album'] ?? '');
$genre = trim($body['genre'] ?? '');

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Judul lagu tidak boleh kosong']);
    exit;
}

if (!file_exists($cacheFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Cache library belum dibuat']);
    exit;
}

$cacheData = json_decode(file_get_contents($cacheFile), true);
if (!$cacheData || !isset($cacheData['songs'])) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca library']);
    exit;
}

$updatedSong = null;
foreach ($cacheData['songs'] as &$s) {
    if ($s['id'] === $trackId) {
        $s['title'] = $title;
        if (!empty($artist)) $s['artist'] = $artist;
        if (!empty($album)) $s['album'] = $album;
        if (!empty($genre)) $s['genre'] = $genre;

        // Handle cover_url enrichment
        $coverUrl = trim($body['cover_url'] ?? '');
        if (!empty($coverUrl) && filter_var($coverUrl, FILTER_VALIDATE_URL)) {
            $coversDir = __DIR__ . '/../songs/covers';
            if (!is_dir($coversDir)) @mkdir($coversDir, 0777, true);
            $coverFilename = 'cover_' . md5($trackId) . '.jpg';
            $targetCoverPath = $coversDir . '/' . $coverFilename;
            
            // Download cover artwork
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $coverUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ]);
            $coverData = curl_exec($ch);
            $cHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($cHttp === 200 && $coverData && strlen($coverData) > 500) {
                file_put_contents($targetCoverPath, $coverData);
                $s['cover'] = 'songs/covers/' . $coverFilename;
            }
        }

        $updatedSong = $s;
        break;
    }
}

if (!$updatedSong) {
    echo json_encode(['status' => 'error', 'message' => 'Lagu tidak ditemukan di koleksi']);
    exit;
}

// Persist updated library cache
require_once __DIR__ . '/cache_helper.php';
$cacheData['timestamp'] = time();
file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES));
AuraCache::set('library_scan', $cacheData, 3600);

echo json_encode([
    'status' => 'success',
    'message' => 'Metadata lagu berhasil diperbarui!',
    'song' => $updatedSong
], JSON_UNESCAPED_SLASHES);
