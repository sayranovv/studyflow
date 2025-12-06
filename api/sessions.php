<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/StudySession.php';
require_once __DIR__ . '/../classes/Topic.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$session = new StudySession($db);

if ($method === 'GET') {
    $limit = $_GET['limit'] ?? 10;
    $topicId = $_GET['topic_id'] ?? null;

    if ($topicId) {
        $sessions = $session->getByTopic($topicId, $limit);
    } else {
        $sessions = $session->getRecent($userId, $limit);
    }

    jsonResponse(['success' => true, 'sessions' => $sessions]);
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $session->create(
        $userId,
        $data['topic_id'] ?? 0,
        $data['duration_minutes'] ?? 25,
        $data['session_type'] ?? 'work',
        $data['notes'] ?? ''
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Session saved', 'session_id' => $result['session_id']], 201);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
}
