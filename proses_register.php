<?php

session_start();

require_once 'config/database.php';

$conn = connectDB();

function redirectRegister($message)
{
    $_SESSION['register_error'] = $message;
    header('Location: register.php');
    exit;
}

function ensureRegisterSchema($conn)
{
    $conn->query('
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ');
}

ensureRegisterSchema($conn);

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    redirectRegister('Username, email, password, dan konfirmasi password wajib diisi.');
}

if (strlen($username) < 3 || preg_match('/\s/', $username)) {
    redirectRegister('Username minimal 3 karakter dan tidak boleh memakai spasi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectRegister('Format email tidak valid.');
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
    redirectRegister('Password minimal 8 karakter dan harus berisi huruf serta angka.');
}

if ($password !== $confirm) {
    redirectRegister('Konfirmasi password tidak cocok.');
}

$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$cekUsername = $stmt->get_result();

if ($cekUsername->num_rows > 0) {
    redirectRegister('Username sudah digunakan.');
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$cekEmail = $stmt->get_result();

if ($cekEmail->num_rows > 0) {
    redirectRegister('Email sudah digunakan.');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('
    INSERT INTO users (username, email, password)
    VALUES (?, ?, ?)
');
$stmt->bind_param('sss', $username, $email, $passwordHash);
$insert = $stmt->execute();

if ($insert) {
    $_SESSION['register_success'] = 'Registrasi berhasil.';
} else {
    $_SESSION['register_error'] = 'Registrasi gagal.';
}

header('Location: register.php');
exit;
