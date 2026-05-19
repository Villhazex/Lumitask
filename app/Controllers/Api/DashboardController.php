<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\TaskRepository;

class DashboardController extends Controller
{
    public function stats(): void
    {
        Auth::requireLogin();
        $stats = (new TaskRepository())->stats(Auth::id());
        $this->jsonOk(['stats' => $stats]);
    }
}
