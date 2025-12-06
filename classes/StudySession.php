<?php

class StudySession {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($userId, $topicId, $durationMinutes, $sessionType = 'work', $notes = '') {
        try {
            if (empty($userId) || empty($topicId)) {
                return ['success' => false, 'error' => 'User ID and Topic ID are required'];
            }

            if ($durationMinutes <= 0) {
                return ['success' => false, 'error' => 'Duration must be greater than 0'];
            }

            if (!in_array($sessionType, ['work', 'review'])) {
                $sessionType = 'work';
            }

            $startedAt = date('Y-m-d H:i:s');
            $completedAt = date('Y-m-d H:i:s', strtotime("+{$durationMinutes} minutes"));

            $sql = 'INSERT INTO study_sessions 
                    (user_id, topic_id, duration_minutes, session_type, notes, completed, started_at, completed_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

            $this->db->query($sql, [
                $userId,
                $topicId,
                $durationMinutes,
                $sessionType,
                $notes,
                1,
                $startedAt,
                $completedAt
            ]);

            $sessionId = $this->db->lastInsertId();

            return [
                'success' => true,
                'session_id' => $sessionId
            ];

        } catch (Exception $e) {
            error_log('StudySession::create error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create session: ' . $e->getMessage()
            ];
        }
    }

    public function getByTopic($topicId, $limit = 10) {
        $sql = 'SELECT * FROM study_sessions 
                WHERE topic_id = ? 
                ORDER BY started_at DESC 
                LIMIT ?';

        return $this->db->getAll($sql, [$topicId, (int)$limit]);
    }

    public function getRecent($userId, $limit = 10) {
        $sql = 'SELECT ss.*, t.name as topic_name, s.name as subject_name
                FROM study_sessions ss
                JOIN topics t ON t.id = ss.topic_id
                JOIN subjects s ON s.id = t.subject_id
                WHERE ss.user_id = ?
                ORDER BY ss.started_at DESC
                LIMIT ?';

        return $this->db->getAll($sql, [$userId, (int)$limit]);
    }

    public function delete($sessionId, $userId) {
        $sql = 'DELETE FROM study_sessions WHERE id = ? AND user_id = ?';
        $this->db->query($sql, [$sessionId, $userId]);

        return ['success' => true];
    }

    public function getStatsByUser($userId) {
        $sql = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(duration_minutes) as total_minutes
                FROM study_sessions 
                WHERE user_id = ? 
                  AND DATE(started_at) = CURDATE()
                  AND completed = 1";

        return $this->db->getOne($sql, [$userId]);
    }
}
