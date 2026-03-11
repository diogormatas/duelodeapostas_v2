<?php

require_once __DIR__ . '/../Repositories/CouponRepository.php';
require_once __DIR__ . '/../../core/Database.php';

class RankingController
{

    public function show($couponId)
    {

        $db = Database::getConnection();

        $repo = new CouponRepository($db);

        $coupon = $repo->getCoupon($couponId);

        if (!$coupon) {
            die("Cup«ªo n«ªo encontrado");
        }

        $stmt = $db->prepare("
            SELECT
                b.id,
                u.username,
                b.score,
                b.stake,
                b.status,
                b.created_at
            FROM bets_v2 b

            JOIN users_v2 u
                ON u.id = b.user_id

            WHERE b.coupon_id=?

            ORDER BY b.score DESC, b.created_at ASC
        ");

        $stmt->bind_param("i",$couponId);

        $stmt->execute();

        $stmt->bind_result(
            $betId,
            $username,
            $score,
            $stake,
            $status,
            $createdAt
        );

        $ranking = [];

        while ($stmt->fetch()) {

            $ranking[] = [
                "bet_id"=>$betId,
                "username"=>$username,
                "score"=>$score,
                "stake"=>$stake,
                "status"=>$status,
                "created_at"=>$createdAt
            ];

        }

        $stmt->close();

        // decidir se picks s«ªo vis«¿veis
        $showPicks = false;

        if ($coupon['status'] !== 'OPEN') {
            $showPicks = true;
        }

        require __DIR__ . '/../../resources/views/ranking.php';

    }

}