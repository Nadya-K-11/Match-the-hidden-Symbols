<?php
require 'db.php';
session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {

  case 'register':
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$email || !$password) {
      echo "Липсват полета!";
      exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,registered_at,total_score) VALUES (?,?,?,?,100)");
    $stmt->execute([$username,$email,$hash,date('Y-m-d H:i:s')]);
    $user_id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO statistics (user_id,total_games,total_wins,total_score,total_points,total_time,average_score,average_time)
                   VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$user_id,0,0,100,0,0,0,0]);

    echo "<h3>Регистрация успешна!</h3>";
    echo "<p>Добре дошъл, $username</p>";
    echo "<p><a href='index.php'>Вход</a></p>";
    break;

  case 'login':
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->execute([$login,$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password,$user['password_hash'])) {
      echo "Грешни данни за вход!";
      exit;
    }

    $_SESSION['user_id']=$user['user_id'];
    $_SESSION['username']=$user['username'];

    $today=date('Y-m-d');
    $lastLogin=$user['last_login_at']?date('Y-m-d',strtotime($user['last_login_at'])):null;
    if ($lastLogin!==$today) {
      $pdo->prepare("UPDATE users SET daily_login_bonus_count=0 WHERE user_id=?")->execute([$user['user_id']]);
      $user['daily_login_bonus_count']=0;
    }
    if ($user['daily_login_bonus_count']<2) {
      $pdo->prepare("UPDATE users SET total_score=total_score+10, daily_login_bonus_count=daily_login_bonus_count+1 WHERE user_id=?")
          ->execute([$user['user_id']]);
      $pdo->prepare("UPDATE statistics SET total_score=total_score+10 WHERE user_id=?")->execute([$user['user_id']]);
    }
    $pdo->prepare("UPDATE users SET last_login_at=? WHERE user_id=?")->execute([date('Y-m-d H:i:s'),$user['user_id']]);

    header("Location: info.php");
    exit;
    break;

case 'game_submit':
    if (!isset($_SESSION['user_id'])) {
      echo "Не сте влезли!";
      exit;
    }

    $uid        = $_SESSION['user_id'];
    $points     = (int)($_POST['points'] ?? 0);
    $time_taken = (int)($_POST['time_taken'] ?? 0);
    $moves_used = (int)($_POST['moves_used'] ?? 0);
    $attempts   = (int)($_POST['attempts'] ?? 0);

    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("INSERT INTO results (user_id,score,attempts,time_taken,moves_used) VALUES (?,?,?,?,?)");
      $stmt->execute([$uid,$points,$attempts,$time_taken,$moves_used]);

      $username = $_SESSION['username'];
      $stmt = $pdo->prepare("INSERT INTO leaderboard (user_id,username,score,time_taken,attempts) VALUES (?,?,?,?,?)");
      $stmt->execute([$uid,$username,$points,$time_taken,$attempts]);

      $pdo->prepare("UPDATE users SET 
          total_score = total_score + ?,
          total_time  = total_time + ?,
          total_games = total_games + 1,
          total_wins  = total_wins + 1
        WHERE user_id=?")->execute([$points,$time_taken,$uid]);

      $pdo->commit();

      echo "<h3>Резултатът е записан успешно!</h3>";
      echo "<p>Точки: $points</p>";
      echo "<p>Време: $time_taken сек.</p>";
      echo "<p><a href='leaderboard.php'>Виж класацията</a></p>";

    } catch(Exception $e) {
      $pdo->rollBack();
      echo "Грешка при запис на резултата!";
    }
    break;

  case 'leaderboard':
    $stmt=$pdo->query("SELECT username,score,time_taken,attempts,played_at FROM leaderboard ORDER BY score DESC,time_taken ASC LIMIT 20");
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Класация</h2><table border='1'><tr><th>#</th><th>Потребител</th><th>Точки</th><th>Време</th><th>Опити</th><th>Дата</th></tr>";
    foreach($rows as $i=>$r){
      echo "<tr><td>".($i+1)."</td><td>{$r['username']}</td><td>{$r['score']}</td><td>{$r['time_taken']}</td><td>{$r['attempts']}</td><td>{$r['played_at']}</td></tr>";
    }
    echo "</table>";
    break;

  case 'statistics':
    $stmt=$pdo->query("SELECT username,total_score,total_time,total_games,total_wins FROM users");
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Статистика</h2><table border='1'><tr><th>Потребител</th><th>Общо точки</th><th>Общо време</th><th>Игри</th><th>Победи</th></tr>";
    foreach($rows as $r){
      echo "<tr><td>{$r['username']}</td><td>{$r['total_score']}</td><td>{$r['total_time']}</td><td>{$r['total_games']}</td><td>{$r['total_wins']}</td></tr>";
    }
    echo "</table>";
    break;
	
	case 'get_score':
    if(!isset($_SESSION['user_id'])){
        echo json_encode(['error'=>'not logged']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT total_score FROM users WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $score = $stmt->fetchColumn();
    echo json_encode(['total_score'=>$score]);
    break;

	
  default:
    echo "Непознато действие!";
}
