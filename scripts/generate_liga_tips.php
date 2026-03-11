<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/LoggerService.php';

$config = require __DIR__ . '/../config/app.php';

$db = Database::getConnection();

echo "=== GERAR LIGA TIPS ===\n";

try {

    $db->begin_transaction();

    $matchesLimit = $config['liga_tips_matches'];
    $entryPrice = $config['liga_tips_entry_price'];
    $maxPlayers = $config['liga_tips_max_players'];
    $closeMinutes = (int)$config['coupon_close_minutes_before_match'];

    $stmt = $db->prepare("
        SELECT id, scheduled_at
        FROM matches_v2
        WHERE scheduled_at > NOW()
        AND status = 'SCHEDULED'
        ORDER BY scheduled_at ASC
        LIMIT ?
    ");

    $stmt->bind_param("i", $matchesLimit);
    $stmt->execute();

    $stmt->bind_result($matchId, $scheduledAt);

    $matches = [];

    while ($stmt->fetch()) {
        $matches[] = [
            'id' => $matchId,
            'scheduled_at' => $scheduledAt
        ];
    }

    $stmt->close();

    $matchesCount = count($matches);

    echo "Jogos encontrados: $matchesCount\n";

    if ($matchesCount == 0) {

        LoggerService::warning(
            'coupons',
            'generate_liga_tips',
            'No matches found for Liga Tips'
        );

        $db->rollback();
        exit;
    }

    $year = date('Y');
    $month = date('n');

    $stmt = $db->prepare("
        SELECT id
        FROM coupons_v2
        WHERE type='LIGA_TIPS'
        AND YEAR(created_at)=?
        AND MONTH(created_at)=?
        LIMIT 1
    ");

    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();

    $stmt->bind_result($existingCouponId);
    $stmt->fetch();

    $stmt->close();

    if ($existingCouponId) {

        echo "Liga Tips já existe para este mês (ID $existingCouponId)\n";

        LoggerService::info(
            'coupons',
            'generate_liga_tips',
            'Liga Tips already exists',
            ['coupon_id' => $existingCouponId]
        );

        $db->rollback();
        exit;
    }

    $firstMatchDate = $matches[0]['scheduled_at'];
    $bettingClosesAt = date(
        'Y-m-d H:i:s',
        strtotime($firstMatchDate . " -{$closeMinutes} minutes")
    );

    $stmt = $db->prepare("
        INSERT INTO coupons_v2
        (type, entry_price, max_players, status, prize_status, visibility, betting_closes_at, created_at)
        VALUES ('LIGA_TIPS', ?, ?, 'OPEN', 'PENDING', 'PUBLIC', ?, NOW())
    ");

    $stmt->bind_param("dis", $entryPrice, $maxPlayers, $bettingClosesAt);
    $stmt->execute();

    $couponId = $db->insert_id;

    echo "Cupão criado ID: $couponId\n";

    $stmt->close();

    $stmt = $db->prepare("
        INSERT INTO coupon_matches_v2 (coupon_id, match_id)
        VALUES (?,?)
    ");

    foreach ($matches as $match) {

        $matchId = $match['id'];

        $stmt->bind_param("ii", $couponId, $matchId);
        $stmt->execute();

    }

    $stmt->close();

    $db->commit();

    echo "Liga Tips criada com $matchesCount jogos.\n";

    LoggerService::info(
        'coupons',
        'generate_liga_tips',
        'Liga Tips created',
        [
            'coupon_id' => $couponId,
            'matches' => $matchesCount,
            'betting_closes_at' => $bettingClosesAt
        ]
    );

} catch (Exception $e) {

    $db->rollback();

    LoggerService::error(
        'coupons',
        'generate_liga_tips',
        $e->getMessage()
    );

    echo "Erro: " . $e->getMessage() . "\n";
}