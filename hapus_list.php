<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "class/TugasModel.php";

$result = ['ok' => false, 'msg' => 'List tidak ditemukan.'];

if (isset($_POST['list_id'])) {
    $tugas = new TugasModel();
    $result = $tugas->hapusList($_SESSION['user_id'], $_POST['list_id']);
}

$_SESSION['toast'] = [
    'msg' => $result['msg'],
    'type' => $result['ok'] ? 'ok' : 'error',
];

header("Location: index.php");
exit;

?>
