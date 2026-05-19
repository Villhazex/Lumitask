<?php

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(array $config): void
    {
        if (self::$started) {
            return;
        }

        session_name($config['name'] ?? 'lumitask_session');
        session_set_cookie_params([
            'lifetime' => $config['lifetime'] ?? 7200,
            'path' => '/',
            'secure' => $config['secure'] ?? false,
            'httponly' => $config['httponly'] ?? true,
            'samesite' => $config['samesite'] ?? 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    public static function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }
}
