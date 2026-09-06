<?php
/**
 * NadaKita - On-Demand YouTube Audio Stream Proxy API
 * Streams audio directly to the browser with HTTP 206 Partial Content / Range support and CORS
 */

// Disable output buffering and time limits for continuous streaming
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@set_time_limit(0);

require_once __DIR__ . '/cache_helper.php';

// Handle CORS Preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Access-Control-Allow-Headers: Range, Content-Type');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

$videoId = trim($_GET['id'] ?? '');

// Basic video ID sanitization (alphanumeric, -, _)
if (!preg_match('/^[a-zA-Z0-9_\-]{6,15}$/', $videoId)) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Video ID tidak valid.']);
    exit;
}

// 1. Check Stream URL Cache (TTL 4 hours = 14400s)
$cacheKey = 'yt_stream_' . $videoId;
$streamData = AuraCache::get($cacheKey);

if (!$streamData || empty($streamData['stream_url'])) {
    $pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'stream_extractor.py';
    $cmd = sprintf('python %s stream %s', escapeshellarg($pythonScript), escapeshellarg($videoId));

    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    $rawJson = implode("\n", $output);
    $data = json_decode($rawJson, true);

    if ($returnCode !== 0 || !$data || !isset($data['status']) || $data['status'] !== 'success' || empty($data['stream_url'])) {
        header('HTTP/1.1 502 Bad Gateway');
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $data['message'] ?? 'Gagal mengekstrak stream audio dari YouTube.'
        ]);
        exit;
    }

    $streamData = $data;
    // Cache stream URL for 4 hours (14400s)
    AuraCache::set($cacheKey, $streamData, 14400);
}

$streamUrl = $streamData['stream_url'];
$ext = $streamData['ext'] ?? 'm4a';
$mimeType = ($ext === 'opus' || $ext === 'webm') ? 'audio/webm' : 'audio/mp4';

// 2. Stream Audio with HTTP Range forwarding
$range = $_SERVER['HTTP_RANGE'] ?? '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $streamUrl);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024); // 128KB chunks
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);

$reqHeaders = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Accept: */*'
];

if (!empty($range)) {
    curl_setopt($ch, CURLOPT_RANGE, str_replace('bytes=', '', $range));
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

// Capture response headers from YouTube CDN
$sentHeaders = false;
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$sentHeaders, $mimeType) {
    $len = strlen($header);
    $headerTrim = trim($header);
    
    if (empty($headerTrim)) {
        return $len;
    }

    // Pass relevant headers to browser
    $lower = strtolower($headerTrim);
    if (strpos($lower, 'http/') === 0) {
        header($headerTrim);
    } elseif (strpos($lower, 'content-range:') === 0 ||
              strpos($lower, 'content-length:') === 0 ||
              strpos($lower, 'accept-ranges:') === 0) {
        header($headerTrim);
    }

    if (!$sentHeaders && empty($headerTrim)) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
        header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=14400');
        $sentHeaders = true;
    }

    return $len;
});

// Write audio chunks directly to client output
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$sentHeaders, $mimeType) {
    if (!$sentHeaders) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
        header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=14400');
        $sentHeaders = true;
    }
    echo $data;
    if (ob_get_level() > 0) {
        @ob_flush();
    }
    flush();
    return strlen($data);
});

curl_exec($ch);

if (curl_errno($ch)) {
    // If stream URL expired, clear cache so next request regenerates
    if (curl_errno($ch) === 28 || curl_getinfo($ch, CURLINFO_HTTP_CODE) === 403) {
        AuraCache::delete($cacheKey);
    }
}

curl_close($ch);
exit;
