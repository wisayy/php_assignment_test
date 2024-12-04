<?php
session_start();

if (!isset($_SESSION['bet_confirmation'])) {
    header('Location: index.php');
    exit();
}

$bet_confirmation = $_SESSION['bet_confirmation'];
unset($_SESSION['bet_confirmation']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение ставки</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
    <div class="bet-confirmation-container">
        <h1>Подтверждение ставки</h1>
        <p class="success-message">Ваша ставка была успешно поставлена!</p>
        <div class="bet-details">
            <p>Матч: <span class="bet-info"><?php echo htmlspecialchars($bet_confirmation['event']); ?></span></p>
            <p>Выбранная команда: <span class="bet-info"><?php echo htmlspecialchars($bet_confirmation['outcome_team']); ?></span></p>
            <p>Исход: <span class="bet-info"><?php echo htmlspecialchars($bet_confirmation['outcome']); ?></span></p>
            <p>Коэффициент: <span class="bet-info"><?php echo htmlspecialchars($bet_confirmation['odds']); ?></span></p>
            <p>Ставка: <span class="bet-info"><?php echo htmlspecialchars($bet_confirmation['amount']);?> <?php echo htmlspecialchars($bet_confirmation['currency']); ?></span></p>
        </div>
        <a class="back-home-link" href="index.php">Вернуться домой</a>
    </div>
</body>
</html>