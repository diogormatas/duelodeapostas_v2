<?php require __DIR__.'/layout.php'; ?>

<h1>System Health</h1>

<div class="card">

<h2>Database</h2>

<p>Status: <?= $dbStatus ?></p>

</div>


<div class="card">

<h2>Cron jobs</h2>

<table>

<tr>
<th>Job</th>
<th>Last run</th>
</tr>

<?php foreach($cron as $job=>$time): ?>

<tr>
<td><?= $job ?></td>
<td><?= $time ?></td>
</tr>

<?php endforeach; ?>

</table>

</div>


<div class="card">

<h2>Matches</h2>

<p>Future matches: <?= $futureMatches ?></p>

<p>Missing results: <?= $missingResults ?></p>

</div>


<div class="card">

<h2>Errors</h2>

<p>Errors last 24h: <?= $errors24h ?></p>

</div>

<?php require __DIR__.'/footer.php'; ?>