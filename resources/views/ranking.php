<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<style>

.card{
background:white;
padding:15px;
border-radius:8px;
margin-bottom:10px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
display:flex;
justify-content:space-between;
align-items:center;
}

.rank{
font-size:18px;
font-weight:bold;
}

.user{
font-size:16px;
font-weight:bold;
}

.score{
font-size:16px;
color:#2e7d32;
font-weight:bold;
}

</style>


<h1>🏆 Ranking</h1>

<?php $pos=1; ?>

<?php foreach($ranking as $r): ?>

<div class="card">

<div class="rank">
<?= $pos ?>️⃣
</div>

<div class="user">
<?= htmlspecialchars($r['username'] ?? '—') ?>
</div>

<div class="score">
<?= $r['score'] ?> pts
</div>

</div>

<?php $pos++; ?>

<?php endforeach; ?>


<br>

<a href="<?= $base ?>/coupon/<?= $couponId ?>">← Voltar</a>


<?php require __DIR__ . '/layout/footer.php'; ?>