<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['username'] || !$data['email'] || !$data['password']) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }

    if ($data['password'] !== $data['password_confirm']) {
        jsonResponse(['success' => false, 'error' => 'Passwords do not match'], 400);
    }

    $user = new User($db);
    $result = $user->register($data['username'], $data['email'], $data['password']);

    if ($result['success']) {
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['username'] = $data['username'];
        jsonResponse(['success' => true, 'message' => 'Registration successful']);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

elseif ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data['email'] || !$data['password']) {
        jsonResponse(['success' => false, 'error' => 'Missing credentials'], 400);
    }

    $user = new User($db);

    if ($user->checkLoginAttempts($data['email'])) {
        jsonResponse(['success' => false, 'error' => 'Too many login attempts. Try again later.'], 429);
    }

    $result = $user->login($data['email'], $data['password']);

    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['email'] = $result['user']['email'];

        if ($data['remember'] ?? false) {
            setcookie('remember_user', $result['user']['id'], time() + (30 * 24 * 60 * 60), '/');
        }

        jsonResponse(['success' => true, 'message' => 'Login successful']);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 401);
    }
}

elseif ($method === 'POST' && $action === 'logout') {
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    jsonResponse(['success' => true, 'message' => 'Logged out']);
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
}
