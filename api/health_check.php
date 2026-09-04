<?php
/**
 * Aura Music - Audio Library Health Doctor & Auto-Repair Engine
 * Diagnoses missing, corrupt, 0-byte, or inaccessible audio files in the library,
 * fixes illegal URL characters (#, ?, %) on disk, and enables 1-click automatic
 * re-download via YouTube backend.
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

$allowedExtensions = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'opus'];
$healthySongs = [];
$brokenSongs = [];
$renamedFiles = [];

// Helper: Check if file header looks like valid audio
function is_valid_audio_header($path) {
    $fp = @fopen($path, 'rb');
    if (!$fp) return false;
    $bytes = fread($fp, 12);
    fclose($fp);
    if (strlen($bytes) < 4) return false;

    // ID3v2: "ID3"
    if (substr($bytes, 0, 3) === 'ID3') return true;
    // MP3 frame sync: 0xFF 0xFB, 0xFF 0xF3, 0xFF 0xF2, 0xFF 0xE3
    if (ord($bytes[0]) === 0xFF && (ord($bytes[1]) & 0xE0) === 0xE0) return true;
    // FLAC: "fLaC"
    if (substr($bytes, 0, 4) === 'fLaC') return true;
    // OGG: "OggS"
    if (substr($bytes, 0, 4) === 'OggS') return true;
    // WAV: "RIFF" ... "WAVE"
    if (substr($bytes, 0, 4) === 'RIFF') return true;
    // M4A / AAC: contains "ftyp"
    if (substr($bytes, 4, 4) === 'ftyp') return true;

    return true; // Unknown but allow if non-zero
}

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

            // 1. Detect and auto-fix illegal characters (#, ?, %) in filenames on disk
            if (preg_match('/[#\?%]/', $fileName)) {
                $cleanName = preg_replace('/[#\?%]/', '_', $fileName);
                $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
                $newPath = dirname($realPath) . DIRECTORY_SEPARATOR . $cleanName;
                if (!file_exists($newPath) && @rename($realPath, $newPath)) {
                    // Rename associated .lrc if present
                    $oldLrc = dirname($realPath) . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME) . '.lrc';
                    $newLrc = dirname($realPath) . DIRECTORY_SEPARATOR . pathinfo($cleanName, PATHINFO_FILENAME) . '.lrc';
                    if (file_exists($oldLrc) && !file_exists($newLrc)) {
                        @rename($oldLrc, $newLrc);
                    }
                    $renamedFiles[] = ['old' => $fileName, 'new' => $cleanName];
                    $realPath = $newPath;
                    $fileName = $cleanName;
                }
            }

            $fileSize = @filesize($realPath);
            $relPath = str_replace('\\', '/', substr($realPath, strlen($songsDir) + 1));
            $encodedRelPath = implode('/', array_map('rawurlencode', explode('/', $relPath)));

            $songInfo = [
                'id' => 'track_' . md5($relPath),
                'filename' => $fileName,
                'path' => $realPath,
                'url' => 'songs/' . $encodedRelPath,
                'size' => $fileSize
            ];

            // 2. Health verification
            if ($fileSize === false || $fileSize === 0) {
                $songInfo['reason'] = 'File kosong (0 bytes)';
                $brokenSongs[] = $songInfo;
            } elseif ($fileSize < 10240) {
                $songInfo['reason'] = 'Ukuran file terlalu kecil (<10KB), kemungkinan korup';
                $brokenSongs[] = $songInfo;
            } elseif (!is_valid_audio_header($realPath)) {
                $songInfo['reason'] = 'Header file audio tidak valid / rusak';
                $brokenSongs[] = $songInfo;
            } else {
                $healthySongs[] = $songInfo;
            }
        }
    }

    // 3. Invalidate cache if any file was renamed
    if (!empty($renamedFiles) && file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    // 4. Also check cached library to catch ghost tracks (indexed but missing on disk)
    if (file_exists($cacheFile)) {
        $cachedData = @json_decode(file_get_contents($cacheFile), true);
        if ($cachedData && !empty($cachedData['songs'])) {
            foreach ($cachedData['songs'] as $cs) {
                $checkPath = $songsDir . '/' . ($cs['filename'] ?? '');
                if (!empty($cs['filename']) && !file_exists($checkPath)) {
                    $brokenSongs[] = [
                        'id' => $cs['id'] ?? 'ghost_' . md5($cs['filename']),
                        'filename' => $cs['filename'],
                        'title' => $cs['title'] ?? $cs['filename'],
                        'artist' => $cs['artist'] ?? '',
                        'reason' => 'File tidak ditemukan di disk server (404)'
                    ];
                }
            }
        }
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// Action: Repair single or all broken songs
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
        $fn = $song['filename'] ?? '';
        if (empty($fn)) continue;

        // Remove corrupted local file if exists
        $localFile = $songsDir . '/' . $fn;
        if (file_exists($localFile)) {
            @unlink($localFile);
        }

        // Clean query
        $query = pathinfo($fn, PATHINFO_FILENAME);
        $query = preg_replace('/#[\w\d_\-]+/u', ' ', $query);
        $query = trim(preg_replace('/\s+/', ' ', $query));

        if (!empty($query)) {
            $cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($downloaderScript) . " youtube " . escapeshellarg($query) . " 2>&1";
            $out = [];
            $ret = 0;
            @exec($cmd, $out, $ret);

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

// Return Full Health Diagnostics
echo json_encode([
    'status' => 'success',
    'total_scanned' => count($healthySongs) + count($brokenSongs),
    'healthy_count' => count($healthySongs),
    'broken_count' => count($brokenSongs),
    'broken_songs' => $brokenSongs,
    'renamed_count' => count($renamedFiles),
    'renamed_files' => $renamedFiles,
    'healthy_percentage' => (count($healthySongs) + count($brokenSongs)) > 0 
        ? round((count($healthySongs) / (count($healthySongs) + count($brokenSongs))) * 100, 1) 
        : 100
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
