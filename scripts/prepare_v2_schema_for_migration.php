<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

echo "========================================\n";
echo "PREPARE V2 SCHEMA FOR MIGRATION\n";
echo "========================================\n\n";

function hasColumn(mysqli $db, string $table, string $column): bool
{
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);

    $sql = "
        SHOW COLUMNS
        FROM `{$table}`
        LIKE '{$column}'
    ";

    $res = $db->query($sql);

    return $res instanceof mysqli_result && $res->num_rows > 0;
}

function addColumnIfMissing(mysqli $db, string $table, string $column, string $definition): void
{
    if (hasColumn($db, $table, $column)) {
        echo "[SKIP] {$table}.{$column} already exists\n";
        return;
    }

    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
    if (!$db->query($sql)) {
        throw new RuntimeException("Failed altering {$table}.{$column}: " . $db->error);
    }

    echo "[OK] Added {$table}.{$column}\n";
}

$db->begin_transaction();

try {
    $tablesWithUpdatedAt = [
        'users_v2',
        'wallets_v2',
        'transactions_v2',
        'coupons_v2',
        'coupon_matches_v2',
        'bets_v2',
        'bet_picks_v2',
        'teams_v2',
        'matches_v2',
        'competitions_v2',
        'notifications_v2',
        'system_logs_v2',
    ];

    foreach ($tablesWithUpdatedAt as $table) {
        addColumnIfMissing(
            $db,
            $table,
            'updated_at',
            "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
    }

    $tablesWithSource = [
        'users_v2',
        'wallets_v2',
        'transactions_v2',
        'coupons_v2',
        'coupon_matches_v2',
        'bets_v2',
        'bet_picks_v2',
        'teams_v2',
        'matches_v2',
    ];

    foreach ($tablesWithSource as $table) {
        addColumnIfMissing(
            $db,
            $table,
            'source_system',
            "VARCHAR(20) NULL DEFAULT 'v2'"
        );
    }

    addColumnIfMissing(
        $db,
        'teams_v2',
        'legacy_v1_id',
        "INT NULL UNIQUE"
    );

    addColumnIfMissing(
        $db,
        'matches_v2',
        'legacy_v1_id',
        "INT NULL UNIQUE"
    );

    addColumnIfMissing(
        $db,
        'transactions_v2',
        'legacy_type',
        "VARCHAR(30) NULL"
    );

    // Expand enum only if needed
    $sql = "SHOW COLUMNS FROM `transactions_v2` LIKE 'type'";
    $res = $db->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    $typeDef = $row['Type'] ?? '';

    $needed = [
        'deposit',
        'bonus',
        'withdraw',
        'bet',
        'refund',
        'prize',
        'jackpot',
        'jackpot_contribution',
        'jackpot_payout',
    ];

    $missingEnum = false;
    foreach ($needed as $v) {
        if (strpos($typeDef, "'" . $v . "'") === false) {
            $missingEnum = true;
            break;
        }
    }

    if ($missingEnum) {
        $alter = "
            ALTER TABLE `transactions_v2`
            MODIFY COLUMN `type`
            ENUM(
                'deposit',
                'bonus',
                'withdraw',
                'bet',
                'refund',
                'prize',
                'jackpot',
                'jackpot_contribution',
                'jackpot_payout'
            ) NULL
        ";
        if (!$db->query($alter)) {
            throw new RuntimeException("Failed altering transactions_v2.type enum: " . $db->error);
        }
        echo "[OK] Expanded transactions_v2.type enum\n";
    } else {
        echo "[SKIP] transactions_v2.type enum already compatible\n";
    }

    $db->commit();

    echo "\n========================================\n";
    echo "SCHEMA PREP COMPLETE\n";
    echo "========================================\n";

} catch (Throwable $e) {
    $db->rollback();
    echo "[FAIL] " . $e->getMessage() . "\n";
}