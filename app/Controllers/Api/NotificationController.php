<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Repositories\NotificationRepository;

class NotificationController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $repo = new NotificationRepository();
        $repo->syncDeadlineReminders(Auth::id());
        $this->jsonOk(['notifications' => $repo->forUser(Auth::id())]);
    }

    public function markRead(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();
        $id = Request::input('id') ? (int) Request::input('id') : null;
        (new NotificationRepository())->markRead(Auth::id(), $id);
        $this->jsonOk(['message' => 'Notifikasi ditandai dibaca']);
    }
}
