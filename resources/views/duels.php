<?php require __DIR__ . '/layout/header.php'; ?>

<h1>⚔️ Duelos</h1>

<div style="margin-bottom:20px;">

<a href="<?= $base ?>/duels/create" style="
background:#1976d2;
color:white;
padding:10px 18px;
border-radius:6px;
text-decoration:none;
font-weight:bold;
">
Criar desafio
</a>

</div>


<?php if(empty($duels)): ?>

<p>Não existem duelos disponíveis.</p>

<?php else: ?>

<table style="width:100%;border-collapse:collapse;background:white">

<tr style="background:#f5f5f5">
<th style="padding:8px;text-align:left">Desafiante</th>
<th>Adversário</th>
<th>Stake</th>
<th>Jogos</th>
<th></th>
</tr>

<?php foreach($duels as $d): ?>

<tr style="border-bottom:1px solid #eee">

<td style="padding:8px">
<?= htmlspecialchars($d['challenger']) ?>
</td>

<td style="text-align:center">
<?= htmlspecialchars($d['opponent'] ?? 'Aguardando') ?>
</td>

<td style="text-align:center">
<?= number_format($d['stake'],2) ?> balas
</td>

<td style="text-align:center">
<?= $d['matches'] ?>
</td>

<td style="text-align:right;padding-right:10px">

<?php if($d['status']=='PENDING'): ?>

<a href="<?= $base ?>/duel/<?= $d['id'] ?>/accept" style="
background:#43a047;
color:white;
padding:6px 12px;
border-radius:5px;
text-decoration:none;
">
Aceitar
</a>

<?php else: ?>

<a href="<?= $base ?>/coupon/<?= $d['coupon_id'] ?>">
Ver
</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php endif; ?>


<?php require __DIR__ . '/layout/footer.php'; ?>