<?php

require_once __DIR__ . '/../core/Database.php';

$config = require __DIR__ . '/../config/api.php';

$apiToken = $config['football_data']['token'];
$baseUrl = $config['football_data']['base_url'];

$db = Database::getConnection();

$matches = $db->query("
SELECT id, external_id
FROM matches_v2
WHERE status != 'FINISHED'
LIMIT 100
");

while ($m = $matches->fetch_assoc()) {

    $matchId = $m['id'];
    $externalId = $m['external_id'];

    $url = "$baseUrl/matches/$externalId";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Auth-Token: $apiToken"
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['match'])) {
        continue;
    }

    $match = $data['match'];

    $status = $match['status'];

    $homeScore = $match['score']['fullTime']['home'] ?? null;
    $awayScore = $match['score']['fullTime']['away'] ?? null;

    $resultCode = null;

    if ($homeScore !== null && $awayScore !== null) {

        if ($homeScore > $awayScore) $resultCode = "1";
        if ($homeScore < $awayScore) $resultCode = "2";
        if ($homeScore == $awayScore) $resultCode = "X";

    }

    $stmt = $db->prepare("
    UPDATE matches_v2
    SET
        status=?,
        home_score=?,
        away_score=?,
        result_code=?,
        api_updated_at=NOW()
    WHERE id=?
    ");

    $stmt->bind_param(
        "siisi",
        $status,
        $homeScore,
        $awayScore,
        $resultCode,
        $matchId
    );

    $stmt->execute();

}

echo "Results updated.\n";