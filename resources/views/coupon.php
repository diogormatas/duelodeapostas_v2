<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>

.card{
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
}

th,td{
padding:12px;
border-bottom:1px solid #ddd;
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

.button{
padding:6px 10px;
background:#1565c0;
color:white;
border-radius:4px;
text-decoration:none;
font-weight:bold;
margin-right:5px;
}

.meta{
font-size:13px;
color:#666;
}

</style>


<div class="card">

<h1><?= htmlspecialchars($coupon['name'] ?? 'Duelo'); ?></h1>

<p class="meta">
Stake: <?= number_format($coupon['entry_price'], 2); ?> balas
</p>

<div style="margin-bottom:20px;">

<a class="button" href="<?= $base ?>/coupon/<?= (int)$coupon['id']; ?>/ranking">
Ranking
</a>

<a class="button" href="<?= $base ?>/coupon/<?= (int)$coupon['id']; ?>/picks">
Picks
</a>

<a class="button" href="<?= $base ?>/coupon/<?= (int)$coupon['id']; ?>/pick-stats">
Pick Stats
</a>

</div>


<?php if (!empty($_SESSION['error'])): ?>

<div style="color:red;margin-bottom:15px;">
<?= htmlspecialchars($_SESSION['error']); ?>
</div>

<?php unset($_SESSION['error']); endif; ?>


<?php if (!empty($_SESSION['success'])): ?>

<div style="color:green;margin-bottom:15px;">
<?= htmlspecialchars($_SESSION['success']); ?>
</div>

<?php unset($_SESSION['success']); endif; ?>


<form method="POST" action="<?= $base ?>/bet">

<input type="hidden" name="coupon_id" value="<?= (int)$coupon['id']; ?>">

<table>

<tr>
<th>Data</th>
<th class="match">Jogo</th>
<th>1</th>
<th>X</th>
<th>2</th>
</tr>

<?php foreach ($matches as $match): ?>

<tr>

<td>

<div class="meta">
<?= htmlspecialchars($match['competition']); ?>
</div>

<?= date("d M H:i", strtotime($match['scheduled_at'])); ?>

</td>

<td class="match">

<div class="team">

<?php if(!empty($match['home_logo'])): ?>
<img src="<?= htmlspecialchars($match['home_logo']); ?>">
<?php endif; ?>

<?= htmlspecialchars($match['home_team']); ?>

</div>

vs

<div class="team">

<?php if(!empty($match['away_logo'])): ?>
<img src="<?= htmlspecialchars($match['away_logo']); ?>">
<?php endif; ?>

<?= htmlspecialchars($match['away_team']); ?>

</div>

</td>

<td>
<input type="radio" name="predictions[<?= (int)$match['id']; ?>]" value="1" required>
</td>

<td>
<input type="radio" name="predictions[<?= (int)$match['id']; ?>]" value="X">
</td>

<td>
<input type="radio" name="predictions[<?= (int)$match['id']; ?>]" value="2">
</td>

</tr>

<?php endforeach; ?>

</table>

<br>

<button type="submit">Apostar</button>

</form>

<br>

<a href="<?= $base ?>/coupons">← Voltar</a>

</div>


<?php require __DIR__ . '/layout/footer.php'; ?>