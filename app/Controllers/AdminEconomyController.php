<?php

require_once __DIR__ . '/../../core/Database.php';

class AdminEconomyController
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

        $walletSupply = $this->db->query("
            SELECT COALESCE(SUM(balance),0) total
            FROM wallets_v2
        ")->fetch_assoc()['total'];

        $totalBets = $this->db->query("
            SELECT COUNT(*) c
            FROM bets_v2
        ")->fetch_assoc()['c'];

        $volume = $this->db->query("
            SELECT COALESCE(SUM(stake),0) total
            FROM bets_v2
        ")->fetch_assoc()['total'];

        $openCoupons = $this->db->query("
            SELECT COUNT(*) c
            FROM coupons_v2
            WHERE status='OPEN'
        ")->fetch_assoc()['c'];

        $pendingDuels = $this->db->query("
            SELECT COUNT(*) c
            FROM duels_v2
            WHERE status='PENDING'
        ")->fetch_assoc()['c'];

        $jackpot = $this->db->query("
            SELECT amount
            FROM monthly_jackpots_v2
            ORDER BY year DESC, month DESC
            LIMIT 1
        ")->fetch_assoc()['amount'] ?? 0;

        require __DIR__ . '/../../resources/views/admin/economy.php';

    }

}