<?php

require_once __DIR__ . '/../core/Database.php';

$config = require __DIR__ . '/../config/api.php';

$apiToken = $config['football_data']['token'];
$baseUrl = $config['football_data']['base_url'];

$db = Database::getConnection();

$dateFrom = date("Y-m-d", strtotime("-7 days"));
$dateTo = date("Y-m-d", strtotime("+21 days"));

echo "Import window: $dateFrom → $dateTo\n\n";

$competitions = $db->query("
SELECT id, external_id, name
FROM competitions_v2
");

while ($c = $competitions->fetch_assoc()) {

    $competitionId = $c['id'];
    $externalCompetition = $c['external_id'];
    $name = $c['name'];

    echo "Checking competition: $name\n";

    $url = "$baseUrl/competitions/$externalCompetition/matches?dateFrom=$dateFrom&dateTo=$dateTo";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Auth-Token: $apiToken"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {

        echo "Curl error: " . curl_error($ch) . "\n";
        curl_close($ch);
        continue;

    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['matches'])) {
        continue;
    }

    echo "Matches returned: " . count($data['matches']) . "\n";

    foreach ($data['matches'] as $m) {

        if ($m['status'] === "FINISHED") {
            continue;
        }

        $externalId = $m['id'];
        $homeExternal = $m['homeTeam']['id'];
        $awayExternal = $m['awayTeam']['id'];

        $utcDate = $m['utcDate'];
        $status = $m['status'];
        $matchday = $m['matchday'] ?? null;

        $homeScore = $m['score']['fullTime']['home'] ?? null;
        $awayScore = $m['score']['fullTime']['away'] ?? null;

        $resultCode = null;

        if ($homeScore !== null && $awayScore !== null) {

            if ($homeScore > $awayScore) $resultCode = "1";
            if ($homeScore < $awayScore) $resultCode = "2";
            if ($homeScore == $awayScore) $resultCode = "X";

        }

        $homeTeamId = null;
        $awayTeamId = null;

        $stmt = $db->prepare("SELECT id FROM teams_v2 WHERE external_id=?");
        $stmt->bind_param("i", $homeExternal);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $homeTeamId = $row['id'];
        }
        $stmt->close();

        $stmt = $db->prepare("SELECT id FROM teams_v2 WHERE external_id=?");
        $stmt->bind_param("i", $awayExternal);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $awayTeamId = $row['id'];
        }
        $stmt->close();

        if (!$homeTeamId || !$awayTeamId) {
            echo "Skipping match (team not found)\n";
            continue;
        }

        if ($homeTeamId == $awayTeamId) {
            echo "Skipping invalid match\n";
            continue;
        }

        $stmt = $db->prepare("
        INSERT INTO matches_v2
        (
            external_id,
            competition_id,
            home_team_id,
            away_team_id,
            matchday,
            scheduled_at,
            status,
            result_code,
            home_score,
            away_score,
            api_updated_at
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            result_code = VALUES(result_code),
            home_score = VALUES(home_score),
            away_score = VALUES(away_score),
            api_updated_at = NOW()
        ");

        $stmt->bind_param(
            "iiiiisssii",
            $externalId,
            $competitionId,
            $homeTeamId,
            $awayTeamId,
            $matchday,
            $utcDate,
            $status,
            $resultCode,
            $homeScore,
            $awayScore
        );

        $stmt->execute();

        if ($stmt->error) {
            echo "SQL ERROR: " . $stmt->error . "\n";
        }

        $stmt->close();
    }

    echo "Import done for $name\n\n";
}

echo "All competitions processed.\n";