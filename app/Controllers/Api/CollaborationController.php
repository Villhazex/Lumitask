<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ActivityLogRepository;
use App\Repositories\TaskRepository;

class CollaborationController extends Controller
{
    /**
     * Polling endpoint for near-realtime collaboration (activities + task changes).
     */
    public function poll(): void
    {
        Auth::requireLogin();
        $since = Request::input('since', date('Y-m-d H:i:s', time() - 30));
        $userId = Auth::id();

        $activities = (new ActivityLogRepository())->since($since, $userId);
        $tasks = (new TaskRepository())->allForUser($userId);
        $updated = array_values(array_filter($tasks, function ($t) use ($since) {
            return ($t['updated_at'] ?? $t['created_at'] ?? '') > $since;
        }));

        $this->jsonOk([
            'server_time' => date('Y-m-d H:i:s'),
            'activities' => $activities,
            'updated_tasks' => $updated,
        ]);
    }
}
