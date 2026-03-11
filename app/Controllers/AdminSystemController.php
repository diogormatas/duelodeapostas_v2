<?php

require_once __DIR__ . '/../../core/Database.php';

class AdminSystemController
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function checkAdmin()
    {

        if(session_status()===PHP_SESSION_NONE){
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

        /*
        -------------------------
        PLATFORM
        -------------------------
        */

        $matches = $this->db->query("SELECT COUNT(*) c FROM matches_v2")->fetch_assoc()['c'];
        $teams = $this->db->query("SELECT COUNT(*) c FROM teams_v2")->fetch_assoc()['c'];
        $competitions = $this->db->query("SELECT COUNT(*) c FROM competitions_v2")->fetch_assoc()['c'];
        $users = $this->db->query("SELECT COUNT(*) c FROM users_v2")->fetch_assoc()['c'];

        /*
        -------------------------
        BETTING ENGINE
        -------------------------
        */

        $coupons = $this->db->query("SELECT COUNT(*) c FROM coupons_v2")->fetch_assoc()['c'];

        $openCoupons = $this->db->query("
            SELECT COUNT(*) c
            FROM coupons_v2
            WHERE status='OPEN'
        ")->fetch_assoc()['c'];

        $bets = $this->db->query("SELECT COUNT(*) c FROM bets_v2")->fetch_assoc()['c'];

        $pendingDuels = $this->db->query("
            SELECT COUNT(*) c
            FROM duels_v2
            WHERE status='PENDING'
        ")->fetch_assoc()['c'];

        /*
        -------------------------
        FINANCE
        -------------------------
        */

        $wallets = $this->db->query("
            SELECT SUM(balance) total
            FROM wallets_v2
        ")->fetch_assoc()['total'] ?? 0;

        $volume = $this->db->query("
            SELECT SUM(stake) total
            FROM bets_v2
        ")->fetch_assoc()['total'] ?? 0;

        $jackpot = $this->db->query("
            SELECT amount
            FROM monthly_jackpots_v2
            ORDER BY year DESC, month DESC
            LIMIT 1
        ")->fetch_assoc()['amount'] ?? 0;

        /*
        -------------------------
        SYSTEM HEALTH
        -------------------------
        */

        $apiStatus = "OK";

        try{

            $config = require __DIR__ . '/../../config/api.php';

            $url = $config['football_data']['base_url']."/matches?limit=1";

            $ch = curl_init($url);

            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch,CURLOPT_TIMEOUT,3);

            $response = curl_exec($ch);

            if(!$response){
                $apiStatus="ERROR";
            }

            curl_close($ch);

        }catch(Exception $e){

            $apiStatus="ERROR";

        }

        $futureMatches = $this->db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE scheduled_at > NOW()
        ")->fetch_assoc()['c'];

        if($futureMatches > 50){
            $matchHealth="OK";
        }elseif($futureMatches > 10){
            $matchHealth="LOW";
        }else{
            $matchHealth="CRITICAL";
        }

        $missingResults = $this->db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE status='FINISHED'
            AND result_code IS NULL
        ")->fetch_assoc()['c'];

        require __DIR__ . '/../../resources/views/admin/system.php';

    }

}