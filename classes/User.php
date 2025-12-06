<?php

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function register($username, $email, $password) {
        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'error' => 'Username must be 3-30 characters'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        $existing = $this->db->getOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->db->query(
                'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
                [$username, $email, $password_hash]
            );
            
            $user_id = $this->db->lastInsertId();
            
            $this->db->query(
                'INSERT INTO user_settings (user_id) VALUES (?)',
                [$user_id]
            );

            $this->db->query(
                'INSERT INTO user_streaks (user_id) VALUES (?)',
                [$user_id]
            );

            return ['success' => true, 'user_id' => $user_id];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }

    public function login($email, $password) {
        $user = $this->db->getOne('SELECT * FROM users WHERE email = ?', [$email]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordLoginAttempt($email, false);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }

        $this->recordLoginAttempt($email, true);
        $this->db->query('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);

        return ['success' => true, 'user' => ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email']]];
    }

    public function recordLoginAttempt($email, $success = false) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->db->query(
            'INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)',
            [$email, $ip, $success ? 1 : 0]
        );
    }

    public function checkLoginAttempts($email) {
        $fifteenMinutesAgo = date('Y-m-d H:i:s', time() - 900);
        
        $attempts = $this->db->getOne(
            'SELECT COUNT(*) as count FROM login_attempts WHERE email = ? AND attempted_at > ? AND success = 0',
            [$email, $fifteenMinutesAgo]
        );

        return $attempts['count'] >= 5;
    }

    public function getUserById($id) {
        return $this->db->getOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function updateProfile($user_id, $username = null, $email = null, $password = null) {
        if ($username) {
            if (strlen($username) < 3 || strlen($username) > 30) {
                return ['success' => false, 'error' => 'Invalid username'];
            }
            $this->db->query('UPDATE users SET username = ? WHERE id = ?', [$username, $user_id]);
        }

        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email'];
            }
            $this->db->query('UPDATE users SET email = ? WHERE id = ?', [$email, $user_id]);
        }

        if ($password) {
            if (strlen($password) < 6) {
                return ['success' => false, 'error' => 'Password too short'];
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->query('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $user_id]);
        }

        return ['success' => true];
    }
}
