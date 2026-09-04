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
$duration = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $title = $body['title'] ?? ($_POST['title'] ?? '');
    $artist = $body['artist'] ?? ($_POST['artist'] ?? '');
    $filename = $body['filename'] ?? ($_POST['filename'] ?? '');
    $duration = floatval($body['duration'] ?? ($_POST['duration'] ?? 0));
} else {
    $title = $_GET['title'] ?? '';
    $artist = $_GET['artist'] ?? '';
    $filename = $_GET['filename'] ?? '';
    $duration = floatval($_GET['duration'] ?? 0);
}

if (empty($title) && empty($filename)) {
    echo json_encode(['status' => 'error', 'message' => 'Judul lagu atau nama file diperlukan']);
    exit;
}

/**
 * Intelligent string cleaner for music titles and artists
 */
function clean_music_text($str) {
    if (!$str) return '';
    // 1. Remove hashtags (e.g. #music, #shorts, #viral, #remix)
    $str = preg_replace('/#[\w\d_\-]+/u', ' ', $str);
    // 2. Remove parenthesized/bracketed clutter
    $str = preg_replace('/\[(?:official|audio|video|lyrics|lirik|mv|hd|4k|remastered|hq|clip|explicit|visualizer).*?\]/iu', ' ', $str);
    $str = preg_replace('/\((?:official|audio|video|lyrics|lirik|mv|hd|4k|remastered|hq|clip|explicit|visualizer).*?\)/iu', ' ', $str);
    $str = preg_replace('/【.*?】|（.*?）/u', ' ', $str);
    // 3. Remove standalone noise words
    $str = preg_replace('/\b(official\s+video|official\s+music\s+video|official\s+audio|lyrics\s+video|music\s+video|audio\s+visualizer|lyric\s+video|full\s+version|audio\s+only|lirik\s+lagu)\b/iu', ' ', $str);
    // 4. Remove unwanted symbols but preserve letters, numbers, apostrophes
    $str = preg_replace('/[^\p{L}\p{N}\s\'\-_]/u', ' ', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

/**
 * Extract Artist and Title from filename if structured like "Artist - Title"
 */
function parse_filename_meta($filename) {
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $base = preg_replace('/#[\w\d_\-]+/u', ' ', $base);
    
    // Check standard separators: " - ", " – ", " — ", " _ "
    if (preg_match('/^(.*?)\s*[-–—_]\s*(.*?)$/u', $base, $m)) {
        return [
            'artist' => clean_music_text($m[1]),
            'title' => clean_music_text($m[2])
        ];
    }
    return [
        'artist' => '',
        'title' => clean_music_text($base)
    ];
}

// Perform smart cleaning
$cleanTitle = clean_music_text($title);
$cleanArtist = ($artist && $artist !== 'Unknown Artist') ? clean_music_text($artist) : '';

$fileMeta = !empty($filename) ? parse_filename_meta($filename) : ['artist' => '', 'title' => ''];
if (empty($cleanArtist) && !empty($fileMeta['artist'])) {
    $cleanArtist = $fileMeta['artist'];
}
if (empty($cleanTitle) && !empty($fileMeta['title'])) {
    $cleanTitle = $fileMeta['title'];
}

/**
 * Fast & reliable HTTP GET using cURL with fallback to file_get_contents
 */
function fetch_api($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 7);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AuraMusicPlayer/2.2 (https://github.com/frambudi75/musik-player)');
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $res) {
            return $res;
        }
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 7, 'user_agent' => 'AuraMusicPlayer/2.2'],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    return @file_get_contents($url, false, $ctx);
}

$syncedLyrics = null;
$plainLyrics = null;

// Search Permutation 1: Direct Exact Lookup via LRCLIB
if (!empty($cleanTitle)) {
    $params = ['track_name' => $cleanTitle];
    if (!empty($cleanArtist)) {
        $params['artist_name'] = $cleanArtist;
    }
    $resp = fetch_api('https://lrclib.net/api/get?' . http_build_query($params));
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['syncedLyrics'])) {
            $syncedLyrics = $data['syncedLyrics'];
        } elseif (!empty($data['plainLyrics'])) {
            $plainLyrics = $data['plainLyrics'];
        }
    }
}

// Search Permutation 2: Search Query with Artist + Title
if (!$syncedLyrics) {
    $queries = array_filter(array_unique([
        trim("{$cleanArtist} {$cleanTitle}"),
        $cleanTitle,
        !empty($fileMeta['title']) ? trim("{$fileMeta['artist']} {$fileMeta['title']}") : '',
        !empty($filename) ? clean_music_text(pathinfo($filename, PATHINFO_FILENAME)) : ''
    ]));

    foreach ($queries as $q) {
        if (empty($q)) continue;
        $resp = fetch_api('https://lrclib.net/api/search?' . http_build_query(['q' => $q]));
        if (!$resp) continue;

        $results = json_decode($resp, true);
        if (!is_array($results) || empty($results)) continue;

        // If duration provided, sort results by closest duration match
        if ($duration > 0) {
            usort($results, function($a, $b) use ($duration) {
                $durA = floatval($a['duration'] ?? 0);
                $durB = floatval($b['duration'] ?? 0);
                return abs($durA - $duration) <=> abs($durB - $duration);
            });
        }

        // Pick best synced lyrics
        foreach ($results as $item) {
            if (!empty($item['syncedLyrics'])) {
                $syncedLyrics = $item['syncedLyrics'];
                break 2;
            }
        }

        // Fallback plain lyrics if none found yet
        if (!$plainLyrics && !empty($results[0]['plainLyrics'])) {
            $plainLyrics = $results[0]['plainLyrics'];
        }
    }
}

// Search Permutation 3: Netease Cloud Music Fallback (for Anime / J-Pop / K-Pop / Asian Music)
if (!$syncedLyrics && !empty($cleanTitle)) {
    $neteaseSearch = fetch_api('https://music.163.com/api/search/get/web?' . http_build_query([
        's' => trim("{$cleanArtist} {$cleanTitle}"),
        'type' => 1,
        'offset' => 0,
        'total' => 'true',
        'limit' => 3
    ]));
    if ($neteaseSearch) {
        $nData = json_decode($neteaseSearch, true);
        $songs = $nData['result']['songs'] ?? [];
        if (!empty($songs) && isset($songs[0]['id'])) {
            $songId = $songs[0]['id'];
            $lrcResp = fetch_api("https://music.163.com/api/song/lyric?os=pc&id={$songId}&lv=-1&kv=-1&tv=-1");
            if ($lrcResp) {
                $lrcData = json_decode($lrcResp, true);
                if (!empty($lrcData['lrc']['lyric']) && strpos($lrcData['lrc']['lyric'], '[') !== false) {
                    $syncedLyrics = $lrcData['lrc']['lyric'];
                }
            }
        }
    }
}

$chosenLyrics = $syncedLyrics ?: $plainLyrics;

// If lyrics found and filename provided, save automatically to .lrc file in songs folder
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
        'lyrics_url' => 'songs/' . rawurlencode($baseName) . '.lrc',
        'saved_file' => $baseName . '.lrc',
        'message' => !empty($syncedLyrics) ? 'Lirik sinkron (.LRC) berhasil ditemukan & disimpan!' : 'Lirik teks berhasil ditemukan!'
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
    'message' => 'Lirik tidak ditemukan di database online untuk "' . ($cleanTitle ?: $filename) . '"'
], JSON_UNESCAPED_UNICODE);

