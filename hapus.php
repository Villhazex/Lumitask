<?php

require __DIR__ . '/bootstrap/app.php';

use App\Core\Auth;
use App\Core\ActivityLogger;
use App\Repositories\TaskRepository;

Auth::requireLogin();

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id && (new TaskRepository())->softDelete($id, Auth::id())) {
    ActivityLogger::log(Auth::id(), 'task.deleted', 'task', $id);
}

header('Location: index.php');
exit;
