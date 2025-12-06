<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Schedule.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$scheduleModel = new Schedule($db);

$today = date('Y-m-d');
$weekEnd = date('Y-m-d', strtotime('+7 days'));

$schedule = $scheduleModel->getSchedule($userId, $today, $weekEnd);
$userSettings = $db->getOne('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .schedule-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .schedule-period {
            display: flex;
            gap: 8px;
        }

        .btn-period {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-period.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-generate {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-generate:hover {
            background: #2563EB;
        }

        .schedule-calendar {
            display: grid;
            gap: 16px;
        }

        .schedule-day {
            background: var(--surface);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .day-header {
            background: linear-gradient(135deg, var(--primary) 0%, #2563EB 100%);
            color: white;
            padding: 16px;
            font-weight: 600;
            font-size: 16px;
        }

        .day-header .date {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.9;
        }

        .day-items {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
        }

        .day-items:empty::after {
            content: 'Нет запланировано';
            color: var(--text-light);
            font-size: 12px;
            padding: 20px;
            text-align: center;
        }

        .schedule-session {
            background: var(--bg);
            border-left: 4px solid;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .schedule-session:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .session-type-new {
            border-left-color: var(--primary);
        }

        .session-type-review {
            border-left-color: var(--warning);
        }

        .session-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .session-subject {
            color: var(--text-light);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .session-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-new {
            background: #DBEAFE;
            color: #0C4A6E;
        }

        .badge-review {
            background: #FEF3C7;
            color: #92400E;
        }

        .session-status {
            margin-left: auto;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            background: #F3F4F6;
            color: #6B7280;
        }

        @media (max-width: 768px) {
            .schedule-controls {
                flex-direction: column;
                align-items: flex-start;
            }

            .schedule-calendar {
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
                    <a href="/pages/subjects.php">Предметы</a>
                    <a href="/pages/timer.php">Таймер</a>
                    <a href="/pages/schedule.php" style="font-weight: bold;">Расписание</a>
                    <a href="/pages/statistics.php">Статистика</a>
                    <a href="/pages/settings.php">Настройки</a>
                    <a href="#" onclick="logout()">Выход</a>
                </nav>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <h2 style="margin-bottom: 20px;">Расписание</h2>

                <div class="schedule-controls">
                    <div class="schedule-period">
                        <button class="btn-period active" onclick="changePeriod(1)">Неделя</button>
                        <button class="btn-period" onclick="changePeriod(2)">2 недели</button>
                        <button class="btn-period" onclick="changePeriod(4)">Месяц</button>
                    </div>
                    <button class="btn-generate" onclick="generateSchedule()">Сгенерировать расписание</button>
                </div>

                <div class="schedule-calendar">
                    <?php
                    $currentDate = strtotime($today);
                    $endDate = strtotime($weekEnd);

                    while ($currentDate <= $endDate) {
                        $dateStr = date('Y-m-d', $currentDate);
                        $daySchedule = array_filter($schedule, fn($s) => $s['scheduled_date'] === $dateStr);

                        $dayName = match(date('w', $currentDate)) {
                            0 => 'Воскресенье',
                            1 => 'Понедельник',
                            2 => 'Вторник',
                            3 => 'Среда',
                            4 => 'Четверг',
                            5 => 'Пятница',
                            6 => 'Суббота'
                        };
                    ?>
                        <div class="schedule-day">
                            <div class="day-header">
                                <?php echo $dayName; ?>
                                <span class="date"><?php echo date('d.m.Y', $currentDate); ?></span>
                            </div>
                            <div class="day-items">
                                <?php foreach ($daySchedule as $item): ?>
                                    <div class="schedule-session session-type-<?php echo $item['session_type']; ?>"
                                         onclick="viewSessionDetail(<?php echo $item['id']; ?>)">
                                        <div style="display: flex; gap: 8px; align-items: start;">
                                            <div style="flex: 1;">
                                                <div class="session-name"><?php echo sanitizeInput($item['topic_name']); ?></div>
                                                <div class="session-subject"><?php echo sanitizeInput($item['subject_name']); ?></div>
                                                <span class="session-type-badge badge-<?php echo $item['session_type']; ?>">
                                                    <?php echo $item['session_type'] === 'new_material' ? 'Новый материал' : 'Повторение'; ?>
                                                </span>
                                            </div>
                                            <span class="session-status"><?php echo ucfirst($item['status']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php
                        $currentDate = strtotime('+1 day', $currentDate);
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function changePeriod(weeks) {
            console.log('Изменить период на', weeks, 'недель');
        }

        function generateSchedule() {
            const periodWeeks = 1;
            const availableDays = [1, 2, 3, 4, 5];
            const timeSlots = {
                1: { start: '18:00', end: '22:00' },
                2: { start: '18:00', end: '22:00' },
                3: { start: '18:00', end: '22:00' },
                4: { start: '18:00', end: '22:00' },
                5: { start: '18:00', end: '22:00' },
                6: { start: '10:00', end: '18:00' },
                0: { start: '10:00', end: '18:00' }
            };

            const scheduleData = {
                period_weeks: periodWeeks,
                start_date: new Date().toISOString().split('T')[0],
                available_days: availableDays,
                time_slots: timeSlots,
                max_sessions_per_day: 4
            };

            fetch('/api/schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(scheduleData)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Расписание сгенерировано! Создано ${data.items_created} сессий`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            })
            .catch(e => alert('Ошибка при генерации расписания'));
        }

        function viewSessionDetail(id) {
            alert('Детали сессии ID: ' + id);
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
