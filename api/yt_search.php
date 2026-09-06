<?php
/**
 * NadaKita - YouTube Music & Audio Search API
 * Searches YouTube for audio streams without downloading
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/cache_helper.php';

$query = trim($_GET['q'] ?? '');
$limit = max(1, min(25, intval($_GET['limit'] ?? 10)));

if (empty($query)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter q (query pencarian) tidak boleh kosong.'
    ]);
    exit;
}

// Check Cache (TTL 1 hour)
$cacheKey = 'yt_search_' . md5(mb_strtolower($query) . '_' . $limit);
$cached = AuraCache::get($cacheKey);
if ($cached) {
    echo json_encode([
        'status' => 'success',
        'cached' => true,
        'query' => $query,
        'results' => $cached
    ]);
    exit;
}

// Execute stream_extractor.py search
$pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'stream_extractor.py';
$cmd = sprintf('python %s search %s %d', escapeshellarg($pythonScript), escapeshellarg($query), $limit);

$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);

$rawJson = implode("\n", $output);
$data = json_decode($rawJson, true);

if ($returnCode !== 0 || !$data || !isset($data['status']) || $data['status'] !== 'success') {
    $errMsg = $data['message'] ?? 'Gagal mencari musik di YouTube. Pastikan server memiliki koneksi internet.';
    echo json_encode([
        'status' => 'error',
        'message' => $errMsg
    ]);
    exit;
}

$results = $data['results'] ?? [];

// Cache results for 1 hour (3600s)
AuraCache::set($cacheKey, $results, 3600);

echo json_encode([
    'status' => 'success',
    'cached' => false,
    'query' => $query,
    'total' => count($results),
    'results' => $results
]);
