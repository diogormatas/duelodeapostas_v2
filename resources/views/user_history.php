<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<style>

.history-box{
max-width:900px;
margin:auto;
background:white;
padding:20px;
border-radius:8px;
}

.card{
border:1px solid #ddd;
padding:14px;
margin-bottom:12px;
border-radius:6px;
}

.meta{
color:#666;
font-size:13px;
}

.button{
display:inline-block;
background:#1565c0;
color:white;
padding:8px 12px;
border-radius:5px;
text-decoration:none;
font-weight:bold;
}

</style>


<div class="history-box">

<h1>📜 Histórico de Duelos</h1>


<?php foreach($history as $h): ?>

<div class="card">

<div>
<strong><?= htmlspecialchars($h['challenger'] ?? '-') ?></strong>
vs
<strong><?= htmlspecialchars($h['opponent'] ?? 'alguém') ?></strong>
</div>

<div class="meta">
Estado: <?= htmlspecialchars($h['status']) ?> |
Data: <?= $h['created_at'] ?>
</div>

<div style="margin-top:8px;">
Score: <?= (int)$h['my_score'] ?> - <?= (int)$h['other_score'] ?>
</div>

<br>

<a class="button" href="<?= $base ?>/coupon/<?= $h['coupon_id'] ?>">
Ver duelo
</a>

</div>

<?php endforeach; ?>


<br>

<a href="<?= $base ?>/duels">← Voltar aos duelos</a>

</div>


<?php require __DIR__ . '/layout/footer.php'; ?>