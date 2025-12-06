<?php

class Topic {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function calculateReviewDate($difficulty, $reviewCount) {
        $intervals = [1, 3, 7, 14, 30];
        
        if ($reviewCount >= count($intervals)) {
            return null;
        }

        $days = $intervals[$reviewCount];
        $multiplier = match($difficulty) {
            1, 2 => 1.0,
            3 => 1.2,
            4, 5 => 1.5,
            default => 1.0
        };

        $days = (int)($days * $multiplier);
        return date('Y-m-d', strtotime("+$days days"));
    }

    public function create($subject_id, $user_id, $name, $description, $difficulty, $planned_sessions) {
        $subject = $this->db->getOne('SELECT id FROM subjects WHERE id = ? AND user_id = ?', [$subject_id, $user_id]);
        if (!$subject) {
            return ['success' => false, 'error' => 'Subject not found'];
        }

        if (strlen($name) < 3 || strlen($name) > 200) {
            return ['success' => false, 'error' => 'Topic name must be 3-200 characters'];
        }

        if ($difficulty < 1 || $difficulty > 5) {
            return ['success' => false, 'error' => 'Difficulty must be 1-5'];
        }

        if ($planned_sessions < 1 || $planned_sessions > 20) {
            return ['success' => false, 'error' => 'Planned sessions must be 1-20'];
        }

        try {
            $this->db->query(
                'INSERT INTO topics (subject_id, name, description, difficulty, planned_sessions) VALUES (?, ?, ?, ?, ?)',
                [$subject_id, $name, $description, $difficulty, $planned_sessions]
            );
            return ['success' => true, 'topic_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create topic'];
        }
    }

    public function getAll($subject_id, $user_id, $status = null) {
        $query = 'SELECT t.*, s.name as subject_name, s.color as subject_color 
                  FROM topics t 
                  JOIN subjects s ON t.subject_id = s.id 
                  WHERE t.subject_id = ? AND s.user_id = ?';
        $params = [$subject_id, $user_id];

        if ($status) {
            $query .= ' AND t.status = ?';
            $params[] = $status;
        }

        $query .= ' ORDER BY t.created_at DESC';
        return $this->db->getAll($query, $params);
    }

    public function getTopicsNeedingReview($user_id) {
        return $this->db->getAll(
            'SELECT t.*, s.name as subject_name, s.color as subject_color
             FROM topics t
             JOIN subjects s ON t.subject_id = s.id
             WHERE s.user_id = ? AND t.status IN (?, ?) AND t.next_review_date <= CURDATE()
             ORDER BY t.next_review_date ASC',
            [$user_id, 'first_review', 'reviewing']
        );
    }

    public function getById($topic_id, $user_id) {
        return $this->db->getOne(
            'SELECT t.*, s.user_id FROM topics t 
             JOIN subjects s ON t.subject_id = s.id 
             WHERE t.id = ? AND s.user_id = ?',
            [$topic_id, $user_id]
        );
    }

    public function update($topic_id, $user_id, $name, $description, $difficulty, $planned_sessions) {
        $topic = $this->getById($topic_id, $user_id);
        if (!$topic) {
            return ['success' => false, 'error' => 'Topic not found'];
        }

        $this->db->query(
            'UPDATE topics SET name = ?, description = ?, difficulty = ?, planned_sessions = ? WHERE id = ?',
            [$name, $description, $difficulty, $planned_sessions, $topic_id]
        );

        if ($difficulty != $topic['difficulty']) {
            if ($topic['review_count'] > 0 && $topic['last_review_date']) {
                $nextDate = $this->calculateReviewDate($difficulty, $topic['review_count'] - 1);
                $this->db->query('UPDATE topics SET next_review_date = ? WHERE id = ?', [$nextDate, $topic_id]);
            }
        }

        return ['success' => true];
    }

    public function completeSession($topic_id, $user_id) {
        $topic = $this->getById($topic_id, $user_id);
        if (!$topic) {
            return ['success' => false, 'error' => 'Topic not found'];
        }

        $completed = $topic['completed_sessions'] + 1;
        $status = $topic['status'];

        if ($completed >= $topic['planned_sessions']) {
            $status = 'first_review';
            $nextReviewDate = $this->calculateReviewDate($topic['difficulty'], 0);
            $this->db->query(
                'UPDATE topics SET completed_sessions = ?, status = ?, next_review_date = ?, last_review_date = CURDATE() WHERE id = ?',
                [$completed, $status, $nextReviewDate, $topic_id]
            );
        } else {
            if ($status == 'not_started') {
                $status = 'in_progress';
            }
            $this->db->query(
                'UPDATE topics SET completed_sessions = ?, status = ?, last_review_date = CURDATE() WHERE id = ?',
                [$completed, $status, $topic_id]
            );
        }

        return ['success' => true, 'new_status' => $status];
    }

    public function completeReview($topic_id, $user_id) {
        $topic = $this->getById($topic_id, $user_id);
        if (!$topic) {
            return ['success' => false, 'error' => 'Topic not found'];
        }

        $reviewCount = $topic['review_count'] + 1;
        $status = $topic['status'];
        $nextReviewDate = null;

        if ($reviewCount >= 5) {
            $status = 'mastered';
        } else {
            $status = 'reviewing';
            $nextReviewDate = $this->calculateReviewDate($topic['difficulty'], $reviewCount);
        }

        $this->db->query(
            'UPDATE topics SET review_count = ?, status = ?, next_review_date = ?, last_review_date = CURDATE() WHERE id = ?',
            [$reviewCount, $status, $nextReviewDate, $topic_id]
        );

        return ['success' => true, 'new_status' => $status];
    }

    public function delete($topic_id, $user_id) {
        $topic = $this->getById($topic_id, $user_id);
        if (!$topic) {
            return ['success' => false, 'error' => 'Topic not found'];
        }

        try {
            $this->db->beginTransaction();
            $this->db->query('DELETE FROM study_sessions WHERE topic_id = ?', [$topic_id]);
            $this->db->query('DELETE FROM schedule_items WHERE topic_id = ?', [$topic_id]);
            $this->db->query('DELETE FROM topics WHERE id = ?', [$topic_id]);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'Failed to delete topic'];
        }
    }
}
