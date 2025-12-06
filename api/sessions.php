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

    error_log('Session POST data: ' . print_r($data, true));

    if (!isset($data['topic_id']) || empty($data['topic_id'])) {
        jsonResponse(['success' => false, 'error' => 'Topic ID is required'], 400);
        exit;
    }

    if (!isset($data['duration_minutes']) || $data['duration_minutes'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Duration must be greater than 0'], 400);
        exit;
    }

    $sessionType = $data['session_type'] ?? 'work';
    if (!in_array($sessionType, ['work', 'review'])) {
        $sessionType = 'work';
    }

    $topicModel = new Topic($db);
    $topic = $topicModel->getById($data['topic_id'], $userId);

    if (!$topic) {
        jsonResponse(['success' => false, 'error' => 'Topic not found or access denied'], 403);
        exit;
    }

    $result = $session->create(
        $userId,
        $data['topic_id'],
        $data['duration_minutes'],
        $sessionType,
        $data['notes'] ?? ''
    );

    if ($result['success']) {
        updateTopicProgress($db, $data['topic_id'], $userId);

        jsonResponse([
            'success' => true,
            'message' => 'Session saved',
            'session_id' => $result['session_id']
        ], 201);
    } else {
        error_log('Session creation failed: ' . ($result['error'] ?? 'Unknown error'));
        jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Failed to create session'], 400);
    }
}

else {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

function updateTopicProgress($db, $topicId, $userId) {
    try {
        $topic = $db->getOne('SELECT * FROM topics WHERE id = ?', [$topicId]);

        if (!$topic) {
            error_log("Topic $topicId not found for progress update");
            return;
        }

        $completedCount = $db->getOne(
            'SELECT COUNT(*) AS cnt FROM study_sessions 
             WHERE topic_id = ? AND completed = 1',
            [$topicId]
        );

        $completedSessions = (int)($completedCount['cnt'] ?? 0);

        $db->query(
            'UPDATE topics SET completed_sessions = ? WHERE id = ?',
            [$completedSessions, $topicId]
        );

        if ($completedSessions >= $topic['planned_sessions']) {
            $db->query('UPDATE topics SET status = ? WHERE id = ?', ['mastered', $topicId]);
        } elseif ($completedSessions > 0 && $topic['status'] === 'not_started') {
            $db->query('UPDATE topics SET status = ? WHERE id = ?', ['in_progress', $topicId]);
        }

        $subjectStats = $db->getOne(
            'SELECT COUNT(*) AS total, 
                    SUM(CASE WHEN status = "mastered" THEN 1 ELSE 0 END) AS mastered_count
             FROM topics WHERE subject_id = ?',
            [$topic['subject_id']]
        );

        if ($subjectStats && $subjectStats['total'] > 0) {
            $progress = round($subjectStats['mastered_count'] / $subjectStats['total'] * 100);

            $columns = $db->getAll("SHOW COLUMNS FROM subjects LIKE 'progress'");
            if (!empty($columns)) {
                $db->query('UPDATE subjects SET progress = ? WHERE id = ?', [$progress, $topic['subject_id']]);
            }
        }

        error_log("Topic $topicId progress updated: $completedSessions sessions completed");

    } catch (Exception $e) {
        error_log('Error updating topic progress: ' . $e->getMessage());
    }
}
