<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
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
$topic = new Topic($db);

if ($method === 'GET') {
    $subjectId = $_GET['subject_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $needsReview = $_GET['needs_review'] ?? false;

    if ($needsReview) {
        $topics = $topic->getTopicsNeedingReview($userId);
    } elseif ($subjectId) {
        $topics = $topic->getAll($subjectId, $userId, $status);
    } else {
        $topics = [];
    }

    jsonResponse(['success' => true, 'topics' => $topics]);
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $topic->create(
        $data['subject_id'] ?? 0,
        $userId,
        $data['name'] ?? '',
        $data['description'] ?? '',
        $data['difficulty'] ?? 3,
        $data['planned_sessions'] ?? 4
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Topic created', 'topic_id' => $result['topic_id']], 201);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
}
