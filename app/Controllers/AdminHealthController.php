<?php

require_once __DIR__.'/../../core/Database.php';

class AdminHealthController
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

        if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null)!=='ADMIN'){

            http_response_code(403);
            echo "Access denied";
            exit;

        }

    }

    public function index()
    {

        $this->checkAdmin();

        /*
        DB
        */

        $dbStatus = "OK";

        try{
            $this->db->query("SELECT 1");
        }catch(Exception $e){
            $dbStatus="ERROR";
        }

        /*
        CRON
        */

        $cron = [];

        $jobs = [
            'process_results',
            'close_coupons',
            'auto_settle',
            'expire_duels'
        ];

        foreach($jobs as $job){

            $stmt = $this->db->prepare("
                SELECT created_at
                FROM system_logs_v2
                WHERE action=?
                ORDER BY id DESC
                LIMIT 1
            ");

            $stmt->bind_param("s",$job);
            $stmt->execute();
            $stmt->bind_result($date);
            $stmt->fetch();
            $stmt->close();

            $cron[$job]=$date ?: 'never';

        }

        /*
        MATCHES
        */

        $futureMatches = $this->db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE scheduled_at > NOW()
        ")->fetch_assoc()['c'];

        $missingResults = $this->db->query("
            SELECT COUNT(*) c
            FROM matches_v2
            WHERE status='FINISHED'
            AND result_code IS NULL
        ")->fetch_assoc()['c'];

        /*
        ERRORS
        */

        $errors24h = $this->db->query("
            SELECT COUNT(*) c
            FROM system_logs_v2
            WHERE level='ERROR'
            AND created_at > NOW() - INTERVAL 24 HOUR
        ")->fetch_assoc()['c'];

        require __DIR__.'/../../resources/views/admin/health.php';

    }

}