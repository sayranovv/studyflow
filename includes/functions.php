<?php

session_start();

require_once __DIR__ . '/constants.php';

function isAuthenticated() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /pages/auth/login.php');
        exit;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateColor($color) {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatMinutes($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
}

function daysUntil($date) {
    $today = new DateTime();
    $target = new DateTime($date);
    $diff = $target->diff($today);
    return $diff->invert ? -$diff->days : $diff->days;
}
