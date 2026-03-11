<?php require __DIR__ . '/layout/header.php'; ?>

<?php
$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];

function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return "agora mesmo";
    if ($diff < 3600) return floor($diff/60)." min";
    if ($diff < 86400) return floor($diff/3600)." h";
    return floor($diff/86400)." d";
}

function avatar($username){
    return "https://www.gravatar.com/avatar/".md5(strtolower($username))."?s=80&d=identicon";
}
?>

<style>

.card{
background:white;
padding:16px;
border-radius:8px;
margin-bottom:12px;
box-shadow:0 2px 5px rgba(0,0,0,0.08);
display:flex;
gap:12px;
align-items:flex-start;
}

.avatar img{
width:42px;
height:42px;
border-radius:50%;
}

.content{
flex:1;
}

.meta{
color:#666;
font-size:13px;
margin-top:6px;
}

.button{
display:inline-block;
background:#1565c0;
color:white;
padding:8px 12px;
border-radius:5px;
text-decoration:none;
font-weight:bold;
margin-top:8px;
}

.username{
font-weight:bold;
color:#1565c0;
text-decoration:none;
}

</style>


<h1>🔥 Atividade</h1>

<?php foreach($activities as $a): ?>

<div class="card">

<div class="avatar">
<img src="<?= avatar($a['actor']) ?>">
</div>

<div class="content">

<?php if($a['type'] === 'duel_created'): ?>

<div>

⚔️
<a class="username" href="<?= $base ?>/user/<?= urlencode($a['actor']) ?>">
<?= htmlspecialchars($a['actor']) ?>
</a>

criou um desafio de

<strong><?= number_format($a['stake'],2) ?> balas</strong>

</div>

<?php elseif($a['type'] === 'duel_accepted'): ?>

<div>

⚔️
<a class="username" href="<?= $base ?>/user/<?= urlencode($a['target']) ?>">
<?= htmlspecialchars($a['target']) ?>
</a>

aceitou o desafio de

<a class="username" href="<?= $base ?>/user/<?= urlencode($a['actor']) ?>">
<?= htmlspecialchars($a['actor']) ?>
</a>

</div>

<?php endif; ?>

<div class="meta">
<?= timeAgo($a['activity_date']) ?>
</div>

<?php if(!empty($a['coupon_id'])): ?>

<a class="button" href="<?= $base ?>/coupon/<?= $a['coupon_id'] ?>">
Ver duelo
</a>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

<br>

<a href="<?= $base ?>/dashboard">← Voltar</a>

<?php require __DIR__ . '/layout/footer.php'; ?>