<?php

require_once __DIR__ . '/../../core/AuthMiddleware.php';

class BetsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function index()
    {
        AuthMiddleware::check();

        $sql = "
        SELECT

        c.id,
        c.name,
        c.entry_price,
        c.max_players,
        c.created_at,
        c.betting_closes_at,

        COUNT(b.id) AS players

        FROM coupons_v2 c

        LEFT JOIN bets_v2 b
        ON b.coupon_id = c.id

        WHERE c.status = 'OPEN'
        AND (c.betting_closes_at IS NULL OR c.betting_closes_at > NOW())

        GROUP BY c.id

        ORDER BY c.betting_closes_at ASC, c.created_at DESC

        LIMIT 30
        ";

        $result = $this->db->query($sql);

        $openCoupons = [];

        while ($row = $result->fetch_assoc()) {

            $row['minutes_left'] = null;

            if (!empty($row['betting_closes_at'])) {
                $minutes = floor(
                    (strtotime($row['betting_closes_at']) - time()) / 60
                );
                $row['minutes_left'] = $minutes;
            }

            $stmt = $this->db->prepare("
                SELECT u.username
                FROM bets_v2 b
                JOIN users_v2 u ON u.id = b.user_id
                WHERE b.coupon_id = ?
                LIMIT 3
            ");

            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $stmt->bind_result($username);

            $playersList = [];

            while ($stmt->fetch()) {
                $playersList[] = $username;
            }

            $stmt->close();

            $row['players_list'] = $playersList;

            $openCoupons[] = $row;
        }

        require __DIR__ . '/../../resources/views/bets.php';
    }
}