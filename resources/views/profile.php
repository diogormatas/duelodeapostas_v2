<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<div class="card">

<div class="username">
👤 <?= htmlspecialchars($username) ?>
</div>

<img src="<?= $avatar ?>" style="border-radius:50%;margin-bottom:15px;">


<!-- TITULOS -->

<?php if(!empty($titles)): ?>

<div style="margin-bottom:15px;">

<?php foreach($titles as $t): ?>

<span style="
background:#f1f1f1;
padding:6px 10px;
border-radius:6px;
margin-right:6px;
font-size:14px;
">
<?= $t ?>
</span>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="stat">
⚔️ Duelos jogados: <?= $duels ?>
</div>

<div class="stat">
🏆 Vitórias: <?= $wins ?>
</div>

<div class="stat">
📊 Win rate: <?= $winrate ?>%
</div>

<div class="stat">
🎯 Picks feitas: <?= $totalPicks ?>
</div>


<div class="actions">

<a class="button"
href="<?= $base ?>/user/<?= urlencode($username) ?>/history">
Ver histórico
</a>


<?php if(isset($_SESSION['username']) && $_SESSION['username'] !== $username): ?>

<a class="button"
href="<?= $base ?>/duels/challenge/<?= urlencode($username) ?>">
⚔️ Desafiar
</a>

<?php endif; ?>

</div>

</div>


<style>

.card{
background:white;
padding:25px;
border-radius:8px;
max-width:600px;
margin:auto;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.username{
font-size:26px;
font-weight:bold;
margin-bottom:20px;
}

.stat{
font-size:18px;
margin:10px 0;
}

.actions{
margin-top:20px;
}

.button{
display:inline-block;
padding:8px 12px;
background:#1565c0;
color:white;
border-radius:5px;
text-decoration:none;
font-weight:bold;
margin-right:10px;
}

</style>


<?php require __DIR__ . '/layout/footer.php'; ?>