<?php

require_once __DIR__ . '/../../core/Database.php';

class AdminCouponsController
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function checkAdmin()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'ADMIN'){

            http_response_code(403);
            echo "Access denied";
            exit;

        }

    }

    public function index()
    {

        $this->checkAdmin();

        $sql = "
            SELECT 
                c.id,
                c.type,
                c.entry_price,
                c.status,
                c.prize_status,
                c.created_at,

                COUNT(DISTINCT cm.match_id) AS matches,
                COUNT(DISTINCT b.id) AS players,
                SUM(b.stake) AS pool

            FROM coupons_v2 c

            LEFT JOIN coupon_matches_v2 cm
                ON cm.coupon_id = c.id

            LEFT JOIN bets_v2 b
                ON b.coupon_id = c.id

            GROUP BY c.id

            ORDER BY c.id DESC
            LIMIT 100
        ";

        $result = $this->db->query($sql);

        $coupons = [];

        while ($row = $result->fetch_assoc()) {
            $coupons[] = $row;
        }

        require __DIR__ . '/../../resources/views/admin/coupons.php';

    }

}