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
    $subjectId = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $needsReview = isset($_GET['needs_review']) ? $_GET['needs_review'] : false;
    $topicId = isset($_GET['id']) ? $_GET['id'] : null;

    if ($topicId) {
        $topicData = $topic->getById($topicId, $userId);
        jsonResponse(['success' => true, 'topic' => $topicData]);
        exit;
    }

    if ($needsReview) {
        $topics = $topic->getTopicsNeedingReview($userId);
        jsonResponse(['success' => true, 'topics' => $topics]);
        exit;
    }

    if ($subjectId) {
        $topics = $topic->getAll($subjectId, $userId, $status);
        jsonResponse(['success' => true, 'topics' => $topics]);
        exit;
    }

    $statusFilter = null;
    if ($status) {
        $statuses = array_map('trim', explode(',', $status));
        $statusFilter = $statuses;
    }

    $sql = 'SELECT t.*, s.name as subject_name, s.color as subject_color
            FROM topics t
            JOIN subjects s ON t.subject_id = s.id
            WHERE s.user_id = ?';

    $params = [$userId];

    if ($statusFilter && count($statusFilter) > 0) {
        $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
        $sql .= " AND t.status IN ($placeholders)";
        $params = array_merge($params, $statusFilter);
    }

    $sql .= ' ORDER BY t.created_at DESC LIMIT 100';

    $topics = $db->getAll($sql, $params);
    jsonResponse(['success' => true, 'topics' => $topics]);
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $topic->create(
        isset($data['subject_id']) ? $data['subject_id'] : 0,
        $userId,
        isset($data['name']) ? $data['name'] : '',
        isset($data['description']) ? $data['description'] : '',
        isset($data['difficulty']) ? $data['difficulty'] : 3,
        isset($data['planned_sessions']) ? $data['planned_sessions'] : 4
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Topic created', 'topic_id' => $result['topic_id']], 201);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

elseif ($method === 'DELETE') {
    $topicId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$topicId) {
        jsonResponse(['success' => false, 'error' => 'Topic ID required'], 400);
        exit;
    }

    $topicData = $topic->getById($topicId, $userId);
    if (!$topicData) {
        jsonResponse(['success' => false, 'error' => 'Topic not found'], 404);
        exit;
    }

    $result = $db->query('DELETE FROM topics WHERE id = ? AND subject_id IN (SELECT id FROM subjects WHERE user_id = ?)', [$topicId, $userId]);

    jsonResponse(['success' => true, 'message' => 'Topic deleted']);
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 405);
}
