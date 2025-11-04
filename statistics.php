<?php
session_start();
require 'db.php';

$stmt = $pdo->query("SELECT username,total_score,total_time,total_games,total_wins 
                     FROM users 
                     ORDER BY total_score DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['game'])) {
    $_SESSION['game']['paused'] = true;
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>🏆 Обща статистика</title>
<link rel="stylesheet" href="statistics.css">
</head>
<body>

<header>📊 Обща статистика на играчите</header>

<main>
  <table>
    <tr>
      <th>#</th>
      <th>Потребител</th>
      <th>Общо точки</th>
      <th>Общо време (сек)</th>
      <th>Игри</th>
      <th>Победи</th>
    </tr>

    <?php foreach($rows as $i=>$r): ?>
      <tr>
        <td class="rank"><?= $i+1 ?></td>
        <td><?= htmlspecialchars($r['username']) ?></td>
        <td><?= $r['total_score'] ?></td>
        <td><?= $r['total_time'] ?></td>
        <td><?= $r['total_games'] ?></td>
        <td><?= $r['total_wins'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="nav-links">
    <a href="game.php">🎮 Към Играта</a>
    <a href="leaderboard.php">🏆 Към Класацията</a>
    <a href="index.php">🏠 Начало</a>
  </div>
</main>

<footer>Следи своя прогрес и се състезавай с останалите! 🚀</footer>

</body>
</html>
