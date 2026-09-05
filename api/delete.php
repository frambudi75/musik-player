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
    echo json_encode(['status' => 'error', 'message' => 'ID lagu wajib disertakan'], JSON_UNESCAPED_SLASHES);
    exit;
}

$trackId = trim($body['id']);
$songsDir = realpath(__DIR__ . '/../songs');
if (!$songsDir) {
    $songsDir = __DIR__ . '/../songs';
    @mkdir($songsDir, 0777, true);
    $songsDir = realpath($songsDir);
}
$cacheFile = $songsDir . DIRECTORY_SEPARATOR . '.cache_library.json';

// 1. Retrieve or rebuild cache
$cacheData = null;
if (file_exists($cacheFile)) {
    $cacheData = json_decode(@file_get_contents($cacheFile), true);
}
if (!$cacheData || !isset($cacheData['songs']) || empty($cacheData['songs'])) {
    $cacheData = AuraCache::get('library_scan');
}

// Fallback: If cache is completely missing, perform quick directory scan
if (!$cacheData || !isset($cacheData['songs']) || empty($cacheData['songs'])) {
    $allowedExtensions = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'opus'];
    $scannedSongs = [];
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($songsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $rPath = $file->getRealPath();
            if (!$rPath) continue;
            if (strpos($rPath, DIRECTORY_SEPARATOR . 'covers') !== false || strpos($rPath, DIRECTORY_SEPARATOR . '.covers') !== false || strpos($file->getFilename(), '.') === 0) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowedExtensions)) {
                $relPath = str_replace('\\', '/', substr($rPath, strlen($songsDir) + 1));
                $encodedRelPath = implode('/', array_map('rawurlencode', explode('/', $relPath)));
                $scannedSongs[] = [
                    'id' => 'track_' . md5($relPath),
                    'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'artist' => 'Unknown Artist',
                    'album' => 'Single',
                    'url' => 'songs/' . $encodedRelPath,
                    'filename' => $file->getFilename(),
                    'size' => @filesize($rPath),
                    'modified' => @filemtime($rPath)
                ];
            }
        }
    } catch (Exception $e) {}
    $cacheData = ['timestamp' => time(), 'songs' => $scannedSongs];
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

// Locate file physically
$targetRealPath = null;

if ($songToDelete) {
    if (!empty($songToDelete['url'])) {
        $cleanUrl = rawurldecode($songToDelete['url']);
        if (strpos($cleanUrl, 'songs/') === 0) {
            $cleanUrl = substr($cleanUrl, 6);
        }
        $candidate = realpath($songsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanUrl));
        if ($candidate && file_exists($candidate)) {
            $targetRealPath = $candidate;
        }
    }

    if (!$targetRealPath && !empty($songToDelete['filename'])) {
        $candidate = realpath($songsDir . DIRECTORY_SEPARATOR . $songToDelete['filename']);
        if ($candidate && file_exists($candidate)) {
            $targetRealPath = $candidate;
        }
    }
}

// Fallback search across directory by ID hash if not located yet
if (!$targetRealPath) {
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($songsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $rPath = $file->getRealPath();
            if (!$rPath) continue;
            $relPath = str_replace('\\', '/', substr($rPath, strlen($songsDir) + 1));
            if ('track_' . md5($relPath) === $trackId) {
                $targetRealPath = $rPath;
                break;
            }
        }
    } catch (Exception $e) {}
}

// Perform safe file deletion
if ($targetRealPath && file_exists($targetRealPath)) {
    if (strpos($targetRealPath, $songsDir) === 0) {
        @unlink($targetRealPath);

        // Also delete associated .lrc file if present
        $dir = dirname($targetRealPath);
        $fnWithoutExt = pathinfo($targetRealPath, PATHINFO_FILENAME);
        $lrcCandidate = $dir . DIRECTORY_SEPARATOR . $fnWithoutExt . '.lrc';
        if (file_exists($lrcCandidate)) {
            @unlink($lrcCandidate);
        }
    }
}

// Update cache file and memory cache
$cacheData['songs'] = $newSongList;
$cacheData['timestamp'] = time();
@file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES));
AuraCache::set('library_scan', $cacheData, 3600);

echo json_encode([
    'status' => 'success',
    'message' => 'Lagu berhasil dihapus dari server',
    'deleted_id' => $trackId
], JSON_UNESCAPED_SLASHES);

