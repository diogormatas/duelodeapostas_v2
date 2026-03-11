<?php require __DIR__ . '/layout.php'; ?>

<h1>💰 Platform Economy</h1>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px">

<div class="card">
<h3>Wallet Supply</h3>
<p><?= number_format($walletSupply,2) ?> balas</p>
</div>

<div class="card">
<h3>Total Bets</h3>
<p><?= $totalBets ?></p>
</div>

<div class="card">
<h3>Bet Volume</h3>
<p><?= number_format($volume,2) ?> balas</p>
</div>

<div class="card">
<h3>Open Coupons</h3>
<p><?= $openCoupons ?></p>
</div>

<div class="card">
<h3>Pending Duels</h3>
<p><?= $pendingDuels ?></p>
</div>

<div class="card">
<h3>Monthly Jackpot</h3>
<p><?= number_format($jackpot,2) ?> balas</p>
</div>

</div>

<?php require __DIR__ . '/footer.php'; ?>