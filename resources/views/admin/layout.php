<?php

$config = require __DIR__ . '/../../../config/app.php';
$base = $config['base_url'];

$currentPath = $_SERVER['REQUEST_URI'] ?? '';

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">
<title>Admin</title>

<style>

body{
margin:0;
font-family:Arial;
background:#f4f6f8;
}

.wrapper{
display:flex;
min-height:100vh;
}

.sidebar{
width:220px;
background:#1f2933;
color:white;
padding-top:20px;
}

.sidebar h2{
text-align:center;
margin-bottom:20px;
}

.sidebar a{
display:block;
padding:12px 20px;
color:white;
text-decoration:none;
}

.sidebar a:hover{
background:#2c3e50;
}

.sidebar a.active{
background:#34495e;
}

.main{
flex:1;
padding:30px;
}

.card{
background:white;
padding:20px;
border-radius:6px;
margin-bottom:20px;
box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

</style>

</head>

<body>

<div class="wrapper">

<div class="sidebar">

<h2>⚙ Admin</h2>


<a class="<?= ($currentPath == $base.'/admin' || strpos($currentPath,'/admin/system')!==false)?'active':'' ?>" href="<?= $base ?>/admin/system">
Sistema
</a>


<a class="<?= strpos($currentPath,'/admin/coupons')!==false?'active':'' ?>" href="<?= $base ?>/admin/coupons">
Cupões
</a>

<a class="<?= strpos($currentPath,'/admin/economy')!==false?'active':'' ?>" href="<?= $base ?>/admin/economy">
Economy
</a>

<a class="<?= strpos($currentPath,'/admin/health')!==false?'active':'' ?>" href="<?= $base ?>/admin/health">
Health
</a>

<a class="<?= strpos($currentPath,'/admin/import')!==false?'active':'' ?>" href="<?= $base ?>/admin/import">
Import API
</a>

<a class="<?= strpos($currentPath,'/admin/system-logs')!==false?'active':'' ?>" href="<?= $base ?>/admin/system-logs">
Logs
</a>

<a class="<?= strpos($currentPath,'/admin/cron-status')!==false?'active':'' ?>" href="<?= $base ?>/admin/cron-status">
Cron Jobs
</a>



<hr>

<a href="<?= $base ?>/dashboard">
← Voltar ao site
</a>

</div>

<div class="main">