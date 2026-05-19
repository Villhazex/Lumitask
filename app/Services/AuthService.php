<?php

namespace App\Services;

use App\Core\ActivityLogger;
use App\Core\Auth;
use App\Core\Validator;
use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $users;

    public function __construct(UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    public function attempt(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['ok' => false, 'msg' => 'Username dan password wajib diisi.'];
        }

        $user = $this->users->findByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'msg' => 'Username atau password salah.'];
        }

        Auth::login($user);
        ActivityLogger::log((int) $user['id'], 'auth.login', 'user', (int) $user['id']);

        return ['ok' => true];
    }

    public function register(string $username, string $email, string $password): array
    {
        $data = compact('username', 'email', 'password');
        $v = new Validator($data);
        $v->required('username', 'Username')->max('username', 50, 'Username')
            ->required('email', 'Email')->max('email', 120, 'Email')
            ->required('password', 'Password');

        if ($v->fails()) {
            return ['ok' => false, 'msg' => implode(' ', $v->errors())];
        }

        if ($this->users->findByUsername($username)) {
            return ['ok' => false, 'msg' => 'Username sudah digunakan.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->users->create($username, $email, $hash, 'member');
        ActivityLogger::log($id, 'auth.register', 'user', $id);

        return ['ok' => true, 'user_id' => $id];
    }
}
