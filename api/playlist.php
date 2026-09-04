<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/cache_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    exit(0);
}

$dataFile = __DIR__ . '/../data_playlists.json';

// Initialize default data if not exists
if (!file_exists($dataFile)) {
    $initialData = [
        'playlists' => [
            [
                'id' => 'favorites',
                'name' => 'Liked Songs',
                'description' => 'Lagu-lagu favorit pilihan Anda',
                'created_at' => time(),
                'song_ids' => []
            ]
        ],
        'history' => []
    ];
    file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

if ($method === 'GET') {
    $etag = file_exists($dataFile) ? md5_file($dataFile) : 'empty';
    AuraCache::checkETagAndExit($etag, 300);

    $cached = AuraCache::get('playlists_data');
    if ($cached) {
        echo json_encode(['status' => 'success', 'data' => $cached]);
        exit;
    }

    $data = json_decode(@file_get_contents($dataFile), true) ?: ['playlists' => [], 'history' => []];
    AuraCache::set('playlists_data', $data, 3600);
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) {
        $payload = $_POST;
    }

    $action = $payload['action'] ?? '';
    $currentData = json_decode(file_get_contents($dataFile), true) ?: ['playlists' => [], 'history' => []];

    switch ($action) {
        case 'save_all':
            if (isset($payload['playlists'])) {
                $currentData['playlists'] = $payload['playlists'];
            }
            if (isset($payload['history'])) {
                $currentData['history'] = $payload['history'];
            }
            file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
            AuraCache::set('playlists_data', $currentData, 3600);
            echo json_encode(['status' => 'success', 'message' => 'Data tersimpan']);
            break;

        case 'toggle_like':
            $songId = $payload['song_id'] ?? '';
            if ($songId) {
                $favIndex = -1;
                foreach ($currentData['playlists'] as $i => $pl) {
                    if ($pl['id'] === 'favorites') {
                        $favIndex = $i;
                        break;
                    }
                }
                if ($favIndex === -1) {
                    $currentData['playlists'][] = [
                        'id' => 'favorites',
                        'name' => 'Liked Songs',
                        'description' => 'Lagu-lagu favorit pilihan Anda',
                        'created_at' => time(),
                        'song_ids' => [$songId]
                    ];
                    $isLiked = true;
                } else {
                    $key = array_search($songId, $currentData['playlists'][$favIndex]['song_ids']);
                    if ($key !== false) {
                        array_splice($currentData['playlists'][$favIndex]['song_ids'], $key, 1);
                        $isLiked = false;
                    } else {
                        $currentData['playlists'][$favIndex]['song_ids'][] = $songId;
                        $isLiked = true;
                    }
                }
                file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
                AuraCache::set('playlists_data', $currentData, 3600);
                echo json_encode(['status' => 'success', 'is_liked' => $isLiked, 'song_ids' => $currentData['playlists'][$favIndex]['song_ids'] ?? []]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Song ID required']);
            }
            break;

        case 'create_playlist':
            $name = trim($payload['name'] ?? 'Playlist Baru');
            $desc = trim($payload['description'] ?? '');
            $newPl = [
                'id' => 'pl_' . uniqid(),
                'name' => $name,
                'description' => $desc,
                'created_at' => time(),
                'song_ids' => $payload['song_ids'] ?? []
            ];
            $currentData['playlists'][] = $newPl;
            file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
            AuraCache::set('playlists_data', $currentData, 3600);
            echo json_encode(['status' => 'success', 'playlist' => $newPl]);
            break;

        case 'delete_playlist':
            $id = $payload['id'] ?? '';
            if ($id && $id !== 'favorites') {
                $currentData['playlists'] = array_values(array_filter($currentData['playlists'], function($p) use ($id) {
                    return $p['id'] !== $id;
                }));
                file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
                AuraCache::set('playlists_data', $currentData, 3600);
                echo json_encode(['status' => 'success', 'message' => 'Playlist dihapus']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menghapus playlist sistem']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
            break;
    }
}
