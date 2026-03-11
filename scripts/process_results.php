<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/ResultService.php';
require_once __DIR__ . '/../app/Services/LoggerService.php';

echo "=== PROCESS RESULTS ===\n";

$start = microtime(true);

try {

    LoggerService::info(
        "cron",
        "process_results_start",
        "cron started"
    );

    $service = new ResultService();

    $processed = $service->processFinishedMatches();

    $runtime = round(microtime(true) - $start, 4);

    echo "Matches processed: " . $processed . "\n";
    echo "Runtime: {$runtime}s\n";

    LoggerService::info(
        "cron",
        "process_results_success",
        "cron finished",
        [
            "processed" => $processed,
            "runtime" => $runtime
        ]
    );

} catch (Exception $e) {

    $runtime = round(microtime(true) - $start, 4);

    LoggerService::error(
        "cron",
        "process_results_error",
        $e->getMessage(),
        [
            "runtime" => $runtime
        ]
    );

    echo "Erro: " . $e->getMessage() . "\n";

}