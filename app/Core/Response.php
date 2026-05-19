<?php

namespace App\Core;

class Response
{
    public static function json($data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;
    }

    public static function redirect(string $url, int $status = 302)
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}
