<?php require __DIR__ . '/layout/header.php'; ?>

<h1>🎯 Apostas</h1>

<div style="margin-bottom:30px;">

<a href="<?= $base ?>/duels/create" style="
background:#1976d2;
color:white;
padding:10px 18px;
border-radius:6px;
text-decoration:none;
margin-right:10px;
font-weight:bold;
">
Criar Duelo
</a>

<a href="<?= $base ?>/coupons/create" style="
background:#43a047;
color:white;
padding:10px 18px;
border-radius:6px;
text-decoration:none;
font-weight:bold;
">
Criar Cupão
</a>

</div>


<h2>🧾 Cupões abertos</h2>

<?php if(empty($coupons)): ?>

<p>Não existem cupões disponíveis.</p>

<?php else: ?>

<table style="width:100%;border-collapse:collapse;background:white">

<tr style="background:#f5f5f5">
<th style="padding:8px;text-align:left">Cupão</th>
<th>Entrada</th>
<th>Jogadores</th>
<th></th>
</tr>

<?php foreach($coupons as $c): ?>

<tr style="border-bottom:1px solid #eee">

<td style="padding:8px">

<strong>
<?= htmlspecialchars($c['name'] ?? 'Cupão '.$c['id']) ?>
</strong>

</td>

<td style="text-align:center">
<?= number_format($c['entry_price'],2) ?> balas
</td>

<td style="text-align:center">
<?= $c['players'] ?? 0 ?>
</td>

<td style="text-align:right;padding-right:10px">

<a href="<?= $base ?>/coupon/<?= $c['id'] ?>" style="
background:#1976d2;
color:white;
padding:6px 12px;
border-radius:5px;
text-decoration:none;
">
Jogar
</a>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php endif; ?>


<?php require __DIR__ . '/layout/footer.php'; ?>