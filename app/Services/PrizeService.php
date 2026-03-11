<?php

class PrizeService
{
    private $db;
    private $tableColumnsCache = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function settleCoupon($couponId)
    {
        $couponId = (int)$couponId;

        $this->db->begin_transaction();

        try {
            // Lock do cupão para evitar double settlement
            $stmt = $this->db->prepare("
                SELECT prize_status, status
                FROM coupons_v2
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->bind_result($prizeStatus, $couponStatus);

            if (!$stmt->fetch()) {
                $stmt->close();
                throw new Exception("Cupão não encontrado.");
            }
            $stmt->close();

            if ($prizeStatus !== 'PENDING') {
                throw new Exception("Cupão já processado.");
            }

            // Obter e lockar apostas do cupão
            $stmt = $this->db->prepare("
                SELECT id, user_id, stake, score, created_at, status
                FROM bets_v2
                WHERE coupon_id = ?
                ORDER BY score DESC, created_at ASC, id ASC
                FOR UPDATE
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->bind_result($betId, $userId, $stake, $score, $createdAt, $betStatus);

            $bets = [];

            while ($stmt->fetch()) {
                $bets[] = [
                    'bet_id'     => (int)$betId,
                    'user_id'    => (int)$userId,
                    'stake'      => (float)$stake,
                    'score'      => (float)$score,
                    'created_at' => $createdAt,
                    'status'     => $betStatus,
                ];
            }
            $stmt->close();

            $players = count($bets);

            // Refund se houver 0 ou 1 jogador
            if ($players <= 1) {
                foreach ($bets as $bet) {
                    $this->refundBet($couponId, $bet);
                }

                $newPrizeStatus = 'REFUNDED';

                $stmt = $this->db->prepare("
                    UPDATE coupons_v2
                    SET prize_status = ?,
                        status = 'SETTLED',
                        settled_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $newPrizeStatus, $couponId);
                $stmt->execute();
                $stmt->close();

                $this->db->commit();
                return;
            }

            // Pool total em cêntimos
            $poolCents = 0;
            foreach ($bets as $bet) {
                $poolCents += $this->toCents($bet['stake']);
            }

            // 10% jackpot, 90% prémios
            $config = require __DIR__ . '/../../config/app.php';

            $jackpotPercent = $config['jackpot_percentage'];
            
            $jackpotCents = (int) round($poolCents * ($jackpotPercent / 100));
            
            $prizePoolCents = $poolCents - $jackpotCents;

            // Jackpot mensal
            $this->addToJackpot($couponId, $this->fromCents($jackpotCents));

            // Tabela de distribuição por posição
            $distribution = $this->getPrizeDistribution($players);

            // Distribuir prizePool por posições de forma exata ao cêntimo
            $positionPrizeCents = $this->allocateByPercentages($prizePoolCents, $distribution);

            // Agrupar apostas por score (empates)
            $groups = [];
            foreach ($bets as $bet) {
                $scoreKey = (string)$bet['score'];
                if (!isset($groups[$scoreKey])) {
                    $groups[$scoreKey] = [];
                }
                $groups[$scoreKey][] = $bet;
            }

            // Ordenar scores desc
            uksort($groups, function ($a, $b) {
                return ((float)$b <=> (float)$a);
            });

            $position = 1;

            foreach ($groups as $score => $group) {
                $groupSize = count($group);

                // Somar prémios das posições ocupadas pelo grupo empatado
                $groupPrizeCents = 0;
                for ($i = 0; $i < $groupSize; $i++) {
                    $pos = $position + $i;
                    if (isset($positionPrizeCents[$pos])) {
                        $groupPrizeCents += $positionPrizeCents[$pos];
                    }
                }

                // Se este grupo ainda apanha posições premiadas
                if ($groupPrizeCents > 0) {
                    $splitAmounts = $this->splitCentsEqually($groupPrizeCents, $groupSize);

                    foreach ($group as $index => $bet) {
                        $amountCents = $splitAmounts[$index];
                        $amount = $this->fromCents($amountCents);

                        $percentage = 0.0;
                        if ($prizePoolCents > 0) {
                            $percentage = round(($amountCents / $prizePoolCents) * 100, 4);
                        }

                        $this->payPrize(
                            $couponId,
                            $bet,
                            $position,
                            $percentage,
                            $amount
                        );
                    }
                }

                $position += $groupSize;
            }

            // Todas as restantes ACTIVE passam para LOST
            $stmt = $this->db->prepare("
                UPDATE bets_v2
                SET status = 'LOST'
                WHERE coupon_id = ?
                  AND status = 'ACTIVE'
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->close();

            // Fechar cupão
            $stmt = $this->db->prepare("
                UPDATE coupons_v2
                SET status = 'SETTLED',
                    prize_status = 'PAID',
                    settled_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $couponId);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function refundBet($couponId, array $bet)
    {
        $wallet = $this->getWalletForUser($bet['user_id']);
        $amount = (float)$bet['stake'];

        $stmt = $this->db->prepare("
            UPDATE wallets_v2
            SET balance = balance + ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $amount, $wallet['id']);
        $stmt->execute();
        $stmt->close();

        $this->insertTransaction(
            $wallet['id'],
            $bet['user_id'],
            'refund',
            $amount,
            'Coupon refund',
            $couponId,
            $bet['bet_id']
        );

        $stmt = $this->db->prepare("
            UPDATE bets_v2
            SET status = 'REFUNDED'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bet['bet_id']);
        $stmt->execute();
        $stmt->close();
    }

    private function payPrize($couponId, array $bet, $position, $percentage, $amount)
    {
        $wallet = $this->getWalletForUser($bet['user_id']);

        $stmt = $this->db->prepare("
            UPDATE wallets_v2
            SET balance = balance + ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $amount, $wallet['id']);
        $stmt->execute();
        $stmt->close();

        $description = 'Coupon prize - position ' . $position;

        $this->insertTransaction(
            $wallet['id'],
            $bet['user_id'],
            'prize',
            $amount,
            $description,
            $couponId,
            $bet['bet_id']
        );

        $stmt = $this->db->prepare("
            INSERT INTO coupon_prizes_v2
            (coupon_id, bet_id, user_id, position, percentage, amount)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiiidd",
            $couponId,
            $bet['bet_id'],
            $bet['user_id'],
            $position,
            $percentage,
            $amount
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("
            UPDATE bets_v2
            SET status = 'WON'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bet['bet_id']);
        $stmt->execute();
        $stmt->close();
    }

    private function addToJackpot($couponId, $amount)
    {
        $stmt = $this->db->prepare("
            SELECT MONTH(MIN(m.scheduled_at)) AS jackpot_month,
                   YEAR(MIN(m.scheduled_at)) AS jackpot_year
            FROM matches_v2 m
            INNER JOIN coupon_matches_v2 cm ON cm.match_id = m.id
            WHERE cm.coupon_id = ?
        ");
        $stmt->bind_param("i", $couponId);
        $stmt->execute();
        $stmt->bind_result($month, $year);

        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("Não foi possível determinar o mês do jackpot para o cupão {$couponId}.");
        }
        $stmt->close();

        if (empty($month) || empty($year)) {
            throw new Exception("Cupão sem jogos associados para cálculo do jackpot.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO monthly_jackpots_v2 (year, month, amount)
            VALUES (?, ?, 0)
            ON DUPLICATE KEY UPDATE amount = amount
        ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("
            UPDATE monthly_jackpots_v2
            SET amount = amount + ?
            WHERE year = ? AND month = ?
        ");
        $stmt->bind_param("dii", $amount, $year, $month);
        $stmt->execute();
        $stmt->close();
    }

    private function getPrizeDistribution($players)
    {
        $config = require __DIR__ . '/../../config/app.php';
    
        $rules = $config['prize_distribution'];
    
        foreach ($rules as $rule) {
    
            if ($players <= $rule['max_players']) {
    
                return $rule['positions'];
    
            }
    
        }
    
        return [];
    }

    private function allocateByPercentages($totalCents, array $distribution)
    {
        if ($totalCents <= 0 || empty($distribution)) {
            return [];
        }

        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($distribution as $position => $percentage) {
            $raw = ($totalCents * $percentage) / 100;
            $base = (int) floor($raw);
            $allocations[$position] = $base;
            $remainders[$position] = $raw - $base;
            $allocated += $base;
        }

        $missing = $totalCents - $allocated;

        if ($missing > 0) {
            arsort($remainders, SORT_NUMERIC);
            $positions = array_keys($remainders);
            $countPositions = count($positions);

            for ($i = 0; $i < $missing; $i++) {
                $pos = $positions[$i % $countPositions];
                $allocations[$pos]++;
            }
        }

        ksort($allocations);
        return $allocations;
    }

    private function splitCentsEqually($totalCents, $parts)
    {
        if ($parts <= 0) {
            return [];
        }

        $base = intdiv($totalCents, $parts);
        $remainder = $totalCents % $parts;

        $result = array_fill(0, $parts, $base);

        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }

        return $result;
    }

    private function getWalletForUser($userId)
    {
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
            throw new Exception("Wallet não encontrada para o user_id {$userId}.");
        }

        $stmt->close();

        return [
            'id' => (int)$walletId,
            'balance' => (float)$balance
        ];
    }

    private function insertTransaction($walletId, $userId, $type, $amount, $description, $couponId = null, $betId = null)
    {
        $columns = ['user_id', 'type', 'amount', 'description'];
        $types = 'isds';
        $values = [$userId, $type, $amount, $description];

        if ($this->tableHasColumn('transactions_v2', 'wallet_id')) {
            $columns[] = 'wallet_id';
            $types .= 'i';
            $values[] = $walletId;
        }

        if ($couponId !== null && $this->tableHasColumn('transactions_v2', 'coupon_id')) {
            $columns[] = 'coupon_id';
            $types .= 'i';
            $values[] = $couponId;
        }

        if ($betId !== null && $this->tableHasColumn('transactions_v2', 'bet_id')) {
            $columns[] = 'bet_id';
            $types .= 'i';
            $values[] = $betId;
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO transactions_v2 (" . implode(',', $columns) . ") VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar insert de transaction: " . $this->db->error);
        }

        $bindParams = [];
        $bindParams[] = $types;

        foreach ($values as $key => $value) {
            $bindParams[] = &$values[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
        $stmt->close();
    }

    private function tableHasColumn($table, $column)
    {
        if (!isset($this->tableColumnsCache[$table])) {
            $this->tableColumnsCache[$table] = [];

            $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

            $result = $this->db->query("SHOW COLUMNS FROM `{$tableSafe}`");
            if (!$result) {
                throw new Exception("Erro ao ler estrutura da tabela {$tableSafe}: " . $this->db->error);
            }

            while ($row = $result->fetch_assoc()) {
                $this->tableColumnsCache[$table][$row['Field']] = true;
            }
        }

        return isset($this->tableColumnsCache[$table][$column]);
    }

    private function toCents($amount)
    {
        return (int) round(((float)$amount) * 100);
    }

    private function fromCents($cents)
    {
        return round($cents / 100, 2);
    }
}