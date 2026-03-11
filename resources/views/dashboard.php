<?php require __DIR__ . '/layout/header.php'; ?>

<h1>🏠 Dashboard</h1>

<!-- JACKPOT -->

<div style="
background:white;
padding:20px;
border-radius:8px;
margin-bottom:20px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>🏆 Jackpot mensal</h2>

<p>
Prize pool:
<strong><?= number_format($jackpot['amount'] ?? 0, 2) ?> balas</strong>
</p>

<table style="width:100%;border-collapse:collapse;">

<tr>
<th style="text-align:left;">#</th>
<th style="text-align:left;">Jogador</th>
<th>Pontos</th>
</tr>

<?php $pos = 1; ?>

<?php if (!empty($ranking)): ?>

<?php foreach ($ranking as $r): ?>

<tr>
<td><?= $pos ?></td>
<td><?= htmlspecialchars($r['username']) ?></td>
<td><?= $r['points'] ?? 0 ?></td>
</tr>

<?php $pos++; ?>
<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="3">Sem ranking ainda.</td>
</tr>

<?php endif; ?>

</table>

</div>



<!-- MINHAS APOSTAS -->

<div style="
background:white;
padding:20px;
border-radius:8px;
margin-bottom:20px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>🎯 As minhas apostas</h2>

<?php if (empty($myCoupons)): ?>

<p>Não tens apostas em curso.</p>

<?php else: ?>

<?php foreach ($myCoupons as $c): ?>

<div style="margin-bottom:20px;">

<strong>
<?= htmlspecialchars($c['name'] ?? 'Cupão') ?>
</strong>

<table style="width:100%;margin-top:8px;">

<?php foreach ($c['ranking'] as $r): ?>

<tr>
<td><?= htmlspecialchars($r['username']) ?></td>
<td style="text-align:right;"><?= $r['score'] ?? 0 ?></td>
</tr>

<?php endforeach; ?>

</table>

<a href="/duelo/v2/public/coupon/<?= $c['id'] ?>">
Ver aposta
</a>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>



<!-- CUPÕES A FECHAR -->

<?php if (!empty($closingSoon)): ?>

<div style="
background:white;
padding:20px;
border-radius:8px;
margin-bottom:20px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>⏱ A fechar em breve</h2>

<?php foreach ($closingSoon as $c): ?>

<div style="margin-bottom:10px;">

<strong>
<?= htmlspecialchars($c['name'] ?? 'Cupão') ?>
</strong>

<br>

Entrada: <?= number_format($c['entry_price'], 2) ?> balas

<br>

👥 <?= $c['players'] ?> jogadores

<br>

<span style="color:#e53935;font-weight:bold;">
fecha em <?= $c['minutes_left'] ?> min
</span>

<br>

<a href="/duelo/v2/public/coupon/<?= $c['id'] ?>">
Entrar
</a>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>



<!-- ACTIVITY -->

<div style="
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>🔥 Atividade</h2>

<?php if (empty($activity)): ?>

<p>Sem atividade recente.</p>

<?php else: ?>

<?php foreach ($activity as $a): ?>

<div style="margin-bottom:10px;">

<strong><?= htmlspecialchars($a['username'] ?? 'Sistema') ?></strong>

<?php if ($a['type'] === 'duel_created'): ?>
criou uma aposta
<?php elseif ($a['type'] === 'duel_accepted'): ?>
aceitou um duelo
<?php elseif ($a['type'] === 'bet_placed'): ?>
entrou numa aposta
<?php else: ?>
realizou uma ação
<?php endif; ?>

<br>

<small style="color:#666;">
<?= $a['created_at'] ?>
</small>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php require __DIR__ . '/layout/footer.php'; ?>