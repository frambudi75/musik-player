<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get input URL from POST JSON, POST form-data, or GET query
$url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!empty($body['url'])) {
        $url = trim($body['url']);
    } elseif (!empty($_POST['url'])) {
        $url = trim($_POST['url']);
    }
} elseif (isset($_GET['url'])) {
    $url = trim($_GET['url']);
}

if (empty($url)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter URL playlist atau lagu wajib diisi.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate basic URL structure
if (!filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'spotify:') !== 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Format URL tidak valid. Pastikan diawali https://...'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scriptPath = __DIR__ . '/playlist_extractor.py';

// Try standard python or full path
$pythonCommands = ['python', 'py', 'python3', 'C:\\Python310\\python.exe', 'C:\\Python311\\python.exe', 'C:\\Python312\\python.exe'];
$output = null;
$returnVar = 1;

foreach ($pythonCommands as $pyCmd) {
    $escapedUrl = escapeshellarg($url);
    $cmd = "{$pyCmd} \"{$scriptPath}\" {$escapedUrl} 2>&1";
    $output = @shell_exec($cmd);
    if ($output) {
        $data = @json_decode(trim($output), true);
        if ($data && isset($data['status'])) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// If shell_exec failed or returned raw output
if ($output) {
    $data = json_decode(trim($output), true);
    if ($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

echo json_encode([
    'status' => 'error',
    'message' => 'Gagal mengekstrak playlist. Pastikan link publik dan dapat diakses.',
    'raw_output' => $output
], JSON_UNESCAPED_UNICODE);
