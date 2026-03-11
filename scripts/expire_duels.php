<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/LoggerService.php';

$config = require __DIR__ . '/../config/app.php';

$db = Database::getConnection();

$start = microtime(true);

$hours = (int)$config['duels']['pending_expiration_hours'];

echo "=== EXPIRE DUELS ===\n";

try {

    LoggerService::info("cron","expire_duels_start","cron started");

    $sql = "
        UPDATE duels_v2
        SET status='CANCELLED'
        WHERE status='PENDING'
        AND created_at < NOW() - INTERVAL {$hours} HOUR
    ";

    $db->query($sql);

    $affected = $db->affected_rows;

    echo "Expired duels: ".$affected."\n";

    $runtime = round(microtime(true) - $start,4);

    LoggerService::info(
        "cron",
        "expire_duels_success",
        "cron finished",
        [
            "expired"=>$affected,
            "runtime"=>$runtime
        ]
    );

} catch (Exception $e) {

    $runtime = round(microtime(true) - $start,4);

    LoggerService::error(
        "cron",
        "expire_duels_error",
        $e->getMessage(),
        ["runtime"=>$runtime]
    );

}