
<?php

class AdminCronController
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function checkAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {

            http_response_code(403);
            echo "Precisa de login.";
            exit;

        }
    }

    public function index()
    {

        $this->checkAdmin();

        $jobs = [

            'process_results',
            'close_coupons',
            'auto_settle',
            'generate_liga_tips'

        ];

        $status = [];

        foreach ($jobs as $job) {

            $stmt = $this->db->prepare("
                SELECT created_at
                FROM system_logs_v2
                WHERE action = ?
                ORDER BY id DESC
                LIMIT 1
            ");

            $stmt->bind_param("s",$job);
            $stmt->execute();
            $stmt->bind_result($date);
            $stmt->fetch();
            $stmt->close();

            $status[$job] = $date ?: 'never';

        }

        require __DIR__ . '/../../resources/views/admin/cron_status.php';

    }

}
