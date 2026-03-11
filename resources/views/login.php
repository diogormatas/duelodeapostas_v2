<?php

$config = require __DIR__ . '/../../config/app.php';
$base = $config['base_url'];

?>

<!DOCTYPE html>
<html>

<head>

<title>Login - Duelo de Apostas</title>

<style>

body{
font-family:Arial;
background:linear-gradient(135deg,#1e3c72,#2a5298);
height:100vh;
display:flex;
align-items:center;
justify-content:center;
margin:0;
}

.card{
background:white;
padding:40px;
border-radius:10px;
width:320px;
box-shadow:0 8px 25px rgba(0,0,0,0.2);
text-align:center;
}

.logo{
font-size:22px;
font-weight:bold;
margin-bottom:25px;
color:#2a5298;
}

input{
width:100%;
padding:10px;
margin-top:6px;
margin-bottom:16px;
border-radius:6px;
border:1px solid #ddd;
font-size:14px;
}

button{
width:100%;
padding:12px;
border:none;
border-radius:6px;
background:#2a5298;
color:white;
font-weight:bold;
font-size:15px;
cursor:pointer;
}

button:hover{
background:#1e3c72;
}

.link{
margin-top:15px;
display:block;
font-size:14px;
}

.link a{
color:#2a5298;
text-decoration:none;
font-weight:bold;
}

.error{
color:red;
margin-bottom:15px;
font-size:14px;
}

</style>

</head>

<body>

<div class="card">

<div class="logo">
⚔️ Duelo de Apostas
</div>

<?php if(!empty($error)): ?>

<div class="error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<form method="POST" action="<?= $base ?>/login">

<label>Username</label>
<input type="text" name="username" required>

<label>Password</label>
<input type="password" name="password" required>

<button type="submit">Entrar</button>

</form>

<div class="link">
Ainda não tens conta?
<br>
<a href="<?= $base ?>/register">Criar conta</a>
</div>

</div>

</body>

</html>