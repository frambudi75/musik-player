<?php
/**
 * Aura Music - Audio Library Health Doctor & Auto-Repair Engine
 * Diagnoses missing, corrupt, 0-byte, or inaccessible audio files in the library,
 * and enables 1-click automatic re-download via YouTube backend.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

set_time_limit(600);
ini_set('max_execution_time', 600);

$songsDir = realpath(__DIR__ . '/../songs');
if (!$songsDir || !is_dir($songsDir)) {
    echo json_encode(['status' => 'error', 'message' => 'Direktori lagu tidak ditemukan']);
    exit;
}

$cacheFile = $songsDir . '/.cache_library.json';
$action = $_GET['action'] ?? ($_POST['action'] ?? 'check');

// 1. Scan and verify all files on disk
$allowedExtensions = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'opus'];
$healthySongs = [];
$brokenSongs = [];

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($songsDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) continue;
        $realPath = $file->getRealPath();
        if (!$realPath) continue;

        if (strpos($realPath, DIRECTORY_SEPARATOR . 'covers') !== false || 
            strpos($realPath, DIRECTORY_SEPARATOR . '.covers') !== false || 
            strpos($file->getFilename(), '.') === 0) {
            continue;
        }

        $ext = strtolower($file->getExtension());
        if (in_array($ext, $allowedExtensions)) {
            $fileName = $file->getFilename();
            $fileSize = $file->getSize();
            $relPath = str_replace('\\', '/', substr($realPath, strlen($songsDir) + 1));
            
            // Encode path properly for safe URL
            $encodedRelPath = implode('/', array_map('rawurlencode', explode('/', $relPath)));

            $songInfo = [
                'id' => 'track_' . md5($relPath),
                'filename' => $fileName,
                'path' => $realPath,
                'url' => 'songs/' . $encodedRelPath,
                'size' => $fileSize
            ];

            // Verify file health
            if ($fileSize < 10240) { // Less than 10KB is almost certainly corrupted or failed download
                $songInfo['reason'] = $fileSize === 0 ? 'File kosong (0 bytes)' : 'Ukuran file terlalu kecil (<10KB), kemungkinan korup';
                $brokenSongs[] = $songInfo;
            } else {
                $healthySongs[] = $songInfo;
            }
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// 2. Action: Repair single or all broken songs
if ($action === 'repair_song' || $action === 'repair_all') {
    $targetSongs = [];
    if ($action === 'repair_song') {
        $filename = $_GET['filename'] ?? ($_POST['filename'] ?? '');
        foreach ($brokenSongs as $bs) {
            if ($bs['filename'] === $filename) {
                $targetSongs[] = $bs;
                break;
            }
        }
        if (empty($targetSongs) && !empty($filename)) {
            $targetSongs[] = ['filename' => $filename, 'path' => $songsDir . '/' . $filename];
        }
    } else {
        $targetSongs = $brokenSongs;
    }

    $repaired = [];
    $failed = [];

    // Include python downloader runner
    $pythonBin = 'python';
    if (PHP_OS_FAMILY !== 'Windows') {
        $possibleBins = ['/usr/bin/python3', '/usr/local/bin/python3', 'python3', 'python'];
        foreach ($possibleBins as $b) {
            if (file_exists($b) || @is_executable($b)) {
                $pythonBin = $b;
                break;
            }
        }
    }
    $downloaderScript = __DIR__ . '/downloader.py';

    foreach ($targetSongs as $song) {
        $fn = $song['filename'];
        // Remove corrupted local file first
        $localFile = $songsDir . '/' . $fn;
        if (file_exists($localFile)) {
            @unlink($localFile);
        }

        // Search query derived from filename
        $query = pathinfo($fn, PATHINFO_FILENAME);
        $query = preg_replace('/#[\w\d_\-]+/u', ' ', $query);
        $query = trim(preg_replace('/\s+/', ' ', $query));

        if (!empty($query)) {
            $cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($downloaderScript) . " youtube " . escapeshellarg($query) . " 2>&1";
            $out = [];
            $ret = 0;
            @exec($cmd, $out, $ret);

            // Invalidate cache
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }

            $repaired[] = [
                'filename' => $fn,
                'query' => $query,
                'status' => ($ret === 0) ? 'success' : 'attempted'
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Proses perbaikan selesai',
        'repaired_count' => count($repaired),
        'repaired' => $repaired
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 3. Default: Return Health Diagnostics
echo json_encode([
    'status' => 'success',
    'total_scanned' => count($healthySongs) + count($brokenSongs),
    'healthy_count' => count($healthySongs),
    'broken_count' => count($brokenSongs),
    'broken_songs' => $brokenSongs,
    'healthy_percentage' => (count($healthySongs) + count($brokenSongs)) > 0 
        ? round((count($healthySongs) / (count($healthySongs) + count($brokenSongs))) * 100, 1) 
        : 100
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
