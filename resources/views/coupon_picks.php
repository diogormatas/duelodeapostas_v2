<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<style>

table{
width:100%;
border-collapse:collapse;
background:white;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

th{
background:#222;
color:white;
padding:10px;
}

td{
padding:10px;
border-bottom:1px solid #eee;
text-align:center;
}

.match{
text-align:left;
font-weight:bold;
}

.pick{
font-weight:bold;
font-size:16px;
}

.p1{color:#2e7d32;}
.px{color:#f9a825;}
.p2{color:#1565c0;}

</style>


<h1>🎯 Picks</h1>


<table>

<tr>

<th>Jogo</th>

<?php foreach($players as $player): ?>

<th><?= htmlspecialchars($player) ?></th>

<?php endforeach; ?>

</tr>


<?php foreach($matches as $match): ?>

<tr>

<td class="match">

<?= htmlspecialchars($match['home_team']) ?>

vs

<?= htmlspecialchars($match['away_team']) ?>

<br>

<small><?= htmlspecialchars($match['scheduled_at']) ?></small>

</td>


<?php foreach($players as $player): ?>

<?php
$pick = $match['picks'][$player] ?? "-";

$class = "";

if($pick=="1") $class="p1";
if($pick=="X") $class="px";
if($pick=="2") $class="p2";
?>

<td class="pick <?= $class ?>">

<?= $pick ?>

</td>

<?php endforeach; ?>

</tr>

<?php endforeach; ?>

</table>


<br>

<a href="<?= $base ?>/coupon/<?= $couponId ?>">← Voltar</a>


<?php require __DIR__ . '/layout/footer.php'; ?>