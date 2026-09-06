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

/**
 * Detect available Python 3 binary
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
 * Fallback to Invidious audio stream if Python yt_dlp is not available
 */
function purePhpGetStreamData($videoId) {
    $invidiousInstances = [
        'https://invidious.nerdvpn.de',
        'https://inv.nadeko.net',
        'https://invidious.drgns.space',
        'https://vid.priv.au'
    ];

    foreach ($invidiousInstances as $instance) {
        $url = $instance . '/api/v1/videos/' . urlencode($videoId);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            if ($data && !empty($data['adaptiveFormats'])) {
                $bestAudio = null;
                $highestBitrate = 0;
                foreach ($data['adaptiveFormats'] as $fmt) {
                    $type = $fmt['type'] ?? '';
                    if (stripos($type, 'audio') !== false && !empty($fmt['url'])) {
                        $bitrate = intval($fmt['bitrate'] ?? 0);
                        if ($bitrate > $highestBitrate) {
                            $highestBitrate = $bitrate;
                            $bestAudio = $fmt;
                        }
                    }
                }

                if ($bestAudio && !empty($bestAudio['url'])) {
                    $ext = (stripos($bestAudio['type'], 'webm') !== false) ? 'webm' : 'm4a';
                    return [
                        'status' => 'success',
                        'id' => $videoId,
                        'title' => $data['title'] ?? 'YouTube Audio',
                        'artist' => $data['author'] ?? 'YouTube Artist',
                        'duration' => intval($data['lengthSeconds'] ?? 0),
                        'stream_url' => $bestAudio['url'],
                        'ext' => $ext
                    ];
                }
            }
        }
    }

    return null;
}

// 1. Check Stream URL Cache (TTL 4 hours = 14400s)
$cacheKey = 'yt_stream_' . $videoId;
$streamData = AuraCache::get($cacheKey);

if (!$streamData || empty($streamData['stream_url'])) {
    $extracted = null;

    // Try Python yt_dlp extractor
    if (function_exists('exec')) {
        $py = getPythonCommand();
        $pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'stream_extractor.py';
        $cmd = sprintf('%s %s stream %s', $py, escapeshellarg($pythonScript), escapeshellarg($videoId));

        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            $rawJson = implode("\n", $output);
            $data = json_decode($rawJson, true);
            if ($data && isset($data['status']) && $data['status'] === 'success' && !empty($data['stream_url'])) {
                $extracted = $data;
            }
        }
    }

    // If Python failed, try pure PHP stream resolver
    if (!$extracted) {
        $extracted = purePhpGetStreamData($videoId);
    }

    if (!$extracted || empty($extracted['stream_url'])) {
        header('HTTP/1.1 502 Bad Gateway');
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mengekstrak stream audio dari YouTube. Pastikan server memiliki koneksi internet.'
        ]);
        exit;
    }

    $streamData = $extracted;
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
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 64 * 1024); // 64KB chunks for smooth buffering
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

// Capture and forward response headers
$sentHeaders = false;
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$sentHeaders, $mimeType, $range) {
    $len = strlen($header);
    $headerTrim = trim($header);
    
    if (empty($headerTrim)) {
        // End of headers, send CORS & Content-Type
        if (!$sentHeaders) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
            header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
            header('Content-Type: ' . $mimeType);
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=14400');
            $sentHeaders = true;
        }
        return $len;
    }

    $lower = strtolower($headerTrim);
    if (strpos($lower, 'http/') === 0) {
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode === 206 || (!empty($range) && $httpCode === 200)) {
            http_response_code(206);
        } elseif ($httpCode > 0) {
            http_response_code($httpCode);
        }
    } elseif (strpos($lower, 'content-range:') === 0 ||
              strpos($lower, 'content-length:') === 0 ||
              strpos($lower, 'accept-ranges:') === 0) {
        header($headerTrim);
    }

    return $len;
});

// Write audio chunks directly to client
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$sentHeaders, $mimeType, $range) {
    if (!$sentHeaders) {
        if (!empty($range)) {
            http_response_code(206);
        }
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

$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch) || $httpStatus === 403 || $httpStatus === 410) {
    // If URL expired, clear cache
    AuraCache::delete($cacheKey);
}

curl_close($ch);
exit;
