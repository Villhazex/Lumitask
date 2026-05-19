<?php

$app = require __DIR__ . '/bootstrap/app.php';

use App\Core\Session;
use App\Services\AuthService;

$result = (new AuthService())->attempt(
    trim($_POST['username'] ?? ''),
    $_POST['password'] ?? ''
);

if (!$result['ok']) {
    Session::flash('login_error', $result['msg']);
    header('Location: login.php');
    exit;
}

header('Location: index.php');
exit;
