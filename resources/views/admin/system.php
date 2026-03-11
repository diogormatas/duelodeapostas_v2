<?php require __DIR__ . '/layout.php'; ?>

<h1>🖥 Platform Monitor</h1>

<h2>📊 Plataforma</h2>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;">

<div class="card">
<h3>⚽ Jogos</h3>
<p><?= $matches ?></p>
</div>

<div class="card">
<h3>🏆 Competições</h3>
<p><?= $competitions ?></p>
</div>

<div class="card">
<h3>👥 Equipas</h3>
<p><?= $teams ?></p>
</div>

<div class="card">
<h3>👤 Utilizadores</h3>
<p><?= $users ?></p>
</div>

</div>


<h2>🎯 Betting Engine</h2>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;">

<div class="card">
<h3>🎟 Cupões</h3>
<p><?= $coupons ?></p>
</div>

<div class="card">
<h3>📦 Cupões abertos</h3>
<p><?= $openCoupons ?></p>
</div>

<div class="card">
<h3>🎯 Apostas</h3>
<p><?= $bets ?></p>
</div>

<div class="card">
<h3>⚔ Duelos pendentes</h3>
<p><?= $pendingDuels ?></p>
</div>

</div>


<h2>💰 Financeiro</h2>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:30px;">

<div class="card">
<h3>💳 Saldo total wallets</h3>
<p><?= number_format($wallets,2) ?> balas</p>
</div>

<div class="card">
<h3>💸 Volume apostado</h3>
<p><?= number_format($volume,2) ?> balas</p>
</div>

<div class="card">
<h3>🏆 Jackpot</h3>
<p><?= number_format($jackpot,2) ?> balas</p>
</div>

</div>


<h2>🚦 System Health</h2>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">

<div class="card">

<h3>API Football Data</h3>

<?php if($apiStatus=="OK"): ?>
<p style="color:green;">🟢 OK</p>

<?php else: ?>
<p style="color:red;">🔴 ERROR</p>
<?php endif; ?>

</div>


<div class="card">

<h3>Jogos futuros</h3>

<?php if($matchHealth=="OK"): ?>
<p style="color:green;">🟢 OK</p>

<?php elseif($matchHealth=="LOW"): ?>
<p style="color:orange;">🟡 Poucos jogos</p>

<?php else: ?>
<p style="color:red;">🔴 CRÍTICO</p>
<?php endif; ?>

</div>


<div class="card">

<h3>Jogos sem resultado</h3>

<p><?= $missingResults ?></p>

</div>

</div>

<?php require __DIR__ . '/footer.php'; ?>