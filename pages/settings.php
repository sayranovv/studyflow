<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

requireAuth();

$db = new Database([
    'host' => DB_HOST,
    'user' => DB_USER,
    'password' => DB_PASSWORD,
    'dbname' => DB_NAME
]);

$userId = getCurrentUserId();
$user = $db->getOne('SELECT * FROM users WHERE id = ?', [$userId]);
$settings = $db->getOne('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .settings-grid {
            display: grid;
            gap: 24px;
            max-width: 800px;
        }

        .settings-section {
            background: var(--surface);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .settings-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
        }

        .setting-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .setting-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .setting-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .setting-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .setting-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .setting-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .checkbox-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .slider-input {
            width: 100%;
            max-width: 200px;
        }

        .slider-value {
            display: inline-block;
            margin-left: 8px;
            font-weight: 600;
            color: var(--primary);
        }

        .btn-save {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 16px;
        }

        .btn-save:hover {
            background: #2563EB;
        }

        .success-message {
            background: #DCFCE7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        @media (max-width: 768px) {
            .setting-row {
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
                    <a href="/pages/schedule.php">Расписание</a>
                    <a href="/pages/statistics.php">Статистика</a>
                    <a href="/pages/settings.php" style="font-weight: bold;">Настройки</a>
                    <a href="#" onclick="logout()">Выход</a>
                </nav>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <h2 style="margin-bottom: 30px;">Настройки</h2>

                <div id="successMessage" class="success-message">✓ Настройки сохранены!</div>

                <div class="settings-grid">
                    <!-- Профиль -->
                    <div class="settings-section">
                        <h3>👤 Профиль</h3>
                        
                        <div class="setting-item">
                            <label class="setting-label">Имя пользователя</label>
                            <input type="text" class="setting-input" value="<?php echo sanitizeInput($user['username']); ?>" readonly disabled>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Email</label>
                            <input type="email" class="setting-input" value="<?php echo sanitizeInput($user['email']); ?>" readonly disabled>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Дата регистрации</label>
                            <input type="text" class="setting-input" value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" readonly disabled>
                        </div>
                    </div>

                    <!-- Помодоро -->
                    <div class="settings-section">
                        <h3>🍅 Техника Помодоро</h3>

                        <div class="setting-item">
                            <label class="setting-label">Длительность рабочей сессии (минуты)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="range" class="slider-input" min="5" max="60" step="5" 
                                       value="<?php echo $settings['work_duration']; ?>" 
                                       id="workDuration"
                                       onchange="updateValue('workDuration')">
                                <span class="slider-value" id="workDurationValue"><?php echo $settings['work_duration']; ?></span>
                            </div>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Короткий перерыв (минуты)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="range" class="slider-input" min="1" max="15" step="1" 
                                       value="<?php echo $settings['short_break_duration']; ?>" 
                                       id="shortBreak"
                                       onchange="updateValue('shortBreak')">
                                <span class="slider-value" id="shortBreakValue"><?php echo $settings['short_break_duration']; ?></span>
                            </div>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Длинный перерыв (минуты)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="range" class="slider-input" min="10" max="30" step="5" 
                                       value="<?php echo $settings['long_break_duration']; ?>" 
                                       id="longBreak"
                                       onchange="updateValue('longBreak')">
                                <span class="slider-value" id="longBreakValue"><?php echo $settings['long_break_duration']; ?></span>
                            </div>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Сессий до длинного перерыва</label>
                            <input type="number" class="setting-input" min="2" max="6" 
                                   value="<?php echo $settings['sessions_before_long_break']; ?>" 
                                   id="sessionsBeforeLongBreak">
                        </div>

                        <div class="setting-item">
                            <div class="toggle-switch">
                                <input type="checkbox" class="checkbox-input" 
                                       id="autoStartBreaks" 
                                       <?php echo $settings['auto_start_breaks'] ? 'checked' : ''; ?>>
                                <label for="autoStartBreaks">Автоматически запускать перерывы</label>
                            </div>
                        </div>
                    </div>

                    <!-- Уведомления -->
                    <div class="settings-section">
                        <h3>🔔 Уведомления</h3>

                        <div class="setting-item">
                            <div class="toggle-switch">
                                <input type="checkbox" class="checkbox-input" 
                                       id="soundEnabled" 
                                       <?php echo $settings['sound_enabled'] ? 'checked' : ''; ?>>
                                <label for="soundEnabled">Звуковые уведомления</label>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="toggle-switch">
                                <input type="checkbox" class="checkbox-input" 
                                       id="notificationsEnabled" 
                                       <?php echo $settings['notifications_enabled'] ? 'checked' : ''; ?>>
                                <label for="notificationsEnabled">Браузерные уведомления</label>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="toggle-switch">
                                <input type="checkbox" class="checkbox-input" 
                                       id="emailReminders" 
                                       <?php echo $settings['email_reminders'] ? 'checked' : ''; ?>>
                                <label for="emailReminders">Email-напоминания</label>
                            </div>
                        </div>
                    </div>

                    <!-- Расписание -->
                    <div class="settings-section">
                        <h3>📅 Расписание</h3>

                        <div class="setting-item">
                            <label class="setting-label">Максимум сессий в день</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="range" class="slider-input" min="1" max="12" step="1" 
                                       value="<?php echo $settings['max_sessions_per_day']; ?>" 
                                       id="maxSessionsPerDay"
                                       onchange="updateValue('maxSessionsPerDay')">
                                <span class="slider-value" id="maxSessionsPerDayValue"><?php echo $settings['max_sessions_per_day']; ?></span>
                            </div>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">Период планирования по умолчанию (недели)</label>
                            <select class="setting-input" id="planningPeriod">
                                <option value="1" <?php echo $settings['planning_period_weeks'] == 1 ? 'selected' : ''; ?>>1 неделя</option>
                                <option value="2" <?php echo $settings['planning_period_weeks'] == 2 ? 'selected' : ''; ?>>2 недели</option>
                                <option value="4" <?php echo $settings['planning_period_weeks'] == 4 ? 'selected' : ''; ?>>1 месяц</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button class="btn-save" onclick="saveSettings()">Сохранить настройки</button>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function updateValue(fieldId) {
            const value = document.getElementById(fieldId).value;
            document.getElementById(fieldId + 'Value').textContent = value;
        }

        async function saveSettings() {
            const settings = {
                work_duration: parseInt(document.getElementById('workDuration').value),
                short_break_duration: parseInt(document.getElementById('shortBreak').value),
                long_break_duration: parseInt(document.getElementById('longBreak').value),
                sessions_before_long_break: parseInt(document.getElementById('sessionsBeforeLongBreak').value),
                auto_start_breaks: document.getElementById('autoStartBreaks').checked,
                sound_enabled: document.getElementById('soundEnabled').checked,
                notifications_enabled: document.getElementById('notificationsEnabled').checked,
                email_reminders: document.getElementById('emailReminders').checked,
                max_sessions_per_day: parseInt(document.getElementById('maxSessionsPerDay').value),
                planning_period_weeks: parseInt(document.getElementById('planningPeriod').value)
            };

            const successMsg = document.getElementById('successMessage');

            try {
                const response = await fetch('/api/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                });

                const result = await response.json();

                if (result.success) {
                    successMsg.textContent = '✓ Настройки сохранены!';
                    successMsg.style.backgroundColor = '#DCFCE7';
                    successMsg.style.color = '#166534';
                    successMsg.style.display = 'block';

                } else {
                    throw new Error(result.error || 'Ошибка сохранения');
                }
            } catch (error) {
                successMsg.textContent = '❌ Ошибка: ' + error.message;
                successMsg.style.backgroundColor = '#FEE2E2';
                successMsg.style.color = '#991B1B';
                successMsg.style.display = 'block';
            }

            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 3000);
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
