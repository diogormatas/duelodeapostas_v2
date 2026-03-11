<?php require __DIR__ . '/layout.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<style>

h1{
margin-bottom:20px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
border-radius:6px;
overflow:hidden;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

th{
background:#222;
color:white;
padding:12px;
text-align:left;
}

td{
padding:12px;
border-bottom:1px solid #eee;
}

tr:hover{
background:#fafafa;
}

.button{
background:#1565c0;
color:white;
padding:6px 10px;
border-radius:4px;
text-decoration:none;
font-weight:bold;
}

.price{
font-weight:bold;
}

</style>


<h1>🎟️ Cupões disponíveis</h1>


<table>

<tr>
<th>ID</th>
<th>Nome</th>
<th>Preço</th>
<th>Vagas</th>
<th>Ação</th>
</tr>

<?php foreach($coupons as $c): ?>

<tr>

<td>
<?= (int)$c['id'] ?>
</td>

<td>
<?= htmlspecialchars($c['name'] ?? 'Duelo') ?>
</td>

<td class="price">
<?= number_format($c['entry_price'],2) ?>€
</td>

<td>
<?= $c['players'] ?? 0 ?> / <?= $c['max_players'] ?>
</td>

<td>

<a class="button" href="<?= $base ?>/coupon/<?= $c['id'] ?>">
Ver Cupão
</a>

</td>

</tr>

<?php endforeach; ?>

</table>

<br>

<a href="<?= $base ?>/dashboard">← Voltar</a>


<?php require __DIR__ . '/layout/footer.php'; ?>

</div>
</div>
</body>
</html>