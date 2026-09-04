<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$songsDir = __DIR__ . '/../songs';
$cacheFile = __DIR__ . '/../songs/.cache_library.json';

$title = '';
$artist = '';
$filename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $title = $body['title'] ?? ($_POST['title'] ?? '');
    $artist = $body['artist'] ?? ($_POST['artist'] ?? '');
    $filename = $body['filename'] ?? ($_POST['filename'] ?? '');
} else {
    $title = $_GET['title'] ?? '';
    $artist = $_GET['artist'] ?? '';
    $filename = $_GET['filename'] ?? '';
}

if (empty($title) && empty($filename)) {
    echo json_encode(['status' => 'error', 'message' => 'Judul lagu atau nama file diperlukan']);
    exit;
}

// Clean title and artist for better query matching
function clean_music_title($str) {
    // Remove unwanted tags like [Official Music Video], (Audio), (Lyrics), 【MV】, ft. etc.
    $str = preg_replace('/\[.*?\]|\(.*?\)|【.*?】|（.*?）/u', ' ', $str);
    $str = preg_replace('/\b(official|music|video|audio|lyrics|lyric|remaster|hd|4k|mv|ver|version|full)\b/iu', ' ', $str);
    $str = preg_replace('/[^\p{L}\p{N}\s\-_]/u', ' ', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

$cleanTitle = clean_music_title($title);
$cleanArtist = clean_music_title($artist);

if ($cleanArtist === 'Unknown Artist') {
    $cleanArtist = '';
}

// Prepare SSL context for external request
$ctx = stream_context_create([
    'http' => [
        'timeout' => 8,
        'user_agent' => 'AuraMusic/2.0 (Local Music Player)'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$syncedLyrics = null;
$plainLyrics = null;

// Strategy 1: Direct get with track_name & artist_name
if (!empty($cleanTitle)) {
    $params = ['track_name' => $cleanTitle];
    if (!empty($cleanArtist)) {
        $params['artist_name'] = $cleanArtist;
    }
    $url = 'https://lrclib.net/api/get?' . http_build_query($params);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['syncedLyrics'])) {
            $syncedLyrics = $data['syncedLyrics'];
        } elseif (!empty($data['plainLyrics'])) {
            $plainLyrics = $data['plainLyrics'];
        }
    }
}

// Strategy 2: Search API query
if (!$syncedLyrics && !$plainLyrics) {
    $q = trim("{$cleanArtist} {$cleanTitle}");
    if (empty($q) && !empty($filename)) {
        $q = clean_music_title(pathinfo($filename, PATHINFO_FILENAME));
    }
    
    if (!empty($q)) {
        $searchUrl = 'https://lrclib.net/api/search?' . http_build_query(['q' => $q]);
        $resp = @file_get_contents($searchUrl, false, $ctx);
        if ($resp) {
            $results = json_decode($resp, true);
            if (is_array($results) && count($results) > 0) {
                // Find first result with synced lyrics
                foreach ($results as $item) {
                    if (!empty($item['syncedLyrics'])) {
                        $syncedLyrics = $item['syncedLyrics'];
                        break;
                    }
                }
                if (!$syncedLyrics && !empty($results[0]['plainLyrics'])) {
                    $plainLyrics = $results[0]['plainLyrics'];
                }
            }
        }
    }
}

$chosenLyrics = $syncedLyrics ?: $plainLyrics;

if ($chosenLyrics && !empty($filename)) {
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $lrcFilePath = $songsDir . '/' . $baseName . '.lrc';
    @file_put_contents($lrcFilePath, $chosenLyrics);

    // Invalidate library cache so next scan picks up the .lrc file
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    echo json_encode([
        'status' => 'success',
        'is_synced' => !empty($syncedLyrics),
        'lyrics' => $chosenLyrics,
        'lyrics_url' => 'songs/' . $baseName . '.lrc',
        'message' => 'Lirik sinkron berhasil ditemukan dan disimpan!'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($chosenLyrics) {
    echo json_encode([
        'status' => 'success',
        'is_synced' => !empty($syncedLyrics),
        'lyrics' => $chosenLyrics,
        'lyrics_url' => null,
        'message' => 'Lirik berhasil ditemukan!'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'status' => 'not_found',
    'message' => 'Lirik sinkron tidak ditemukan untuk lagu ini.'
], JSON_UNESCAPED_UNICODE);
