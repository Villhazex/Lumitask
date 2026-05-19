<?php

namespace App\Controllers\Api;

use App\Core\ActivityLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\NotificationRepository;
use App\Repositories\TaskRepository;

class TaskController extends Controller
{
    private TaskRepository $tasks;

    public function __construct()
    {
        $this->tasks = new TaskRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $userId = Auth::id();
        $filters = [
            'q' => Validator::sanitizeString(Request::input('q', '')),
            'status' => Request::input('status'),
            'prioritas' => Request::input('prioritas'),
            'list_id' => Request::input('list_id'),
            'due' => Request::input('due'),
        ];

        (new NotificationRepository())->syncDeadlineReminders($userId);

        $this->jsonOk(['tasks' => $this->tasks->allForUser($userId, $filters)]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $data = array_merge(Request::all(), $_POST);
        $v = new Validator($data);
        $v->required('nama_tugas', 'Nama tugas')->max('nama_tugas', 255, 'Nama tugas')->date('due_date', 'Deadline');

        if ($v->fails()) {
            $this->jsonError('Validasi gagal', 422, ['errors' => $v->errors()]);
        }

        $userId = Auth::id();
        $id = $this->tasks->create($userId, [
            'nama_tugas' => Validator::sanitizeString($data['nama_tugas']),
            'deskripsi' => Validator::sanitizeString($data['deskripsi'] ?? ''),
            'due_date' => $data['due_date'] ?? null,
            'prioritas' => $data['prioritas'] ?? null,
            'kategori' => $data['kategori'] ?? '',
        ]);

        if (!$id) {
            $this->jsonError('Gagal menambah tugas');
        }

        ActivityLogger::log($userId, 'task.created', 'task', $id, ['nama' => $data['nama_tugas']]);
        $this->jsonOk(['task_id' => $id, 'message' => 'Tugas ditambahkan']);
    }

    public function complete(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();
        $id = (int) Request::input('id', 0);
        $userId = Auth::id();

        if (!$this->tasks->complete($id, $userId)) {
            $this->jsonError('Tugas tidak ditemukan', 404);
        }

        ActivityLogger::log($userId, 'task.completed', 'task', $id);
        $this->jsonOk(['message' => 'Tugas diselesaikan']);
    }

    public function destroy(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();
        $id = (int) Request::input('id', 0);
        $userId = Auth::id();

        if (!$this->tasks->softDelete($id, $userId)) {
            $this->jsonError('Tugas tidak ditemukan', 404);
        }

        ActivityLogger::log($userId, 'task.deleted', 'task', $id);
        $this->jsonOk(['message' => 'Tugas dihapus']);
    }

    public function restore(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();
        $id = (int) Request::input('id', 0);

        if (!Auth::isAdmin()) {
            $this->jsonError('Hanya admin yang dapat restore', 403);
        }

        if (!$this->tasks->restore($id, Auth::id())) {
            $this->jsonError('Gagal restore');
        }

        $this->jsonOk(['message' => 'Tugas dipulihkan']);
    }
}
