<?php
/**
 * Aura Music - Authentication Guard & Rate Limiter Middleware
 * Protects sensitive API endpoints (upload, download, edit, trim, lyrics save)
 *
 * Usage in protected endpoints:
 *   require_once __DIR__ . '/auth_guard.php';
 *   AuraAuth::requireAuth();                   // Basic auth check
 *   AuraAuth::requireAuth(['rateLimit' => 10]); // Auth + 10 req/min rate limit
 *
 * Login endpoint:
 *   POST /api/auth_guard.php  { "password": "your_password" }
 *   Returns: { "status": "success", "token": "abc123..." }
 */

class AuraAuth {
    private static $secretFile = __DIR__ . '/.aura_secret';
    private static $rateCacheDir = __DIR__ . '/../songs/.cache';

    /**
     * Get or generate the admin password hash + API token
     */
    private static function getSecrets() {
        if (!file_exists(self::$secretFile)) {
            // First run: generate default password and token
            $defaultPassword = 'aura' . date('Y'); // e.g. aura2026
            $token = bin2hex(random_bytes(32));
            $data = [
                'password_hash' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                'token' => $token,
                'created_at' => time(),
                'default_password' => $defaultPassword // Stored only for first-time display
            ];
            @file_put_contents(self::$secretFile, json_encode($data, JSON_PRETTY_PRINT));
            @chmod(self::$secretFile, 0600);
            return $data;
        }
        return json_decode(file_get_contents(self::$secretFile), true) ?: [];
    }

    /**
     * Validate token from request header or cookie
     */
    public static function validateToken() {
        $secrets = self::getSecrets();
        if (empty($secrets['token'])) return false;

        // Check X-Aura-Token header
        $headerToken = $_SERVER['HTTP_X_AURA_TOKEN'] ?? '';
        if ($headerToken && hash_equals($secrets['token'], $headerToken)) {
            return true;
        }

        // Check cookie
        $cookieToken = $_COOKIE['aura_token'] ?? '';
        if ($cookieToken && hash_equals($secrets['token'], $cookieToken)) {
            return true;
        }

        // Check query param (fallback for simple testing)
        $paramToken = $_GET['token'] ?? '';
        if ($paramToken && hash_equals($secrets['token'], $paramToken)) {
            return true;
        }

        return false;
    }

    /**
     * Rate limiter: track requests per IP per minute
     * @param int $maxPerMinute Maximum allowed requests per minute
     * @return bool true if allowed, false if rate limited
     */
    public static function checkRateLimit($maxPerMinute = 30) {
        if (!is_dir(self::$rateCacheDir)) {
            @mkdir(self::$rateCacheDir, 0777, true);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $minute = date('YmdHi');
        $key = md5('rate_' . $ip . '_' . $minute);
        $filePath = self::$rateCacheDir . '/rate_' . $key . '.json';

        $count = 0;
        if (file_exists($filePath)) {
            $data = @json_decode(file_get_contents($filePath), true);
            $count = (int)($data['count'] ?? 0);
        }

        if ($count >= $maxPerMinute) {
            return false;
        }

        @file_put_contents($filePath, json_encode([
            'count' => $count + 1,
            'ip' => $ip,
            'minute' => $minute
        ]), LOCK_EX);

        return true;
    }

    /**
     * Clean up old rate limit files (older than 5 minutes)
     */
    public static function cleanupRateLimitFiles() {
        if (!is_dir(self::$rateCacheDir)) return;
        $files = glob(self::$rateCacheDir . '/rate_*.json');
        if (!$files) return;
        $cutoff = time() - 300;
        foreach ($files as $f) {
            if (@filemtime($f) < $cutoff) {
                @unlink($f);
            }
        }
    }

    /**
     * Main guard: require valid auth, optionally enforce rate limit
     * @param array $options ['rateLimit' => int] max requests per minute
     */
    public static function requireAuth($options = []) {
        // CORS preflight always passes
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return;
        }

        if (!self::validateToken()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'code' => 'UNAUTHORIZED',
                'message' => 'Akses ditolak. Silakan login terlebih dahulu.'
            ]);
            exit;
        }

        // Rate limit check
        if (!empty($options['rateLimit'])) {
            if (!self::checkRateLimit((int)$options['rateLimit'])) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'code' => 'RATE_LIMITED',
                    'message' => 'Terlalu banyak permintaan. Coba lagi dalam 1 menit.'
                ]);
                exit;
            }
        }

        // Occasionally clean old rate files
        if (rand(1, 20) === 1) {
            self::cleanupRateLimitFiles();
        }
    }

    /**
     * Handle login request
     */
    public static function handleLogin() {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $password = trim($body['password'] ?? '');

        if (empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Password tidak boleh kosong']);
            exit;
        }

        $secrets = self::getSecrets();
        if (password_verify($password, $secrets['password_hash'] ?? '')) {
            // Set cookie (30 days)
            setcookie('aura_token', $secrets['token'], [
                'expires' => time() + 2592000,
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Login berhasil!',
                'token' => $secrets['token']
            ]);
        } else {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Password salah'
            ]);
        }
        exit;
    }

    /**
     * Handle password change request
     */
    public static function handleChangePassword() {
        if (!self::validateToken()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $newPassword = trim($body['new_password'] ?? '');

        if (strlen($newPassword) < 4) {
            echo json_encode(['status' => 'error', 'message' => 'Password minimal 4 karakter']);
            exit;
        }

        $secrets = self::getSecrets();
        $secrets['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        $newToken = bin2hex(random_bytes(32));
        $secrets['token'] = $newToken;
        unset($secrets['default_password']);
        @file_put_contents(self::$secretFile, json_encode($secrets, JSON_PRETTY_PRINT));

        setcookie('aura_token', $newToken, [
            'expires' => time() + 2592000,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password berhasil diubah!',
            'token' => $newToken
        ]);
        exit;
    }

    /**
     * Check current auth status
     */
    public static function handleStatus() {
        $isAuth = self::validateToken();
        $secrets = self::getSecrets();
        $isFirstRun = !empty($secrets['default_password']);

        echo json_encode([
            'status' => 'success',
            'authenticated' => $isAuth,
            'first_run' => $isFirstRun,
            'default_password' => $isFirstRun ? $secrets['default_password'] : null
        ]);
        exit;
    }
}

// Direct access: handle login / status / change-password endpoints
if (basename($_SERVER['SCRIPT_FILENAME']) === 'auth_guard.php') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Aura-Token');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
        AuraAuth::handleChangePassword();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        AuraAuth::handleLogin();
    } elseif ($action === 'status') {
        AuraAuth::handleStatus();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gunakan POST untuk login atau GET ?action=status']);
    }
}
