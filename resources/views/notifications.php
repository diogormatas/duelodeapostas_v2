<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];
?>

<style>

.card{
background:white;
padding:16px;
border-radius:8px;
margin-bottom:12px;
box-shadow:0 2px 5px rgba(0,0,0,0.08);
}

.meta{
font-size:12px;
color:#777;
margin-top:8px;
}

.button{
background:#1565c0;
color:white;
padding:6px 10px;
border-radius:5px;
text-decoration:none;
font-weight:bold;
display:inline-block;
margin-top:8px;
}

</style>


<h1>🔔 Notificações</h1>


<?php if(empty($notifications)): ?>

<p>Sem notificações.</p>

<?php else: ?>


<?php foreach($notifications as $n): ?>

<div class="card">

<?php

$data = $n['data'] ?? [];

if($n['type'] === "DUEL_CHALLENGE"){

echo "⚔️ Foste desafiado para um duelo";

echo "<br>";

echo "<a class='button' href='{$base}/duels'>Ver desafios</a>";

}

?>

<div class="meta">
<?= htmlspecialchars($n['created_at']) ?>
</div>

</div>

<?php endforeach; ?>


<?php endif; ?>


<br>

<a href="<?= $base ?>/dashboard">← Voltar</a>


<?php require __DIR__ . '/layout/footer.php'; ?>