<?php

session_start();

require_once 'config/database.php';

$conn = connectDB();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare('
    SELECT id, username, password
    FROM users
    WHERE username = ?
    LIMIT 1
');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['login_error'] = 'Username tidak ditemukan.';
    header('Location: login.php');
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Password salah.';
    header('Location: login.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama'] = $user['username'];
$_SESSION['user_name'] = $user['username'];

header('Location: index.php');
exit;
