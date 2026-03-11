<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

$EXECUTE = in_array('--execute', $argv, true);
$RESET_V2 = in_array('--reset-v2', $argv, true);

echo "========================================\n";
echo "ADVANCED V1 -> V2 MIGRATOR\n";
echo "========================================\n";
echo "MODE: " . ($EXECUTE ? 'EXECUTE' : 'DRY RUN') . "\n";
echo "RESET V2: " . ($RESET_V2 ? 'YES' : 'NO') . "\n";
echo "SAFETY: V1 TABLES ARE NEVER MODIFIED\n\n";

function q(mysqli $db, string $sql): mysqli_result|bool
{
    $res = $db->query($sql);
    if ($res === false) {
        throw new RuntimeException("SQL failed: " . $db->error . "\nQuery: " . $sql);
    }
    return $res;
}

function hasColumn(mysqli $db, string $table, string $column): bool
{
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);

    $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
    $res = $db->query($sql);

    return $res instanceof mysqli_result && $res->num_rows > 0;
}

function normalize(?string $v): string
{
    $v = trim((string)$v);
    $v = mb_strtolower($v);
    $v = preg_replace('/\s+/', ' ', $v);
    return $v;
}

function mapRole(string $username, ?string $access): string
{
    if (normalize($username) === 'diogormatas') {
        return 'ADMIN';
    }

    return strtoupper((string)$access) === 'ADMIN' ? 'ADMIN' : 'USER';
}

function mapTxType(?string $legacyType): string
{
    return match (strtoupper((string)$legacyType)) {
        'DEPOSIT' => 'deposit',
        'BONUS' => 'bonus',
        'WITHDRAW' => 'withdraw',
        'BET' => 'bet',
        'PRIZE' => 'prize',
        'JACKPOT' => 'jackpot',
        default => 'refund',
    };
}

function mapMatchStatus(?string $legacy): string
{
    return match (strtoupper((string)$legacy)) {
        'IN_PLAY', 'PAUSED', 'SUSPENDED' => 'IN_PLAY',
        'FINISHED', 'AWARDED' => 'FINISHED',
        'POSTPONED' => 'POSTPONED',
        'CANCELED', 'CANCELLED' => 'CANCELLED',
        default => 'SCHEDULED',
    };
}

function mapResultCode(?string $legacy): ?string
{
    $legacy = strtoupper((string)$legacy);
    return in_array($legacy, ['1','X','2'], true) ? $legacy : null;
}

function couponStatusFromLegacy(?string $payment, ?string $minMatchDate): array
{
    $payment = strtoupper((string)$payment);
    $minMatchDateTs = $minMatchDate ? strtotime($minMatchDate) : null;

    if ($payment === 'PAID') {
        return ['SETTLED', 'PAID'];
    }

    if ($minMatchDateTs !== null && $minMatchDateTs < time()) {
        return ['CLOSED', 'PENDING'];
    }

    return ['OPEN', 'PENDING'];
}

function typeFromLegacyCoupon(?string $name, ?string $type): string
{
    $hay = normalize((string)$name . ' ' . (string)$type);

    if (str_contains($hay, 'liga tips')) {
        return 'LIGA_TIPS';
    }

    return 'DUELO';
}

