<?php

class CouponRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getOpenCoupons()
    {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.entry_price,
                c.max_players,
                COUNT(b.id) AS current_players
            FROM coupons_v2 c
            LEFT JOIN bets_v2 b ON b.coupon_id = c.id
            WHERE c.status = 'OPEN'
            GROUP BY c.id, c.name, c.entry_price, c.max_players
            ORDER BY c.created_at DESC
        ";

        $result = $this->db->query($sql);

        $coupons = [];

        while ($row = $result->fetch_assoc()) {
            $coupons[] = $row;
        }

        return $coupons;
    }
    
    public function getCoupon($id)
    {
    
        $stmt = $this->db->prepare("
    
        SELECT
        id,
        name,
        type,
        entry_price,
        max_players,
        status,
        created_at
    
        FROM coupons_v2
    
        WHERE id = ?
    
        LIMIT 1
    
        ");
    
        $stmt->bind_param("i", $id);
    
        $stmt->execute();
    
        $stmt->bind_result(
            $cid,
            $name,
            $type,
            $entryPrice,
            $maxPlayers,
            $status,
            $createdAt
        );
    
        $coupon = null;
    
        if($stmt->fetch()){
    
            $coupon = [
                "id"=>$cid,
                "name"=>$name,
                "type"=>$type,
                "entry_price"=>$entryPrice,
                "max_players"=>$maxPlayers,
                "status"=>$status,
                "created_at"=>$createdAt
            ];
    
        }
    
        $stmt->close();
    
        return $coupon;
    
    }
    
    
    public function getCouponMatches($couponId)
    {
    
        $stmt = $this->db->prepare("
    
        SELECT
    
        m.id,
        m.scheduled_at,
        m.home_score,
        m.away_score,
        m.result_code,
    
        ht.name AS home_team,
        at.name AS away_team,
    
        ht.logo_url AS home_logo,
        at.logo_url AS away_logo,
    
        c.name AS competition
    
        FROM coupon_matches_v2 cm
    
        JOIN matches_v2 m
        ON m.id = cm.match_id
    
        JOIN teams_v2 ht
        ON ht.id = m.home_team_id
    
        JOIN teams_v2 at
        ON at.id = m.away_team_id
    
        JOIN competitions_v2 c
        ON c.id = m.competition_id
    
        WHERE cm.coupon_id = ?
    
        ORDER BY m.scheduled_at ASC
    
        ");
    
        $stmt->bind_param("i", $couponId);
    
        $stmt->execute();
    
        $stmt->bind_result(
            $id,
            $scheduledAt,
            $homeScore,
            $awayScore,
            $resultCode,
            $homeTeam,
            $awayTeam,
            $homeLogo,
            $awayLogo,
            $competition
        );
    
        $matches = [];
    
        while ($stmt->fetch()) {
    
            $matches[] = [
                "id" => $id,
                "scheduled_at" => $scheduledAt,
                "home_score" => $homeScore,
                "away_score" => $awayScore,
                "result_code" => $resultCode,
                "home_team" => $homeTeam,
                "away_team" => $awayTeam,
                "home_logo" => $homeLogo,
                "away_logo" => $awayLogo,
                "competition" => $competition
            ];
    
        }
    
        $stmt->close();
    
        return $matches;
    
    }

}