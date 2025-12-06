<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Statistics.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$statsModel = new Statistics($db);

$stats = $statsModel->getOverviewStats($userId);
$activity = $statsModel->getActivityHeatmap($userId, 'month');
$distribution = $statsModel->getSubjectTimeDistribution($userId);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--color-surface);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-top: 4px solid var(--primary);
        }

        .stat-card h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 8px 0;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            margin: 0;
        }

        .chart-section {
            background: var(--color-surface);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .chart-section h2 {
            margin-top: 0;
            font-size: 20px;
            margin-bottom: 24px;
        }

        .heatmap {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .heatmap-day {
            width: 100%;
            aspect-ratio: 1;
            background: var(--color-surface);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid var(--color-border);
        }

        .heatmap-day:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .heatmap-day.active {
            background: var(--color-surface);
            color: white;
            border-color: var(--color-border);
        }

        .heatmap-day.has-data-low {
            background: rgba(59, 130, 246, 0.2);
            color: var(--color-surface);
        }

        .heatmap-day.has-data-medium {
            background: rgba(59, 130, 246, 0.5);
            color: white;
        }

        .heatmap-day.has-data-high {
            background: var(--primary);
            color: white;
        }

        .distribution-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .distribution-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .distribution-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .distribution-name {
            flex: 1;
            font-size: 14px;
        }

        .distribution-time {
            font-weight: 600;
            color: var(--primary);
        }

        .achievements {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .achievement {
            background: var(--color-surface);
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid var(--border);
            transition: all 0.3s;
        }

        .achievement.unlocked {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            border-color: var(--primary);
        }

        .achievement-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .achievement-name {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .achievement-desc {
            font-size: 11px;
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .heatmap {
                grid-template-columns: repeat(7, 1fr);
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
                    <a href="/pages/statistics.php" style="font-weight: bold;">Статистика</a>
                    <a href="/pages/settings.php">Настройки</a>
                    <a href="#" onclick="logout()">Выход</a>
                </nav>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <h2 style="margin-bottom: 30px;">Статистика</h2>

                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Всего сессий</h3>
                        <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>
                        <p class="stat-label">за все время</p>
                    </div>

                    <div class="stat-card" style="border-top-color: #10B981;">
                        <h3>Время обучения</h3>
                        <div class="stat-value"><?php echo formatMinutes($stats['total_study_time']); ?></div>
                        <p class="stat-label">часов и минут</p>
                    </div>

                    <div class="stat-card" style="border-top-color: #F59E0B;">
                        <h3>Текущая серия</h3>
                        <div class="stat-value"><?php echo $stats['current_streak']; ?></div>
                        <p class="stat-label">дней подряд</p>
                    </div>

                    <div class="stat-card" style="border-top-color: #8B5CF6;">
                        <h3>Лучшая серия</h3>
                        <div class="stat-value"><?php echo $stats['longest_streak']; ?></div>
                        <p class="stat-label">дней</p>
                    </div>

                    <div class="stat-card" style="border-top-color: #EC4899;">
                        <h3>Освоено тем</h3>
                        <div class="stat-value"><?php echo $stats['topics_mastered']; ?></div>
                        <p class="stat-label">полностью выучено</p>
                    </div>

                    <div class="stat-card" style="border-top-color: #06B6D4;">
                        <h3>Выполнение</h3>
                        <div class="stat-value"><?php echo $stats['completion_rate']; ?>%</div>
                        <p class="stat-label">завершенных сессий</p>
                    </div>
                </div>

                <div class="chart-section">
                    <h2>Активность (последние 30 дней)</h2>
                    <div class="heatmap">
                        <?php 
                        foreach ($activity as $date => $data) {
                            $sessions = $data['sessions'] ?? 0;
                            $class = '';
                            
                            if ($sessions > 0) {
                                if ($sessions <= 2) $class = 'has-data-low';
                                elseif ($sessions <= 5) $class = 'has-data-medium';
                                else $class = 'has-data-high';
                            }
                        ?>
                            <div class="heatmap-day <?php echo $class; ?>" title="<?php echo date('d.m.Y', strtotime($date)); ?>: <?php echo $sessions; ?> сессий">
                                <?php echo date('d', strtotime($date)); ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="chart-section">
                    <h2>Распределение времени по предметам</h2>
                    <div class="distribution-list">
                        <?php 
                        $totalTime = array_sum(array_map(fn($d) => $d['total_minutes'] ?? 0, $distribution));
                        
                        foreach ($distribution as $subject): 
                            $percent = $totalTime > 0 ? round($subject['total_minutes'] / $totalTime * 100) : 0;
                        ?>
                            <div class="distribution-item">
                                <div class="distribution-color" style="background-color: <?php echo $subject['color']; ?>"></div>
                                <div class="distribution-name"><?php echo sanitizeInput($subject['name']); ?></div>
                                <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 6px; background: var(--bg); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo $percent; ?>%; height: 100%; background: <?php echo $subject['color']; ?>;"></div>
                                    </div>
                                </div>
                                <div class="distribution-time"><?php echo $percent; ?>%</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="chart-section">
                    <h2>Достижения</h2>
                    <div class="achievements">
                        <div class="achievement <?php echo $stats['current_streak'] >= 1 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">🔥</div>
                            <div class="achievement-name">Первый день</div>
                            <div class="achievement-desc">Изучайте 1 день подряд</div>
                        </div>
                        <div class="achievement <?php echo $stats['current_streak'] >= 7 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">🎯</div>
                            <div class="achievement-name">Неделя</div>
                            <div class="achievement-desc">7 дней подряд</div>
                        </div>
                        <div class="achievement <?php echo $stats['current_streak'] >= 30 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">⭐</div>
                            <div class="achievement-name">Месяц</div>
                            <div class="achievement-desc">30 дней подряд</div>
                        </div>
                        <div class="achievement <?php echo $stats['topics_mastered'] >= 1 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">📚</div>
                            <div class="achievement-name">Первая тема</div>
                            <div class="achievement-desc">Освойте 1 тему</div>
                        </div>
                        <div class="achievement <?php echo $stats['total_sessions'] >= 100 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">💯</div>
                            <div class="achievement-name">Столетие</div>
                            <div class="achievement-desc">100 сессий</div>
                        </div>
                        <div class="achievement <?php echo $stats['completion_rate'] >= 90 ? 'unlocked' : ''; ?>">
                            <div class="achievement-icon">✅</div>
                            <div class="achievement-name">Ответственный</div>
                            <div class="achievement-desc">90% выполнение</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        async function logout() {
            const response = await fetch('/api/auth.php?action=logout', { method: 'POST' });
            if (response.ok) {
                window.location.href = '/pages/auth/login.php';
            }
        }
    </script>
</body>
</html>
