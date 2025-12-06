<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Schedule.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$schedule = new Schedule($db);

if ($method === 'GET') {
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

    $items = $schedule->getSchedule($userId, $startDate, $endDate);
    jsonResponse(['success' => true, 'schedule' => $items]);
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $schedule->generate(
        $userId,
        $data['period_weeks'] ?? 1,
        $data['start_date'] ?? date('Y-m-d'),
        $data['available_days'] ?? [1, 2, 3, 4, 5],
        $data['time_slots'] ?? [],
        $data['max_sessions_per_day'] ?? 4
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Schedule generated', 'items_created' => $result['items_created']], 201);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

elseif ($method === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);
    $itemId = $_GET['id'] ?? 0;

    $result = $schedule->updateStatus(
        $itemId,
        $userId,
        $data['status'] ?? 'pending',
        $data['new_date'] ?? null
    );

    if ($result['success']) {
        jsonResponse(['success' => true, 'message' => 'Schedule item updated']);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
}
