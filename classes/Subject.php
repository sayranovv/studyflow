<?php

class Subject {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($user_id, $name, $description, $color, $exam_date, $priority) {
        if (strlen($name) < 3 || strlen($name) > 100) {
            return ['success' => false, 'error' => 'Subject name must be 3-100 characters'];
        }

        $existing = $this->db->getOne(
            'SELECT id FROM subjects WHERE user_id = ? AND name = ?',
            [$user_id, $name]
        );
        if ($existing) {
            return ['success' => false, 'error' => 'Subject with this name already exists'];
        }

        $count = $this->db->getOne(
            'SELECT COUNT(*) as cnt FROM subjects WHERE user_id = ? AND is_archived = 0',
            [$user_id]
        );
        if ($count['cnt'] >= 50) {
            return ['success' => false, 'error' => 'Maximum 50 active subjects allowed'];
        }

        try {
            $this->db->query(
                'INSERT INTO subjects (user_id, name, description, color, exam_date, priority) VALUES (?, ?, ?, ?, ?, ?)',
                [$user_id, $name, $description, $color, $exam_date ?: null, $priority]
            );
            return ['success' => true, 'subject_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create subject'];
        }
    }

    public function getAll($user_id, $archived = 0, $sort = 'priority') {
        $orderBy = match($sort) {
            'name' => 'name ASC',
            'exam_date' => 'exam_date ASC',
            'priority' => "FIELD(priority, 'high', 'medium', 'low') ASC",
            default => "FIELD(priority, 'high', 'medium', 'low') ASC"
        };

        return $this->db->getAll(
            "SELECT s.*, COUNT(t.id) as topics_count, SUM(CASE WHEN t.status = 'mastered' THEN 1 ELSE 0 END) as topics_completed
             FROM subjects s
             LEFT JOIN topics t ON s.id = t.subject_id
             WHERE s.user_id = ? AND s.is_archived = ?
             GROUP BY s.id
             ORDER BY $orderBy",
            [$user_id, $archived]
        );
    }

    public function getById($subject_id, $user_id) {
        return $this->db->getOne(
            'SELECT * FROM subjects WHERE id = ? AND user_id = ?',
            [$subject_id, $user_id]
        );
    }

    public function update($subject_id, $user_id, $name, $description, $color, $exam_date, $priority) {
        if (!$this->getById($subject_id, $user_id)) {
            return ['success' => false, 'error' => 'Subject not found'];
        }

        $this->db->query(
            'UPDATE subjects SET name = ?, description = ?, color = ?, exam_date = ?, priority = ? WHERE id = ?',
            [$name, $description, $color, $exam_date ?: null, $priority, $subject_id]
        );

        return ['success' => true];
    }

    public function archive($subject_id, $user_id) {
        if (!$this->getById($subject_id, $user_id)) {
            return ['success' => false, 'error' => 'Subject not found'];
        }

        $this->db->query('UPDATE subjects SET is_archived = 1 WHERE id = ?', [$subject_id]);
        return ['success' => true];
    }

    public function delete($subject_id, $user_id) {
        if (!$this->getById($subject_id, $user_id)) {
            return ['success' => false, 'error' => 'Subject not found'];
        }

        try {
            $this->db->beginTransaction();
            $this->db->query('DELETE FROM topics WHERE subject_id = ?', [$subject_id]);
            $this->db->query('DELETE FROM subjects WHERE id = ?', [$subject_id]);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'Failed to delete subject'];
        }
    }

    public function search($user_id, $query) {
        return $this->db->getAll(
            'SELECT * FROM subjects WHERE user_id = ? AND is_archived = 0 AND name LIKE ? ORDER BY name',
            [$user_id, "%$query%"]
        );
    }
}
