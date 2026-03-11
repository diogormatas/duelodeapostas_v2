<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
    padding:30px;
}

h1{
    margin-bottom:30px;
}

.grid{
    display:grid;
    grid-template-columns:repeat(2,300px);
    gap:20px;
}

.card{

    background:white;
    padding:20px;
    border-radius:6px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);

}

.card a{
    text-decoration:none;
    font-weight:bold;
    color:#333;
    display:block;
}

</style>

</head>

<body>

<h1>Admin Dashboard</h1>

<div class="grid">

<div class="card">
<a href="/admin/import">
📥 Import Football Data
</a>
</div>

<div class="card">
<a href="/admin/system-logs">
📜 System Logs
</a>
</div>

<div class="card">
<a href="/admin/cron-status">
⚙️ Cron Status
</a>
</div>

<div class="card">
<a href="/coupons">
🎟️ Ver Cupões
</a>
</div>

<div class="card">
<a href="/liga-tips/<?= date('Y') ?>/<?= date('n') ?>">
🏆 Liga Tips Atual
</a>
</div>

<div class="card">
<a href="/admin/coupons">
🎟️ Admin Coupons
</a>
</div>

</div>

</body>
</html>