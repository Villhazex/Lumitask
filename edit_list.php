<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'class/TugasModel.php';

$result = ['ok' => false, 'msg' => 'Data list tidak lengkap.'];

if (isset($_POST['list_id'], $_POST['nama_list'])) {
    $members = [];
    if (!empty($_POST['members'])) {
        $members = preg_split('/[\s,]+/', $_POST['members']);
    }

    $tugas = new TugasModel();
    $result = $tugas->editList(
        $_SESSION['user_id'],
        $_POST['list_id'],
        $_POST['nama_list'],
        $_POST['jenis'] ?? 'pribadi',
        $members
    );
}

$_SESSION['toast'] = [
    'msg' => $result['msg'],
    'type' => $result['ok'] ? 'ok' : 'error',
];

header('Location: index.php');
exit;
