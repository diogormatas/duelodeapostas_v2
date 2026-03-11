<?php $base = "/duelo/v2/public"; ?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Coupons</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
    padding:20px;
}

table{
    border-collapse:collapse;
    width:100%;
    background:white;
}

th,td{
    padding:10px;
    border:1px solid #ddd;
    text-align:left;
}

th{
    background:#333;
    color:white;
}

a{
    text-decoration:none;
    font-weight:bold;
}

.button{
    padding:4px 8px;
    background:#333;
    color:white;
    border-radius:4px;
}

</style>

</head>

<body>

<h2>Admin Coupons</h2>

<table>

<tr>
<th>ID</th>
<th>Type</th>
<th>Entry</th>
<th>Players</th>
<th>Pool</th>
<th>Status</th>
<th>Prize</th>
<th>Actions</th>
</tr>

<?php foreach($coupons as $c): ?>

<tr>

<td><?= $c['id'] ?></td>

<td><?= htmlspecialchars($c['type']) ?></td>

<td><?= number_format($c['entry_price'],2) ?></td>

<td><?= $c['players'] ?: 0 ?></td>

<td><?= number_format($c['pool'] ?: 0,2) ?></td>

<td><?= htmlspecialchars($c['status']) ?></td>

<td><?= htmlspecialchars($c['prize_status']) ?></td>

<td>

<a href="<?= $base ?>/coupon/<?= $c['id'] ?>">View</a> |

<a href="<?= $base ?>/coupon/<?= $c['id'] ?>/ranking">Ranking</a>

<?php if($c['status'] === 'CLOSED' && $c['prize_status'] === 'PENDING'): ?>

 | <a class="button" href="<?= $base ?>/settle/<?= $c['id'] ?>">Settle</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>