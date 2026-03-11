<?php require __DIR__ . '/layout/header.php'; ?>

<h1>⚔️ Criar Duelo</h1>

<form method="POST" action="<?= $base ?>/duel/store">

<h3>💰 Stake</h3>

<div style="margin-bottom:20px">

<?php foreach([2,5,10,20] as $s): ?>

<label style="margin-right:15px">

<input type="radio" name="stake_option" value="<?= $s ?>" required>

<?= $s ?> balas

</label>

<?php endforeach; ?>

<br><br>

<label>
<input type="radio" name="stake_option" value="custom">
Personalizado
</label>

<input type="number" name="stake_custom" step="1" min="1" placeholder="valor">

</div>


<h3>🔒 Tipo de duelo</h3>

<label>
<input type="radio" name="visibility" value="PUBLIC" checked>
Público
</label>

<label style="margin-left:15px">
<input type="radio" name="visibility" value="PRIVATE">
Privado
</label>


<br><br>

<button type="button" onclick="generateMatches()" style="
background:#1976d2;
color:white;
padding:10px 18px;
border:none;
border-radius:6px;
cursor:pointer;
">

Gerar jogos

</button>

<br><br>

<div id="matches">

<?php foreach($matches as $m): ?>

<label style="
display:flex;
align-items:center;
margin-bottom:10px;
padding:10px;
border:1px solid #eee;
border-radius:8px;
cursor:pointer;
">

<input type="checkbox" name="matches[]" value="<?= $m['id'] ?>" style="margin-right:10px">

<img src="<?= $m['home_logo'] ?? '' ?>" style="height:22px;margin-right:6px">

<strong><?= htmlspecialchars($m['home_team']) ?></strong>

&nbsp;vs&nbsp;

<img src="<?= $m['away_logo'] ?? '' ?>" style="height:22px;margin-right:6px">

<strong><?= htmlspecialchars($m['away_team']) ?></strong>

<div style="margin-left:auto;font-size:12px;color:#666">

<?= $m['competition'] ?>

•

<?= date("d M H:i",strtotime($m['scheduled_at'])) ?>

</div>

</label>

<?php endforeach; ?>

</div>


<br>

<button type="submit" style="
background:#43a047;
color:white;
padding:12px 22px;
border:none;
border-radius:6px;
font-weight:bold;
cursor:pointer;
">

Criar desafio

</button>

</form>


<script>

async function generateMatches(){

const res = await fetch("<?= $base ?>/duel/generate-matches?count=10");

const data = await res.json();

let html="";

data.matches.forEach(m=>{

html+=`

<label style="
display:flex;
align-items:center;
margin-bottom:10px;
padding:10px;
border:1px solid #eee;
border-radius:8px;
">

<input type="checkbox" name="matches[]" value="${m.id}" style="margin-right:10px">

<strong>${m.home}</strong>

&nbsp;vs&nbsp;

<strong>${m.away}</strong>

<div style="margin-left:auto;font-size:12px;color:#666">

${m.competition}

•

${m.date}

</div>

</label>

`;

});

document.getElementById("matches").innerHTML=html;

}

</script>


<?php require __DIR__ . '/layout/footer.php'; ?>