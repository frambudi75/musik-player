<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/id3.php';
require_once __DIR__ . '/cache_helper.php';

$songsDir = __DIR__ . '/../songs';
$coversDir = __DIR__ . '/../songs/covers';
$cacheFile = __DIR__ . '/../songs/.cache_library.json';

// Create directories if not exist
if (!is_dir($songsDir)) {
    @mkdir($songsDir, 0777, true);
}
if (!is_dir($coversDir)) {
    @mkdir($coversDir, 0777, true);
}

// Migrate any legacy .covers to covers if present
$oldCoversDir = __DIR__ . '/../songs/.covers';
if (is_dir($oldCoversDir)) {
    $cFiles = @scandir($oldCoversDir);
    if ($cFiles) {
        foreach ($cFiles as $cf) {
            if ($cf !== '.' && $cf !== '..') {
                $srcFile = $oldCoversDir . '/' . $cf;
                $dstFile = $coversDir . '/' . $cf;
                if (is_file($srcFile) && !file_exists($dstFile)) {
                    @copy($srcFile, $dstFile);
                }
            }
        }
    }
}

$forceRefresh = (isset($_GET['refresh']) && $_GET['refresh'] == '1') || (isset($argv) && in_array('--refresh', $argv));

// 1. Check Redis / In-Memory Cache first
if (!$forceRefresh) {
    $cachedData = AuraCache::get('library_scan');
    if ($cachedData && !empty($cachedData['songs'])) {
        foreach ($cachedData['songs'] as &$s) {
            if (!empty($s['cover'])) {
                $s['cover'] = str_replace('songs/.covers/', 'songs/covers/', $s['cover']);
                if (!file_exists(__DIR__ . '/../' . $s['cover'])) {
                    $s['cover'] = null;
                }
            }
        }
        unset($s);
        $etag = md5(json_encode($cachedData['timestamp']) . '_v3');
        AuraCache::checkETagAndExit($etag, 1800);
        echo json_encode([
            'status' => 'success',
            'cached' => true,
            'cache_driver' => 'redis_memory',
            'songs_dir' => realpath($songsDir) ?: $songsDir,
            'total' => count($cachedData['songs']),
            'songs' => $cachedData['songs']
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (file_exists($cacheFile)) {
        $cached = json_decode(@file_get_contents($cacheFile), true);
        if ($cached && isset($cached['timestamp']) && (time() - $cached['timestamp'] < 3600)) {
            foreach ($cached['songs'] as &$s) {
                if (!empty($s['cover'])) {
                    $s['cover'] = str_replace('songs/.covers/', 'songs/covers/', $s['cover']);
                    if (!file_exists(__DIR__ . '/../' . $s['cover'])) {
                        $s['cover'] = null;
                    }
                }
            }
            unset($s);
            AuraCache::set('library_scan', $cached, 3600);
            $etag = md5((string)$cached['timestamp'] . '_v2');
            AuraCache::checkETagAndExit($etag, 1800);
            echo json_encode([
                'status' => 'success',
                'cached' => true,
                'cache_driver' => 'disk_cache',
                'songs_dir' => realpath($songsDir) ?: $songsDir,
                'total' => count($cached['songs']),
                'songs' => $cached['songs']
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

$allowedExtensions = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'opus'];
$songs = [];

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($songsDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        $realPath = $file->getRealPath();
        if (!$realPath) continue;

        // Skip covers folder and hidden files/directories (like .cache_library.json or files in covers)
        if (strpos($realPath, DIRECTORY_SEPARATOR . 'covers') !== false || strpos($realPath, DIRECTORY_SEPARATOR . '.covers') !== false || strpos($file->getFilename(), '.') === 0) {
            continue;
        }

        $ext = strtolower($file->getExtension());
        if (in_array($ext, $allowedExtensions)) {
            $fileName = $file->getFilename();
            $fileSize = $file->getSize();
            $fileMtime = $file->getMTime();

            // Skip zero byte ghost files
            if ($fileSize === 0) continue;

            $relPath = str_replace('\\', '/', substr($realPath, strlen(realpath($songsDir)) + 1));
            $encodedRelPath = implode('/', array_map('rawurlencode', explode('/', $relPath)));

            // Extract ID3 with disk-backed covers
            $meta = SimpleID3::getMetadata($realPath, $coversDir);

            // Check if there is an album cover image in the same directory (cover.jpg, folder.jpg, artwork.png)
            $coverUrl = $meta['cover'];
            if (!$coverUrl) {
                $dir = dirname($realPath);
                $commonCovers = ['cover.jpg', 'cover.png', 'folder.jpg', 'folder.png', 'album.jpg', 'album.png', pathinfo($fileName, PATHINFO_FILENAME) . '.jpg', pathinfo($fileName, PATHINFO_FILENAME) . '.png'];
                foreach ($commonCovers as $cov) {
                    if (file_exists($dir . '/' . $cov)) {
                        $covRel = 'songs/' . str_replace('\\', '/', substr(realpath($dir . '/' . $cov), strlen(realpath($songsDir)) + 1));
                        $coverUrl = $covRel;
                        break;
                    }
                }
            }

            // Strictly verify cover file exists on disk to prevent any 404
            if ($coverUrl && !file_exists(__DIR__ . '/../' . $coverUrl)) {
                $coverUrl = null;
            }

            // Check for lyric file
            $lyricsUrl = null;
            $lrcPath = dirname($realPath) . '/' . pathinfo($fileName, PATHINFO_FILENAME) . '.lrc';
            if (file_exists($lrcPath)) {
                $lrcRel = str_replace('\\', '/', substr(realpath($lrcPath), strlen(realpath($songsDir)) + 1));
                $lyricsUrl = 'songs/' . implode('/', array_map('rawurlencode', explode('/', $lrcRel)));
            }

            $songs[] = [
                'id' => 'track_' . md5($relPath),
                'title' => !empty($meta['title']) ? $meta['title'] : pathinfo($fileName, PATHINFO_FILENAME),
                'artist' => !empty($meta['artist']) ? $meta['artist'] : 'Unknown Artist',
                'album' => !empty($meta['album']) ? $meta['album'] : 'Single',
                'year' => $meta['year'] ?? '',
                'genre' => $meta['genre'] ?? 'Other',
                'url' => 'songs/' . $encodedRelPath,
                'filename' => $fileName,
                'cover' => $coverUrl,
                'lyrics' => $lyricsUrl,
                'size' => $fileSize,
                'modified' => $fileMtime
            ];
        }
    }
} catch (Exception $e) {
    // Graceful fallback on directory iteration error
}

$cachePayload = [
    'timestamp' => time(),
    'songs' => $songs
];

// Save to disk cache and Redis/APCu memory
@file_put_contents($cacheFile, json_encode($cachePayload, JSON_UNESCAPED_SLASHES));
AuraCache::set('library_scan', $cachePayload, 3600);

$etag = md5((string)$cachePayload['timestamp']);
AuraCache::checkETagAndExit($etag, 1800);

echo json_encode([
    'status' => 'success',
    'cached' => false,
    'songs_dir' => realpath($songsDir) ?: $songsDir,
    'total' => count($songs),
    'songs' => $songs
], JSON_UNESCAPED_SLASHES);
