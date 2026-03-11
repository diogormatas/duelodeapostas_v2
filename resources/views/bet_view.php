<!DOCTYPE html>
<html>

<head>
<title>Picks</title>
</head>

<body>

<h1>Picks da aposta</h1>

<table border="1" cellpadding="10">

<tr>
<th>Jogo</th>
<th>Pick</th>
</tr>

<?php foreach ($picks as $p): ?>

<tr>

<td>
<?php echo htmlspecialchars($p['home']); ?>
vs
<?php echo htmlspecialchars($p['away']); ?>
</td>

<td>
<?php echo $p['pick']; ?>
</td>

</tr>

<?php endforeach; ?>

</table>

<br>

<a href="javascript:history.back()">← Voltar</a>

</body>

</html>