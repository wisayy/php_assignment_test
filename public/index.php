<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Вывод ошибок если они есть
if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM balances WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM matches");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ставки</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <script>
        function updateOutcomeOptions() {
            const matches = <?php echo json_encode($matches, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const matchSelect = document.getElementById('match_id');
            const outcomeSelect = document.getElementById('outcome');
            const selectedMatch = matches.find(match => match.id == matchSelect.value);

            outcomeSelect.innerHTML = `
                <option value="win">Победа ${selectedMatch.team1} (Odds: ${selectedMatch.odds_team1})</option>
                <option value="draw">Ничья (Odds: ${selectedMatch.odds_draw})</option>
                <option value="lose">Победа ${selectedMatch.team2} (Odds: ${selectedMatch.odds_team2})</option>
            `;

            updateOutcomeTeam(); // Обновить команду и коэффициенты
        }

        function updateOutcomeTeam() {
            const matches = <?php echo json_encode($matches, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const matchSelect = document.getElementById('match_id');
            const outcomeSelect = document.getElementById('outcome');
            const outcomeTeamInput = document.getElementById('outcome_team');
            const oddsInput = document.getElementById('odds');
            const selectedMatch = matches.find(match => match.id == matchSelect.value);

            if (outcomeSelect.value === 'win') {
                outcomeTeamInput.value = selectedMatch.team1;
                oddsInput.value = selectedMatch.odds_team1;
            } else if (outcomeSelect.value === 'lose') {
                outcomeTeamInput.value = selectedMatch.team2;
                oddsInput.value = selectedMatch.odds_team2;
            } else {
                outcomeTeamInput.value = 'draw';
                oddsInput.value = selectedMatch.odds_draw;
            }
        }
    </script>
</head>
<body>
    <header>
        <div class="header">
            <h1>Добро пожаловать, <?php echo htmlspecialchars($user['name']); ?></h1>
        </div>
        <div class="balance">
            <h2>Ваш баланс</h2>
            <ul>
                <?php foreach ($balances as $balance): ?>
                    <li><?php echo htmlspecialchars($balance['currency']) . ': ' . number_format($balance['amount'], 2); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="dashboard">
            <a href="dashboard.php">Личный кабинет</a>
            <a href="logout.php">Выйти</a>
        </div>
    </header>

    <div class="matches">
        <div class="select_match">
            <h2>Доступные матчи</h2>
            <form action="place_bet.php" method="post">
                <select name="match_id" id="match_id" onchange="updateOutcomeOptions()">
                    <?php foreach ($matches as $match): ?>
                        <option value="<?php echo htmlspecialchars($match['id']); ?>">
                            <?php echo htmlspecialchars($match['team1']) . ' vs ' . htmlspecialchars($match['team2']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="outcome" id="outcome" onchange="updateOutcomeTeam()">
                    <!-- Выбор будет появлятся динамически -->
                </select>
                <input type="hidden" name="outcome_team" id="outcome_team" required>
                <input type="hidden" name="odds" id="odds" required>
                <input type="number" name="amount" min="1" max="500" required>
                <select name="currency">
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="RUB">RUB</option>
                </select>
                <button type="submit">Поставить ставку</button>
            </form>
        </div>
    </div>

    <script>
        // Обновить список исходов при загрузке страницы
        document.addEventListener('DOMContentLoaded', updateOutcomeOptions);
    </script>
</body>
</html>