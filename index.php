<?php
session_start();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <title>Match The Hidden Symbols – Начало</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <header>🧩 Match The Hidden Symbols</header>
  <main>
    <?php if(isset($_SESSION['user_id'])): ?>
      <h2>Добре дошъл, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
      <a href="game.php"><button>▶️ Стартирай игра</button></a>
      <a href="info.php"><button class="info-btn">📄 Информация</button></a>
      <a href="leaderboard.php"><button class="alt">🏆 Класация</button></a>
      <a href="logout.php"><button class="logout">🚪 Изход</button></a>
    <?php else: ?>
      <h2>Готов ли си да тестваш паметта си?</h2>
      <p>Влез в профила си или се регистрирай, за да започнеш играта!</p>
      <a href="login.php"><button>🔑 Вход</button></a>
      <a href="register.php"><button class="alt">📝 Регистрация</button></a>
    <?php endif; ?>
  </main>
</body>
</html>
