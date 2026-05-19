<?php

namespace App\Core;

abstract class Controller
{
    protected function validateCsrf(): void
    {
        if (!Csrf::validateRequest()) {
            Response::json(['error' => 'Invalid CSRF token'], 419);
        }
    }

    protected function jsonOk($data = [], int $status = 200)
    {
        Response::json(array_merge(['ok' => true], is_array($data) ? $data : ['data' => $data]), $status);
    }

    protected function jsonError(string $message, int $status = 422, array $extra = [])
    {
        Response::json(array_merge(['ok' => false, 'error' => $message], $extra), $status);
    }
}
