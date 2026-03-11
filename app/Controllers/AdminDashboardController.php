<?php

class AdminDashboardController
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function checkAdmin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {

            header("Location: /dashboard");
            exit;

        }
    }

    public function index()
    {

        $this->checkAdmin();

        $db = $this->db;

        // -------------------
        // PLATFORM
        // -------------------

        $matches = $db->query("SELECT COUNT(*) c FROM matches_v2")->fetch_assoc()['c'];
        $teams = $db->query("SELECT COUNT(*) c FROM teams_v2")->fetch_assoc()['c'];
        $competitions = $db->query("SELECT COUNT(*) c FROM competitions_v2")->fetch_assoc()['c'];
        $users = $db->query("SELECT COUNT(*) c FROM users_v2")->fetch_assoc()['c'];

        // -------------------
        // BETTING ENGINE
        // -------------------

        $coupons = $db->query("SELECT COUNT(*) c FROM coupons_v2")->fetch_assoc()['c'];
        $openCoupons = $db->query("SELECT COUNT(*) c FROM coupons_v2 WHERE status='OPEN'")->fetch_assoc()['c'];
        $bets = $db->query("SELECT COUNT(*) c FROM bets_v2")->fetch_assoc()['c'];
        $pendingDuels = $db->query("SELECT COUNT(*) c FROM duels_v2 WHERE status='PENDING'")->fetch_assoc()['c'];

        // -------------------
        // FINANCE
        // -------------------

        $wallets = $db->query("SELECT SUM(balance) s FROM wallets_v2")->fetch_assoc()['s'] ?? 0;

        $volume = $db->query("
            SELECT SUM(stake) s
            FROM bets_v2
        ")->fetch_assoc()['s'] ?? 0;

        $jackpot = $db->query("
            SELECT amount
            FROM monthly_jackpots_v2
            ORDER BY id DESC
            LIMIT 1
        ")->fetch_assoc()['amount'] ?? 0;

        // -------------------
        // SYSTEM HEALTH
        // -------------------

        $missingResults = $db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE status='FINISHED'
            AND result_code IS NULL
        ")->fetch_assoc()['c'];

        $futureMatches = $db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE scheduled_at > NOW()
        ")->fetch_assoc()['c'];

        $lastApi = $db->query("
            SELECT MAX(api_updated_at) t
            FROM matches_v2
        ")->fetch_assoc()['t'];

        // -------------------
        // HEALTH LOGIC
        // -------------------

        $apiStatus = "OK";

        if (!$lastApi) {

            $apiStatus = "DOWN";

        } else {

            $minutes = (time() - strtotime($lastApi)) / 60;

            if ($minutes > 180) {
                $apiStatus = "DOWN";
            } elseif ($minutes > 60) {
                $apiStatus = "DELAY";
            }

        }

        $matchHealth = "OK";

        if ($futureMatches < 20) {
            $matchHealth = "LOW";
        }

        if ($futureMatches < 5) {
            $matchHealth = "CRITICAL";
        }

        require __DIR__ . '/../../resources/views/admin/system.php';

    }

}