<?php

class StudySession {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($user_id, $topic_id, $duration, $type, $notes = '') {
        $topic = $this->db->getOne(
            'SELECT t.* FROM topics t 
             JOIN subjects s ON t.subject_id = s.id 
             WHERE t.id = ? AND s.user_id = ?',
            [$topic_id, $user_id]
        );

        if (!$topic) {
            return ['success' => false, 'error' => 'Topic not found'];
        }

        try {
            $this->db->query(
                'INSERT INTO study_sessions (user_id, topic_id, duration_minutes, session_type, completed, notes, started_at, completed_at) 
                 VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())',
                [$user_id, $topic_id, $duration, $type, $notes]
            );

            $sessionId = $this->db->lastInsertId();

            if ($type === 'work') {
                $topicModel = new Topic($this->db);
                $result = $topicModel->completeSession($topic_id, $user_id);
                if (!$result['success']) {
                    throw new Exception('Failed to update topic');
                }
            } elseif ($type === 'review') {
                $topicModel = new Topic($this->db);
                $result = $topicModel->completeReview($topic_id, $user_id);
                if (!$result['success']) {
                    throw new Exception('Failed to update topic');
                }
            }

            $this->updateUserStreak($user_id);

            return ['success' => true, 'session_id' => $sessionId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create session'];
        }
    }

    public function updateUserStreak($user_id) {
        $lastActivityDate = $this->db->getOne(
            'SELECT MAX(DATE(started_at)) as last_date FROM study_sessions WHERE user_id = ?',
            [$user_id]
        );

        $lastDate = $lastActivityDate['last_date'];
        if (!$lastDate) return;

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $streak = $this->db->getOne('SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?', [$user_id]);

        if ($lastDate == $today) {
            return;
        }

        if ($lastDate == $yesterday) {
            $newStreak = ($streak['current_streak'] ?? 0) + 1;
            $longestStreak = max($newStreak, $streak['longest_streak'] ?? 0);
        } else {
            $newStreak = 1;
            $longestStreak = $streak['longest_streak'] ?? 0;
        }

        $this->db->query(
            'UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_activity_date = ? WHERE user_id = ?',
            [$newStreak, $longestStreak, $today, $user_id]
        );
    }

    public function getRecent($user_id, $limit = 10) {
        return $this->db->getAll(
            'SELECT s.*, t.name as topic_name, subj.name as subject_name, subj.color 
             FROM study_sessions s
             JOIN topics t ON s.topic_id = t.id
             JOIN subjects subj ON t.subject_id = subj.id
             WHERE s.user_id = ?
             ORDER BY s.started_at DESC
             LIMIT ?',
            [$user_id, $limit]
        );
    }

    public function getByTopic($topic_id, $limit = 20) {
        return $this->db->getAll(
            'SELECT * FROM study_sessions WHERE topic_id = ? ORDER BY started_at DESC LIMIT ?',
            [$topic_id, $limit]
        );
    }

    public function getStatsByUser($user_id, $period = 'all') {
        $dateFilter = '';
        $params = [$user_id];

        if ($period === 'today') {
            $dateFilter = ' AND DATE(started_at) = CURDATE()';
        } elseif ($period === 'week') {
            $dateFilter = ' AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        } elseif ($period === 'month') {
            $dateFilter = ' AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        }

        return $this->db->getOne(
            "SELECT 
                COUNT(*) as total_sessions,
                SUM(duration_minutes) as total_minutes,
                AVG(duration_minutes) as avg_duration,
                SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as incomplete_count
             FROM study_sessions 
             WHERE user_id = ?" . $dateFilter,
            $params
        );
    }

    public function getActivityHeatmap($user_id, $days = 90) {
        return $this->db->getAll(
            'SELECT DATE(started_at) as date, COUNT(*) as sessions, SUM(duration_minutes) as minutes
             FROM study_sessions
             WHERE user_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(started_at)
             ORDER BY date DESC',
            [$user_id, $days]
        );
    }
}
