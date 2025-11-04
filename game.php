<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT total_score FROM users WHERE user_id=?");
$stmt->execute([$uid]);
$totalScore = $stmt->fetchColumn();
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<title>Match The Hidden Symbols – Game</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #f1f5f9;
    margin: 0;
    padding: 0;
    text-align: center;
    overflow-x: hidden;
  }
  header {
    background: linear-gradient(90deg, #3b82f6, #06b6d4);
    padding: 20px 0;
    font-size: 2em;
    font-weight: bold;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
  }
  main {
    max-width: 1100px;
    margin: 30px auto;
    background: rgba(255,255,255,0.08);
    padding: 25px 30px 40px 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    animation: fadeIn 1s ease-in-out;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .stats {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    font-size: 1.1em;
    background: rgba(56,189,248,0.1);
    border-radius: 10px;
    padding: 10px;
    margin-bottom: 15px;
  }
  .stats div { margin: 5px 10px; }

  #controls { margin: 20px 0; }
  #controls button {
    background: linear-gradient(90deg, #3b82f6, #06b6d4);
    border: none;
    color: white;
    font-weight: 500;
    font-size: 0.95em;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.25s;
    margin: 6px;
  }
  #controls button:hover {
    transform: scale(1.07);
    background: linear-gradient(90deg, #38bdf8, #0ea5e9);
  }
  #controls button:nth-child(1) { background: linear-gradient(90deg,#16a34a,#22c55e); }
  #controls button:nth-child(1):hover { background: linear-gradient(90deg,#22c55e,#4ade80); }
  #controls button:last-child { background: linear-gradient(90deg,#ef4444,#dc2626); }

  .grid {
    display: grid;
    grid-template-columns: repeat(12, 70px);
    gap: 6px;
    justify-content: center;
    margin-top: 20px;
    perspective: 1000px;
  }
  .tile {
    position: relative;
    width: 70px;
    height: 70px;
    transform-style: preserve-3d;
    transition: transform 0.4s ease;
    cursor: pointer;
  }
  .tile .front, .tile .back {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.8em;
    backface-visibility: hidden;
  }
  .tile .front {
    background: linear-gradient(145deg, #475569, #334155);
    box-shadow: inset 2px 2px 5px rgba(0,0,0,0.3);
  }
  .tile .back {
    background: #f8fafc;
    color: #0f172a;
    transform: rotateY(180deg);
  }
  .tile.open, .tile.matched { transform: rotateY(180deg); }
  .tile.matched .back {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    box-shadow: 0 0 10px rgba(34,197,94,0.6);
  }

  footer {
    margin-top: 30px;
    color: #94a3b8;
    font-size: 0.9em;
  }
  .logout {
    position: absolute;
    top: 10px;
    right: 20px;
    background: linear-gradient(90deg,#ef4444,#dc2626);
    border: none;
    color: white;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
  }
  .logout:hover { background: linear-gradient(90deg,#f87171,#ef4444); }
</style>
</head>
<body>

<header>🧩 Match The Hidden Symbols</header>

<button class="logout" onclick="exitGame()">🚪 Exit</button>

<main>
  <h2>Добре дошъл, <?= htmlspecialchars($username) ?>!</h2>

  <div class="stats">
    <div>🏆 Общо точки: <strong id="totalScore"><?= htmlspecialchars($totalScore) ?></strong></div>
    <div>⭐ Точки в тази игра: <span id="points">0</span></div>
    <div>🎯 Ходове: <span id="moves">150</span></div>
    <div>⏱ Време: <span id="time">0</span> сек</div>
  </div>

  <div id="controls">
    <button onclick="initBoard()">▶ Start Game</button>
    <button onclick="resetGame()">♻ Reset</button>
    <button onclick="newGame()">🆕 New Game</button>
    <button onclick="pauseGame()">⏸ Pause</button>
    <button onclick="useHint()">💡 Hint (-10)</button>
    <button onclick="buyMoves()">➕ Buy +10 Moves (-10 pts)</button>
    <button onclick="submitResult()">📤 Submit Result</button>
  </div>

  <div id="board" class="grid"></div>

  <form id="submitForm" action="api.php?action=game_submit" method="post">
    <input type="hidden" name="points" id="pointsField">
    <input type="hidden" name="time_taken" id="timeField">
    <input type="hidden" name="moves_used" id="movesField">
    <input type="hidden" name="attempts" id="attemptsField">
  </form>
</main>

<footer>🧩 Намери всички скрити символи! Събери най-високия резултат!</footer>

<script>
const SYMBOLS = ["🔶", "💎", "🌀", "⭐", "🔺", "🌿"];

let board=[],opened=[],matched=new Set();
let moves=150,points=0,streak=0,start=Date.now(),attempts=0;
let paused=false;

function initBoard(){
  const arr=[];
  for(let s=0;s<SYMBOLS.length;s++){
    for(let i=0;i<12;i++) arr.push(SYMBOLS[s]);
  }
  shuffle(arr);
  board=arr; moves=150; points=0; streak=0; matched.clear(); opened=[]; start=Date.now();
  render(); update();
}

function shuffle(array){
  for(let i=array.length-1;i>0;i--){
    const j=Math.floor(Math.random()*(i+1));
    [array[i],array[j]]=[array[j],array[i]];
  }
  return array;
}

function render(){
  const b=document.getElementById('board');
  b.innerHTML='';
  board.forEach((sym,i)=>{
    const tile=document.createElement('div');
    tile.className='tile';
    tile.dataset.i=i;

    const front=document.createElement('div');
    front.className='front';

    const back=document.createElement('div');
    back.className='back';
    back.textContent=sym;

    tile.appendChild(front);
    tile.appendChild(back);
    tile.onclick=()=>clickTile(i,tile);
    b.appendChild(tile);
  });
}

function clickTile(i,el){
  if(paused) return;
  if(matched.has(i)||opened.includes(i)||opened.length===2||moves<=0) return;
  moves--; opened.push(i); update();
  if(opened.length===2){
    const [a,b]=opened;
    setTimeout(()=>{
      if(board[a]===board[b]){
        matched.add(a); matched.add(b);
        streak++; points+=10*streak;
      } else { streak=0; }
      opened=[]; update();
      if(matched.size===72) endGame();
    },500);
  }
}

function update(){
  document.getElementById('moves').textContent=moves;
  document.getElementById('points').textContent=points;
  document.querySelectorAll('.tile').forEach(tile=>{
    const i=+tile.dataset.i;
    const isOpen=opened.includes(i);
    const isMatched=matched.has(i);
    tile.className='tile'+(isMatched?' matched':(isOpen?' open':''));
  });
}

function tick(){
  if(!paused){
    const t=Math.floor((Date.now()-start)/1000);
    document.getElementById('time').textContent=t;
  }
  requestAnimationFrame(tick);
}

function endGame(){
  const t=Math.floor((Date.now()-start)/1000);
  document.getElementById('pointsField').value=points;
  document.getElementById('timeField').value=t;
  document.getElementById('movesField').value=150-moves;
  document.getElementById('attemptsField').value=attempts;
  document.getElementById('submitForm').submit();
  alert("🎉 Поздравления! Резултатът ти беше записан.");
}

function resetGame(){ attempts++; render(); update(); }
function newGame(){ initBoard(); }
function pauseGame(){ paused=!paused; alert(paused?"⏸ Играта е на пауза":"▶ Продължаваме!"); }

function useHint(){
  if(points<10){ alert("Недостатъчно точки!"); return; }
  const seen={};
  for(let i=0;i<board.length;i++){
    if(matched.has(i)) continue;
    if(seen[board[i]]!==undefined){
      alert("💡 Намек: опитай позиции "+seen[board[i]]+" и "+i);
      points-=10; update();
      return;
    }
    seen[board[i]]=i;
  }
}

function buyMoves(){
  if(points<10){ alert("Недостатъчно точки!"); return; }
  points-=10; moves+=10; update();
}

function convertMovesToPoints(){
  if(moves<10){ alert("Нямаш достатъчно ходове!"); return; }
  moves-=10; points+=10; update();
}

function submitResult(){
  const t=Math.floor((Date.now()-start)/1000);
  document.getElementById('pointsField').value=points;
  document.getElementById('timeField').value=t;
  document.getElementById('movesField').value=150-moves;
  document.getElementById('attemptsField').value=attempts;
  document.getElementById('submitForm').submit();
  refreshTotalScore();
  alert("📤 Резултатът е изпратен успешно!");
}

function refreshTotalScore(){
  fetch('api.php?action=get_score')
    .then(r=>r.json())
    .then(data=>{
      if(data.total_score!==undefined){
        document.getElementById('totalScore').textContent=data.total_score;
      }
    });
}

function exitGame(){ window.location.href="index.php"; }

initBoard(); tick();
</script>
</body>
</html>
