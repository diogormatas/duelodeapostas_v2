<?php

namespace App\Repositories;

use PDO;

class WalletRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getWalletByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM wallets_v2 WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $wallet ?: null;
    }

    public function getWalletForUpdate(int $userId): ?array
    {
        $sql = "SELECT * FROM wallets_v2 WHERE user_id = :user_id LIMIT 1 FOR UPDATE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $wallet ?: null;
    }

    public function updateBalance(int $walletId, float $balance): void
    {
        $sql = "UPDATE wallets_v2 SET balance = :balance WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'balance' => $balance,
            'id' => $walletId,
        ]);
    }
}