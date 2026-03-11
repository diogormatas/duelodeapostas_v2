<?php

require_once __DIR__ . '/../../core/Database.php';

class PickStatsController
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function show($couponId)
    {

        $couponId = (int)$couponId;

        $sql = "
            SELECT
                m.id,
                ht.name,
                at.name,

                SUM(CASE WHEN bp.pick='1' THEN 1 ELSE 0 END),
                SUM(CASE WHEN bp.pick='X' THEN 1 ELSE 0 END),
                SUM(CASE WHEN bp.pick='2' THEN 1 ELSE 0 END)

            FROM matches_v2 m

            JOIN coupon_matches_v2 cm
                ON cm.match_id = m.id

            LEFT JOIN bet_picks_v2 bp
                ON bp.match_id = m.id

            LEFT JOIN bets_v2 b
                ON b.id = bp.bet_id
                AND b.coupon_id = cm.coupon_id

            JOIN teams_v2 ht
                ON ht.id = m.home_team_id

            JOIN teams_v2 at
                ON at.id = m.away_team_id

            WHERE cm.coupon_id = ?

            GROUP BY m.id

            ORDER BY m.scheduled_at
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i",$couponId);
        $stmt->execute();

        $stmt->bind_result(
            $matchId,
            $home,
            $away,
            $pick1,
            $pickX,
            $pick2
        );

        $stats = [];

        while($stmt->fetch()){

            $stats[] = [
                'match' => $home . " vs " . $away,
                '1' => (int)($pick1 ?? 0),
                'X' => (int)($pickX ?? 0),
                '2' => (int)($pick2 ?? 0)
            ];

        }

        $stmt->close();

        require __DIR__ . '/../../resources/views/pick_stats.php';

    }


    public function percentages($id)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("

        SELECT
        bp.match_id,
        bp.pick,
        COUNT(*) as total

        FROM bet_picks_v2 bp
        JOIN bets_v2 b ON b.id = bp.bet_id

        WHERE b.coupon_id = ?

        GROUP BY bp.match_id, bp.pick

        ");

        $stmt->bind_param("i",$id);
        $stmt->execute();

        $stmt->bind_result($match,$pick,$count);

        $data=[];

        while($stmt->fetch()){

            $data[$match][$pick]=$count;

        }

        $stmt->close();

        header("Content-Type: application/json");

        echo json_encode($data);

    }

}