<?php

class PickStatsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getCouponPickStats($couponId)
    {
        $couponId = (int)$couponId;

        // 1. validar estado do cupão
        $stmt = $this->db->prepare("
            SELECT status
            FROM coupons_v2
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Erro ao preparar consulta do cupão.");
        }

        $stmt->bind_param("i", $couponId);
        $stmt->execute();
        $stmt->bind_result($status);

        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("Cupão não encontrado.");
        }

        $stmt->close();

        // 2. esconder estatísticas enquanto o cupão estiver aberto
        if ($status === 'OPEN') {
            return [];
        }

        // 3. obter estatísticas reais apenas das picks pertencentes a bets desse cupão
        $stmt = $this->db->prepare("
            SELECT 
                m.id,
                t1.name AS home_team,
                t2.name AS away_team,

                SUM(CASE WHEN bp.pick = '1' THEN 1 ELSE 0 END) AS pick1,
                SUM(CASE WHEN bp.pick = 'X' THEN 1 ELSE 0 END) AS pickX,
                SUM(CASE WHEN bp.pick = '2' THEN 1 ELSE 0 END) AS pick2,
                COUNT(bp.id) AS total_picks

            FROM coupon_matches_v2 cm

            INNER JOIN matches_v2 m
                ON m.id = cm.match_id

            INNER JOIN teams_v2 t1
                ON t1.id = m.home_team_id

            INNER JOIN teams_v2 t2
                ON t2.id = m.away_team_id

            LEFT JOIN bets_v2 b
                ON b.coupon_id = cm.coupon_id

            LEFT JOIN bet_picks_v2 bp
                ON bp.bet_id = b.id
               AND bp.match_id = m.id

            WHERE cm.coupon_id = ?

            GROUP BY m.id, t1.name, t2.name
            ORDER BY m.scheduled_at ASC, m.id ASC
        ");

        if (!$stmt) {
            throw new Exception("Erro ao preparar consulta de estatísticas.");
        }

        $stmt->bind_param("i", $couponId);
        $stmt->execute();

        $stmt->bind_result(
            $matchId,
            $homeTeam,
            $awayTeam,
            $pick1,
            $pickX,
            $pick2,
            $totalPicks
        );

        $stats = [];

        while ($stmt->fetch()) {

            $pick1 = (int)$pick1;
            $pickX = (int)$pickX;
            $pick2 = (int)$pick2;
            $totalPicks = (int)$totalPicks;

            if ($totalPicks <= 0) {
                $percent1 = 0.0;
                $percentX = 0.0;
                $percent2 = 0.0;
            } else {
                $percent1 = round(($pick1 / $totalPicks) * 100, 1);
                $percentX = round(($pickX / $totalPicks) * 100, 1);
                $percent2 = round(($pick2 / $totalPicks) * 100, 1);
            }

            $stats[] = [
                'match_id' => (int)$matchId,
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'total_picks' => $totalPicks,
                'counts' => [
                    '1' => $pick1,
                    'X' => $pickX,
                    '2' => $pick2,
                ],
                'picks' => [
                    '1' => $percent1,
                    'X' => $percentX,
                    '2' => $percent2,
                ],
            ];
        }

        $stmt->close();

        return $stats;
    }
}