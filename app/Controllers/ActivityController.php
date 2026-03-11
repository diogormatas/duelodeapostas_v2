<?php

class ActivityController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function index()
    {
        $activities = [];

        $sql = "

        SELECT
        'duel_created' AS type,
        d.created_at AS activity_date,
        u.username AS actor,
        NULL AS target,
        d.stake,
        d.id AS duel_id,
        d.coupon_id

        FROM duels_v2 d
        JOIN users_v2 u ON u.id = d.challenger_id

        UNION ALL

        SELECT
        'duel_accepted' AS type,
        d.created_at AS activity_date,
        u1.username AS actor,
        u2.username AS target,
        d.stake,
        d.id AS duel_id,
        d.coupon_id

        FROM duels_v2 d
        JOIN users_v2 u1 ON u1.id = d.challenger_id
        JOIN users_v2 u2 ON u2.id = d.opponent_id
        WHERE d.status IN ('ACCEPTED','FINISHED')

        ORDER BY activity_date DESC
        LIMIT 50

        ";

        $result = $this->db->query($sql);

        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        require __DIR__ . '/../../resources/views/activity.php';
    }
}