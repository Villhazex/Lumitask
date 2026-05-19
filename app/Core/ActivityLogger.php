<?php

namespace App\Core;

use App\Repositories\ActivityLogRepository;

class ActivityLogger
{
    public static function log(
        int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $meta = null
    ): void {
        try {
            (new ActivityLogRepository())->create([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Non-blocking: activity log failure must not break main flow
        }
    }
}
