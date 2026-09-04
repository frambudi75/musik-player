<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/auth_guard.php';
AuraAuth::requireAuth(['rateLimit' => 10]);


// Long execution time for playlist batch download
set_time_limit(1800);
ini_set('max_execution_time', 1800);

$songsDir = realpath(__DIR__ . '/../songs');
if (!$songsDir) {
    mkdir(__DIR__ . '/../songs', 0777, true);
    $songsDir = realpath(__DIR__ . '/../songs');
}

$payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$url = trim($payload['url'] ?? $payload['query'] ?? '');

if (empty($url)) {
    echo json_encode(['status' => 'error', 'message' => 'Link Spotify Playlist / Track / Album wajib diisi']);
    exit;
}

// Find ffmpeg location
$ffmpegPaths = [
    'C:\Users\habib\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-8.1.1-full_build\bin',
    'C:\ffmpeg\bin',
    'C:\Program Files\ffmpeg\bin'
];

$ffmpegDir = '';
foreach ($ffmpegPaths as $fpath) {
    if (file_exists($fpath . '\ffmpeg.exe') || file_exists($fpath . '/ffmpeg.exe')) {
        $ffmpegDir = $fpath;
        break;
    }
}

// Add ffmpeg to PATH for spotdl process
if ($ffmpegDir) {
    putenv("PATH=" . $ffmpegDir . ";" . getenv("PATH"));
}

$escapedUrl = escapeshellarg($url);
$escapedDir = escapeshellarg($songsDir);

// Command to download via spotdl with 320k quality, embedded lyrics, and metadata
$cmd = "python -m spotdl download {$escapedUrl} --output {$escapedDir} --format mp3 --bitrate 320k --generate-lrc 2>&1";

$outputLines = [];
$returnVar = 0;
exec($cmd, $outputLines, $returnVar);
$outputStr = implode("\n", $outputLines);

// Invalidate library cache
$cacheFile = $songsDir . '/.cache_library.json';
if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}

// Scan to count downloaded songs
require_once __DIR__ . '/id3.php';

if ($returnVar === 0 || strpos($outputStr, 'Downloaded') !== false || strpos($outputStr, 'Found') !== false) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Proses download playlist Spotify berhasil selesai!',
        'logs' => $outputStr
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mendownload playlist Spotify. Pastikan link Spotify valid.',
        'details' => $outputStr
    ]);
}
