<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../classes/Topic.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$subjectId = $_GET['subject_id'] ?? null;
$status = $_GET['status'] ?? null;

$subjectModel = new Subject($db);
$topicModel = new Topic($db);

if (!$subjectId) {
    redirect('/pages/subjects.php');
}

$subject = $subjectModel->getById($subjectId, $userId);
if (!$subject) {
    redirect('/pages/subjects.php');
}

$topics = $topicModel->getAll($subjectId, $userId, $status);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Темы - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .topic-header-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .subject-badge {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: <?php echo $subject['color']; ?>;
        }

        .header-info h2 {
            margin: 0 0 4px 0;
        }

        .header-info p {
            margin: 0;
            font-size: 14px;
            color: var(--text-light);
        }

        .topic-actions {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary-action {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary-action:hover {
            background-color: #2563EB;
        }

        .topics-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .topics-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .topic-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: start;
            transition: all 0.3s;
        }

        .topic-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .topic-difficulty {
            display: flex;
            gap: 3px;
            flex-shrink: 0;
        }

        .star {
            font-size: 18px;
            opacity: 0.3;
        }

        .star.filled {
            opacity: 1;
        }

        .topic-content {
            flex: 1;
        }

        .topic-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .topic-description {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 8px;
        }

        .topic-stats {
            display: flex;
            gap: 20px;
            font-size: 12px;
        }

        .topic-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: auto;
        }

        .status-not-started {
            background: #F3F4F6;
            color: #6B7280;
        }

        .status-in-progress {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-first-review {
            background: #DBEAFE;
            color: #0C4A6E;
        }

        .status-reviewing {
            background: #E0E7FF;
            color: #3730A3;
        }

        .status-mastered {
            background: #DCFCE7;
            color: #166534;
        }

        .topic-actions-btn {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-small-start {
            background-color: var(--success);
            color: white;
        }

        .btn-small-start:hover {
            background-color: #059669;
        }

        .btn-small-delete {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .btn-small-delete:hover {
            background-color: #FECACA;
        }

        @media (max-width: 768px) {
            .topic-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .topic-item {
                flex-direction: column;
            }
        }
    </style>
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
                <div class="topic-header">
                    <div class="topic-header-title">
                        <div class="subject-badge"></div>
                        <div class="header-info">
                            <h2><?php echo sanitizeInput($subject['name']); ?></h2>
                            <p><?php echo count($topics); ?> тем</p>
                        </div>
                    </div>
                    <div class="topic-actions">
                        <a href="/pages/subjects.php" class="btn-action">← Назад</a>
                        <button class="btn-action btn-primary-action" onclick="addTopic()">+ Добавить тему</button>
                    </div>
                </div>

                <div class="topics-filters">
                    <button class="filter-btn active" onclick="filterTopics(null)">Все</button>
                    <button class="filter-btn" onclick="filterTopics('not_started')">Не начаты</button>
                    <button class="filter-btn" onclick="filterTopics('in_progress')">В процессе</button>
                    <button class="filter-btn" onclick="filterTopics('reviewing')">На повторении</button>
                    <button class="filter-btn" onclick="filterTopics('mastered')">Освоены</button>
                </div>

                <div class="topics-list">
                    <?php if (!empty($topics)): ?>
                        <?php foreach ($topics as $topic): ?>
                            <div class="topic-item">
                                <div class="topic-difficulty">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $topic['difficulty'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>

                                <div class="topic-content">
                                    <div class="topic-title"><?php echo sanitizeInput($topic['name']); ?></div>
                                    <?php if ($topic['description']): ?>
                                        <div class="topic-description"><?php echo substr(sanitizeInput($topic['description']), 0, 100); ?></div>
                                    <?php endif; ?>
                                    <div class="topic-stats">
                                        <span>📚 <?php echo $topic['completed_sessions']; ?>/<?php echo $topic['planned_sessions']; ?> сессий</span>
                                        <?php if ($topic['next_review_date']): ?>
                                            <span>🔄 Повтор: <?php echo date('d.m', strtotime($topic['next_review_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <span class="topic-status status-<?php echo str_replace('_', '-', $topic['status']); ?>">
                                    <?php
                                    echo match($topic['status']) {
                                        'not_started' => 'Не начата',
                                        'in_progress' => 'В процессе',
                                        'first_review' => 'Ждёт повтора',
                                        'reviewing' => 'На повторении',
                                        'mastered' => 'Освоена',
                                        default => $topic['status']
                                    };
                                    ?>
                                </span>

                                <div class="topic-actions-btn">
                                    <button class="btn-small btn-small-start" onclick="startSession(<?php echo $topic['id']; ?>)">Начать</button>
                                    <button class="btn-small btn-small-delete" onclick="deleteTopic(<?php echo $topic['id']; ?>)">Удалить</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            Нет тем. <a href="#" onclick="addTopic()">Добавьте первую тему</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        const subjectId = <?php echo $subjectId; ?>;

        function addTopic() {
            const name = prompt('Название темы:');
            if (!name) return;

            const description = prompt('Описание (опционально):');
            const difficulty = parseInt(prompt('Сложность (1-5) [3]:') || '3');
            const plannedSessions = parseInt(prompt('Планируемое количество сессий (1-20) [4]:') || '4');

            const topicData = {
                subject_id: subjectId,
                name,
                description: description || '',
                difficulty,
                planned_sessions: plannedSessions
            };

            fetch('/api/topics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(topicData)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Тема создана!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            })
            .catch(e => alert('Ошибка при создании темы'));
        }

        function filterTopics(statusFilter) {
            const url = new URL(window.location);
            if (statusFilter) {
                url.searchParams.set('status', statusFilter);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }

        function startSession(topicId) {
            window.location.href = `/pages/timer.php?topic_id=${topicId}`;
        }

        function deleteTopic(topicId) {
            if (confirm('Вы уверены? Это удалит тему и все связанные сессии.')) {
                fetch(`/api/topics.php?id=${topicId}`, {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Тема удалена');
                        location.reload();
                    }
                })
                .catch(e => alert('Ошибка при удалении'));
            }
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
