<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\ActivityLogRepository;

class ActivityController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $logs = (new ActivityLogRepository())->recentForUser(Auth::id());
        $this->jsonOk(['activity' => $logs]);
    }
}
