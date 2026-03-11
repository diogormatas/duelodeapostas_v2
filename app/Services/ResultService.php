<?php

class ResultService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function processFinishedMatches()
    {
        $this->db->begin_transaction();
    
        try {
    
            // 1️⃣ obter jogos finalizados com resultado
            $stmt = $this->db->prepare("
                SELECT id
                FROM matches_v2
                WHERE status = 'FINISHED'
                AND result_code IS NOT NULL
            ");
    
            $stmt->execute();
    
            $stmt->bind_result($matchId);
    
            $matchIds = [];
    
            while ($stmt->fetch()) {
                $matchIds[] = $matchId;
            }
    
            $stmt->close();
    
            if (empty($matchIds)) {
                $this->db->commit();
                return 0;
            }
    
            $ids = implode(",", array_map('intval', $matchIds));
    
            // 2️⃣ atualizar picks corretas
            $sql = "
                UPDATE bet_picks_v2 bp
                INNER JOIN matches_v2 m ON m.id = bp.match_id
                SET bp.is_correct = CASE
                    WHEN bp.pick = m.result_code THEN 1
                    ELSE 0
                END
                WHERE m.id IN ($ids)
            ";
    
            $this->db->query($sql);
    
            // 3️⃣ jogos cancelados/adiados
            $sql = "
                UPDATE bet_picks_v2 bp
                INNER JOIN matches_v2 m ON m.id = bp.match_id
                SET bp.is_correct = NULL
                WHERE m.status IN ('POSTPONED','CANCELLED')
                AND bp.match_id IN ($ids)
            ";
    
            $this->db->query($sql);
    
            // 4️⃣ recalcular score
            $sql = "
                UPDATE bets_v2 b
                SET b.score = (
                    SELECT COUNT(*)
                    FROM bet_picks_v2 bp
                    WHERE bp.bet_id = b.id
                    AND bp.is_correct = 1
                )
                WHERE b.id IN (
                    SELECT DISTINCT bet_id
                    FROM bet_picks_v2
                    WHERE match_id IN ($ids)
                )
            ";
    
            $this->db->query($sql);
    
            $this->db->commit();
    
            return count($matchIds);
    
        } catch (Exception $e) {
    
            $this->db->rollback();
            throw $e;
    
        }
    }
}