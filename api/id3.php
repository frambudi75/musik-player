<?php
/**
 * Pure PHP ID3v2 Tag & Cover Extractor
 * Extracts Title, Artist, Album, Year, Genre, and saves Album Cover to disk for high performance
 */

class SimpleID3 {
    public static function getMetadata($filePath, $coversDir = null) {
        $data = [
            'title' => pathinfo($filePath, PATHINFO_FILENAME),
            'artist' => 'Unknown Artist',
            'album' => 'Unknown Album',
            'year' => '',
            'genre' => '',
            'duration' => 0,
            'cover' => null,
            'has_lyrics' => false
        ];

        // Check for accompanying .lrc file with same name
        $lrcPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.lrc';
        if (file_exists($lrcPath)) {
            $data['has_lyrics'] = true;
            $data['lyrics_file'] = basename($lrcPath);
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $data;
        }

        if (!$coversDir) {
            $coversDir = __DIR__ . '/../songs/covers';
        }
        if (!is_dir($coversDir)) {
            @mkdir($coversDir, 0777, true);
        }

        $fileMtime = @filemtime($filePath) ?: 0;
        $coverHash = md5($filePath . '_' . $fileMtime);
        
        // If cover file already cached on disk
        $cachedCoverJpg = $coversDir . '/' . $coverHash . '.jpg';
        $cachedCoverPng = $coversDir . '/' . $coverHash . '.png';
        if (file_exists($cachedCoverJpg)) {
            $data['cover'] = 'songs/covers/' . $coverHash . '.jpg';
        } elseif (file_exists($cachedCoverPng)) {
            $data['cover'] = 'songs/covers/' . $coverHash . '.png';
        }

        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return $data;
        }

        // Read first 10 bytes for ID3v2 header
        $header = fread($handle, 10);
        if (strlen($header) === 10 && substr($header, 0, 3) === 'ID3') {
            $majorVersion = ord($header[3]);
            $tagSize = self::decodeSyncsafeInteger(substr($header, 6, 4));

            if ($tagSize > 0 && $tagSize < 10485760) { // Limit to 10MB tag size for performance
                $tagData = fread($handle, $tagSize);
                self::parseID3v2Frames($tagData, $majorVersion, $data, $coversDir, $coverHash);
            }
        }

        fclose($handle);

        // Fallback cleanups if tags are messy or missing
        $data['title'] = trim($data['title']);
        $data['artist'] = trim($data['artist']);
        $data['album'] = trim($data['album']);

        $cleanBase = pathinfo($filePath, PATHINFO_FILENAME);
        // If Title equals raw filename with underscores or is empty
        if (empty($data['title']) || $data['title'] === $cleanBase) {
            if (strpos($cleanBase, ' - ') !== false) {
                $parts = explode(' - ', $cleanBase, 2);
                if ($data['artist'] === 'Unknown Artist' || empty($data['artist'])) {
                    $data['artist'] = trim(str_replace('_', ' ', $parts[0]));
                }
                $data['title'] = trim(str_replace('_', ' ', $parts[1]));
            } else {
                $data['title'] = trim(str_replace('_', ' ', $cleanBase));
            }
        }

        if (empty($data['artist']) || $data['artist'] === 'Unknown Artist') {
            if ($cleanBase === 'Midnight_Aura_Ambient') {
                $data['artist'] = 'Aura Sound Lab';
                $data['album'] = 'Midnight Ambient Session';
                $data['genre'] = 'Ambient / Synth';
            } else {
                $data['artist'] = 'Unknown Artist';
            }
        }

        if (empty($data['album']) || $data['album'] === 'Unknown Album') {
            $data['album'] = 'Single';
        }

