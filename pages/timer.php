<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Topic.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$topicId = isset($_GET['topic_id']) ? $_GET['topic_id'] : null;
$topicModel = new Topic($db);

$topic = null;
if ($topicId) {
    $topic = $topicModel->getById($topicId, $userId);
}

$settings = $db->getOne('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);

$allTopics = [];
if (!$topic) {
    require_once __DIR__ . '/../classes/Subject.php';

    $subjectModel = new Subject($db);
    $subjects = $subjectModel->getAll($userId, 0);

    foreach ($subjects as $subj) {
        $subjTopics = $topicModel->getAll($subj['id'], $userId, null);
        foreach ($subjTopics as $t) {
            if ($t['status'] !== 'mastered') {
                $allTopics[] = $t;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Помодоро Таймер - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/timer.css">
</head>
<body>
    <div class="timer-container">
        <div class="timer-header">
            <a href="/pages/dashboard.php" class="btn-back">← Назад</a>
            <h1>StudyFlow Таймер</h1>
            <div></div>
        </div>

        <div class="timer-content">
            <div class="timer-display-wrapper">
                <div id="phaseLabel" class="phase-label">Работа</div>
                <div class="timer-display">
                    <svg viewBox="0 0 100 100" class="progress-circle">
                        <circle cx="50" cy="50" r="45" class="progress-bg"/>
                        <circle cx="50" cy="50" r="45" id="progressCircle" class="progress-fill"/>
                    </svg>
                    <div id="timerDisplay" class="time-display">25:00</div>
                </div>
                <p id="topicName" class="topic-name">
                    <?php echo $topic ? sanitizeInput($topic['name']) : 'Выберите тему'; ?>
                </p>
            </div>

            <div class="timer-controls">
                <button id="startBtn" class="btn btn-primary" onclick="timerStart()">Начать</button>
                <button id="pauseBtn" class="btn btn-secondary" onclick="timerPause()" style="display: none;">Пауза</button>
                <button id="resumeBtn" class="btn btn-secondary" onclick="timerResume()" style="display: none;">Продолжить</button>
                <button id="stopBtn" class="btn btn-danger" onclick="timerStop()" style="display: none;">Завершить</button>
            </div>

            <div class="timer-session-info">
                <span>
    Сессия
    <span id="sessionCount">
        <?php echo (int)(isset($topic['completed_sessions']) ? $topic['completed_sessions'] : 0); ?>
    </span>
    /
    <?php echo (int)(isset($topic['planned_sessions']) ? $topic['planned_sessions'] : 4); ?>
</span>

                <div id="progressBar" class="progress-bar-linear">
                    <div id="progressFill" class="progress-fill-linear"></div>
                </div>
            </div>

            <div class="timer-notes">
                <label for="notes">Заметки:</label>
                <textarea id="notes" placeholder="Напишите, что вы изучали..." rows="4"></textarea>
            </div>

            <div class="timer-topics">
                <h3>Выберите тему</h3>
                <select id="topicSelect" onchange="selectTopic(this.value)">
                    <option value="">-- Выберите тему для изучения --</option>
                </select>
            </div>
        </div>
    </div>

    <audio id="bellSound" src="/assets/sounds/bell.mp3"></audio>

    <script src="/assets/js/main.js"></script>
    <script>
        let timer = null;

        document.addEventListener('DOMContentLoaded', () => {
            const topicId = new URLSearchParams(window.location.search).get('topic_id');

            timer = new PomodoroTimer({
                workDuration: <?php echo $settings['work_duration'] ?? 25; ?>,
                shortBreakDuration: <?php echo $settings['short_break_duration'] ?? 5; ?>,
                longBreakDuration: <?php echo $settings['long_break_duration'] ?? 15; ?>,
                sessionsBeforeLongBreak: <?php echo $settings['sessions_before_long_break'] ?? 4; ?>,
                autoStartBreaks: <?php echo ($settings['auto_start_breaks'] ?? 0) ? 'true' : 'false'; ?>,
                soundEnabled: true,
                topicId: topicId || null
            });

            loadTopics();

            if (topicId) {
                document.getElementById('topicSelect').value = topicId;
            }
        });

        function timerStart() {
            if (!timer.topicId) {
                alert('Пожалуйста, выберите тему для изучения');
                return;
            }

            timer.start();
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('pauseBtn').style.display = 'inline-block';
            document.getElementById('stopBtn').style.display = 'inline-block';
            document.getElementById('resumeBtn').style.display = 'none';
        }

        function timerPause() {
            timer.pause();
            document.getElementById('pauseBtn').style.display = 'none';
            document.getElementById('resumeBtn').style.display = 'inline-block';
        }

        function timerResume() {
            timer.start();
            document.getElementById('resumeBtn').style.display = 'none';
            document.getElementById('pauseBtn').style.display = 'inline-block';
        }

        function timerStop() {
            if (confirm('Вы уверены, что хотите остановить таймер?')) {
                timer.stop();

                document.getElementById('startBtn').style.display = 'inline-block';
                document.getElementById('pauseBtn').style.display = 'none';
                document.getElementById('resumeBtn').style.display = 'none';
                document.getElementById('stopBtn').style.display = 'none';
            }
        }

        function selectTopic(topicId) {
            if (topicId) {
                timer.topicId = topicId;
                timer.saveToLocalStorage();

                fetch(`/api/topics.php?id=${topicId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.topic) {
                            document.getElementById('topicName').textContent = data.topic.name;
                        }
                    })
                    .catch(e => console.error('Error loading topic name:', e));
            }
        }

        async function loadTopics() {
            try {
                const response = await fetch('/api/topics.php?status=not_started,in_progress');
                const data = await response.json();

                console.log('Topics loaded:', data);

                if (data.success && data.topics && Array.isArray(data.topics)) {
                    const select = document.getElementById('topicSelect');

                    while (select.options.length > 1) {
                        select.remove(1);
                    }

                    if (data.topics.length === 0) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Нет доступных тем. Создайте тему в разделе "Предметы"';
                        option.disabled = true;
                        select.appendChild(option);
                    } else {
                        data.topics.forEach(topic => {
                            const option = document.createElement('option');
                            option.value = topic.id;
                            option.textContent = `${topic.name} (${topic.subject_name || 'Без предмета'})`;
                            select.appendChild(option);
                        });
                    }
                } else {
                    console.error('Invalid response format:', data);
                }
            } catch (error) {
                console.error('Error loading topics:', error);
            }
        }
    </script>
</body>
</html>
