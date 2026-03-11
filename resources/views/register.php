<!DOCTYPE html>
<html>

<head>

<title>Registo - Duelo de Apostas</title>

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
width:340px;
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

</style>

<script>

function validateForm(){

    const pass = document.getElementById("password").value;
    const confirm = document.getElementById("password_confirm").value;

    if(pass !== confirm){
        alert("As passwords não coincidem.");
        return false;
    }

    return true;

}

</script>

</head>

<body>

<div class="card">

<div class="logo">
⚔️ Duelo de Apostas
</div>

<form method="POST" action="/duelo/v2/public/register" onsubmit="return validateForm()">

<label>Username</label>
<input type="text" name="username" required>

<label>Email</label>
<input type="email" name="email">

<label>Password</label>
<input type="password" id="password" name="password" required>

<label>Confirmar Password</label>
<input type="password" id="password_confirm" name="password_confirm" required>

<button type="submit">Criar conta</button>

</form>

<div class="link">
Já tens conta?
<br>
<a href="/duelo/v2/public/login">Entrar</a>
</div>

</div>

</body>

</html>