<?php
require 'db.php';
session_start();

$stmt = $pdo->query("
    SELECT 
        username,
        SUM(score) AS total_score,
        SUM(time_taken) AS total_time,
        COUNT(*) AS total_games,
        SUM(CASE WHEN won=1 THEN 1 ELSE 0 END) AS total_wins,
        SUM(attempts) AS total_attempts
    FROM leaderboard
    GROUP BY username
    ORDER BY total_score DESC, total_time ASC
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>🏆 Класация – Топ 20</title>
<link rel="stylesheet" href="leaderboard.css">
</head>
<body>

<header>🏆 Класация в Match The Hidden Symbols</header>

<main>
  <h2>Топ 10 най-добри играчи:</h2>

  <table>
    <tr>
      <th>#</th>
      <th>Потребител</th>
      <th>Общо точки</th>
      <th>Общо време (сек)</th>
      <th>Игри</th>
      <th>Победи</th>
      <th>Опити</th>
    </tr>

    <?php foreach($rows as $i => $r): 
      $class = '';
      if ($i === 0) $class = 'first-place';
      elseif ($i === 1) $class = 'second-place';
      elseif ($i === 2) $class = 'third-place';
    ?>
      <tr class="<?= $class ?>">
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($r['username']) ?></td>
        <td><?= $r['total_score'] ?></td>
        <td><?= $r['total_time'] ?></td>
        <td><?= $r['total_games'] ?></td>
        <td><?= $r['total_wins'] ?></td>
        <td><?= $r['total_attempts'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="nav-links">
    <a href="info.php">📄 Информация</a>
    <a href="game.php">🎮 Игра</a>
    <a href="statistics.php">📊 Статистика</a>
    <a href="index.php">🏠 Начало</a>
  </div>
</main>

<footer> 🥇Памет и бързина - път към първото място!🥇</footer>

</body>
</html>
