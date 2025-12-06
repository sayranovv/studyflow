<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    $sql = "UPDATE user_settings SET 
            work_duration = ?, 
            short_break_duration = ?, 
            long_break_duration = ?, 
            sessions_before_long_break = ?, 
            auto_start_breaks = ?, 
            sound_enabled = ?, 
            notifications_enabled = ?, 
            email_reminders = ?, 
            max_sessions_per_day = ?, 
            planning_period_weeks = ?
            WHERE user_id = ?";

    $params = [
        (int)$data['work_duration'],
        (int)$data['short_break_duration'],
        (int)$data['long_break_duration'],
        (int)$data['sessions_before_long_break'],
        $data['auto_start_breaks'] ? 1 : 0,
        $data['sound_enabled'] ? 1 : 0,
        $data['notifications_enabled'] ? 1 : 0,
        $data['email_reminders'] ? 1 : 0,
        (int)$data['max_sessions_per_day'],
        (int)$data['planning_period_weeks'],
        $userId
    ];

    $db->query($sql, $params);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Settings save error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save settings: ' . $e->getMessage()]);
}
