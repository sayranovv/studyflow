<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Subject.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$subject = new Subject($db);

if ($method === 'GET') {
    $archived = $_GET['archived'] ?? 0;
    $sort = $_GET['sort'] ?? 'priority';
    
    $subjects = $subject->getAll($userId, $archived, $sort);
    jsonResponse(['success' => true, 'subjects' => $subjects]);
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $result = $subject->create(
        $userId,
        $data['name'] ?? '',
        $data['description'] ?? '',
        $data['color'] ?? '#3B82F6',
        $data['exam_date'] ?? null,
        $data['priority'] ?? 'medium'
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Subject created', 'subject_id' => $result['subject_id']], 201);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
}
