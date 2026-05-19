<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'class/TugasModel.php';

if (isset($_POST['nama_list'])) {
    $tugas = new TugasModel();
    $members = [];
    if (!empty($_POST['members'])) {
        $members = preg_split('/[\s,]+/', $_POST['members']);
    }
    $result = $tugas->tambahList(
        $_SESSION['user_id'],
        $_POST['nama_list'],
        $_POST['jenis'] ?? 'pribadi',
        $members
    );

    $_SESSION['toast'] = [
        'msg' => $result['msg'],
        'type' => $result['ok'] ? 'ok' : 'error',
    ];
}

header('Location: index.php');
exit;