function fetchAll(mysqli $db, string $sql): array
{
    $res = q($db, $sql);
    $rows = [];

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function printSection(string $title): void
{
    echo "----------------------------------------\n";
    echo $title . "\n";
    echo "----------------------------------------\n";
}

$hasUpdatedAtUsers = hasColumn($db, 'users_v2', 'updated_at');
$hasUpdatedAtWallets = hasColumn($db, 'wallets_v2', 'updated_at');
$hasUpdatedAtTx = hasColumn($db, 'transactions_v2', 'updated_at');
$hasUpdatedAtCoupons = hasColumn($db, 'coupons_v2', 'updated_at');
$hasUpdatedAtCouponMatches = hasColumn($db, 'coupon_matches_v2', 'updated_at');
$hasUpdatedAtBets = hasColumn($db, 'bets_v2', 'updated_at');
$hasUpdatedAtBetPicks = hasColumn($db, 'bet_picks_v2', 'updated_at');
$hasUpdatedAtTeams = hasColumn($db, 'teams_v2', 'updated_at');
$hasUpdatedAtMatches = hasColumn($db, 'matches_v2', 'updated_at');

$hasSourceUsers = hasColumn($db, 'users_v2', 'source_system');
$hasSourceWallets = hasColumn($db, 'wallets_v2', 'source_system');
$hasSourceTx = hasColumn($db, 'transactions_v2', 'source_system');
$hasSourceCoupons = hasColumn($db, 'coupons_v2', 'source_system');
$hasSourceCouponMatches = hasColumn($db, 'coupon_matches_v2', 'source_system');
$hasSourceBets = hasColumn($db, 'bets_v2', 'source_system');
$hasSourceBetPicks = hasColumn($db, 'bet_picks_v2', 'source_system');
$hasSourceTeams = hasColumn($db, 'teams_v2', 'source_system');
$hasSourceMatches = hasColumn($db, 'matches_v2', 'source_system');

$hasLegacyTeams = hasColumn($db, 'teams_v2', 'legacy_v1_id');
$hasLegacyMatches = hasColumn($db, 'matches_v2', 'legacy_v1_id');
$hasLegacyTypeTx = hasColumn($db, 'transactions_v2', 'legacy_type');

printSection('PREFLIGHT COUNTS');

foreach ([
    'apostadores',
    'apostas',
    'cupoes',
    'equipas',
    'equipas_api',
    'jogos',
    'transaccoes',
] as $table) {
    $rows = fetchAll($db, "SELECT COUNT(*) c FROM `{$table}`");
    echo $table . ': ' . (int)$rows[0]['c'] . "\n";
}

echo "\n";

if ($EXECUTE) {
    q($db, "SET time_zone = '+00:00'");
    $db->begin_transaction();
}

try {
    if ($EXECUTE && $RESET_V2) {
        printSection('RESETTING V2 (SAFE SCOPE ONLY)');

        q($db, "SET FOREIGN_KEY_CHECKS=0");

        foreach ([
            'bet_picks_v2',
            'bets_v2',
            'coupon_matches_v2',
            'coupons_v2',
            'transactions_v2',
            'wallets_v2',
            'users_v2',
        ] as $table) {
            echo "[RESET] {$table}\n";
            q($db, "TRUNCATE `{$table}`");
        }

        q($db, "SET FOREIGN_KEY_CHECKS=1");
        echo "\n";
    }

    /*
    ----------------------------------------
    CACHES
    ----------------------------------------
    */
    printSection('BUILDING CACHES');

    $existingUsersByUsername = [];
    foreach (fetchAll($db, "SELECT id, username FROM users_v2") as $row) {
        $existingUsersByUsername[normalize($row['username'])] = (int)$row['id'];
    }

    $existingWalletsByUser = [];
    foreach (fetchAll($db, "SELECT id, user_id FROM wallets_v2") as $row) {
        $existingWalletsByUser[(int)$row['user_id']] = (int)$row['id'];
    }

    $existingTeamsByName = [];
    foreach (fetchAll($db, "SELECT id, name FROM teams_v2") as $row) {
        $existingTeamsByName[normalize($row['name'])] = (int)$row['id'];
    }

    $existingTeamsByExternal = [];
    foreach (fetchAll($db, "SELECT id, external_id FROM teams_v2 WHERE external_id IS NOT NULL") as $row) {
        $existingTeamsByExternal[(int)$row['external_id']] = (int)$row['id'];
    }

    $existingTeamsByLegacy = [];
    if ($hasLegacyTeams) {
        foreach (fetchAll($db, "SELECT id, legacy_v1_id FROM teams_v2 WHERE legacy_v1_id IS NOT NULL") as $row) {
            $existingTeamsByLegacy[(int)$row['legacy_v1_id']] = (int)$row['id'];
        }
    }

    $existingMatchesByExternal = [];
    foreach (fetchAll($db, "SELECT id, external_id FROM matches_v2 WHERE external_id IS NOT NULL") as $row) {
        $existingMatchesByExternal[(int)$row['external_id']] = (int)$row['id'];
    }

    $existingMatchesByLegacy = [];
    if ($hasLegacyMatches) {
        foreach (fetchAll($db, "SELECT id, legacy_v1_id FROM matches_v2 WHERE legacy_v1_id IS NOT NULL") as $row) {
            $existingMatchesByLegacy[(int)$row['legacy_v1_id']] = (int)$row['id'];
        }
    }

    $competitionsByCode = [];
    foreach (fetchAll($db, "SELECT id, code FROM competitions_v2 WHERE code IS NOT NULL") as $row) {
        $competitionsByCode[normalize($row['code'])] = (int)$row['id'];
    }

    $legacyApiTeamsByIdApi = [];
    foreach (fetchAll($db, "SELECT * FROM equipas_api") as $row) {
        $legacyApiTeamsByIdApi[(int)$row['id_api']] = $row;
    }

    $legacyPrizeTickets = [];
    foreach (fetchAll($db, "SELECT DISTINCT ticket_nr FROM transaccoes WHERE UPPER(tipo)='PRIZE' AND ticket_nr IS NOT NULL AND ticket_nr <> ''") as $row) {
        $legacyPrizeTickets[(string)$row['ticket_nr']] = true;
    }

    echo "[OK] caches ready\n\n";

    /*
    ----------------------------------------
    USERS
    ----------------------------------------
    */
    printSection('MIGRATING USERS');

    $legacyUsers = fetchAll($db, "SELECT * FROM apostadores ORDER BY id ASC");
    $dummyHash = password_hash('dummy', PASSWORD_DEFAULT);

    $insertedUsers = 0;
    $skippedUsers = 0;

    foreach ($legacyUsers as $u) {
        $id = (int)$u['id'];
        $username = trim((string)$u['username']);
        $email = $u['email'] !== '' ? $u['email'] : null;
        $createdAt = $u['criado_em'] ?: date('Y-m-d H:i:s');
        $role = mapRole($username, $u['access'] ?? null);

        if (isset($existingUsersByUsername[normalize($username)])) {
            $skippedUsers++;
            echo "[SKIP USER] {$username} already exists in users_v2\n";
            continue;
        }

        if ($EXECUTE) {
            $columns = ['id','username','email','password_hash','role','created_at'];
            $values = ['?','?','?','?','?','?'];
            $types = 'isssss';
            $params = [$id, $username, $email, $dummyHash, $role, $createdAt];

            if ($hasSourceUsers) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtUsers) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $u['alterado_em'] ?: $createdAt;
            }

            $sql = "INSERT INTO users_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";

            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();

            $existingUsersByUsername[normalize($username)] = $id;
        }

        $insertedUsers++;
        echo "[USER] {$username} -> users_v2.id={$id}\n";
    }

    echo "Users inserted: {$insertedUsers}\n";
    echo "Users skipped: {$skippedUsers}\n\n";

    /*
    ----------------------------------------
    WALLETS
    ----------------------------------------
    */
    printSection('MIGRATING WALLETS');

    $insertedWallets = 0;

    foreach ($legacyUsers as $u) {
        $userId = (int)$u['id'];
        $balance = (float)$u['saldo'];

        if ($EXECUTE) {
            $columns = ['user_id','balance'];
            $values = ['?','?'];
            $types = 'id';
            $params = [$userId, $balance];

            if ($hasSourceWallets) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtWallets) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $u['alterado_em'] ?: date('Y-m-d H:i:s');
            }

            $sql = "INSERT INTO wallets_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")
                    ON DUPLICATE KEY UPDATE balance=VALUES(balance)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        $insertedWallets++;
        echo "[WALLET] user_id={$userId} balance={$balance}\n";
    }

    echo "Wallet rows processed: {$insertedWallets}\n\n";

    /*
    ----------------------------------------
    TEAMS
    ----------------------------------------
    */
    printSection('MIGRATING / MAPPING TEAMS');

    $legacyTeams = fetchAll($db, "SELECT * FROM equipas ORDER BY id ASC");
    $teamMap = [];
    $insertedTeams = 0;
    $mappedTeams = 0;

    foreach ($legacyTeams as $t) {
        $legacyTeamId = (int)$t['id'];

        if ($hasLegacyTeams && isset($existingTeamsByLegacy[$legacyTeamId])) {
            $teamMap[$legacyTeamId] = $existingTeamsByLegacy[$legacyTeamId];
            $mappedTeams++;
            continue;
        }

        $name = trim((string)$t['equipa']);
        $normName = normalize($name);

        if (isset($existingTeamsByName[$normName])) {
            $teamMap[$legacyTeamId] = $existingTeamsByName[$normName];
            $mappedTeams++;
            continue;
        }

        $apiInfo = null;
        $idApi = isset($t['id_api']) ? (int)$t['id_api'] : 0;
        if ($idApi !== 0 && isset($legacyApiTeamsByIdApi[$idApi])) {
            $apiInfo = $legacyApiTeamsByIdApi[$idApi];
        }

        $country = trim((string)$t['pais']);
        if ($country === '' || normalize($country) === 'vazio') {
            $country = $apiInfo['pais'] ?? null;
            if (normalize((string)$country) === 'vazio') {
                $country = null;
            }
        }

        $logoUrl = $t['url_logo'] ?: ($apiInfo['logo_url'] ?? null);

        $externalId = null;
        if ($idApi > 0 && !isset($existingTeamsByExternal[$idApi])) {
            $externalId = $idApi;
        }

        if ($EXECUTE) {
            $columns = ['external_id','name','country','logo_url','created_at'];
            $values = ['?','?','?','?','?'];
            $types = 'issss';
            $params = [$externalId, $name, $country, $logoUrl, $t['criado_em'] ?: date('Y-m-d H:i:s')];

            if ($hasSourceTeams) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasLegacyTeams) {
                $columns[] = 'legacy_v1_id';
                $values[] = '?';
                $types .= 'i';
                $params[] = $legacyTeamId;
            }

            if ($hasUpdatedAtTeams) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $t['alterado_em'] ?: ($t['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO teams_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $newId = (int)$db->insert_id;
            $stmt->close();

            $teamMap[$legacyTeamId] = $newId;
            $existingTeamsByName[$normName] = $newId;
            if ($externalId !== null) {
                $existingTeamsByExternal[$externalId] = $newId;
            }
            if ($hasLegacyTeams) {
                $existingTeamsByLegacy[$legacyTeamId] = $newId;
            }
        } else {
            $teamMap[$legacyTeamId] = -1;
        }

        $insertedTeams++;
        echo "[TEAM] {$name} -> mapped from V1 id {$legacyTeamId}\n";
    }

    echo "Teams mapped existing: {$mappedTeams}\n";
    echo "Teams inserted new: {$insertedTeams}\n\n";

    /*
    ----------------------------------------
    MATCHES
    ----------------------------------------
    */
    printSection('MIGRATING / MAPPING MATCHES');

    $legacyMatches = fetchAll($db, "SELECT * FROM jogos ORDER BY id ASC");
    $matchMap = [];
    $insertedMatches = 0;
    $mappedExistingMatches = 0;

    foreach ($legacyMatches as $m) {
        $legacyMatchId = (int)$m['id'];

        if ($hasLegacyMatches && isset($existingMatchesByLegacy[$legacyMatchId])) {
            $matchMap[$legacyMatchId] = $existingMatchesByLegacy[$legacyMatchId];
            $mappedExistingMatches++;
            continue;
        }

        $legacyApiId = !empty($m['id_api']) ? (int)$m['id_api'] : null;
        if ($legacyApiId !== null && isset($existingMatchesByExternal[$legacyApiId])) {
            $matchMap[$legacyMatchId] = $existingMatchesByExternal[$legacyApiId];
            $mappedExistingMatches++;
            continue;
        }

        $homeTeamId = null;
        $awayTeamId = null;

        if (!empty($m['id_equipa_casa']) && isset($teamMap[(int)$m['id_equipa_casa']])) {
            $homeTeamId = $teamMap[(int)$m['id_equipa_casa']];
        } else {
            $homeName = normalize($m['equipa_casa'] ?? '');
            if (isset($existingTeamsByName[$homeName])) {
                $homeTeamId = $existingTeamsByName[$homeName];
            }
        }

        if (!empty($m['id_equipa_fora']) && isset($teamMap[(int)$m['id_equipa_fora']])) {
            $awayTeamId = $teamMap[(int)$m['id_equipa_fora']];
        } else {
            $awayName = normalize($m['equipa_fora'] ?? '');
            if (isset($existingTeamsByName[$awayName])) {
                $awayTeamId = $existingTeamsByName[$awayName];
            }
        }

        $competitionId = null;
        if (!empty($m['league_code'])) {
            $code = normalize($m['league_code']);
            if (isset($competitionsByCode[$code])) {
                $competitionId = $competitionsByCode[$code];
            }
        }

        $scheduledAt = $m['data_jogo'];
        $status = mapMatchStatus($m['status'] ?? null);
        $resultCode = mapResultCode($m['resultado'] ?? null);
        $homeScore = $m['golos_casa'] !== null ? (int)$m['golos_casa'] : null;
        $awayScore = $m['golos_fora'] !== null ? (int)$m['golos_fora'] : null;

        // try exact match by teams + datetime before inserting
        if ($homeTeamId && $awayTeamId && $scheduledAt) {
            $checkSql = "
                SELECT id
                FROM matches_v2
                WHERE home_team_id = {$homeTeamId}
                  AND away_team_id = {$awayTeamId}
                  AND scheduled_at = '" . $db->real_escape_string($scheduledAt) . "'
                LIMIT 1
            ";
            $rows = fetchAll($db, $checkSql);
            if (!empty($rows)) {
                $matchMap[$legacyMatchId] = (int)$rows[0]['id'];
                $mappedExistingMatches++;
                continue;
            }
        }

        if ($EXECUTE) {
            $externalId = null;
            if ($legacyApiId !== null && !isset($existingMatchesByExternal[$legacyApiId])) {
                $externalId = $legacyApiId;
            }

            $columns = [
                'external_id',
                'competition_id',
                'home_team_id',
                'away_team_id',
                'scheduled_at',
                'status',
                'result_code',
                'home_score',
                'away_score',
                'created_at',
            ];

            $values = ['?','?','?','?','?','?','?','?','?','?'];
            $types = 'iiiisssiis';
            $params = [
                $externalId,
                $competitionId,
                $homeTeamId,
                $awayTeamId,
                $scheduledAt,
                $status,
                $resultCode,
                $homeScore,
                $awayScore,
                $m['criado_em'] ?: date('Y-m-d H:i:s'),
            ];

            if ($hasSourceMatches) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasLegacyMatches) {
                $columns[] = 'legacy_v1_id';
                $values[] = '?';
                $types .= 'i';
                $params[] = $legacyMatchId;
            }

            if ($hasUpdatedAtMatches) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $m['alterado_em'] ?: ($m['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO matches_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $newId = (int)$db->insert_id;
            $stmt->close();

            $matchMap[$legacyMatchId] = $newId;

            if ($externalId !== null) {
                $existingMatchesByExternal[$externalId] = $newId;
            }
            if ($hasLegacyMatches) {
                $existingMatchesByLegacy[$legacyMatchId] = $newId;
            }
        } else {
            $matchMap[$legacyMatchId] = -1;
        }

        $insertedMatches++;
    }

    echo "Matches mapped existing: {$mappedExistingMatches}\n";
    echo "Matches inserted new: {$insertedMatches}\n\n";

    /*
    ----------------------------------------
    COUPONS
    ----------------------------------------
    */
    printSection('MIGRATING COUPONS');

    $couponHeaders = fetchAll($db, "
        SELECT
            c.id_cupao,
            MIN(c.criado_em) AS criado_em,
            MAX(c.alterado_em) AS alterado_em,
            MIN(c.nome) AS nome,
            MAX(c.max_jogadores) AS max_jogadores,
            MAX(c.custo) AS custo,
            MAX(c.type) AS legacy_type,
            MAX(c.creator_username) AS creator_username,
            MAX(c.hide) AS hide,
            MAX(c.token) AS token,
            MAX(c.payment) AS payment,
            MIN(j.data_jogo) AS first_match_date
        FROM cupoes c
        LEFT JOIN jogos j ON j.id = c.id_jogo
        GROUP BY c.id_cupao
        ORDER BY c.id_cupao ASC
    ");

    $insertedCoupons = 0;

    foreach ($couponHeaders as $c) {
        $couponId = (int)$c['id_cupao'];
        $creatorUserId = null;
        $creatorUsernameNorm = normalize($c['creator_username'] ?? '');
        if (isset($existingUsersByUsername[$creatorUsernameNorm])) {
            $creatorUserId = $existingUsersByUsername[$creatorUsernameNorm];
        }

        [$status, $prizeStatus] = couponStatusFromLegacy($c['payment'] ?? null, $c['first_match_date'] ?? null);

        $visibility = normalize($c['token'] ?? '') === 'publico' ? 'PUBLIC' : 'PRIVATE';
        $privateToken = $visibility === 'PRIVATE' ? (string)$c['token'] : null;

        $type = typeFromLegacyCoupon($c['nome'] ?? null, $c['legacy_type'] ?? null);

        if ($EXECUTE) {
            $columns = [
                'id',
                'name',
                'type',
                'entry_price',
                'max_players',
                'created_by',
                'status',
                'prize_status',
                'settled_at',
                'visibility',
                'private_token',
                'betting_closes_at',
                'created_at',
            ];
            $values = ['?','?','?','?','?','?','?','?','?','?','?','?','?'];
            $types = 'issdiisssssss';
            $params = [
                $couponId,
                $c['nome'],
                $type,
                (float)$c['custo'],
                $c['max_jogadores'] !== null ? (int)$c['max_jogadores'] : null,
                $creatorUserId,
                $status,
                $prizeStatus,
                $status === 'SETTLED' ? ($c['alterado_em'] ?: $c['criado_em']) : null,
                $visibility,
                $privateToken,
                $c['first_match_date'] ?: null,
                $c['criado_em'] ?: date('Y-m-d H:i:s'),
            ];

            if ($hasSourceCoupons) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtCoupons) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $c['alterado_em'] ?: ($c['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO coupons_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        $insertedCoupons++;
    }

    echo "Coupons inserted: {$insertedCoupons}\n\n";

    /*
    ----------------------------------------
    COUPON MATCHES
    ----------------------------------------
    */
    printSection('MIGRATING COUPON MATCHES');

    $legacyCouponRows = fetchAll($db, "
        SELECT c.id_cupao, c.id_jogo, j.data_jogo
        FROM cupoes c
        LEFT JOIN jogos j ON j.id = c.id_jogo
        ORDER BY c.id_cupao ASC, j.data_jogo ASC, c.id_jogo ASC
    ");

    $insertedCouponMatches = 0;
    $sortOrderMap = [];

    foreach ($legacyCouponRows as $row) {
        $couponId = (int)$row['id_cupao'];
        $legacyMatchId = (int)$row['id_jogo'];

        if (!isset($matchMap[$legacyMatchId])) {
            echo "[WARN] coupon {$couponId} references legacy match {$legacyMatchId} with no V2 mapping\n";
            continue;
        }

        $matchId = $matchMap[$legacyMatchId];
        $sortOrderMap[$couponId] = ($sortOrderMap[$couponId] ?? 0) + 1;
        $sortOrder = $sortOrderMap[$couponId];

        if ($EXECUTE) {
            $columns = ['coupon_id','match_id','sort_order'];
            $values = ['?','?','?'];
            $types = 'iii';
            $params = [$couponId, $matchId, $sortOrder];

            if ($hasSourceCouponMatches) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtCouponMatches) {
                $columns[] = 'updated_at';
                $values[] = 'CURRENT_TIMESTAMP';
            }

            $sql = "INSERT INTO coupon_matches_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);

            // only bind placeholders, not CURRENT_TIMESTAMP
            $bindCount = substr_count(implode(',', $values), '?');
            if ($bindCount === count($params)) {
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO coupon_matches_v2
                    (coupon_id,match_id,sort_order" . ($hasSourceCouponMatches ? ",source_system" : "") . ($hasUpdatedAtCouponMatches ? ",updated_at" : "") . ")
                    VALUES
                    (?, ?, ?" . ($hasSourceCouponMatches ? ", ?" : "") . ($hasUpdatedAtCouponMatches ? ", CURRENT_TIMESTAMP" : "") . ")
                ");
                if ($hasSourceCouponMatches) {
                    $stmt->bind_param('iiis', $couponId, $matchId, $sortOrder, $params[count($params)-1]);
                } else {
                    $stmt->bind_param('iii', $couponId, $matchId, $sortOrder);
                }
            }

            $stmt->execute();
            $stmt->close();
        }

        $insertedCouponMatches++;
    }

    echo "Coupon matches inserted: {$insertedCouponMatches}\n\n";

    /*
    ----------------------------------------
    BETS
    ----------------------------------------
    */
    printSection('MIGRATING BETS');

    $couponCostMap = [];
    foreach ($couponHeaders as $c) {
        $couponCostMap[(int)$c['id_cupao']] = (float)$c['custo'];
    }

    $couponPaymentMap = [];
    foreach ($couponHeaders as $c) {
        $couponPaymentMap[(int)$c['id_cupao']] = strtoupper((string)$c['payment']);
    }

    $betGroups = fetchAll($db, "
        SELECT
            id_chave,
            ticket_nr,
            MAX(id_cupao) AS id_cupao,
            MAX(id_tipster) AS id_tipster,
            MIN(criado_em) AS criado_em,
            MAX(alterado_em) AS alterado_em
        FROM apostas
        GROUP BY id_chave, ticket_nr
        ORDER BY id_chave ASC
    ");

    $insertedBets = 0;

    foreach ($betGroups as $b) {
        $betId = (int)$b['id_chave'];
        $ticketNumber = (string)$b['ticket_nr'];
        $couponId = (int)$b['id_cupao'];
        $userId = (int)$b['id_tipster'];
        $stake = $couponCostMap[$couponId] ?? 0.0;
        $legacyPaid = $couponPaymentMap[$couponId] ?? 'NOT PAID';

        $status = 'ACTIVE';
        if (isset($legacyPrizeTickets[$ticketNumber])) {
            $status = 'WON';
        } elseif ($legacyPaid === 'PAID') {
            $status = 'LOST';
        }

        if ($EXECUTE) {
            $columns = [
                'id',
                'coupon_id',
                'user_id',
                'ticket_number',
                'stake',
                'score',
                'status',
                'created_at',
                'final_position',
            ];
            $values = ['?','?','?','?','?','?','?','?','?'];
            $types = 'iiisdissi';
            $score = 0;
            $finalPosition = null;
            $params = [
                $betId,
                $couponId,
                $userId,
                $ticketNumber,
                $stake,
                $score,
                $status,
                $b['criado_em'] ?: date('Y-m-d H:i:s'),
                $finalPosition,
            ];

            if ($hasSourceBets) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtBets) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $b['alterado_em'] ?: ($b['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO bets_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        $insertedBets++;
    }

    echo "Bets inserted: {$insertedBets}\n\n";

    /*
    ----------------------------------------
    BET PICKS
    ----------------------------------------
    */
    printSection('MIGRATING BET PICKS');

    $legacyMatchResults = [];
    foreach (fetchAll($db, "SELECT id, resultado FROM jogos") as $row) {
        $legacyMatchResults[(int)$row['id']] = mapResultCode($row['resultado']);
    }

    $legacyPicks = fetchAll($db, "
        SELECT
            id_linha,
            id_chave,
            id_jogo,
            tip,
            criado_em,
            alterado_em
        FROM apostas
        ORDER BY id_linha ASC
    ");

    $insertedBetPicks = 0;
    $betScore = [];

    foreach ($legacyPicks as $p) {
        $betId = (int)$p['id_chave'];
        $legacyMatchId = (int)$p['id_jogo'];

        if (!isset($matchMap[$legacyMatchId])) {
            echo "[WARN] bet {$betId} references legacy match {$legacyMatchId} with no V2 mapping\n";
            continue;
        }

        $matchId = $matchMap[$legacyMatchId];
        $pick = mapResultCode($p['tip']);
        if ($pick === null) {
            continue;
        }

        $official = $legacyMatchResults[$legacyMatchId] ?? null;
        $isCorrect = null;
        if ($official !== null) {
            $isCorrect = ($pick === $official) ? 1 : 0;
            $betScore[$betId] = ($betScore[$betId] ?? 0) + $isCorrect;
        }

        if ($EXECUTE) {
            $columns = ['bet_id','match_id','pick','is_correct'];
            $values = ['?','?','?','?'];
            $types = 'iisi';
            $params = [$betId, $matchId, $pick, $isCorrect];

            if ($hasSourceBetPicks) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtBetPicks) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $p['alterado_em'] ?: ($p['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO bet_picks_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        $insertedBetPicks++;
    }

    if ($EXECUTE && !empty($betScore)) {
        foreach ($betScore as $betId => $score) {
            q($db, "UPDATE bets_v2 SET score = " . (int)$score . " WHERE id = " . (int)$betId);
        }
    }

    echo "Bet picks inserted: {$insertedBetPicks}\n\n";

    /*
    ----------------------------------------
    TRANSACTIONS
    ----------------------------------------
    */
    printSection('MIGRATING TRANSACTIONS');

    // refresh wallet cache after inserts
    $existingWalletsByUser = [];
    foreach (fetchAll($db, "SELECT id, user_id FROM wallets_v2") as $row) {
        $existingWalletsByUser[(int)$row['user_id']] = (int)$row['id'];
    }

    $legacyTx = fetchAll($db, "SELECT * FROM transaccoes ORDER BY id_transaccao ASC");
    $insertedTx = 0;
    $skippedTx = 0;

    foreach ($legacyTx as $tx) {
        $userId = !empty($tx['id_tipster']) ? (int)$tx['id_tipster'] : null;

        if ($userId === null || !isset($existingWalletsByUser[$userId])) {
            $skippedTx++;
            echo "[SKIP TX] id_transaccao={$tx['id_transaccao']} missing wallet/user mapping\n";
            continue;
        }

        $walletId = $existingWalletsByUser[$userId];
        $type = mapTxType($tx['tipo'] ?? null);
        $amount = (float)$tx['valor'];

        $description = trim(
            'legacy:' .
            ($tx['transaction_nr'] ?: 'ND') .
            ' ticket:' .
            ($tx['ticket_nr'] ?: 'ND') .
            ' user:' .
            ($tx['username'] ?: 'ND')
        );

        if ($EXECUTE) {
            $columns = [
                'id',
                'wallet_id',
                'user_id',
                'type',
                'amount',
                'description',
                'created_at',
            ];
            $values = ['?','?','?','?','?','?','?'];
            $types = 'iiisdss';
            $params = [
                (int)$tx['id_transaccao'],
                $walletId,
                $userId,
                $type,
                $amount,
                $description,
                $tx['criado_em'] ?: date('Y-m-d H:i:s'),
            ];

            if ($hasLegacyTypeTx) {
                $columns[] = 'legacy_type';
                $values[] = '?';
                $types .= 's';
                $params[] = strtoupper((string)$tx['tipo']);
            }

            if ($hasSourceTx) {
                $columns[] = 'source_system';
                $values[] = '?';
                $types .= 's';
                $params[] = 'v1';
            }

            if ($hasUpdatedAtTx) {
                $columns[] = 'updated_at';
                $values[] = '?';
                $types .= 's';
                $params[] = $tx['alterado_em'] ?: ($tx['criado_em'] ?: date('Y-m-d H:i:s'));
            }

            $sql = "INSERT INTO transactions_v2 (" . implode(',', $columns) . ")
                    VALUES (" . implode(',', $values) . ")";
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        $insertedTx++;
    }

    echo "Transactions inserted: {$insertedTx}\n";
    echo "Transactions skipped: {$skippedTx}\n\n";

    /*
    ----------------------------------------
    SUMMARY
    ----------------------------------------
    */
    printSection('SUMMARY');

    echo "Users processed: " . count($legacyUsers) . "\n";
    echo "Legacy teams processed: " . count($legacyTeams) . "\n";
    echo "Legacy matches processed: " . count($legacyMatches) . "\n";
    echo "Coupons processed: " . count($couponHeaders) . "\n";
    echo "Grouped bets processed: " . count($betGroups) . "\n";
    echo "Legacy picks processed: " . count($legacyPicks) . "\n";
    echo "Legacy transactions processed: " . count($legacyTx) . "\n";

    if ($EXECUTE) {
        $db->commit();
        echo "\n========================================\n";
        echo "MIGRATION COMPLETE (COMMITTED)\n";
        echo "========================================\n";
    } else {
        echo "\n========================================\n";
        echo "DRY RUN COMPLETE (NO WRITES)\n";
        echo "========================================\n";
    }

} catch (Throwable $e) {
    if ($EXECUTE) {
        $db->rollback();
    }

    echo "\n[FAIL] " . $e->getMessage() . "\n";
    echo "Migration aborted.\n";
}