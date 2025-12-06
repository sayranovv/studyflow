<?php

class Statistics {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getOverviewStats($user_id) {
        $total = $this->db->getOne(
            'SELECT COUNT(*) as cnt, COALESCE(SUM(duration_minutes), 0) as total_minutes
             FROM study_sessions WHERE user_id = ?',
            [$user_id]
        );

        $today = $this->db->getOne(
            'SELECT COUNT(*) as cnt FROM study_sessions WHERE user_id = ? AND DATE(started_at) = CURDATE()',
            [$user_id]
        );

        $streak = $this->db->getOne(
            'SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?',
            [$user_id]
        );

        $topics = $this->db->getOne(
            'SELECT 
                SUM(CASE WHEN t.status = "mastered" THEN 1 ELSE 0 END) as mastered,
                SUM(CASE WHEN t.status IN ("in_progress", "first_review", "reviewing") THEN 1 ELSE 0 END) as in_progress,
                COUNT(*) as total
             FROM topics t
             JOIN subjects s ON t.subject_id = s.id
             WHERE s.user_id = ?',
            [$user_id]
        );

        $avgDuration = $this->db->getOne(
            'SELECT AVG(duration_minutes) as avg FROM study_sessions WHERE user_id = ?',
            [$user_id]
        );

        $completed = $this->db->getOne(
            'SELECT COUNT(*) as cnt FROM study_sessions WHERE user_id = ? AND completed = 1',
            [$user_id]
        );

        $total_sessions = $total['cnt'] ?? 0;
        $completion_rate = $total_sessions > 0 ? round(($completed['cnt'] ?? 0) / $total_sessions * 100) : 0;

        return [
            'total_sessions' => $total_sessions,
            'total_study_time' => $total['total_minutes'] ?? 0,
            'sessions_today' => $today['cnt'] ?? 0,
            'current_streak' => $streak['current_streak'] ?? 0,
            'longest_streak' => $streak['longest_streak'] ?? 0,
            'topics_mastered' => $topics['mastered'] ?? 0,
            'topics_in_progress' => $topics['in_progress'] ?? 0,
            'topics_total' => $topics['total'] ?? 0,
            'average_session_duration' => round($avgDuration['avg'] ?? 0),
            'completion_rate' => $completion_rate
        ];
    }

    public function getSubjectStats($subject_id, $user_id) {
        $subject = $this->db->getOne(
            'SELECT * FROM subjects WHERE id = ? AND user_id = ?',
            [$subject_id, $user_id]
        );

        if (!$subject) {
            return null;
        }

        $sessionTime = $this->db->getOne(
            'SELECT COUNT(*) as cnt, COALESCE(SUM(duration_minutes), 0) as total
             FROM study_sessions ss
             JOIN topics t ON ss.topic_id = t.id
             WHERE t.subject_id = ?',
            [$subject_id]
        );

        $topicStats = $this->db->getOne(
            'SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = "mastered" THEN 1 ELSE 0 END) as mastered,
                SUM(CASE WHEN status IN ("in_progress", "first_review", "reviewing") THEN 1 ELSE 0 END) as in_progress,
                AVG(difficulty) as avg_difficulty
             FROM topics WHERE subject_id = ?',
            [$subject_id]
        );

        $progress = $topicStats['total'] > 0 ? round($topicStats['mastered'] / $topicStats['total'] * 100) : 0;

        return [
            'subject' => $subject,
            'total_study_time' => $sessionTime['total'] ?? 0,
            'total_sessions' => $sessionTime['cnt'] ?? 0,
            'progress' => $progress,
            'topics_total' => $topicStats['total'] ?? 0,
            'topics_mastered' => $topicStats['mastered'] ?? 0,
            'topics_in_progress' => $topicStats['in_progress'] ?? 0,
            'avg_difficulty' => round($topicStats['avg_difficulty'] ?? 0, 1)
        ];
    }

    public function getActivityHeatmap($user_id, $period = 'month') {
        $days = match($period) {
            'week' => 7,
            'month' => 30,
            '3months' => 90,
            default => 30
        };

        $activity = $this->db->getAll(
            'SELECT DATE(started_at) as date, COUNT(*) as sessions, SUM(duration_minutes) as minutes
             FROM study_sessions
             WHERE user_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(started_at)
             ORDER BY date ASC',
            [$user_id, $days]
        );

        $result = [];
        $startDate = new DateTime("-$days days");
        $today = new DateTime();

        while ($startDate <= $today) {
            $dateStr = $startDate->format('Y-m-d');
            $found = array_filter($activity, fn($a) => $a['date'] === $dateStr);
            
            if ($found) {
                $item = reset($found);
                $result[$dateStr] = ['sessions' => $item['sessions'], 'minutes' => $item['minutes']];
            } else {
                $result[$dateStr] = ['sessions' => 0, 'minutes' => 0];
            }

            $startDate->modify('+1 day');
        }

        return $result;
    }

    public function getTopicStats($topic_id, $user_id) {
        $topic = $this->db->getOne(
            'SELECT t.*, s.id as subject_id FROM topics t
             JOIN subjects s ON t.subject_id = s.id
             WHERE t.id = ? AND s.user_id = ?',
            [$topic_id, $user_id]
        );

        if (!$topic) {
            return null;
        }

        $sessions = $this->db->getAll(
            'SELECT * FROM study_sessions WHERE topic_id = ? ORDER BY started_at DESC',
            [$topic_id]
        );

        $totalTime = array_reduce($sessions, fn($carry, $item) => $carry + $item['duration_minutes'], 0);

        return [
            'topic' => $topic,
            'sessions' => count($sessions),
            'total_time' => $totalTime,
            'session_history' => $sessions,
            'next_review' => $topic['next_review_date'],
            'last_review' => $topic['last_review_date']
        ];
    }

    public function getSubjectTimeDistribution($user_id) {
        return $this->db->getAll(
            'SELECT s.name, s.color, SUM(ss.duration_minutes) as total_minutes
             FROM study_sessions ss
             JOIN topics t ON ss.topic_id = t.id
             JOIN subjects s ON t.subject_id = s.id
             WHERE s.user_id = ? AND s.is_archived = 0
             GROUP BY s.id
             ORDER BY total_minutes DESC',
            [$user_id]
        );
    }
}