        return $data;
    }

    private static function decodeSyncsafeInteger($str) {
        if (strlen($str) < 4) return 0;
        return (ord($str[0]) << 21) | (ord($str[1]) << 14) | (ord($str[2]) << 7) | ord($str[3]);
    }

    private static function decodeRegularInteger($str) {
        if (strlen($str) < 4) return 0;
        return (ord($str[0]) << 24) | (ord($str[1]) << 16) | (ord($str[2]) << 8) | ord($str[3]);
    }

    private static function parseID3v2Frames($tagData, $version, &$data, $coversDir, $coverHash) {
        $pos = 0;
        $len = strlen($tagData);

        while ($pos < $len - 10) {
            $frameId = substr($tagData, $pos, 4);
            // Check for valid frame identifier (ASCII uppercase and digits)
            if (!preg_match('/^[A-Z0-9]{4}$/', $frameId)) {
                break;
            }

            if ($version >= 4) {
                $frameSize = self::decodeSyncsafeInteger(substr($tagData, $pos + 4, 4));
            } else {
                $frameSize = self::decodeRegularInteger(substr($tagData, $pos + 4, 4));
            }

            if ($frameSize <= 0 || $pos + 10 + $frameSize > $len) {
                break;
            }

            $frameBody = substr($tagData, $pos + 10, $frameSize);
            $pos += 10 + $frameSize;

            switch ($frameId) {
                case 'TIT2': // Title
                    $data['title'] = self::decodeTextFrame($frameBody);
                    break;
                case 'TPE1': // Artist
                case 'TPE2':
                    $data['artist'] = self::decodeTextFrame($frameBody);
                    break;
                case 'TALB': // Album
                    $data['album'] = self::decodeTextFrame($frameBody);
                    break;
                case 'TYER': // Year (v2.3)
                case 'TDRC': // Recording time (v2.4)
                    $data['year'] = self::decodeTextFrame($frameBody);
                    break;
                case 'TCON': // Genre
                    $data['genre'] = self::decodeTextFrame($frameBody);
                    break;
                case 'APIC': // Attached Picture / Album Cover
                    if (!$data['cover']) {
                        $coverInfo = self::extractAPICBinary($frameBody);
                        if ($coverInfo && !empty($coverInfo['data'])) {
                            $ext = (strpos($coverInfo['mime'], 'png') !== false) ? 'png' : 'jpg';
                            $savePath = $coversDir . '/' . $coverHash . '.' . $ext;
                            $written = @file_put_contents($savePath, $coverInfo['data']);
                            if ($written !== false && $written > 0 && file_exists($savePath)) {
                                @chmod($savePath, 0666);
                                $data['cover'] = 'songs/covers/' . $coverHash . '.' . $ext;
                            }
                        }
                    }
                    break;
            }
        }
    }

    private static function decodeTextFrame($raw) {
        if (strlen($raw) < 1) return '';
        $encoding = ord($raw[0]);
        $text = substr($raw, 1);
        $text = trim($text, "\0");

        if ($encoding === 0) {
            // ISO-8859-1
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
            } elseif (function_exists('iconv')) {
                return @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text) ?: $text;
            } elseif (function_exists('utf8_encode')) {
                return @utf8_encode($text);
            }
            return $text;
        } elseif ($encoding === 1) {
            // UTF-16 with BOM
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($text, 'UTF-8', 'UTF-16');
            } elseif (function_exists('iconv')) {
                return @iconv('UTF-16', 'UTF-8//IGNORE', $text) ?: $text;
            }
            return preg_replace('/[^\x20-\x7E]/', '', $text);
        } elseif ($encoding === 2) {
            // UTF-16BE
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($text, 'UTF-8', 'UTF-16BE');
            } elseif (function_exists('iconv')) {
                return @iconv('UTF-16BE', 'UTF-8//IGNORE', $text) ?: $text;
            }
            return preg_replace('/[^\x20-\x7E]/', '', $text);
        } elseif ($encoding === 3) {
            // UTF-8
            return $text;
        }

        return $text;
    }

    private static function extractAPICBinary($raw) {
        if (strlen($raw) < 10) return null;
        
        // Find JPEG magic bytes (FF D8 FF) or PNG magic bytes (89 50 4E 47)
        $jpgPos = strpos($raw, "\xFF\xD8\xFF");
        $pngPos = strpos($raw, "\x89PNG\x0D\x0A\x1A\x0A");
        if ($pngPos === false) {
            $pngPos = strpos($raw, "\x89PNG");
        }

        $imgData = null;
        $mime = 'image/jpeg';

        if ($jpgPos !== false && ($pngPos === false || $jpgPos < $pngPos)) {
            $imgData = substr($raw, $jpgPos);
            $mime = 'image/jpeg';
        } elseif ($pngPos !== false) {
            $imgData = substr($raw, $pngPos);
            $mime = 'image/png';
        } else {
            // Fallback: standard APIC byte skip
            $pos = 1;
            $mimeEnd = strpos($raw, "\0", $pos);
            if ($mimeEnd !== false) {
                $mime = substr($raw, $pos, $mimeEnd - $pos) ?: 'image/jpeg';
                $pos = $mimeEnd + 2; // skip mime null + pic type
                $descEnd = strpos($raw, "\0", $pos);
                if ($descEnd !== false) {
                    $pos = $descEnd + 1;
                    if (isset($raw[$pos]) && $raw[$pos] === "\0") $pos++;
                }
                $imgData = substr($raw, $pos);
            }
        }

        if ($imgData && strlen($imgData) > 100) {
            return [
                'mime' => $mime,
                'data' => $imgData
            ];
        }
        return null;
    }
}
