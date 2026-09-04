<?php
/**
 * Aura Music - High Performance Redis & Memory Cache Layer
 * Supports Redis, APCu, Fast File Cache, and HTTP ETag 304 Validation
 */

class AuraCache {
    private static $redis = null;
    private static $redisConnected = false;
    private static $cacheDir = __DIR__ . '/../songs/.cache';

    public static function init() {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }

        if (self::$redis === null && class_exists('Redis')) {
            try {
                self::$redis = new Redis();
                $connected = @self::$redis->connect('127.0.0.1', 6379, 0.2); // 200ms timeout
                if ($connected) {
                    self::$redisConnected = true;
                }
            } catch (Exception $e) {
                self::$redisConnected = false;
            }
        }
    }

    public static function get($key) {
        self::init();

        // 1. Try Redis
        if (self::$redisConnected) {
            try {
                $val = self::$redis->get('aura:' . $key);
                if ($val !== false) {
                    return json_decode($val, true);
                }
            } catch (Exception $e) {}
        }

        // 2. Try APCu
        if (function_exists('apcu_fetch')) {
            $val = apcu_fetch('aura:' . $key, $success);
            if ($success) {
                return $val;
            }
        }

        // 3. Try In-Memory / File Cache
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($filePath)) {
            $raw = @file_get_contents($filePath);
            if ($raw) {
                $data = @json_decode($raw, true);
                if ($data && isset($data['exp']) && ($data['exp'] === 0 || $data['exp'] > time())) {
                    return $data['payload'];
                }
            }
        }

        return null;
    }

    public static function set($key, $val, $ttl = 3600) {
        self::init();

        // 1. Try Redis
        if (self::$redisConnected) {
            try {
                $json = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($ttl > 0) {
                    self::$redis->setex('aura:' . $key, $ttl, $json);
                } else {
                    self::$redis->set('aura:' . $key, $json);
                }
            } catch (Exception $e) {}
        }

        // 2. Try APCu
        if (function_exists('apcu_store')) {
            @apcu_store('aura:' . $key, $val, $ttl);
        }

        // 3. Store in Disk/Memory File
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        $entry = [
            'exp' => $ttl > 0 ? time() + $ttl : 0,
            'payload' => $val
        ];
        @file_put_contents($filePath, json_encode($entry, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function delete($key) {
        self::init();
        if (self::$redisConnected) {
            try {
                self::$redis->del('aura:' . $key);
            } catch (Exception $e) {}
        }
        if (function_exists('apcu_delete')) {
            @apcu_delete('aura:' . $key);
        }
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public static function flush() {
        self::init();
        if (self::$redisConnected) {
            try {
                $keys = self::$redis->keys('aura:*');
                if (!empty($keys)) {
                    self::$redis->del($keys);
                }
            } catch (Exception $e) {}
        }
        if (function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
        }
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '/*.cache');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
    }

    public static function checkETagAndExit($etag, $maxAge = 600) {
        header('Cache-Control: public, max-age=' . $maxAge . ', must-revalidate');
        header('ETag: "' . $etag . '"');

        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : '';
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }
    }
}
