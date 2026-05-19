<?php

namespace App\Core;

class Auth
{
    public static function id(): ?int
    {
        $id = Session::get('user_id');

        return $id ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => self::id(),
            'username' => Session::get('username'),
            'role' => Session::get('role', 'member'),
            'name' => Session::get('user_name'),
        ];
    }

    public static function isAdmin(): bool
    {
        return Session::get('role') === 'admin';
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('username', $user['username']);
        Session::set('user_name', $user['username']);
        Session::set('role', $user['role'] ?? 'member');
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (self::wantsJson()) {
                Response::json(['error' => 'Unauthorized'], 401);
            }
            header('Location: login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            Response::json(['error' => 'Forbidden'], 403);
        }
    }

    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || strcasecmp($xhr, 'XMLHttpRequest') === 0
            || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api');
    }
}
