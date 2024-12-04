<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;

$dsn = 'mysql:host=db;dbname=betting_system;charset=utf8';
$username = 'user';
$password = 'password';

$db = new Database($dsn, $username, $password);
$pdo = $db->getPdo();