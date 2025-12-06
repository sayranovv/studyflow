<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Statistics.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$stats = new Statistics($db);

if ($method === 'GET') {
    $type = $_GET['type'] ?? 'overview';
    
    if ($type === 'overview') {
        $data = $stats->getOverviewStats($userId);
        jsonResponse(['success' => true, 'stats' => $data]);
    }
    elseif ($type === 'subject' && isset($_GET['subject_id'])) {
        $data = $stats->getSubjectStats($_GET['subject_id'], $userId);
        if ($data) {
            jsonResponse(['success' => true, 'stats' => $data]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Subject not found'], 404);
        }
    }
    elseif ($type === 'activity') {
        $period = $_GET['period'] ?? 'month';
        $data = $stats->getActivityHeatmap($userId, $period);
        jsonResponse(['success' => true, 'activity' => $data]);
    }
    elseif ($type === 'distribution') {
        $data = $stats->getSubjectTimeDistribution($userId);
        jsonResponse(['success' => true, 'distribution' => $data]);
    }
    else {
        jsonResponse(['success' => false, 'error' => 'Invalid stats type'], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 400);
}
