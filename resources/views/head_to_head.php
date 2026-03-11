<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<!DOCTYPE html>
<html>

<head>

<title><?= htmlspecialchars($user1) ?> vs <?= htmlspecialchars($user2) ?></title>

<style>

body{
font-family:Arial;
background:#f5f5f5;
padding:20px;
}

.card{
background:white;
padding:25px;
border-radius:8px;
max-width:600px;
margin:auto;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
text-align:center;
}

.title{
font-size:24px;
font-weight:bold;
margin-bottom:20px;
}

.stat{
font-size:18px;
margin:10px 0;
}

</style>

</head>

<body>

<div class="card">

<div class="title">
⚔️ <?= htmlspecialchars($user1) ?> vs <?= htmlspecialchars($user2) ?>
</div>

<div class="stat">
<?= htmlspecialchars($user1) ?> vitórias: <?= $stats['p1'] ?>
</div>

<div class="stat">
<?= htmlspecialchars($user2) ?> vitórias: <?= $stats['p2'] ?>
</div>

<div class="stat">
Empates: <?= $stats['draw'] ?>
</div>

<div class="stat">
Total duelos: <?= $stats['total'] ?>
</div>

<br>

<a href="<?= $base ?>/duels">← Voltar</a>

</div>

</body>
</html>

<?php require __DIR__ . '/layout/footer.php'; ?>