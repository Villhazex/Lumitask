<?php

session_start();

$user_id = $_SESSION['user_id'];

require_once 'class/TugasModel.php';

if (isset($_POST['nama_tugas'])) {
    $nama = $_POST['nama_tugas'];
    $due_date = $_POST['due_date'] ?? null;
    $prioritas = $_POST['prioritas'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $user_id = $_SESSION['user_id'];

    $tugas = new TugasModel(
        $nama,
        'Belum Selesai',
        $due_date,
        $prioritas,
        $kategori,
        $user_id
    );

    $tugas->tambahTugas();

    header('Location: index.php');
    exit;
}
