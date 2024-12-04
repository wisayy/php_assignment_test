<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Обработка добавления контакта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_type']) && isset($_POST['contact_value'])) {
    $contactType = $_POST['contact_type'];
    $contactValue = $_POST['contact_value'];

    // Проверка валидности email
    if ($contactType === 'email' && !filter_var($contactValue, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt = $pdo->prepare('INSERT INTO contacts (user_id, type, contact) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $contactType, $contactValue]);


        header('Location: dashboard.php');
        exit();
    }
}


// Выборка информации о пользователе
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Выборка балансов пользователя
$stmt = $pdo->prepare('SELECT * FROM balances WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$balances = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Выборка контактов пользователя
$stmt = $pdo->prepare('SELECT type, contact FROM contacts WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$contacts = $stmt->fetchAll();

// Подсчет количества email и phone контактов
$emailCount = 0;
$phoneCount = 0;
foreach ($contacts as $contact) {
    if ($contact['type'] === 'email') {
        $emailCount++;
    } elseif ($contact['type'] === 'phone') {
        $phoneCount++;
    }
}

// 
$stmt = $pdo->prepare('
    SELECT b.*, m.team1, m.team2 
    FROM bets b 
    JOIN matches m ON b.match_id = m.id 
    WHERE b.user_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$bets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Личный кабинет</title>
    <link rel="stylesheet" type="text/css" href="css/dashboard.css">
</head>
<body>
    <div class="header">
        <h1>Личный кабинет</h1>
        <p>Добро пожаловать, <?php echo htmlspecialchars($user['name']); ?>!</p>
    </div>
    
    <div class="user-info">
        <h2>Информация пользователя</h2>
        <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Пол:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
        <p><strong>Дата рождения:</strong> <?php echo htmlspecialchars($user['birth_date']); ?></p>
        <p><strong>Адрес:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
        <p><strong>Статус:</strong> <?php echo htmlspecialchars($user['status']); ?></p>
    </div>

    <div class="balances">
        <h2>Ваш баланс</h2>
        <ul>
            <?php foreach ($balances as $balance): ?>
                <li><?php echo htmlspecialchars($balance['currency']) . ': ' . number_format($balance['amount'], 1); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="contacts">
        <h2>Контакты</h2>
        <ul>
            <?php foreach ($contacts as $contact): ?>
                <li><strong><?php echo ucfirst($contact['type']); ?>:</strong> <?php echo htmlspecialchars($contact['contact']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="add-contact">
        <h3>Добавить новый контакт</h3>
        <form method="post" action="dashboard.php">
            <label for="contact_type">Тип контакта:</label>
            <select name="contact_type" id="contact_type" required>
                <option value="phone" <?php echo ($phoneCount >= 2) ? 'disabled' : ''; ?>>Phone</option>
                <option value="email" <?php echo ($emailCount >= 2) ? 'disabled' : ''; ?>>Email</option>
            </select>
            <br>
            <label for="contact_value">Введите контакт:</label>
            <input type="text" name="contact_value" id="contact_value" required>
            <br>
            <button type="submit">Добавить контакт</button>
        </form>
    </div>
    
    <div class="bets">
        <h2>Мои ставки</h2>
        <table>
            <thead>
                <tr>
                    <th>Матч</th>
                    <th>Выбранная команда</th>
                    <th>Исход матча</th>
                    <th>Коэффициент</th>
                    <th>Ставка</th>
                    <th>Результат</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bets as $bet): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bet['team1']) . ' vs ' . htmlspecialchars($bet['team2']); ?></td>
                        <td><?php echo htmlspecialchars($bet['outcome_team']); ?></td>
                        <td><?php echo htmlspecialchars($bet['outcome']); ?></td>
                        <td><?php echo htmlspecialchars($bet['odds']); ?></td>
                        <td><?php echo htmlspecialchars($bet['amount']); ?></td>
                        <td><?php echo htmlspecialchars($bet['result']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p><a href="index.php">Вернуться домой</a></p>
        <p><a href="admin.php">Админ панель</a></p>
        <p><a href="logout.php">Выйти</a></p>
    </div>
</body>
</html>