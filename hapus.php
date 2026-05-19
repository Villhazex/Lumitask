<?php

session_start();

require_once 'class/TugasModel.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $user_id = $_SESSION['user_id'];

    $tugas = new TugasModel();

    $tugas->hapusTugas($id, $user_id);

    header('Location: index.php');

    exit;
}
