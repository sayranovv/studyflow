<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/StudySession.php';
require_once __DIR__ . '/../classes/Topic.php';
require_once __DIR__ . '/../classes/Schedule.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$sessionModel = new StudySession($db);
$topicModel = new Topic($db);
$scheduleModel = new Schedule($db);

$stats = $sessionModel->getStatsByUser($userId, 'today');
$topicsNeedingReview = $topicModel->getTopicsNeedingReview($userId);

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$scheduleToday = $scheduleModel->getSchedule($userId, $today, $today);
$scheduleWeek = $scheduleModel->getSchedule($userId, $today, $nextWeek);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="header-content">
                <h1>StudyFlow</h1>
                <nav class="nav">
                    <a href="/pages/dashboard.php">Dashboard</a>
                    <a href="/pages/subjects.php">Предметы</a>
                    <a href="/pages/timer.php">Таймер</a>
                    <a href="/pages/schedule.php">Расписание</a>
                    <a href="/pages/statistics.php">Статистика</a>
                    <a href="/pages/settings.php">Настройки</a>
                    <a href="#" onclick="logout()">Выход</a>
                </nav>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <h2>Добро пожаловать!</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Сегодня</h3>
                        <p class="stat-value"><?php echo $stats['cnt'] ?? 0; ?></p>
                        <p class="stat-label">Сессий</p>
                    </div>
                    <div class="stat-card">
                        <h3>Время</h3>
                        <p class="stat-value"><?php echo formatMinutes($stats['total_minutes'] ?? 0); ?></p>
                        <p class="stat-label">Изучено</p>
                    </div>
                    <div class="stat-card">
                        <h3>К повторению</h3>
                        <p class="stat-value"><?php echo count($topicsNeedingReview); ?></p>
                        <p class="stat-label">Тем</p>
                    </div>
                </div>

                <div class="section">
                    <h3>Требуют повторения</h3>
                    <?php if (!empty($topicsNeedingReview)): ?>
                        <div class="topics-list">
                            <?php foreach (array_slice($topicsNeedingReview, 0, 5) as $topic): ?>
                                <div class="topic-item">
                                    <div class="topic-color" style="background-color: <?php echo $topic['subject_color']; ?>"></div>
                                    <div class="topic-info">
                                        <p class="topic-name"><?php echo sanitizeInput($topic['name']); ?></p>
                                        <p class="topic-subject"><?php echo sanitizeInput($topic['subject_name']); ?></p>
                                    </div>
                                    <button class="btn btn-small" onclick="startSession(<?php echo $topic['id']; ?>)">Начать</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Пока нет тем для повторения</p>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <h3>Расписание на сегодня</h3>
                    <?php if (!empty($scheduleToday)): ?>
                        <div class="schedule-list">
                            <?php foreach ($scheduleToday as $item): ?>
                                <div class="schedule-item">
                                    <div class="schedule-time"><?php echo $item['scheduled_time'] ?? 'Любое время'; ?></div>
                                    <div class="schedule-info">
                                        <p class="schedule-topic"><?php echo sanitizeInput($item['topic_name']); ?></p>
                                        <p class="schedule-subject"><?php echo sanitizeInput($item['subject_name']); ?> - <?php echo $item['session_type'] === 'new_material' ? 'Новый материал' : 'Повторение'; ?></p>
                                    </div>
                                    <div class="schedule-status status-<?php echo $item['status']; ?>">
                                        <?php echo match($item['status']) {
                                            'pending' => '⏳ Ожидает',
                                            'completed' => '✅ Выполнено',
                                            'skipped' => '⏭️ Пропущено',
                                            default => $item['status']
                                        }; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">На сегодня нет запланированных сессий</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function startSession(topicId) {
            window.location.href = `timer.php`;
        }

        async function logout() {
            const response = await fetch('/api/auth.php?action=logout', { method: 'POST' });
            if (response.ok) {
                window.location.href = '/pages/auth/login.php';
            }
        }
    </script>
</body>
</html>
