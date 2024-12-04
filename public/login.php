<?php
// public/login.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $login = $_POST['username'];
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE login = :login');
            $stmt->execute(['login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Проверьте, существует ли пользователь и совпадает ли пароль
            if ($user && password_verify($password, $user['password'])) {
                // Успешный вход
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit();

            } else {
                // Неверный логин или пароль
                echo 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            echo 'Database error: ' . $e->getMessage();
        }
    } else {
        echo 'Username and password are required.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Логин</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Логин</h1>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
            <br>

            <button type="submit" class="login-button">Войти</button>
        </form>
    </div>
</body>

</html>