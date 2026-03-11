<?php require __DIR__ . '/layout/header.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Liga Tips <?php echo (int) $month; ?>/<?php echo (int) $year; ?></title>
</head>
<body>

<h1>Liga Tips - <?php echo str_pad((int) $month, 2, '0', STR_PAD_LEFT); ?>/<?php echo (int) $year; ?></h1>

<p>
    Jackpot mensal:
    <strong><?php echo number_format($jackpot['amount'], 2); ?>€</strong>
</p>

<p>
    Estado pagamento:
    <strong><?php echo htmlspecialchars($jackpot['payout_status']); ?></strong>
</p>

<?php if (!empty($jackpot['paid_at'])): ?>
    <p>Pago em: <?php echo htmlspecialchars($jackpot['paid_at']); ?></p>
<?php endif; ?>

<table border="1" cellpadding="10">
    <tr>
        <th>#</th>
        <th>Utilizador</th>
        <th>Score</th>
    </tr>

    <?php $position = 1; ?>
    <?php foreach ($ranking as $row): ?>
        <tr>
            <td><?php echo $position; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo (int) $row['score']; ?></td>
        </tr>
        <?php $position++; ?>
    <?php endforeach; ?>
</table>

<br>

<?php if ($jackpot['payout_status'] !== 'PAID'): ?>
    <a href="/duelo/v2/public/liga-tips/payout/<?php echo (int) $year; ?>/<?php echo (int) $month; ?>">
        Pagar jackpot mensal
    </a>
<?php endif; ?>

<br><br>
<a href="/duelo/v2/public/dashboard">← Voltar</a>

</body>
</html>

<?php require __DIR__ . '/layout/footer.php'; ?>