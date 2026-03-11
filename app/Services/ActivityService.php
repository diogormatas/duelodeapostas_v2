<?php

require_once __DIR__ . '/../../core/Database.php';

class ActivityService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registar atividade no feed
     *
     * @param string $type
     * @param array $data
     * @param int|null $userId
     */
    public function log($type, $data = [], $userId = null)
    {

        $stmt = $this->db->prepare("
            INSERT INTO activity_feed_v2
            (user_id, type, data, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $json = json_encode($data);

        $stmt->bind_param(
            "iss",
            $userId,
            $type,
            $json
        );

        $stmt->execute();

        $stmt->close();

    }

}