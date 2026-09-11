<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

set_time_limit(120);

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
if (empty($query)) {
    echo json_encode(['status' => 'error', 'message' => 'Query pencarian YouTube kosong']);
    exit;
}

$limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 12);
if ($limit <= 0 || $limit > 30) {
    $limit = 12;
}

$pythonBin = 'python';
if (PHP_OS_FAMILY !== 'Windows') {
    $possibleBins = ['/usr/bin/python3', '/usr/local/bin/python3', '/usr/bin/python', 'python3', 'python'];
    foreach ($possibleBins as $bin) {
        if (file_exists($bin) || @is_executable($bin)) {
            $pythonBin = $bin;
            break;
        }
    }
}

$scriptPath = __DIR__ . '/yt_search.py';
$cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($query) . " " . escapeshellarg((string)$limit) . " 2>&1";

$outputLines = [];
$returnVar = 0;
exec($cmd, $outputLines, $returnVar);

$result = null;
foreach (array_reverse($outputLines) as $line) {
    $decoded = json_decode($line, true);
    if ($decoded && isset($decoded['status'])) {
        $result = $decoded;
        break;
    }
}

if ($result) {
    echo json_encode($result);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memproses pencarian YouTube: ' . implode("\n", $outputLines)
    ]);
}
