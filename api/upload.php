<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/auth_guard.php';
AuraAuth::requireAuth();


$songsDir = __DIR__ . '/../songs';
if (!is_dir($songsDir)) {
    mkdir($songsDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Hanya menerima request POST']);
    exit;
}

$uploadedFiles = [];
$errors = [];

// Handle Audio Files
if (!empty($_FILES['audio'])) {
    $audio = $_FILES['audio'];
    $allowedExts = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'opus'];

    // Handle single or multiple
    $files = is_array($audio['name']) ? $audio : [
        'name' => [$audio['name']],
        'type' => [$audio['type']],
        'tmp_name' => [$audio['tmp_name']],
        'error' => [$audio['error']],
        'size' => [$audio['size']]
    ];

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $originalName = basename($files['name'][$i]);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (in_array($ext, $allowedExts)) {
                // Sanitize filename but preserve readability
                $cleanName = preg_replace('/[^\w\s\d\-_~,;\[\]\(\).]/u', '', $originalName);
                if (empty($cleanName)) {
                    $cleanName = 'track_' . time() . '.' . $ext;
                }
                $targetPath = $songsDir . '/' . $cleanName;

                if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                    $uploadedFiles[] = $cleanName;
                } else {
                    $errors[] = "Gagal memindahkan file: {$originalName}";
                }
            } else {
                $errors[] = "Format file tidak didukung: {$originalName}";
            }
        }
    }
}

// Handle Lyric Files (.lrc)
if (!empty($_FILES['lyrics'])) {
    $lrc = $_FILES['lyrics'];
    if ($lrc['error'] === UPLOAD_ERR_OK) {
        $originalName = basename($lrc['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'lrc' || $ext === 'txt') {
            $cleanName = preg_replace('/[^\w\s\d\-_~,;\[\]\(\).]/u', '', $originalName);
            $targetPath = $songsDir . '/' . $cleanName;
            move_uploaded_file($lrc['tmp_name'], $targetPath);
        }
    }
}

// Invalidate library cache
require_once __DIR__ . '/cache_helper.php';
$cacheFile = $songsDir . '/.cache_library.json';
if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}
AuraCache::delete('library_scan');

if (!empty($uploadedFiles)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Berhasil mengunggah ' . count($uploadedFiles) . ' file musik',
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => !empty($errors) ? implode(', ', $errors) : 'Tidak ada file audio yang diunggah'
    ]);
}
