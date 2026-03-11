<?php

require_once __DIR__ . '/../../core/Database.php';

class AdminLogsController
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

        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'ADMIN') {

            http_response_code(403);
            echo "Access denied";
            exit;

        }

    }

    public function index()
    {

        $this->checkAdmin();

        $conditions = [];

        if (!empty($_GET['level'])) {

            $level = $this->db->real_escape_string($_GET['level']);
            $conditions[] = "level = '$level'";

        }

        if (!empty($_GET['category'])) {

            $cat = $this->db->real_escape_string($_GET['category']);
            $conditions[] = "category = '$cat'";

        }

        if (!empty($_GET['user_id'])) {

            $uid = (int)$_GET['user_id'];
            $conditions[] = "user_id = $uid";

        }

        if (!empty($_GET['request_id'])) {

            $req = $this->db->real_escape_string($_GET['request_id']);
            $conditions[] = "request_id = '$req'";

        }

        $sql = "SELECT * FROM system_logs_v2";

        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY id DESC LIMIT 200";

        $result = $this->db->query($sql);

        $logs = [];

        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        require __DIR__ . '/../../resources/views/admin/system_logs.php';

    }

}