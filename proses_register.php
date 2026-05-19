<?php

require __DIR__ . '/bootstrap/app.php';

use App\Core\Session;
use App\Services\AuthService;

function redirectRegister(string $message): never
{
    Session::flash('register_error', $message);
    header('Location: register.php');
    exit;
}

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

$result = (new AuthService())->register($username, $email, $password);
if (!$result['ok']) {
    redirectRegister($result['msg']);
}

Session::flash('register_success', 'Akun berhasil dibuat. Silakan login.');
header('Location: login.php');
exit;
