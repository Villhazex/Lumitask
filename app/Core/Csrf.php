<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::get(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }

        return Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function validate(?string $token): bool
    {
        $stored = Session::get(self::TOKEN_KEY);

        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    public static function validateRequest(): bool
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return self::validate(is_string($token) ? $token : null);
    }
}
