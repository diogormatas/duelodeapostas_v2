<?php require __DIR__ . '/layout/header.php'; ?>

<h1>👤 A minha conta</h1>

<div style="
background:white;
padding:20px;
border-radius:8px;
margin-bottom:20px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>💰 Saldo</h2>

<div style="font-size:24px;font-weight:bold;">
<?= number_format($balance, 2) ?> balas
</div>

<br>

<strong>🏆 Ganhos totais:</strong>
<?= number_format($totalWins, 2) ?> balas

</div>

<div style="
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
">

<h2>📜 Histórico de transações</h2>

<?php if (empty($transactions)): ?>

<p>Sem transações.</p>

<?php else: ?>

<table style="width:100%;border-collapse:collapse;">

<tr>
<th style="text-align:left;">Descrição</th>
<th>Valor</th>
<th>Data</th>
</tr>

<?php foreach ($transactions as $t): ?>

<tr style="border-bottom:1px solid #eee;">

<td>
<?= htmlspecialchars($t['description'] ?? $t['type']) ?>
</td>

<td style="text-align:center;">

<?php if ($t['amount'] > 0): ?>

<span style="color:#2e7d32;font-weight:bold;">
+<?= number_format($t['amount'], 2) ?>
</span>

<?php else: ?>

<span style="color:#e53935;">
<?= number_format($t['amount'], 2) ?>
</span>

<?php endif; ?>

</td>

<td style="text-align:right;">
<?= $t['created_at'] ?>
</td>

</tr>

<?php endforeach; ?>

</table>

<?php endif; ?>

</div>

<?php require __DIR__ . '/layout/footer.php'; ?>