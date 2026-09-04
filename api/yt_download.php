<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


// Increase execution time for download processing
set_time_limit(1800);
ini_set('max_execution_time', 1800);

$payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$query = trim($payload['url'] ?? $payload['query'] ?? '');

if (empty($query)) {
    echo json_encode(['status' => 'error', 'message' => 'Link YouTube/Spotify atau judul lagu wajib diisi']);
    exit;
}

// Check if exec is disabled in aaPanel PHP configuration
$disabledFunctions = explode(',', ini_get('disable_functions') ?: '');
$disabledFunctions = array_map('trim', $disabledFunctions);
if (in_array('exec', $disabledFunctions) || !function_exists('exec')) {
    echo json_encode([
        'status' => 'error',
        'message' => "Fungsi exec() dinonaktifkan di PHP server aaPanel. Buka aaPanel > PHP > Disabled Functions > Hapus 'exec' dan 'proc_open'."
    ]);
    exit;
}

// Determine Python binary path (Windows vs Linux / aaPanel)
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

// Ensure songs directory exists and has write permission
$songsDir = __DIR__ . '/../songs';
if (!is_dir($songsDir)) {
    @mkdir($songsDir, 0777, true);
}
@chmod($songsDir, 0777);

$downloaderScript = __DIR__ . '/downloader.py';
$mode = preg_match('/(spotify\.com|spotify:)/i', $query) ? 'spotify' : 'youtube';

$cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($downloaderScript) . " " . escapeshellarg($mode) . " " . escapeshellarg($query) . " 2>&1";

$outputLines = [];
$returnVar = 0;
exec($cmd, $outputLines, $returnVar);

$outputStr = implode("\n", $outputLines);

// Invalidate library cache
$cacheFile = __DIR__ . '/../songs/.cache_library.json';
if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}

// Check JSON output from python script
$result = null;
foreach (array_reverse($outputLines) as $line) {
    $decoded = json_decode($line, true);
    if ($decoded && isset($decoded['status'])) {
        $result = $decoded;
        break;
    }
}

if ($result && ($result['status'] === 'success' || $result['status'] === 'already_exists')) {
    require_once __DIR__ . '/id3.php';

    $isDuplicate = ($result['status'] === 'already_exists');
    $songData = null;
    if (!empty($result['filename'])) {
        $filePath = __DIR__ . '/../songs/' . $result['filename'];
        if (file_exists($filePath)) {
            $meta = SimpleID3::getMetadata($filePath);
            $songData = [
                'id' => 'track_' . md5($result['filename']),
                'title' => $meta['title'] ?? $result['title'],
                'artist' => $meta['artist'] ?? $result['artist'],
                'album' => $meta['album'] ?? 'Single',
                'url' => 'songs/' . $result['filename'],
                'filename' => $result['filename'],
                'cover' => $meta['cover'],
                'lyrics' => null
            ];
        }
    }

    if (!$isDuplicate) {
        $cacheFile = __DIR__ . '/../songs/.cache_library.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    echo json_encode([
        'status' => $result['status'],
        'already_exists' => $isDuplicate,
        'message' => $isDuplicate ? ($result['message'] ?? 'Lagu ini sudah ada di koleksi musik Anda!') : 'Lagu berhasil didownload dan disimpan ke koleksi!',
        'song' => $songData,
        'details' => $result
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $result['message'] ?? 'Gagal mendownload audio. Pastikan link valid.',
        'details' => $outputStr
    ]);
}
