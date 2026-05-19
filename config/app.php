<?php

return [
    'name' => 'Lumitask',
    'env' => 'local',
    'debug' => true,
    'base_path' => '/Lumitask',
    'timezone' => 'Asia/Jakarta',
    'session' => [
        'name' => 'lumitask_session',
        'lifetime' => 7200,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'upload' => [
        'max_size' => 5 * 1024 * 1024,
        'allowed' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'],
        'path' => dirname(__DIR__) . '/storage/uploads',
    ],
];
