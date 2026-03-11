<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/PrizeService.php';
require_once __DIR__ . '/../app/Services/LoggerService.php';

echo "=== AUTO SETTLE ===\n";

$start = microtime(true);

$db = Database::getConnection();

try {

    LoggerService::info("cron","auto_settle_start","cron started");

    $sql = "
        SELECT id
        FROM coupons_v2
        WHERE status='CLOSED'
        AND prize_status='PENDING'
    ";

    $result = $db->query($sql);

    $coupons = [];

    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row['id'];
    }

    if (empty($coupons)) {

        echo "Nenhum cup«ªo para settlement.\n";

        LoggerService::info(
            "cron",
            "auto_settle_success",
            "no coupons to settle",
            ["processed"=>0]
        );

        exit;

    }

    $prizeService = new PrizeService();

    $processed = 0;

    foreach ($coupons as $couponId) {

        echo "Settling coupon ID: $couponId\n";

        try {

            $prizeService->settleCoupon($couponId);

            $processed++;

            LoggerService::info(
                "settlement",
                "coupon_settled",
                "coupon settled",
                ["coupon_id"=>$couponId]
            );

        } catch (Exception $e) {

            LoggerService::error(
                "settlement",
                "coupon_settle_error",
                $e->getMessage(),
                ["coupon_id"=>$couponId]
            );

        }

    }

    $runtime = round(microtime(true) - $start,4);

    LoggerService::info(
        "cron",
        "auto_settle_success",
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
        "auto_settle_error",
        $e->getMessage(),
        ["runtime"=>$runtime]
    );

    echo "Erro geral: ".$e->getMessage()."\n";

}