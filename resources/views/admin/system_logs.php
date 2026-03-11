<?php require __DIR__ . '/layout.php'; ?>

<h1>📜 System Logs</h1>

<div class="card">

<table style="width:100%;border-collapse:collapse;">

<tr>
<th>ID</th>
<th>Category</th>
<th>Action</th>
<th>Message</th>
<th>Level</th>
<th>Date</th>
</tr>

<?php foreach($logs as $log): ?>

<tr>

<td><?= $log['id'] ?></td>

<td><?= htmlspecialchars($log['category']) ?></td>

<td><?= htmlspecialchars($log['action']) ?></td>

<td><?= htmlspecialchars($log['message']) ?></td>

<td style="
font-weight:bold;
color:
<?php
if($log['level']=='ERROR') echo 'red';
elseif($log['level']=='WARNING') echo 'orange';
else echo 'green';
?>
">

<?= $log['level'] ?>

</td>

<td><?= $log['created_at'] ?></td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php require __DIR__ . '/footer.php'; ?>