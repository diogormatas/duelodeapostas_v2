<?php

require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

class DashboardController
{

    public function index()
    {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        AuthMiddleware::check();

        $db = Database::getConnection();


        /*
        -----------------------------
        JACKPOT
        -----------------------------
        */

        $jackpot = [
            "amount" => 0
        ];

        $stmt = $db->prepare("
            SELECT amount
            FROM monthly_jackpots_v2
            ORDER BY year DESC, month DESC
            LIMIT 1
        ");

        $stmt->execute();
        $stmt->store_result();

        $stmt->bind_result($amount);

        if ($stmt->fetch()) {

            $jackpot["amount"] = $amount ?? 0;

        }

        $stmt->close();



        /*
        -----------------------------
        RANKING SIMPLES
        -----------------------------
        */
        
        $ranking = [];
        
        $result = $db->query("
            SELECT 
                u.username,
                COUNT(b.id) as bets
            FROM bets_v2 b
            JOIN users_v2 u ON u.id = b.user_id
            GROUP BY b.user_id
            ORDER BY bets DESC
            LIMIT 3
        ");
        
        if ($result) {
        
            while ($row = $result->fetch_assoc()) {
        
                $ranking[] = [
                    "username"=>$row["username"],
                    "points"=>$row["bets"]
                ];
        
            }
        
        }



        /*
        -----------------------------
        ACTIVITY FEED
        -----------------------------
        */

        $activity = [];

        $stmt = $db->prepare("
            SELECT 
                a.type,
                a.data,
                a.created_at,
                u.username
            FROM activity_feed_v2 a
            LEFT JOIN users_v2 u ON u.id = a.user_id
            ORDER BY a.created_at DESC
            LIMIT 20
        ");

        $stmt->execute();
        $stmt->store_result();

        $stmt->bind_result(
            $type,
            $data,
            $created_at,
            $username
        );

        while ($stmt->fetch()) {

            $activity[] = [
                "type"=>$type,
                "data"=>$data ? json_decode($data,true) : [],
                "created_at"=>$created_at,
                "username"=>$username
            ];

        }

        $stmt->close();



        /*
        -----------------------------
        OPEN COUPONS
        -----------------------------
        */

        $openCoupons = [];

        $stmt = $db->prepare("
            SELECT 
                c.id,
                c.entry_price,
                c.max_players,
                COUNT(b.id) as players
            FROM coupons_v2 c
            LEFT JOIN bets_v2 b ON b.coupon_id = c.id
            WHERE c.status='OPEN'
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT 5
        ");

        $stmt->execute();
        $stmt->store_result();

        $stmt->bind_result(
            $couponId,
            $entryPrice,
            $maxPlayers,
            $players
        );

        while ($stmt->fetch()) {

            $openCoupons[] = [
                "id"=>$couponId,
                "entry_price"=>$entryPrice,
                "max_players"=>$maxPlayers,
                "players"=>$players
            ];

        }

        $stmt->close();


        require __DIR__ . '/../../resources/views/dashboard.php';

    }

}