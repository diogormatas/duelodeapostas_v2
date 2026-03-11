<?php

namespace App\Repositories;

use PDO;

class BetRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function userAlreadyBetOnCoupon(int $userId, int $couponId): bool
    {
        $sql = "SELECT COUNT(*) FROM bets_v2 WHERE user_id = :user_id AND coupon_id = :coupon_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'coupon_id' => $couponId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countCouponBets(int $couponId): int
    {
        $sql = "SELECT COUNT(*) FROM bets_v2 WHERE coupon_id = :coupon_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'coupon_id' => $couponId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function createBet(array $data): int
    {
        $sql = "
            INSERT INTO bets_v2 (user_id, coupon_id, amount, status, created_at)
            VALUES (:user_id, :coupon_id, :amount, :status, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'coupon_id' => $data['coupon_id'],
            'amount' => $data['amount'],
            'status' => $data['status'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createBetPick(array $data): void
    {
        $sql = "
            INSERT INTO bet_picks_v2 (bet_id, match_id, prediction, created_at)
            VALUES (:bet_id, :match_id, :prediction, NOW())
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'bet_id' => $data['bet_id'],
            'match_id' => $data['match_id'],
            'prediction' => $data['prediction'],
        ]);
    }
}