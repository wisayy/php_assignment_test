<?php

require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    // Выборка всех ставок с информацией о матчах
    $stmt = $pdo->query("SELECT b.*, m.team1, m.team2 FROM bets b JOIN matches m ON b.match_id = m.id");
    $bets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}
// Выборка всех пользователей
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_balance'])) {
        // Обработка формы обновления баланса
        $userId = $_POST['user_id'];
        $currency = $_POST['currency'];
        $amount = $_POST['amount'];

        // Выборка баланса и валюты пользователя
        $stmt = $pdo->prepare("SELECT currency, amount FROM balances WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($balances) {
            // Обновление баланса
            $stmt = $pdo->prepare("UPDATE balances SET amount = ? WHERE user_id = ? AND currency = ?");
            $stmt->execute([$amount, $userId, $currency]);
        } else {
            // Добавление нового баланса если он не существует ( по умолчанию есть )
            $stmt = $pdo->prepare("INSERT INTO balances (user_id, currency, amount) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $currency, $amount]);
        }

        $message = "Баланс обновлен.";
    } 
    elseif (isset($_POST['add_match'])) {
        // Обработка формы добавления матча
        $team1 = $_POST['team1'];
        $team2 = $_POST['team2'];
        $odds_team1 = $_POST['odds_team1'];
        $odds_draw = $_POST['odds_draw'];
        $odds_team2 = $_POST['odds_team2'];

        $stmt = $pdo->prepare("INSERT INTO matches (team1, team2, odds_team1, odds_draw, odds_team2) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$team1, $team2, $odds_team1, $odds_draw, $odds_team2]);

        $message = "Матч добавлен.";
    }


    elseif (isset($_POST['update_bet'])) {
        // Обработка формы указания результата ставки
        $betId = $_POST['bet_id'];
        $result = $_POST['result'];
    
        // Проверка допустимых значений для результата
        $valid_results = ['won', 'lost', 'draw'];
        if (!in_array($result, $valid_results)) {
            echo "Invalid result: " . htmlspecialchars($result);
            exit();
        }
    
        $stmt = $pdo->prepare("SELECT * FROM bets WHERE id = ?");
        $stmt->execute([$betId]);
        $bet = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($bet) {
            if ($bet['result'] != "pending") {
                echo "Результат ставки уже указан.";
                exit();
            }
    
            $userId = $bet['user_id'];
            $amount = $bet['amount'];
            $outcome = $bet['outcome'];
            $odds = $bet['odds'];
            $matchId = $bet['match_id'];
            $currency = $bet['currency'];
    
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result == 'won') {
                $profit = $amount * $odds;
    
                $stmt = $pdo->prepare("UPDATE balances SET amount = amount + ? WHERE user_id = ? AND currency = ?");
                $stmt->execute([$profit, $userId, $currency]);
    
                $stmt = $pdo->prepare("UPDATE bets SET result = 'win' WHERE id = ?");
                $stmt->execute([$betId]);
            } elseif ($result == 'lost') {
                $stmt = $pdo->prepare("UPDATE bets SET result = 'lose' WHERE id = ?");
                $stmt->execute([$betId]);
            }
    
            $message = "Результат ставки обновлен.";
        } else {
            $message = "Ставка не найдена.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Административная панель</title>
    <link rel="stylesheet" type="text/css" href="css/admin.css">
</head>
<body>
    <h1>Административная панель</h1>
    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <h2>Изменение баланса пользователя</h2>
    <form method="post">
        <input type="hidden" name="update_balance" value="1">
        <label for="user_id">ID пользователя:</label>
        <select id="user_id" name="user_id" required>
        <?php foreach ($users as $user): ?>
            <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['id']) ?> - <?= htmlspecialchars($user['name']) ?></option>
        <?php endforeach; ?>
        </select><br>
        
        <label for="currency">Валюта:</label>
        <select id="currency" name="currency" required>
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
            <option value="RUB">RUB</option>
        </select><br>
        <label for="amount">Сумма:</label>
        <input type="number" step="0.01" id="amount" name="amount" required><br>
        <button type="submit">Обновить баланс</button>
    </form>

    <h2>Добавление матча</h2>
    <form method="post">
        <input type="hidden" name="add_match" value="1">
        <label for="team1">Команда 1:</label>
        <input type="text" id="team1" name="team1" required><br>
        <label for="team2">Команда 2:</label>
        <input type="text" id="team2" name="team2" required><br>
        <label for="odds_team1">Коэффициент на победу команды 1:</label>
        <input type="number" step="0.01" id="odds_team1" name="odds_team1" required><br>
        <label for="odds_draw">Коэффициент на ничью:</label>
        <input type="number" step="0.01" id="odds_draw" name="odds_draw" required><br>
        <label for="odds_team2">Коэффициент на победу команды 2:</label>
        <input type="number" step="0.01" id="odds_team2" name="odds_team2" required><br>
        <button type="submit">Добавить матч</button>
    </form>

    <h2>Указание результата ставки</h2>
    <form method="post">
        <input type="hidden" name="update_bet" value="1">
        <label for="bet_id">ID ставки:</label>
        <select id="bet_id" name="bet_id" required>
            <?php foreach ($bets as $bet): ?>
                <option value="<?= htmlspecialchars($bet['id']) ?>">
                    <?= htmlspecialchars($bet['id']) ?> - <?= htmlspecialchars($bet['team1']) ?> vs <?= htmlspecialchars($bet['team2']) ?> - Предикт: <?= htmlspecialchars($bet['outcome']) ?> - Команда: <?= htmlspecialchars($bet['outcome_team']) ?> - Коэффициент: <?= htmlspecialchars($bet['odds']) ?> - Сумма: <?= htmlspecialchars($bet['amount']) ?> - Валюта: <?= htmlspecialchars($bet['currency']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        <label for="result">Результат:</label>
        <select id="result" name="result" required>
            <option value="won">Выигрыш</option>
            <option value="lost">Проигрыш</option>
        </select><br>
        <button type="submit">Обновить результат</button>
    </form>

    <h2>Вернуться на <a href="index.php">главную страницу</a></h2>
</body>
</html>