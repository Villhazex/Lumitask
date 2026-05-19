<?php

session_start();

require_once 'class/TugasModel.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $tugas = new TugasModel();

    $tugas->selesaiTugas($id, $_SESSION['user_id']);

    header('Location: index.php');
    exit;
}
