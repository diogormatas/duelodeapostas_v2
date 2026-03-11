<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];

$stats = $stats ?? [];
$couponId = $couponId ?? ($_GET['id'] ?? null);
?>

<!DOCTYPE html>
<html>

<head>

<title>Pick Stats</title>

<style>

body{
font-family:Arial;
background:#f5f5f5;
padding:20px;
}

.container{
max-width:900px;
margin:auto;
}

.card{
background:white;
padding:18px;
border-radius:8px;
margin-bottom:12px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.match{
font-weight:bold;
margin-bottom:10px;
}

.bar{
height:18px;
background:#ddd;
border-radius:5px;
overflow:hidden;
margin-bottom:6px;
}

.fill{
height:100%;
}

.home{background:#2e7d32;}
.draw{background:#f9a825;}
.away{background:#1565c0;}

.row{
display:flex;
justify-content:space-between;
font-size:14px;
margin-bottom:4px;
}

.empty{
background:white;
padding:20px;
border-radius:8px;
text-align:center;
}

</style>

</head>

<body>

<div class="container">

<h1>📊 Pick Stats</h1>

<?php if(empty($stats)): ?>

<div class="empty">
Ainda não existem apostas neste cupão.
</div>

<?php else: ?>

<?php foreach($stats as $s): ?>

<?php
$total = $s['1'] + $s['X'] + $s['2'];

$p1 = $total ? round(($s['1']/$total)*100) : 0;
$px = $total ? round(($s['X']/$total)*100) : 0;
$p2 = $total ? round(($s['2']/$total)*100) : 0;
?>

<div class="card">

<div class="match">

⚽ <?= htmlspecialchars($s['match'] ?? '') ?>

</div>

<div class="row">
<div>1</div>
<div><?= $p1 ?>%</div>
</div>

<div class="bar">
<div class="fill home" style="width:<?= $p1 ?>%"></div>
</div>

<div class="row">
<div>X</div>
<div><?= $px ?>%</div>
</div>

<div class="bar">
<div class="fill draw" style="width:<?= $px ?>%"></div>
</div>

<div class="row">
<div>2</div>
<div><?= $p2 ?>%</div>
</div>

<div class="bar">
<div class="fill away" style="width:<?= $p2 ?>%"></div>
</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

<br>

<?php if($couponId): ?>
<a href="<?= $base ?>/coupon/<?= $couponId ?>">← Voltar ao cupão</a>
<?php else: ?>
<a href="<?= $base ?>/coupons">← Voltar</a>
<?php endif; ?>

</div>

</body>
</html>

<?php require __DIR__ . '/layout/footer.php'; ?>