<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

echo "========================================\n";
echo "V1 -> V2 MIGRATION AUDIT (READ ONLY)\n";
echo "========================================\n";
echo "SAFETY: THIS SCRIPT DOES NOT WRITE ANYTHING\n";
echo "WRITE QUERIES USED: 0\n\n";

$v1Tables = [
    'apostadores',
    'apostas',
    'cupoes',
    'equipas',
    'equipas_api',
    'jogos',
    'transaccoes',
];

$v2Tables = [
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
];

function safeIdent(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException("Unsafe identifier: {$name}");
    }
    return "`{$name}`";
}

function tableExists(mysqli $db, string $table): bool
{
    $tableEsc = $db->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '{$tableEsc}'";
    $res = $db->query($sql);
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

function getColumns(mysqli $db, string $table): array
{
    $cols = [];
    $sql = "SHOW COLUMNS FROM " . safeIdent($table);
    $res = $db->query($sql);

    if (!$res) {
        return $cols;
    }

    while ($row = $res->fetch_assoc()) {
        $cols[] = $row;
    }

    return $cols;
}

function getColumnNames(mysqli $db, string $table): array
{
    $out = [];
    foreach (getColumns($db, $table) as $col) {
        $out[] = $col['Field'];
    }
    return $out;
}

function hasColumn(mysqli $db, string $table, string $column): bool
{
    return in_array($column, getColumnNames($db, $table), true);
}

function rowCount(mysqli $db, string $table): int
{
    $sql = "SELECT COUNT(*) c FROM " . safeIdent($table);
    $res = $db->query($sql);

    if (!$res) {
        return -1;
    }

    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function printHeader(string $title): void
{
    echo "----------------------------------------\n";
    echo $title . "\n";
    echo "----------------------------------------\n";
}

function printColumns(mysqli $db, string $table): void
{
    echo $table . ":\n";
    $cols = getColumns($db, $table);

    if (!$cols) {
        echo "  (no columns or table missing)\n";
        return;
    }

    foreach ($cols as $col) {
        echo "  - {$col['Field']} | {$col['Type']} | Null={$col['Null']} | Key={$col['Key']} | Default=";
        echo ($col['Default'] === null ? 'NULL' : $col['Default']);
        echo "\n";
    }
}

function printSample(mysqli $db, string $table, int $limit = 3): void
{
    echo $table . " sample:\n";
    $sql = "SELECT * FROM " . safeIdent($table) . " LIMIT " . (int)$limit;
    $res = $db->query($sql);

    if (!$res) {
        echo "  (query failed)\n";
        return;
    }

    if ($res->num_rows === 0) {
        echo "  (empty)\n";
        return;
    }

    while ($row = $res->fetch_assoc()) {
        echo "  - " . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}

function distinctNotNullCount(mysqli $db, string $table, string $column): int
{
    $sql = "SELECT COUNT(DISTINCT " . safeIdent($column) . ") c
            FROM " . safeIdent($table) . "
            WHERE " . safeIdent($column) . " IS NOT NULL";
    $res = $db->query($sql);

    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function joinMatchCount(mysqli $db, string $srcTable, string $srcCol, string $dstTable, string $dstCol): int
{
    $sql = "
        SELECT COUNT(*) c
        FROM " . safeIdent($srcTable) . " s
        INNER JOIN " . safeIdent($dstTable) . " d
            ON s." . safeIdent($srcCol) . " = d." . safeIdent($dstCol) . "
        WHERE s." . safeIdent($srcCol) . " IS NOT NULL
    ";

    $res = $db->query($sql);

    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function overlapCountCaseInsensitive(mysqli $db, string $tableA, string $colA, string $tableB, string $colB): int
{
    $sql = "
        SELECT COUNT(*) c
        FROM " . safeIdent($tableA) . " a
        INNER JOIN " . safeIdent($tableB) . " b
            ON LOWER(TRIM(a." . safeIdent($colA) . ")) = LOWER(TRIM(b." . safeIdent($colB) . "))
        WHERE a." . safeIdent($colA) . " IS NOT NULL
          AND b." . safeIdent($colB) . " IS NOT NULL
          AND TRIM(a." . safeIdent($colA) . ") <> ''
          AND TRIM(b." . safeIdent($colB) . ") <> ''
    ";

    $res = $db->query($sql);

    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

printHeader('TABLE EXISTENCE');

foreach ($v1Tables as $table) {
    echo "[V1] {$table}: " . (tableExists($db, $table) ? 'YES' : 'NO') . "\n";
}
echo "\n";
foreach ($v2Tables as $table) {
    echo "[V2] {$table}: " . (tableExists($db, $table) ? 'YES' : 'NO') . "\n";
}

echo "\n";

printHeader('ROW COUNTS');

foreach (array_merge($v1Tables, $v2Tables) as $table) {
    if (!tableExists($db, $table)) {
        continue;
    }
    echo $table . ': ' . rowCount($db, $table) . "\n";
}

echo "\n";

printHeader('V1 SCHEMA');

foreach ($v1Tables as $table) {
    if (!tableExists($db, $table)) {
        continue;
    }
    printColumns($db, $table);
    echo "\n";
}

printHeader('V2 TARGET SCHEMA (MAIN)');

foreach (['users_v2','wallets_v2','transactions_v2','coupons_v2','bets_v2','teams_v2','matches_v2'] as $table) {
    if (!tableExists($db, $table)) {
        continue;
    }
    printColumns($db, $table);
    echo "\n";
}

printHeader('V1 SAMPLE DATA');

foreach ($v1Tables as $table) {
    if (!tableExists($db, $table)) {
        continue;
    }
    printSample($db, $table, 3);
    echo "\n";
}

printHeader('LIKELY V1 INTERNAL RELATIONS');

$allV1Cols = [];
foreach ($v1Tables as $table) {
    if (!tableExists($db, $table)) {
        continue;
    }
    $allV1Cols[$table] = getColumnNames($db, $table);
}

foreach ($allV1Cols as $srcTable => $cols) {
    foreach ($cols as $col) {
        if ($col === 'id') {
            continue;
        }

        if (!preg_match('/(^|_)id$/i', $col)) {
            continue;
        }

        $distinct = distinctNotNullCount($db, $srcTable, $col);

        if ($distinct === 0) {
            continue;
        }

        echo "{$srcTable}.{$col} (distinct non-null: {$distinct})\n";

        foreach ($v1Tables as $dstTable) {
            if (!tableExists($db, $dstTable) || !hasColumn($db, $dstTable, 'id')) {
                continue;
            }

            $matches = joinMatchCount($db, $srcTable, $col, $dstTable, 'id');

            if ($matches > 0) {
                echo "  -> matches {$dstTable}.id : {$matches}\n";
            }
        }

        echo "\n";
    }
}

printHeader('V1 -> V2 USER OVERLAP');

if (tableExists($db, 'apostadores') && tableExists($db, 'users_v2')) {

    $apoCols = getColumnNames($db, 'apostadores');
    $usrCols = getColumnNames($db, 'users_v2');

    if (in_array('id', $apoCols, true) && in_array('id', $usrCols, true)) {
        $count = joinMatchCount($db, 'apostadores', 'id', 'users_v2', 'id');
        echo "apostadores.id -> users_v2.id matches: {$count}\n";
    }

    $candidateA = null;
    foreach (['username','user','nome','name','nick','login'] as $c) {
        if (in_array($c, $apoCols, true)) {
            $candidateA = $c;
            break;
        }
    }

    if ($candidateA && in_array('username', $usrCols, true)) {
        $count = overlapCountCaseInsensitive($db, 'apostadores', $candidateA, 'users_v2', 'username');
        echo "apostadores.{$candidateA} -> users_v2.username overlaps: {$count}\n";
    }

    if (in_array('email', $apoCols, true) && in_array('email', $usrCols, true)) {
        $count = overlapCountCaseInsensitive($db, 'apostadores', 'email', 'users_v2', 'email');
        echo "apostadores.email -> users_v2.email overlaps: {$count}\n";
    }
}

echo "\n";

printHeader('V1 -> V2 TEAM OVERLAP');

$teamSources = ['equipas', 'equipas_api'];
foreach ($teamSources as $srcTable) {

    if (!tableExists($db, $srcTable) || !tableExists($db, 'teams_v2')) {
        continue;
    }

    $srcCols = getColumnNames($db, $srcTable);
    $dstCols = getColumnNames($db, 'teams_v2');

    echo "Source table: {$srcTable}\n";

    if (in_array('id', $srcCols, true) && in_array('id', $dstCols, true)) {
        $count = joinMatchCount($db, $srcTable, 'id', 'teams_v2', 'id');
        echo "  {$srcTable}.id -> teams_v2.id matches: {$count}\n";
    }

    if (in_array('id', $srcCols, true) && in_array('external_id', $dstCols, true)) {
        $count = joinMatchCount($db, $srcTable, 'id', 'teams_v2', 'external_id');
        echo "  {$srcTable}.id -> teams_v2.external_id matches: {$count}\n";
    }

    $srcNameCol = null;
    foreach (['name','nome','team_name','designacao'] as $c) {
        if (in_array($c, $srcCols, true)) {
            $srcNameCol = $c;
            break;
        }
    }

    if ($srcNameCol && in_array('name', $dstCols, true)) {
        $count = overlapCountCaseInsensitive($db, $srcTable, $srcNameCol, 'teams_v2', 'name');
        echo "  {$srcTable}.{$srcNameCol} -> teams_v2.name overlaps: {$count}\n";
    }

    if ($srcNameCol && in_array('short_name', $dstCols, true)) {
        $count = overlapCountCaseInsensitive($db, $srcTable, $srcNameCol, 'teams_v2', 'short_name');
        echo "  {$srcTable}.{$srcNameCol} -> teams_v2.short_name overlaps: {$count}\n";
    }

    echo "\n";
}

printHeader('V1 -> V2 MATCH / COUPON / BET RELATION HINTS');

$hintPairs = [
    ['apostas', 'cupoes'],
    ['apostas', 'jogos'],
    ['apostas', 'apostadores'],
    ['jogos', 'equipas'],
    ['jogos', 'equipas_api'],
];

foreach ($hintPairs as [$srcTable, $dstTable]) {
    if (!tableExists($db, $srcTable) || !tableExists($db, $dstTable)) {
        continue;
    }

    echo "{$srcTable} vs {$dstTable}\n";

    $srcCols = getColumnNames($db, $srcTable);

    foreach ($srcCols as $col) {
        if ($col === 'id') {
            continue;
        }

        if (!preg_match('/(^|_)id$/i', $col)) {
            continue;
        }

        if (!hasColumn($db, $dstTable, 'id')) {
            continue;
        }

        $matches = joinMatchCount($db, $srcTable, $col, $dstTable, 'id');

        if ($matches > 0) {
            echo "  {$srcTable}.{$col} -> {$dstTable}.id : {$matches}\n";
        }
    }

    echo "\n";
}

printHeader('RECOMMENDED MIGRATION APPROACH');

echo "1) Keep V1 tables untouched.\n";
echo "2) Do NOT delete competitions_v2 / teams_v2 / matches_v2.\n";
echo "3) Keep users_v2 rows already in V2.\n";
echo "4) Audit collisions first before inserting apostadores into users_v2 with same id.\n";
echo "5) Force dummy password only in migrated V1 users, not existing V2 users.\n";
echo "6) Migrate in this order:\n";
echo "   users_v2 (missing only)\n";
echo "   wallets_v2 (missing only)\n";
echo "   coupons_v2\n";
echo "   coupon_matches_v2\n";
echo "   bets_v2\n";
echo "   bet_picks_v2 (if V1 has equivalent data)\n";
echo "   transactions_v2\n";

echo "\n";

printHeader('IMPORTANT SAFETY NOTE');

echo "THIS SCRIPT DID NOT WRITE ANY DATA.\n";
echo "V1 TABLES ARE STILL INTACT.\n";
echo "NEXT STEP: paste this output here and I will build the migration script.\n";

echo "\n========================================\n";
echo "AUDIT COMPLETE\n";
echo "========================================\n";