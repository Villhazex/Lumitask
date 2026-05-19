<?php

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function required(string $field, string $label = ''): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field] = ($label ?: $field) . ' wajib diisi.';
        }

        return $this;
    }

    public function max(string $field, int $max, string $label = ''): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = ($label ?: $field) . " maksimal {$max} karakter.";
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = ($label ?: $field) . ' tidak valid.';
        }

        return $this;
    }

    public function date(string $field, string $label = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value === '') {
            return $this;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->errors[$field] = ($label ?: $field) . ' format tanggal tidak valid.';
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public static function sanitizeString(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }
}
