<?php

namespace App\Repositories;

use PDO;

class TransactionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO transactions_v2
            (wallet_id, user_id, type, amount, description, reference_id, balance_after, created_at)
            VALUES
            (:wallet_id, :user_id, :type, :amount, :description, :reference_id, :balance_after, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'wallet_id' => $data['wallet_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'reference_id' => $data['reference_id'],
            'balance_after' => $data['balance_after'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}