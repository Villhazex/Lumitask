<?php

require_once __DIR__ . '/autoload.php';

use App\Core\Application;

$appConfig = require dirname(__DIR__) . '/config/app.php';
$dbConfig = require dirname(__DIR__) . '/config/database.php';

date_default_timezone_set($appConfig['timezone'] ?? 'UTC');

return Application::boot($appConfig, $dbConfig);
