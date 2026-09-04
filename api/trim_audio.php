<?php
// Audio Trimmer & Ringtone Generator Backend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


$songsDir = __DIR__ . '/../songs';

$filename = $_GET['filename'] ?? ($_POST['filename'] ?? '');
$start = max(0, floatval($_GET['start'] ?? ($_POST['start'] ?? 0)));
$duration = max(1, min(120, floatval($_GET['duration'] ?? ($_POST['duration'] ?? 30))));

if (empty($filename)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Parameter filename diperlukan']);
    exit;
}

$filename = basename($filename);
$sourcePath = $songsDir . '/' . $filename;

if (!file_exists($sourcePath)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'File musik tidak ditemukan']);
    exit;
}

$outputBase = pathinfo($filename, PATHINFO_FILENAME);
$downloadName = $outputBase . '_ringtone_' . intval($start) . 's.mp3';

// Strategy 1: Check if ffmpeg is available
$ffmpegPath = 'ffmpeg';
$hasFfmpeg = false;
@exec('ffmpeg -version 2>&1', $out, $ret);
if ($ret === 0) {
    $hasFfmpeg = true;
}

if ($hasFfmpeg) {
    $tempOutput = sys_get_temp_dir() . '/' . uniqid('trim_') . '.mp3';
    $cmd = sprintf(
        'ffmpeg -y -ss %s -t %s -i %s -acodec libmp3lame -b:a 192k %s 2>&1',
        escapeshellarg(strval($start)),
        escapeshellarg(strval($duration)),
        escapeshellarg($sourcePath),
        escapeshellarg($tempOutput)
    );
    @exec($cmd, $execOut, $execRet);

    if ($execRet === 0 && file_exists($tempOutput)) {
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Content-Length: ' . filesize($tempOutput));
        header('Cache-Control: no-cache');
        readfile($tempOutput);
        @unlink($tempOutput);
        exit;
    }
}

// Strategy 2: Python fallback if ffmpeg is not in system PATH
$pythonScript = __DIR__ . '/trimmer.py';
if (!file_exists($pythonScript)) {
    $pyCode = <<<'PY'
import sys, subprocess, os
# Try to find ffmpeg via imageio_ffmpeg or yt_dlp
ffmpeg_bin = 'ffmpeg'
try:
    import imageio_ffmpeg
    ffmpeg_bin = imageio_ffmpeg.get_ffmpeg_exe()
except:
    pass

src, start, dur, out = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4]
cmd = [ffmpeg_bin, '-y', '-ss', start, '-t', dur, '-i', src, '-acodec', 'libmp3lame', '-b:a', '192k', out]
p = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
sys.exit(p.returncode)
PY;
    @file_put_contents($pythonScript, $pyCode);
}

$tempOutput = sys_get_temp_dir() . '/' . uniqid('trim_') . '.mp3';
$cmd = sprintf(
    'python %s %s %s %s %s 2>&1',
    escapeshellarg($pythonScript),
    escapeshellarg($sourcePath),
    escapeshellarg(strval($start)),
    escapeshellarg(strval($duration)),
    escapeshellarg($tempOutput)
);
@exec($cmd, $pOut, $pRet);

if ($pRet === 0 && file_exists($tempOutput)) {
    header('Content-Type: audio/mpeg');
    header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
    header('Content-Length: ' . filesize($tempOutput));
    header('Cache-Control: no-cache');
    readfile($tempOutput);
    @unlink($tempOutput);
    exit;
}

// Fallback: If no encoder on server, send source file with attachment header
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . filesize($sourcePath));
readfile($sourcePath);
exit;
