<?php

require_once __DIR__ . '/../core/Database.php';

$config = require __DIR__ . '/../config/api.php';

$apiToken = $config['football_data']['token'];
$baseUrl = $config['football_data']['base_url'];

$url = "$baseUrl/competitions";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Auth-Token: $apiToken"
]);

$response = curl_exec($ch);

curl_close($ch);

$data = json_decode($response, true);

$db = Database::getConnection();

foreach ($data['competitions'] as $c) {

    $externalId = $c['id'];
    $code = $c['code'];
    $name = $c['name'];
    $country = $c['area']['name'] ?? null;

    $stmt = $db->prepare("
    INSERT INTO competitions_v2
    (external_id, code, name, country)
    VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE
        code = VALUES(code),
        name = VALUES(name),
        country = VALUES(country)
    ");

    $stmt->bind_param(
        "isss",
        $externalId,
        $code,
        $name,
        $country
    );

    $stmt->execute();
}

echo "Competitions synced.\n";