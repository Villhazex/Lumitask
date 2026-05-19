<?php

function connectDB($host = 'localhost', $username = 'root', $password = '', $database = 'lumitask')
{
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        exit('Koneksi gagal: '.$conn->connect_error);
    }

    return $conn;
}
