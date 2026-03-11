<?php

require_once __DIR__ . '/../../core/AuthMiddleware.php';

class AccountController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function index()
    {
        AuthMiddleware::check();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'];

        $stmt = $this->db->prepare("
            SELECT balance
            FROM wallets_v2
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($balance);
        $stmt->fetch();
        $stmt->close();

        $balance = $balance ?? 0;

        $stmt = $this->db->prepare("
            SELECT SUM(amount)
            FROM transactions_v2
            WHERE user_id = ?
            AND type = 'prize'
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($totalWins);
        $stmt->fetch();
        $stmt->close();

        $totalWins = $totalWins ?? 0;

        $stmt = $this->db->prepare("
            SELECT
            type,
            amount,
            description,
            created_at
            FROM transactions_v2
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result(
            $type,
            $amount,
            $description,
            $createdAt
        );

        $transactions = [];

        while ($stmt->fetch()) {
            $transactions[] = [
                "type" => $type,
                "amount" => $amount,
                "description" => $description,
                "created_at" => $createdAt
            ];
        }

        $stmt->close();

        require __DIR__ . '/../../resources/views/account.php';
    }
}