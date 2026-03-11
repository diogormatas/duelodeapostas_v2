<?php require __DIR__ . '/layout.php'; ?>

<h1>📥 Import Football Data</h1>

<div class="card">

<form method="POST" action="<?= $base ?>/admin/import/competitions">
<button type="submit">Import Competitions</button>
</form>

<br>

<form method="POST" action="<?= $base ?>/admin/import/teams">
<button type="submit">Import Teams</button>
</form>

<br>

<form method="POST" action="<?= $base ?>/admin/import/matches">
<button type="submit">Import Matches</button>
</form>

</div>

<?php require __DIR__ . '/footer.php'; ?>