<?php require __DIR__ . '/layout.php'; ?>

<h1>⚙️ Cron Jobs</h1>

<div class="card">

<table style="width:500px;border-collapse:collapse;">

<tr>
<th style="padding:10px;border-bottom:1px solid #ddd;">Job</th>
<th style="padding:10px;border-bottom:1px solid #ddd;">Last Run</th>
</tr>

<?php foreach($status as $job=>$time): ?>

<tr>
<td style="padding:10px;border-bottom:1px solid #eee;">
<?= htmlspecialchars($job) ?>
</td>

<td style="padding:10px;border-bottom:1px solid #eee;">
<?= htmlspecialchars($time) ?>
</td>
</tr>

<?php endforeach; ?>

</table>

</div>

<?php require __DIR__ . '/footer.php'; ?>