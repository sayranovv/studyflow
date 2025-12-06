<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Subject.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$subjectModel = new Subject($db);

$archived = $_GET['archived'] ?? 0;
$sort = $_GET['sort'] ?? 'priority';
$subjects = $subjectModel->getAll($userId, $archived, $sort);

$colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#6366F1', '#14B8A6', '#F97316'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Предметы - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .subjects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .subjects-controls {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .subject-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border-left: 4px solid;
        }

        .subject-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-4px);
        }

        .subject-header {
            display: flex;
            align-items: start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .subject-color-badge {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .subject-title {
            flex: 1;
        }

        .subject-title h3 {
            margin: 0 0 4px 0;
            font-size: 18px;
        }

        .subject-title p {
            margin: 0;
            font-size: 12px;
            color: var(--text-light);
        }

        .subject-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 16px 0;
            padding: 16px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .info-item {
            text-align: center;
        }

        .info-item .label {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 4px;
        }

        .info-item .value {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
        }

        .subject-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary-small {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary-small:hover {
            background-color: #2563EB;
        }

        .btn-secondary-small {
            background-color: var(--border);
            color: var(--text);
        }

        .btn-secondary-small:hover {
            background-color: #D1D5DB;
        }

        .add-subject-btn {
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-subject-btn:hover {
            background-color: #2563EB;
        }

        .filter-tabs {
            display: flex;
            gap: 12px;
        }

        .filter-tab {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .progress-bar {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .subjects-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .subjects-grid {
                grid-template-columns: 1fr;
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
                    <a href="/pages/subjects.php" style="font-weight: bold; color: white; opacity: 1;">Предметы</a>
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
                <div class="subjects-header">
                    <div>
                        <h2>Мои предметы</h2>
                    </div>
                    <button class="add-subject-btn" onclick="openAddSubjectModal()">+ Добавить предмет</button>
                </div>

                <div class="subjects-controls">
                    <div class="filter-tabs">
                        <button class="filter-tab <?php echo $archived == 0 ? 'active' : ''; ?>" onclick="filterSubjects(0)">Активные</button>
                        <button class="filter-tab <?php echo $archived == 1 ? 'active' : ''; ?>" onclick="filterSubjects(1)">Архивные</button>
                    </div>
                </div>

                <div class="subjects-grid">
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <div class="subject-card" style="border-left-color: <?php echo $subject['color']; ?>;">
                                <div class="subject-header">
                                    <div class="subject-color-badge" style="background-color: <?php echo $subject['color']; ?>"></div>
                                    <div class="subject-title">
                                        <h3><?php echo sanitizeInput($subject['name']); ?></h3>
                                        <p><?php echo $subject['topics_count'] ?? 0; ?> тем</p>
                                    </div>
                                </div>

                                <?php if ($subject['exam_date']): ?>
                                    <p style="font-size: 12px; color: var(--text-light); margin: 0 0 12px 0;">
                                        📅 Экзамен: <?php echo date('d.m.Y', strtotime($subject['exam_date'])); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="subject-info">
                                    <div class="info-item">
                                        <div class="label">Прогресс</div>
                                        <div class="value"><?php echo (int)(($subject['topics_completed'] ?? 0) / max(1, $subject['topics_count'] ?? 1) * 100); ?>%</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Приоритет</div>
                                        <div class="value" style="font-size: 14px;">
                                            <?php
                                            echo match($subject['priority']) {
                                                'high' => '🔴 Высокий',
                                                'medium' => '🟡 Средний',
                                                'low' => '🟢 Низкий',
                                                default => $subject['priority']
                                            };
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo (int)(($subject['topics_completed'] ?? 0) / max(1, $subject['topics_count'] ?? 1) * 100); ?>%"></div>
                                </div>

                                <div class="subject-actions">
                                    <button class="btn-small btn-primary-small" onclick="openSubject(<?php echo $subject['id']; ?>)">Открыть</button>
                                    <button class="btn-small btn-secondary-small" onclick="editSubject(<?php echo $subject['id']; ?>)">Редактировать</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="grid-column: 1/-1; text-align: center; color: var(--text-light); padding: 40px 20px;">
                            Нет предметов. <a href="#" onclick="openAddSubjectModal()">Создайте первый предмет</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function filterSubjects(archived) {
            window.location.href = `/pages/subjects.php?archived=${archived}`;
        }

        function openAddSubjectModal() {
            const name = prompt('Название предмета:');
            if (!name) return;

            const description = prompt('Описание (опционально):');
            const priority = prompt('Приоритет (low/medium/high) [medium]:') || 'medium';
            const examDate = prompt('Дата экзамена (опционально, YYYY-MM-DD):');

            const colorIndex = Math.floor(Math.random() * 10);
            const color = <?php echo json_encode($colors); ?>[colorIndex];

            const subjectData = {
                name,
                description: description || '',
                color,
                exam_date: examDate || null,
                priority
            };

            fetch('/api/subjects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(subjectData)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Предмет создан!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            })
            .catch(e => alert('Ошибка при создании предмета'));
        }

        function editSubject(id) {
            alert('Редактирование предметов в разработке');
        }

        function openSubject(id) {
            window.location.href = `/pages/topics.php?subject_id=${id}`;
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
