<?php

require_once __DIR__ . '/ActivityService.php';

class BetService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function placeBet($userId, $couponId, $predictions)
    {
        if ($couponId <= 0) {
            throw new Exception("Cupão inválido.");
        }

        if (!is_array($predictions) || empty($predictions)) {
            throw new Exception("Tens de escolher os prognósticos.");
        }

        $this->db->begin_transaction();

        try {

            $config = require __DIR__ . '/../../config/app.php';
            $closeMinutes = (int)$config['coupon_close_minutes_before_match'];

            // 1. Obter dados do cupão
            $stmt = $this->db->prepare("
                SELECT id, name, entry_price, max_players, status, betting_closes_at
                FROM coupons_v2
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->bind_result(
                $dbCouponId,
                $couponName,
                $entryPrice,
                $maxPlayers,
                $couponStatus,
                $bettingClosesAt
            );

            if (!$stmt->fetch()) {
                $stmt->close();
                throw new Exception("Cupão não encontrado.");
            }
            $stmt->close();

            if (!empty($couponStatus) && strtoupper($couponStatus) !== 'OPEN') {
                throw new Exception("Este cupão já não está aberto.");
            }

            // 2. Obter jogos do cupão
            $stmt = $this->db->prepare("
                SELECT m.id, m.scheduled_at
                FROM coupon_matches_v2 cm
                INNER JOIN matches_v2 m ON m.id = cm.match_id
                WHERE cm.coupon_id = ?
                ORDER BY m.scheduled_at ASC
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->bind_result($matchId, $scheduledAt);

            $couponMatchIds = [];
            $firstMatchDate = null;

            while ($stmt->fetch()) {
                $couponMatchIds[] = (int)$matchId;

                if ($firstMatchDate === null) {
                    $firstMatchDate = $scheduledAt;
                }
            }

            $stmt->close();

            if (empty($couponMatchIds)) {
                throw new Exception("Este cupão não tem jogos.");
            }

            // 3. Verificar se o cupão já fechou
            $now = date('Y-m-d H:i:s');

            $closeTime = null;

            if (!empty($bettingClosesAt)) {
                $closeTime = $bettingClosesAt;
            } elseif ($firstMatchDate !== null) {
                $closeTime = date(
                    'Y-m-d H:i:s',
                    strtotime($firstMatchDate . " -{$closeMinutes} minutes")
                );
            }

            if ($closeTime !== null && $now >= $closeTime) {
                throw new Exception("Este cupão já fechou para apostas.");
            }

            // 4. Impedir aposta incompleta
            if (count($predictions) !== count($couponMatchIds)) {
                throw new Exception("Tens de preencher todos os jogos do cupão.");
            }

            // 5. Validar match ids e picks
            $couponMatchIdsMap = array_fill_keys($couponMatchIds, true);

            foreach ($predictions as $postedMatchId => $pick) {
                $postedMatchId = (int)$postedMatchId;
                $pick = strtoupper(trim($pick));

                if (!isset($couponMatchIdsMap[$postedMatchId])) {
                    throw new Exception("Existe um jogo inválido na aposta.");
                }

                if (!in_array($pick, ['1', 'X', '2'], true)) {
                    throw new Exception("Existe um prognóstico inválido.");
                }
            }

            // 6. Impedir aposta duplicada
            $stmt = $this->db->prepare("
                SELECT id
                FROM bets_v2
                WHERE coupon_id = ? AND user_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("ii", $couponId, $userId);
            $stmt->execute();
            $stmt->bind_result($existingBetId);

            if ($stmt->fetch()) {
                $stmt->close();
                throw new Exception("Já apostaste neste cupão.");
            }
            $stmt->close();

            // 7. Impedir cupão cheio
            if ((int)$maxPlayers > 0) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*)
                    FROM bets_v2
                    WHERE coupon_id = ?
                ");
                $stmt->bind_param("i", $couponId);
                $stmt->execute();
                $stmt->bind_result($currentPlayers);
                $stmt->fetch();
                $stmt->close();

                if ((int)$currentPlayers >= (int)$maxPlayers) {
                    throw new Exception("Este cupão já está cheio.");
                }
            }

            // 8. Obter wallet com lock
            $stmt = $this->db->prepare("
                SELECT id, balance
                FROM wallets_v2
                WHERE user_id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->bind_result($walletId, $balance);

            if (!$stmt->fetch()) {
                $stmt->close();
                throw new Exception("Wallet não encontrada.");
            }
            $stmt->close();

            // 9. Verificar saldo
            if ((float)$balance < (float)$entryPrice) {
                throw new Exception("Saldo insuficiente.");
            }

            // 10. Criar aposta
            $stmt = $this->db->prepare("
                INSERT INTO bets_v2 (coupon_id, user_id, stake, status)
                VALUES (?, ?, ?, 'ACTIVE')
            ");
            $stmt->bind_param("iid", $couponId, $userId, $entryPrice);
            $stmt->execute();

            $betId = $this->db->insert_id;
            $stmt->close();

            // 11. Inserir picks
            foreach ($predictions as $postedMatchId => $pick) {
                $postedMatchId = (int)$postedMatchId;
                $pick = strtoupper(trim($pick));

                $stmt = $this->db->prepare("
                    INSERT INTO bet_picks_v2 (bet_id, match_id, pick)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iis", $betId, $postedMatchId, $pick);
                $stmt->execute();
                $stmt->close();
            }

            // 12. Debitar wallet
            $newBalance = (float)$balance - (float)$entryPrice;

            $stmt = $this->db->prepare("
                UPDATE wallets_v2
                SET balance = ?
                WHERE id = ?
            ");
            $stmt->bind_param("di", $newBalance, $walletId);
            $stmt->execute();
            $stmt->close();

            // 13. Registar transação
            $amount = -1 * (float)$entryPrice;

            $stmt = $this->db->prepare("
                INSERT INTO transactions_v2
                (wallet_id, user_id, type, amount, description)
                VALUES (?, ?, 'bet', ?, 'Coupon bet')
            ");
            $stmt->bind_param("iid", $walletId, $userId, $amount);
            $stmt->execute();
            $stmt->close();

            // 14. Feed de atividade
            $activity = new ActivityService();
            $activity->log(
                "bet_placed",
                [
                    "coupon_id" => $couponId
                ],
                $userId
            );

            $this->db->commit();

        } catch (Exception $e) {

            $this->db->rollback();
            throw $e;

        }
    }
}