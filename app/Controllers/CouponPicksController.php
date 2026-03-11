<?php

require_once __DIR__ . '/../Repositories/CouponRepository.php';
require_once __DIR__ . '/../../core/Database.php';

class CouponPicksController
{

    public function index($couponId)
    {

        $db = Database::getConnection();

        $repo = new CouponRepository($db);

        $matches = $repo->getCouponMatches($couponId);

        $stmt = $db->prepare("

        SELECT
        u.username,
        bp.match_id,
        bp.pick

        FROM bets_v2 b

        JOIN users_v2 u
        ON u.id = b.user_id

        JOIN bet_picks_v2 bp
        ON bp.bet_id = b.id

        WHERE b.coupon_id = ?

        ");

        $stmt->bind_param("i", $couponId);

        $stmt->execute();

        $stmt->bind_result(
            $username,
            $matchId,
            $pick
        );

        $players = [];
        $picks = [];

        while ($stmt->fetch()) {

            if (!in_array($username, $players)) {
                $players[] = $username;
            }

            $picks[$matchId][$username] = $pick;

        }

        $stmt->close();

        foreach ($matches as &$m) {

            $id = $m['id'];

            $m['picks'] = $picks[$id] ?? [];

        }

        require __DIR__ . '/../../resources/views/coupon_picks.php';

    }

}