<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyFlow - Вход</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>StudyFlow</h1>
                <p>Планируй. Учись. Добивайся.</p>
            </div>

            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Запомнить меня</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Войти</button>
            </form>

            <div class="auth-footer">
                <p>Нет аккаунта? <a href="/pages/auth/register.php">Зарегистрироваться</a></p>
            </div>

            <div id="errorMessage" class="error-message" style="display: none;"></div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;

            try {
                const response = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password, remember })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '../dashboard.php';
                } else {
                    showError(data.error);
                }
            } catch (error) {
                showError('Ошибка при входе');
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    </script>
</body>
</html>
