<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$match_id = $_POST['match_id'];
$outcome = $_POST['outcome'];
$currency = $_POST['currency'];
$amount = $_POST['amount'];
$outcome_team = $_POST['outcome_team'];
$odds = $_POST['odds'];




// Проверка на валидность исхода
$valid_outcomes = ['win', 'draw', 'lose'];
if (!in_array($outcome, $valid_outcomes)) {
    echo "Invalid outcome: " . htmlspecialchars($outcome);
    exit();
}

try {

    // Проверка поставил ли юзер ставку на этот матч
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bets WHERE user_id = :user_id AND match_id = :match_id");
    $stmt->execute(['user_id' => $user_id, 'match_id' => $match_id]);
    $bet_count = $stmt->fetchColumn();

    if ($bet_count > 0) {
        $_SESSION['error_message'] = "You have already placed a bet on this match.";
        header('Location: index.php');
        exit();
    }
    // Получение баланса пользователя
    $stmt = $pdo->prepare("SELECT currency, amount FROM balances WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Создание ассоциативного массива балансов
    $userBalances = [];
    foreach ($balances as $balance) {
        $userBalances[$balance['currency']] = $balance['amount'];
    }


    // Проверка на валидность суммы ставки
    if ($amount < 1 || $amount > 500 || $amount > $balance) {
        echo "Неверная сумма ставки.";
        exit();
    }

    // Проверка баланса пользователя чтобы он не поставил больше чем у него есть
    if ($userBalances[$currency] < $amount) {
        echo "Ваш баланс недостаточен для этой ставки.";
        exit();
    }

    // Получение информации о матче
    $stmt = $pdo->prepare("SELECT team1, team2 FROM matches WHERE id = :match_id");
    $stmt->execute(['match_id' => $match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    $event = $match['team1'] . ' vs ' . $match['team2'];

    // Начало транзакции
    $pdo->beginTransaction();

    // Добавление ставки в базу данных
    $stmt = $pdo->prepare("INSERT INTO bets (user_id, match_id, event, outcome_team, outcome, odds, amount, currency) VALUES (:user_id, :match_id, :event, :outcome_team, :outcome, :odds, :amount, :currency)");
    $stmt->execute([
        'user_id' => $user_id,
        'match_id' => $match_id,
        'event' => $event,
        'outcome_team' => $outcome_team,
        'outcome' => $outcome,
        'odds' => $odds,
        'amount' => $amount,
        'currency' => $currency
    ]);

    // Обновление баланса пользователя
    $stmt = $pdo->prepare("UPDATE balances SET amount = amount - :amount WHERE user_id = :user_id AND currency = :currency");
    $stmt->execute(['amount' => $amount, 'user_id' => $user_id, 'currency' => $currency]);
    $pdo->commit();

    // Сохранение информации о ставке в сессии для отправки на страницу подтверждения ставки
    $_SESSION['bet_confirmation'] = [
        'event' => $event,
        'outcome' => $outcome,
        'amount' => $amount,
        'odds' => $odds,
        'outcome_team' => $outcome_team,
        'currency' => $currency
    ];

    header('Location: bet_confirmation.php');
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>