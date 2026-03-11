<?php

require_once __DIR__ . '/../Repositories/CouponRepository.php';
require_once __DIR__ . '/../../core/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Database.php';

class CouponsController
{
    public function index()
    {
        AuthMiddleware::check();

        $db = Database::getConnection();

        $repo = new CouponRepository($db);

        $coupons = $repo->getOpenCoupons();

        require __DIR__ . '/../../resources/views/coupons.php';
    }
    
    public function show($id)
    {
    
        AuthMiddleware::check();
    
        $db = Database::getConnection();
    
        $repo = new CouponRepository($db);
    
        $coupon = $repo->getCoupon($id);
    
        $matches = $repo->getCouponMatches($id);
    
        // Se for duelo carregar picks
        if ($coupon['type'] === 'DUELO') {
    
            $stmt = $db->prepare("
    
            SELECT
    
            b.id,
            b.user_id,
            u.username,
            b.score
    
            FROM bets_v2 b
    
            JOIN users_v2 u
            ON u.id = b.user_id
    
            WHERE b.coupon_id=?
    
            ");
    
            $stmt->bind_param("i",$id);
            $stmt->execute();
    
            $stmt->bind_result(
                $betId,
                $userId,
                $username,
                $score
            );
    
            $players=[];
    
            while($stmt->fetch()){
    
                $players[]=[
                    "bet_id"=>$betId,
                    "user_id"=>$userId,
                    "username"=>$username,
                    "score"=>$score
                ];
    
            }
    
            $stmt->close();
    
            // picks
    
            $stmt = $db->prepare("
    
            SELECT
    
            bp.bet_id,
            bp.match_id,
            bp.pick
    
            FROM bet_picks_v2 bp
    
            JOIN bets_v2 b
            ON b.id = bp.bet_id
    
            WHERE b.coupon_id=?
    
            ");
    
            $stmt->bind_param("i",$id);
            $stmt->execute();
    
            $stmt->bind_result(
                $betId,
                $matchId,
                $pick
            );
    
            $picks=[];
    
            while($stmt->fetch()){
    
                $picks[$betId][$matchId]=$pick;
    
            }
    
            $stmt->close();
    
            require __DIR__ . '/../../resources/views/duel_view.php';
    
            return;
    
        }
    
        require __DIR__ . '/../../resources/views/coupon.php';
    
    }
    
    public function create()
    {
        $db = Database::getConnection();
    
        $result = $db->query("
            SELECT
            m.id,
            m.scheduled_at,
            ht.name AS home,
            at.name AS away,
            c.name AS competition
            FROM matches_v2 m
            JOIN teams_v2 ht ON ht.id=m.home_team_id
            JOIN teams_v2 at ON at.id=m.away_team_id
            JOIN competitions_v2 c ON c.id=m.competition_id
            WHERE m.status='SCHEDULED'
            ORDER BY m.scheduled_at
            LIMIT 200
        ");
    
        $matches=[];
    
        while($row=$result->fetch_assoc())
            $matches[]=$row;
    
        require __DIR__.'/../../resources/views/coupon_create.php';
    }

}