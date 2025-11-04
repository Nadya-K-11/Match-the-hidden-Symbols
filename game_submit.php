<?php
require 'db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    die("Не сте влезли в системата!");
}

$uid = $_SESSION['user_id'];
$points = (int)($_POST['points'] ?? 0);
$time_taken = (int)($_POST['time_taken'] ?? 0);
$moves_used = (int)($_POST['moves_used'] ?? 0);
$attempts = (int)($_POST['attempts'] ?? 0);

$pdo->prepare("INSERT INTO results (user_id,score,attempts,time_taken,moves_used) VALUES (?,?,?,?,?)")
    ->execute([$uid,$points,$attempts,$time_taken,$moves_used]);

$username = $_SESSION['username'];
$pdo->prepare("INSERT INTO leaderboard (user_id,username,score,time_taken,attempts) VALUES (?,?,?,?,?)")
    ->execute([$uid,$username,$points,$time_taken,$attempts]);

$pdo->prepare("UPDATE users SET 
    total_score = total_score + ?,
    total_time = total_time + ?,
    total_games = total_games + 1,
    total_wins = total_wins + 1
  WHERE user_id=?")->execute([$points,$time_taken,$uid]);

echo "<h3>Резултатът е записан!</h3>";
echo "<p>Точки: $points</p>";
echo "<p>Време: $time_taken сек.</p>";
echo "<p><a href='leaderboard.php'>Виж класацията</a></p>";
