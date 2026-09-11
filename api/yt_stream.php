<?php
// Disable output buffering for real-time streaming
while (ob_get_level()) {
    ob_end_clean();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Range');
    exit(0);
}

set_time_limit(600);
ini_set('memory_limit', '256M');

if (isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

$videoId = trim($_GET['id'] ?? $_POST['id'] ?? $_GET['url'] ?? $_POST['url'] ?? '');
$action = $_GET['action'] ?? 'audio'; // default to 'audio'

if (empty($videoId)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['status' => 'error', 'message' => 'Video ID atau URL YouTube wajib diisi']);
    exit;
}

// Function to find Python binary across Linux (aaPanel/Ubuntu/CentOS) and Windows
function getPythonExecutable() {
    if (PHP_OS_FAMILY === 'Windows') {
        return 'python';
    }
    $candidates = [
        '/usr/bin/python3',
        '/usr/local/bin/python3',
        '/usr/bin/python',
        '/bin/python3',
        '/bin/python',
        'python3',
        'python'
    ];
    $aaPanelEnvs = glob('/www/server/pyproject_evn/*/bin/python');
    if (!empty($aaPanelEnvs)) {
        $candidates = array_merge($aaPanelEnvs, $candidates);
    }
    foreach ($candidates as $bin) {
        if (str_starts_with($bin, '/')) {
            if (file_exists($bin) && is_executable($bin)) {
                return $bin;
            }
        } else {
            return $bin;
        }
    }
    return 'python3';
}

$pythonBin = getPythonExecutable();

// 1. Check if video already exists as local MP3 in songs directory
$songsDir = __DIR__ . '/../songs';
$existingFile = null;
if (is_dir($songsDir)) {
    $files = @scandir($songsDir) ?: [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if (str_contains($f, "[$videoId]") || str_contains($f, $videoId)) {
            $existingFile = $songsDir . '/' . $f;
            break;
        }
    }
}

// If local file exists, serve local file
if ($existingFile && file_exists($existingFile)) {
    serveLocalAudio($existingFile);
    exit;
}

// If action is info, extract metadata JSON via yt_stream.py
if ($action === 'info') {
    $scriptPath = __DIR__ . '/yt_stream.py';
    $cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($videoId) . " 2>&1";
    $outputLines = [];
    $returnVar = 0;
    exec($cmd, $outputLines, $returnVar);

    $result = null;
    foreach (array_reverse($outputLines) as $line) {
        $decoded = json_decode($line, true);
        if ($decoded && isset($decoded['status'])) {
            $result = $decoded;
            break;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    if ($result && $result['status'] === 'success') {
        $result['proxy_stream_url'] = 'api/yt_stream.php?action=audio&id=' . urlencode($videoId);
        echo json_encode($result);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mengambil info YouTube: ' . implode("\n", $outputLines)
        ]);
    }
    exit;
}

// Action is audio: Stream real-time progressive audio
header('Content-Type: audio/mpeg');
header('Access-Control-Allow-Origin: *');
header('Accept-Ranges: none');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Accel-Buffering: no'); // Crucial for aaPanel Nginx to disable buffering

// Check if proc_open is available
$disabledFunctions = explode(',', ini_get('disable_functions') ?: '');
$disabledFunctions = array_map('trim', $disabledFunctions);
$canProcOpen = function_exists('proc_open') && !in_array('proc_open', $disabledFunctions);
$canPopen = function_exists('popen') && !in_array('popen', $disabledFunctions);

$pipeScript = __DIR__ . '/yt_pipe.py';

if ($canProcOpen) {
    $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($pipeScript) . ' ' . escapeshellarg($videoId);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, __DIR__);
    if (is_resource($proc)) {
        fclose($pipes[0]);
        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 16384);
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            if (connection_aborted()) {
                break;
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_terminate($proc);
        proc_close($proc);
        exit;
    }
} elseif ($canPopen) {
    $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($pipeScript) . ' ' . escapeshellarg($videoId);
    $handle = popen($cmd, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            $chunk = fread($handle, 16384);
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            if (connection_aborted()) {
                break;
            }
        }
        pclose($handle);
        exit;
    }
}

// Fallback if pipe cannot be opened: fetch direct URL and proxy with cURL
$scriptPath = __DIR__ . '/yt_stream.py';
$cmd = escapeshellcmd($pythonBin) . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($videoId) . " 2>&1";
$outputLines = [];
$returnVar = 0;
exec($cmd, $outputLines, $returnVar);
$result = null;
foreach (array_reverse($outputLines) as $line) {
    $decoded = json_decode($line, true);
    if ($decoded && isset($decoded['status'])) {
        $result = $decoded;
        break;
    }
}

if ($result && !empty($result['stream_url'])) {
    $ch = curl_init($result['stream_url']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_exec($ch);
    curl_close($ch);
}
exit;

/**
 * Serve local audio file with HTTP Range support for seeking
 */
function serveLocalAudio($filePath) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    $size = filesize($filePath);
    $mime = mime_content_type($filePath) ?: 'audio/mpeg';

    header("Content-Type: $mime");
    header("Accept-Ranges: bytes");
    header("Access-Control-Allow-Origin: *");
    header("X-Accel-Buffering: no");

    if (isset($_SERVER['HTTP_RANGE'])) {
        list($param, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strtolower(trim($param)) === 'bytes') {
            list($from, $to) = explode('-', $range);
            $from = intval($from);
            $to = $to ? intval($to) : $size - 1;
            if ($to >= $size) $to = $size - 1;

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $from-$to/$size");
            header('Content-Length: ' . ($to - $from + 1));

            $fp = fopen($filePath, 'rb');
            fseek($fp, $from);
            while (!feof($fp) && ($pos = ftell($fp)) <= $to) {
                $chunk = min(1024 * 64, $to - $pos + 1);
                echo fread($fp, $chunk);
                flush();
            }
            fclose($fp);
            exit;
        }
    }

    header("Content-Length: $size");
    readfile($filePath);
    exit;
}
