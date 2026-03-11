<?php

require_once __DIR__ . '/../core/Database.php';

$config = require __DIR__ . '/../config/api.php';

$apiToken = $config['football_data']['token'];
$baseUrl = $config['football_data']['base_url'];

$db = Database::getConnection();

$competitions = $db->query("
SELECT id, external_id
FROM competitions_v2
");

while ($c = $competitions->fetch_assoc()) {

    $competitionId = $c['external_id'];

    $url = "$baseUrl/competitions/$competitionId/teams";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Auth-Token: $apiToken"
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['teams'])) {
        continue;
    }

    foreach ($data['teams'] as $t) {

        $externalId = $t['id'];
        $name = $t['name'] ?? null;
        $shortName = $t['shortName'] ?? null;
        $tla = $t['tla'] ?? null;
        $country = $t['area']['name'] ?? null;
        $logo = $t['crest'] ?? null;

        $stmt = $db->prepare("
        INSERT INTO teams_v2
        (external_id, name, short_name, tla, country, logo_url)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            short_name = VALUES(short_name),
            tla = VALUES(tla),
            country = VALUES(country),
            logo_url = VALUES(logo_url)
        ");

        $stmt->bind_param(
            "isssss",
            $externalId,
            $name,
            $shortName,
            $tla,
            $country,
            $logo
        );

        $stmt->execute();
        $stmt->close();
    }

}

echo "Teams synced.\n";