<?php
$config = require __DIR__ . '/../../../config/app.php';
$base = $config['base_url'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user_id'] ?? null;
$notificationCount = $_SESSION['notification_count'] ?? 0;
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Duelo de Apostas</title>

<style>

body{
font-family:Arial;
background:#f5f5f5;
margin:0;
}

.header{
background:#222;
color:white;
padding:14px 20px;
display:flex;
justify-content:space-between;
align-items:center;
flex-wrap:wrap;
}

.logo a{
color:white;
text-decoration:none;
font-weight:bold;
font-size:18px;
}

.menu{
display:flex;
gap:18px;
align-items:center;
flex-wrap:wrap;
}

.menu a{
color:white;
text-decoration:none;
font-weight:bold;
opacity:0.85;
}

.menu a:hover{
opacity:1;
}

.menu a.active{
border-bottom:2px solid #fff;
padding-bottom:2px;
}

.container{
max-width:1100px;
margin:auto;
padding:20px;
}

.notification{
background:#e53935;
padding:2px 6px;
border-radius:10px;
font-size:12px;
margin-left:4px;
}

.admin-link{
color:#ffd54f;
}

</style>

</head>

<body>

<div class="header">

<div class="logo">
<a href="<?= $base ?>/dashboard">Duelo de Apostas</a>
</div>

<div class="menu">

<a class="<?= strpos($currentPath,'/dashboard')!==false ? 'active':'' ?>" href="<?= $base ?>/dashboard">
🏠 Dashboard
</a>

<a class="<?= strpos($currentPath,'/bets')!==false ? 'active':'' ?>" href="<?= $base ?>/bets">
🎯 Apostas
</a>

<a class="<?= strpos($currentPath,'ranking')!==false ? 'active':'' ?>" href="<?= $base ?>/duels/ranking-weekly">
🏆 Ranking
</a>

<a class="<?= strpos($currentPath,'/notifications')!==false?'active':'' ?>" href="<?= $base ?>/notifications">
🔔
<?php if($notificationCount > 0): ?>
<span class="notification"><?= $notificationCount ?></span>
<?php endif; ?>
</a>

<?php if($currentUser): ?>

<a class="<?= strpos($currentPath,'/account')!==false?'active':'' ?>" href="<?= $base ?>/account">
👤 A minha conta
</a>

<?php endif; ?>

<?php if($isAdmin): ?>

<a class="admin-link <?= strpos($currentPath,'/admin')!==false?'active':'' ?>" href="<?= $base ?>/admin">
⚙ Admin
</a>

<?php endif; ?>

<a href="<?= $base ?>/logout">Sair</a>

</div>

</div>

<div class="container">