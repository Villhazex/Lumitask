<?php

namespace App\Controllers\Api;

use App\Core\ActivityLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\PublicTaskRepository;
use App\Repositories\TaskRepository;

class PublicTaskController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $items = (new PublicTaskRepository())->listForUser(Auth::id());
        $this->jsonOk(['public_tasks' => $items]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $taskId = (int) Request::input('task_id', 0);
        $title = Validator::sanitizeString(Request::input('title', ''));
        $visibility = Request::input('visibility', 'link');

        if (!$title || !(new TaskRepository())->canAccess($taskId, Auth::id())) {
            $this->jsonError('Data tidak valid');
        }

        $result = (new PublicTaskRepository())->create($taskId, Auth::id(), $title, $visibility);
        ActivityLogger::log(Auth::id(), 'public_task.created', 'task', $taskId, $result);
        $this->jsonOk($result);
    }

    public function join(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $token = (string) Request::input('token', '');
        $public = (new PublicTaskRepository())->findByToken($token);
        if (!$public) {
            $this->jsonError('Link tidak valid', 404);
        }

        (new PublicTaskRepository())->join((int) $public['id'], Auth::id(), 'view');
        ActivityLogger::log(Auth::id(), 'public_task.joined', 'public_task', (int) $public['id']);
        $this->jsonOk(['message' => 'Berhasil bergabung', 'task' => $public]);
    }

    public function view(): void
    {
        $token = (string) Request::input('token', '');
        $public = (new PublicTaskRepository())->findByToken($token);
        if (!$public) {
            $this->jsonError('Tugas publik tidak ditemukan', 404);
        }
        $this->jsonOk(['task' => $public]);
    }
}
