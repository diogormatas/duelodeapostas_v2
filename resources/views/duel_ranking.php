<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Ranking Duelistas</title>
<style>
body{
    font-family:Arial;
    background:#f5f5f5;
    padding:20px;
}
.container{
    max-width:800px;
    margin:auto;
    background:white;
    padding:20px;
    border-radius:8px;
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:left;
}
th{
    background:#333;
    color:white;
}
</style>
</head>
<body>

<div class="container">

<h1>🏆 Ranking de Duelistas</h1>

<table>
<tr>
<th>#</th>
<th>Jogador</th>
<th>Duelos</th>
<th>Vitórias</th>
</tr>

<?php $pos = 1; ?>
<?php foreach($ranking as $r): ?>

<tr>
<td><?= $pos++ ?></td>
<td><?= htmlspecialchars($r['username']) ?></td>
<td><?= (int)$r['duels'] ?></td>
<td><?= (int)$r['wins'] ?></td>
</tr>

<?php endforeach; ?>
</table>

<br>
<a href="<?= $base ?>/duels">← Voltar aos duelos</a>

</div>

</body>
</html>

<?php require __DIR__ . '/layout/footer.php'; ?>