<?php
/**
 * Aura Music - Auto Metadata & High-Res Artwork Enricher
 * Connects to iTunes Search & MusicBrainz API with caching
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/cache_helper.php';

$title = trim($_GET['title'] ?? $_POST['title'] ?? '');
$artist = trim($_GET['artist'] ?? $_POST['artist'] ?? '');

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Judul lagu wajib diisi untuk pencarian metadata']);
    exit;
}

// Clean title from common suffixes like (Official Music Video), [Lyric Video], etc.
$cleanTitle = preg_replace('/\s*[\(\[](official\s*(music\s*)?video|lyrics?|audio|hd|4k|mv|remastered|explicit)[\)\]]/i', '', $title);
$cleanArtist = preg_replace('/\s*[\(\[](official\s*(music\s*)?video|lyrics?|audio|hd)[\)\]]/i', '', $artist);

$cacheKey = 'meta_enrich_' . md5(strtolower($cleanTitle . '_' . $cleanArtist));
$cached = AuraCache::get($cacheKey);
if ($cached) {
    echo json_encode(['status' => 'success', 'data' => $cached, 'cached' => true]);
    exit;
}

$results = [];

// 1. Search iTunes Store API (Fast, Reliable, High-Res 1000x1000 Covers)
$searchTerm = trim($cleanArtist . ' ' . $cleanTitle);
$itunesUrl = 'https://itunes.apple.com/search?term=' . urlencode($searchTerm) . '&entity=song&limit=6';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $itunesUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 6,
    CURLOPT_USERAGENT => 'AuraMusic/2.0 (Audio Metadata Enricher)'
]);
$rawResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $rawResponse) {
    $json = json_decode($rawResponse, true);
    if (!empty($json['results'])) {
        foreach ($json['results'] as $item) {
            $cover100 = $item['artworkUrl100'] ?? '';
            // Upgrade to pristine 1000x1000 / 600x600 resolution artwork
            $coverHd = !empty($cover100) ? preg_replace('/100x100bb\./i', '1000x1000bb.', $cover100) : '';
            if (empty($coverHd)) {
                $coverHd = !empty($cover100) ? preg_replace('/100x100bb\./i', '600x600bb.', $cover100) : '';
            }

            $year = '';
            if (!empty($item['releaseDate'])) {
                $year = substr($item['releaseDate'], 0, 4);
            }

            $results[] = [
                'title' => $item['trackName'] ?? $title,
                'artist' => $item['artistName'] ?? $artist,
                'album' => $item['collectionName'] ?? '',
                'genre' => $item['primaryGenreName'] ?? 'Pop',
                'year' => $year,
                'cover_url' => $coverHd ?: $cover100,
                'preview_url' => $item['previewUrl'] ?? '',
                'source' => 'iTunes'
            ];
        }
    }
}

// Fallback search if artist + title had no hits, search with title alone
if (empty($results)) {
    $fallbackUrl = 'https://itunes.apple.com/search?term=' . urlencode($cleanTitle) . '&entity=song&limit=4';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fallbackUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_USERAGENT => 'AuraMusic/2.0 (Audio Metadata Enricher)'
    ]);
    $rawFallback = curl_exec($ch);
    curl_close($ch);

    if ($rawFallback) {
        $json = json_decode($rawFallback, true);
        if (!empty($json['results'])) {
            foreach ($json['results'] as $item) {
                $cover100 = $item['artworkUrl100'] ?? '';
                $coverHd = !empty($cover100) ? preg_replace('/100x100bb\./i', '1000x1000bb.', $cover100) : '';
                $year = !empty($item['releaseDate']) ? substr($item['releaseDate'], 0, 4) : '';

                $results[] = [
                    'title' => $item['trackName'] ?? $title,
                    'artist' => $item['artistName'] ?? $artist,
                    'album' => $item['collectionName'] ?? '',
                    'genre' => $item['primaryGenreName'] ?? 'Pop',
                    'year' => $year,
                    'cover_url' => $coverHd ?: $cover100,
                    'preview_url' => $item['previewUrl'] ?? '',
                    'source' => 'iTunes'
                ];
            }
        }
    }
}

if (!empty($results)) {
    AuraCache::set($cacheKey, $results, 86400); // 24hr cache
    echo json_encode([
        'status' => 'success',
        'total' => count($results),
        'data' => $results
    ]);
} else {
    echo json_encode([
        'status' => 'empty',
        'message' => 'Tidak ditemukan metadata resmi yang cocok',
        'data' => []
    ]);
}
