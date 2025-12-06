<?php

class Schedule {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function generate($user_id, $periodWeeks, $startDate, $availableDays, $timeSlots, $maxSessionsPerDay) {
        try {
            $this->db->beginTransaction();

            $topicsNeeding = $this->getTopicsForScheduling($user_id);
            
            if (empty($topicsNeeding)) {
                $this->db->commit();
                return ['success' => true, 'items_created' => 0];
            }

            $endDate = date('Y-m-d', strtotime("+$periodWeeks weeks", strtotime($startDate)));
            $currentDate = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);
            $itemsCreated = 0;

            while ($currentDate <= $endDateObj) {
                $dayOfWeek = (int)$currentDate->format('N');
                
                if (!in_array($dayOfWeek, $availableDays)) {
                    $currentDate->modify('+1 day');
                    continue;
                }

                $slots = $timeSlots[$dayOfWeek] ?? null;
                if (!$slots) {
                    $currentDate->modify('+1 day');
                    continue;
                }

                $sessionsForDay = 0;
                $topicsByPriority = $this->prioritizeTopics($topicsNeeding);

                foreach ($topicsByPriority as $topic) {
                    if ($sessionsForDay >= $maxSessionsPerDay) {
                        break;
                    }

                    $topicSessionsToday = $this->db->getOne(
                        'SELECT COUNT(*) as cnt FROM schedule_items 
                         WHERE topic_id = ? AND scheduled_date = ?',
                        [$topic['id'], $currentDate->format('Y-m-d')]
                    );

                    if ($topicSessionsToday['cnt'] >= 3) {
                        continue;
                    }

                    $this->db->query(
                        'INSERT INTO schedule_items (user_id, topic_id, scheduled_date, scheduled_time, session_type, status)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [$user_id, $topic['id'], $currentDate->format('Y-m-d'), $slots['start'], $topic['session_type'], 'pending']
                    );

                    $itemsCreated++;
                    $sessionsForDay++;
                }

                $currentDate->modify('+1 day');
            }

            $this->db->commit();
            return ['success' => true, 'items_created' => $itemsCreated];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'Schedule generation failed'];
        }
    }

    private function getTopicsForScheduling($user_id) {
        return $this->db->getAll(
            'SELECT t.*, s.priority, s.exam_date, s.id as subject_id
             FROM topics t
             JOIN subjects s ON t.subject_id = s.id
             WHERE s.user_id = ? AND s.is_archived = 0
             AND (t.status IN (?, ?) OR (t.status IN (?, ?) AND t.next_review_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)))
             ORDER BY 
                CASE WHEN t.next_review_date < CURDATE() THEN 1 ELSE 2 END,
                CASE WHEN s.exam_date < DATE_ADD(CURDATE(), INTERVAL 14 DAY) THEN 1 ELSE 2 END,
                FIELD(s.priority, "high", "medium", "low") ASC',
            [$user_id, 'not_started', 'in_progress', 'first_review', 'reviewing']
        );
    }

    private function prioritizeTopics($topics) {
        $prioritized = [];
        
        foreach ($topics as $topic) {
            if ($topic['next_review_date'] && $topic['next_review_date'] < date('Y-m-d')) {
                $topic['session_type'] = 'review';
                $topic['priority_score'] = 1;
            } elseif ($topic['status'] === 'not_started' || $topic['status'] === 'in_progress') {
                $topic['session_type'] = 'new_material';
                $topic['priority_score'] = 2;
            } else {
                $topic['session_type'] = 'review';
                $topic['priority_score'] = 3;
            }

            $prioritized[] = $topic;
        }

        usort($prioritized, function($a, $b) {
            return $a['priority_score'] - $b['priority_score'];
        });

        return $prioritized;
    }

    public function getSchedule($user_id, $startDate, $endDate) {
        return $this->db->getAll(
            'SELECT si.*, t.name as topic_name, t.difficulty, s.name as subject_name, s.color
             FROM schedule_items si
             JOIN topics t ON si.topic_id = t.id
             JOIN subjects s ON t.subject_id = s.id
             WHERE si.user_id = ? AND si.scheduled_date BETWEEN ? AND ?
             ORDER BY si.scheduled_date ASC, si.scheduled_time ASC',
            [$user_id, $startDate, $endDate]
        );
    }

    public function updateStatus($item_id, $user_id, $status, $newDate = null) {
        $item = $this->db->getOne(
            'SELECT * FROM schedule_items WHERE id = ? AND user_id = ?',
            [$item_id, $user_id]
        );

        if (!$item) {
            return ['success' => false, 'error' => 'Schedule item not found'];
        }

        if ($status === 'rescheduled' && $newDate) {
            $this->db->query(
                'UPDATE schedule_items SET status = ?, scheduled_date = ? WHERE id = ?',
                [$status, $newDate, $item_id]
            );
        } else {
            $this->db->query(
                'UPDATE schedule_items SET status = ? WHERE id = ?',
                [$status, $item_id]
            );
        }

        return ['success' => true];
    }

    public function markCompleted($item_id, $user_id, $sessionId) {
        $this->db->query(
            'UPDATE schedule_items SET status = ?, session_id = ? WHERE id = ? AND user_id = ?',
            ['completed', $sessionId, $item_id, $user_id]
        );
        return ['success' => true];
    }
}
