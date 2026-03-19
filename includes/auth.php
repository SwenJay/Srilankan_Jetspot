<?php
// ============================================================
//  SL JetSpot — Auth helpers
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $username, string $password): bool {
        self::start();
        $row = DB::query(
            'SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1',
            [$username]
        )->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $row['id'];
            $_SESSION['admin_user'] = $row['username'];
            $_SESSION['last_active'] = time();
            return true;
        }
        return false;
    }

    public static function check(): bool {
        self::start();
        if (empty($_SESSION['admin_id'])) return false;
        // Auto-logout after 2 hours inactivity
        if (time() - ($_SESSION['last_active'] ?? 0) > 7200) {
            self::logout();
            return false;
        }
        $_SESSION['last_active'] = time();
        return true;
    }

    public static function require(): void {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function user(): ?string {
        return $_SESSION['admin_user'] ?? null;
    }
}

// ---- Helper functions ----

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    Auth::start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    Auth::start();
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function redirect(string $url, int $code = 302): never {
    header('Location: ' . $url, true, $code);
    exit;
}

function flash(string $key, string $msg = ''): string {
    Auth::start();
    if ($msg !== '') {
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    $out = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $out;
}
