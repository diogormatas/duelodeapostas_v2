<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/LoggerService.php';

echo "=== CLOSE COUPONS ===\n";

$start = microtime(true);

$db = Database::getConnection();

try {

    LoggerService::info("cron","close_coupons_start","cron started");

    $sql = "
        SELECT c.id
        FROM coupons_v2 c
        WHERE c.status='OPEN'
        AND NOT EXISTS (
            SELECT 1
            FROM coupon_matches_v2 cm
            JOIN matches_v2 m ON m.id = cm.match_id
            WHERE cm.coupon_id = c.id
            AND m.status != 'FINISHED'
        )
    ";

    $result = $db->query($sql);

    $coupons = [];

    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row['id'];
    }

    if (empty($coupons)) {

        LoggerService::info(
            "cron",
            "close_coupons_success",
            "no coupons to close",
            ["processed"=>0]
        );

        exit;

    }

    $processed = 0;

    foreach ($coupons as $couponId) {

        $stmt = $db->prepare("
            UPDATE coupons_v2
            SET status='CLOSED'
            WHERE id=?
        ");

        $stmt->bind_param("i",$couponId);
        $stmt->execute();

        $processed++;

        LoggerService::info(
            "coupons",
            "coupon_closed",
            "coupon closed",
            ["coupon_id"=>$couponId]
        );

    }

    $runtime = round(microtime(true) - $start,4);

    LoggerService::info(
        "cron",
        "close_coupons_success",
        "cron finished",
        [
            "processed"=>$processed,
            "runtime"=>$runtime
        ]
    );

} catch (Exception $e) {

    $runtime = round(microtime(true) - $start,4);

    LoggerService::error(
        "cron",
        "close_coupons_error",
        $e->getMessage(),
        ["runtime"=>$runtime]
    );

}