<?php require __DIR__ . '/layout.php'; ?>

<h1>🎟 Coupon Manager</h1>

<div class="card">

<table style="width:100%;border-collapse:collapse;font-size:14px;">

<tr style="background:#eee">

<th>ID</th>
<th>Type</th>
<th>Entry</th>
<th>Players</th>
<th>Matches</th>
<th>Pool</th>
<th>Status</th>
<th>Prize</th>
<th>Created</th>
<th>Actions</th>

</tr>

<?php if(empty($coupons)): ?>

<tr>
<td colspan="10" style="text-align:center;padding:20px;">
No coupons found
</td>
</tr>

<?php endif; ?>


<?php foreach($coupons as $c): ?>

<?php

$statusColor="black";

if($c['status']=="OPEN") $statusColor="green";
if($c['status']=="CLOSED") $statusColor="orange";
if($c['status']=="SETTLED") $statusColor="blue";

$capacity=0;

if(isset($c['max_players']) && $c['max_players']>0){
    $capacity = round(($c['players']/$c['max_players'])*100);
}

?>

<tr style="border-top:1px solid #ddd">

<td>#<?= $c['id'] ?></td>

<td><?= htmlspecialchars($c['type']) ?></td>

<td><?= number_format($c['entry_price'],2) ?></td>

<td>

<?= $c['players'] ?>

<?php if(isset($c['max_players'])): ?>

/ <?= $c['max_players'] ?>

<div style="background:#eee;height:6px;border-radius:3px;margin-top:4px;">

<div style="width:<?= $capacity ?>%;background:#2a5298;height:6px;border-radius:3px"></div>

</div>

<?php endif; ?>

</td>

<td><?= $c['matches'] ?></td>

<td>

<strong>
<?= number_format($c['pool'],2) ?>
</strong>

</td>

<td>

<span style="color:<?= $statusColor ?>;font-weight:bold">
<?= $c['status'] ?>
</span>

</td>

<td><?= $c['prize_status'] ?></td>

<td style="font-size:12px">

<?= $c['created_at'] ?>

</td>

<td>

<a href="<?= $base ?>/coupon/<?= $c['id'] ?>" target="_blank">
view
</a>

<?php if($c['status']=="OPEN"): ?>

 | <a href="<?= $base ?>/admin/coupons/close/<?= $c['id'] ?>">close</a>

<?php endif; ?>

<?php if($c['status']=="CLOSED"): ?>

 | <a href="<?= $base ?>/admin/coupons/settle/<?= $c['id'] ?>">settle</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php require __DIR__ . '/footer.php'; ?>