<?php

require __DIR__ . '/bootstrap/app.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Validator;
use App\Core\ActivityLogger;
use App\Repositories\TaskRepository;

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validateRequest()) {
    Session::flash('toast', ['msg' => 'Permintaan tidak valid.', 'type' => 'error']);
    header('Location: index.php');
    exit;
}

$repo = new TaskRepository();
$id = $repo->create(Auth::id(), [
    'nama_tugas' => Validator::sanitizeString($_POST['nama_tugas'] ?? ''),
    'deskripsi' => Validator::sanitizeString($_POST['deskripsi'] ?? ''),
    'due_date' => $_POST['due_date'] ?? null,
    'prioritas' => $_POST['prioritas'] ?? null,
    'kategori' => $_POST['kategori'] ?? '',
]);

if ($id) {
    ActivityLogger::log(Auth::id(), 'task.created', 'task', $id);
    Session::flash('toast', ['msg' => 'Tugas ditambahkan.', 'type' => 'ok']);
} else {
    Session::flash('toast', ['msg' => 'Gagal menambah tugas.', 'type' => 'error']);
}

header('Location: index.php');
exit;
