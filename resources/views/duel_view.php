<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];

$p1 = $players[0] ?? null;
$p2 = $players[1] ?? null;

$showPicks = ($coupon['status'] !== 'OPEN');

$p1Score = $p1['score'] ?? 0;
$p2Score = $p2['score'] ?? 0;

$leader = null;

if($p1Score > $p2Score){
$leader = 1;
}
elseif($p2Score > $p1Score){
$leader = 2;
}

$p1Avatar = $p1 ? "https://www.gravatar.com/avatar/".md5(strtolower($p1['username']))."?s=120&d=identicon" : "";
$p2Avatar = $p2 ? "https://www.gravatar.com/avatar/".md5(strtolower($p2['username']))."?s=120&d=identicon" : "";
?>

<style>

.duel-box{
max-width:900px;
margin:auto;
background:white;
padding:20px;
border-radius:6px;
box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

.players{
display:flex;
justify-content:space-between;
align-items:center;
font-size:22px;
font-weight:bold;
margin-bottom:20px;
}

.player{
width:40%;
text-align:center;
}

.player img{
width:60px;
height:60px;
border-radius:50%;
margin-bottom:6px;
}

.leader{
color:#2e7d32;
}

.score{
font-size:26px;
font-weight:bold;
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
}

th,td{
padding:12px;
border-bottom:1px solid #eee;
text-align:center;
}

.match{
text-align:left;
}

.team{
display:flex;
align-items:center;
gap:6px;
}

.team img{
height:18px;
}

.pick{
font-size:18px;
font-weight:bold;
}

</style>


<div class="duel-box">

<div class="players">

<div class="player <?= $leader===1 ? 'leader' : '' ?>">

<?php if($p1Avatar): ?>
<img src="<?= $p1Avatar ?>">
<?php endif; ?>

<?= htmlspecialchars($p1['username'] ?? "Player 1") ?>

<div class="score">
<?= $p1Score ?>
</div>

</div>

<div>
vs
</div>

<div class="player <?= $leader===2 ? 'leader' : '' ?>">

<?php if($p2Avatar): ?>
<img src="<?= $p2Avatar ?>">
<?php endif; ?>

<?= htmlspecialchars($p2['username'] ?? "Player 2") ?>

<div class="score">
<?= $p2Score ?>
</div>

</div>

</div>


<table>

<tr>
<th class="match">Jogo</th>
<th><?= htmlspecialchars($p1['username'] ?? "") ?></th>
<th><?= htmlspecialchars($p2['username'] ?? "") ?></th>
</tr>

<?php foreach($matches as $m): ?>

<tr>

<td class="match">

<div class="team">
<?php if(!empty($m['home_logo'])): ?>
<img src="<?= htmlspecialchars($m['home_logo']) ?>">
<?php endif; ?>
<?= htmlspecialchars($m['home_team']) ?>
</div>

vs

<div class="team">
<?php if(!empty($m['away_logo'])): ?>
<img src="<?= htmlspecialchars($m['away_logo']) ?>">
<?php endif; ?>
<?= htmlspecialchars($m['away_team']) ?>
</div>

</td>

<td class="pick">

<?php
if(!$showPicks){
echo "?";
}else{
echo $p1 ? ($picks[$p1['bet_id']][$m['id']] ?? "-") : "-";
}
?>

</td>

<td class="pick">

<?php
if(!$showPicks){
echo "?";
}else{
echo $p2 ? ($picks[$p2['bet_id']][$m['id']] ?? "-") : "-";
}
?>

</td>

</tr>

<?php endforeach; ?>

</table>


<hr>

<h3>💬 Chat do duelo</h3>

<div id="chat-box" style="margin-bottom:15px;"></div>

<form method="POST" action="<?= $base ?>/duels/chat/send">

<input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">

<input
type="text"
name="message"
placeholder="Escreve uma mensagem..."
style="width:70%;padding:6px;"
required
>

<button type="submit">
Enviar
</button>

</form>

<br>

<a href="<?= $base ?>/duels">← Voltar</a>

</div>


<script>

function loadChat(){

fetch("<?= $base ?>/duels/chat/<?= $coupon['id'] ?>")

.then(res => res.json())

.then(messages => {

let html = "";

messages.forEach(m => {

html += `
<div style="margin-bottom:6px;">
<strong>${m.username}</strong>: ${m.message}
</div>
`;

});

document.getElementById("chat-box").innerHTML = html;

});

}

loadChat();

setInterval(loadChat,3000);

</script>


<?php require __DIR__ . '/layout/footer.php'; ?>