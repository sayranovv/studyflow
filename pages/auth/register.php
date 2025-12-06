<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - StudyFlow</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --primary: #3B82F6;
            --primary-dark: #2563EB;
            --primary-light: #60A5FA;
            --success: #10B981;
            --warning: #F59E0B;
            --error: #EF4444;
            --bg: #F9FAFB;
            --surface: #FFFFFF;
            --border: #E5E7EB;
            --text: #111827;
            --text-light: #6B7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .auth-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .auth-box {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .auth-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text);
        }

        .form-group input {
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #FEE;
            color: var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid var(--error);
            display: none;
        }

        .success-message {
            background-color: #EFF6FF;
            color: var(--primary);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid var(--primary);
            display: none;
        }

        @media (max-width: 768px) {
            .auth-box {
                padding: 30px 20px;
            }

            .auth-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>StudyFlow</h1>
                <p>Создайте аккаунт и начните учиться</p>
            </div>

            <div id="successMessage" class="success-message"></div>
            <div id="errorMessage" class="error-message"></div>

            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" required placeholder="Ваше имя" minlength="3" maxlength="30">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Подтвердите пароль</label>
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••" minlength="6">
                </div>

                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            </form>

            <div class="auth-footer">
                <p>Уже есть аккаунт? <a href="/pages/auth/login.php">Войти</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            if (password !== passwordConfirm) {
                showError('Пароли не совпадают');
                return;
            }

            try {
                const response = await fetch('/api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, email, password, password_confirm: passwordConfirm })
                });

                const data = await response.json();

                console.log(data);

                if (data.success) {
                    showSuccess('Регистрация успешна! Перенаправление...');
                    setTimeout(() => {
                        window.location.href = '/pages/dashboard.php';
                    }, 2000);
                } else {
                    showError(data.error || 'Ошибка регистрации');
                }
            } catch (error) {
                showError('Ошибка при регистрации');
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
        }
    </script>
</body>
</html>
