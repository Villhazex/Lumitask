<?php

require __DIR__ . '/bootstrap/app.php';

use App\Core\Auth;

Auth::logout();
header('Location: login.php');
exit;
