<?php
/**
 * NadaKita - YouTube Music & Audio Search API
 * Searches YouTube for audio streams without downloading
 * Supports Python yt_dlp extractor + Invidious API & Direct Scraping Fallback
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
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

/**
 * Detect available Python 3 binary across OS / aaPanel / Linux / Windows
 */
function getPythonCommand() {
    static $cmd = null;
    if ($cmd !== null) return $cmd;

    $candidates = [
        'python3',
        'python',
        '/usr/bin/python3',
        '/usr/local/bin/python3',
        '/www/server/pyenv/bin/python3',
        '/www/server/pyenv/bin/python',
        '/bin/python3'
    ];

    if (function_exists('exec')) {
        foreach ($candidates as $c) {
            $out = [];
            $code = 0;
            @exec("$c --version 2>&1", $out, $code);
            if ($code === 0 && !empty($out)) {
                $ver = implode(' ', $out);
                if (stripos($ver, 'Python') !== false) {
                    $cmd = $c;
                    return $cmd;
                }
            }
        }
    }

    $cmd = (DIRECTORY_SEPARATOR === '\\') ? 'python' : 'python3';
    return $cmd;
}

/**
 * Pure PHP YouTube Search Fallback (Invidious API + Direct YouTube Scraper)
 */
function purePhpYouTubeSearch($query, $limit = 10) {
    // 1. Try Invidious Public Mirrors
    $invidiousInstances = [
        'https://invidious.nerdvpn.de',
        'https://inv.nadeko.net',
        'https://invidious.drgns.space',
        'https://vid.priv.au'
    ];

    foreach ($invidiousInstances as $instance) {
        $url = $instance . '/api/v1/search?q=' . urlencode($query) . '&type=video';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $items = json_decode($resp, true);
            if (is_array($items) && !empty($items)) {
                $results = [];
                $count = 0;
                foreach ($items as $item) {
                    $vidId = $item['videoId'] ?? '';
                    if (!$vidId) continue;

                    $results[] = [
                        'id' => $vidId,
                        'title' => $item['title'] ?? 'Unknown Title',
                        'artist' => $item['author'] ?? 'Unknown Artist',
                        'duration' => intval($item['lengthSeconds'] ?? 0),
                        'thumbnail' => "https://i.ytimg.com/vi/{$vidId}/hqdefault.jpg",
                        'url' => "https://www.youtube.com/watch?v={$vidId}"
                    ];
                    $count++;
                    if ($count >= $limit) break;
                }
                if (!empty($results)) return $results;
            }
        }
    }

    // 2. Direct YouTube Scraping via cURL
    $ytUrl = 'https://www.youtube.com/results?search_query=' . urlencode($query);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ytUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Accept-Language: id,en;q=0.9'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html && preg_match('/var ytInitialData = ({.*?});<\/script>/s', $html, $matches)) {
        $ytData = json_decode($matches[1], true);
        $sectionContents = $ytData['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
        $results = [];
        $count = 0;

        foreach ($sectionContents as $section) {
            $contents = $section['itemSectionRenderer']['contents'] ?? [];
            foreach ($contents as $c) {
                $v = $c['videoRenderer'] ?? null;
                if (!$v || empty($v['videoId'])) continue;
                $vidId = $v['videoId'];
                $title = $v['title']['runs'][0]['text'] ?? ($v['title']['simpleText'] ?? 'Unknown');
                $artist = $v['ownerText']['runs'][0]['text'] ?? ($v['longBylineText']['runs'][0]['text'] ?? 'Unknown Artist');

                $durText = $v['lengthText']['simpleText'] ?? '';
                $durSec = 0;
                if ($durText) {
                    $parts = explode(':', $durText);
                    if (count($parts) === 2) {
                        $durSec = intval($parts[0]) * 60 + intval($parts[1]);
                    } else if (count($parts) === 3) {
                        $durSec = intval($parts[0]) * 3600 + intval($parts[1]) * 60 + intval($parts[2]);
                    }
                }

                $results[] = [
                    'id' => $vidId,
                    'title' => $title,
                    'artist' => $artist,
                    'duration' => $durSec,
                    'thumbnail' => "https://i.ytimg.com/vi/{$vidId}/hqdefault.jpg",
                    'url' => "https://www.youtube.com/watch?v={$vidId}"
                ];
                $count++;
                if ($count >= $limit) break;
            }
            if ($count >= $limit) break;
        }

        if (!empty($results)) return $results;
    }

    return [];
}

$results = [];

// Method 1: Try Python yt_dlp search if exec is available
if (function_exists('exec')) {
    $py = getPythonCommand();
    $pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'stream_extractor.py';
    $cmd = sprintf('%s %s search %s %d', $py, escapeshellarg($pythonScript), escapeshellarg($query), $limit);

    $output = [];
    $returnCode = 0;
    @exec($cmd, $output, $returnCode);

    if ($returnCode === 0 && !empty($output)) {
        $rawJson = implode("\n", $output);
        $data = json_decode($rawJson, true);
        if ($data && isset($data['status']) && $data['status'] === 'success' && !empty($data['results'])) {
            $results = $data['results'];
        }
    }
}

// Method 2: If Python didn't return results, use Pure PHP fallback
if (empty($results)) {
    $results = purePhpYouTubeSearch($query, $limit);
}

if (empty($results)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mencari musik di YouTube. Pastikan server memiliki koneksi internet dan modul python / yt-dlp terpasang.'
    ]);
    exit;
}

// Cache results for 1 hour (3600s)
AuraCache::set($cacheKey, $results, 3600);

echo json_encode([
    'status' => 'success',
    'cached' => false,
    'query' => $query,
    'total' => count($results),
    'results' => $results
]);
