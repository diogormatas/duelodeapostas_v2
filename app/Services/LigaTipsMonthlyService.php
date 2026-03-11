<?php

class LigaTipsMonthlyService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getMonthlyRanking($year, $month)
    {
        $year = (int) $year;
        $month = (int) $month;

        if ($year <= 0 || $month < 1 || $month > 12) {
            throw new Exception("Ano ou mês inválidos.");
        }

        $stmt = $this->db->prepare("
            SELECT id, amount, status, payout_status, paid_at
            FROM monthly_jackpots_v2
            WHERE year = ? AND month = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $stmt->bind_result($jackpotId, $jackpotAmount, $jackpotStatus, $payoutStatus, $paidAt);

        $jackpot = null;

        if ($stmt->fetch()) {
            $jackpot = [
                'id' => (int) $jackpotId,
                'amount' => (float) $jackpotAmount,
                'status' => $jackpotStatus,
                'payout_status' => $payoutStatus,
                'paid_at' => $paidAt,
                'year' => $year,
                'month' => $month
            ];
        }

        $stmt->close();

        if (!$jackpot) {
            $jackpot = [
                'id' => null,
                'amount' => 0.0,
                'status' => 'OPEN',
                'payout_status' => 'PENDING',
                'paid_at' => null,
                'year' => $year,
                'month' => $month
            ];
        }

        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.username,
                l.score
            FROM liga_tips_monthly_scores_v2 l
            INNER JOIN users_v2 u ON u.id = l.user_id
            WHERE l.year = ? AND l.month = ?
            ORDER BY l.score DESC, u.username ASC
        ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $stmt->bind_result($userId, $username, $score);

        $ranking = [];

        while ($stmt->fetch()) {
            $ranking[] = [
                'user_id' => (int) $userId,
                'username' => $username,
                'score' => (int) $score
            ];
        }

        $stmt->close();

        return [
            'jackpot' => $jackpot,
            'ranking' => $ranking
        ];
    }

    public function payMonthlyJackpot($year, $month)
    {
        $year = (int) $year;
        $month = (int) $month;

        if ($year <= 0 || $month < 1 || $month > 12) {
            throw new Exception("Ano ou mês inválidos.");
        }

        $this->db->begin_transaction();

        try {
            // 1. Obter jackpot mensal
            $stmt = $this->db->prepare("
                SELECT id, amount, payout_status
                FROM monthly_jackpots_v2
                WHERE year = ? AND month = ?
                LIMIT 1
            ");
            $stmt->bind_param("ii", $year, $month);
            $stmt->execute();
            $stmt->bind_result($jackpotId, $jackpotAmount, $payoutStatus);

            if (!$stmt->fetch()) {
                $stmt->close();
                throw new Exception("Jackpot mensal não encontrado.");
            }
            $stmt->close();

            $jackpotId = (int) $jackpotId;
            $jackpotAmount = (float) $jackpotAmount;

            if ($payoutStatus === 'PAID') {
                throw new Exception("Jackpot mensal já foi pago.");
            }

            if ($jackpotAmount <= 0) {
                throw new Exception("Jackpot mensal sem saldo.");
            }

            // 2. Obter ranking mensal
            $stmt = $this->db->prepare("
                SELECT user_id, score
                FROM liga_tips_monthly_scores_v2
                WHERE year = ? AND month = ?
                ORDER BY score DESC, user_id ASC
            ");
            $stmt->bind_param("ii", $year, $month);
            $stmt->execute();
            $stmt->bind_result($userId, $score);

            $rows = [];
            while ($stmt->fetch()) {
                $rows[] = [
                    'user_id' => (int) $userId,
                    'score' => (int) $score
                ];
            }
            $stmt->close();

            if (empty($rows)) {
                throw new Exception("Não há ranking mensal para pagar.");
            }

            // 3. Distribuição mensal
            $distribution = $this->getMonthlyJackpotDistribution(count($rows));

            // 4. Agrupar por score
            $groups = [];
            foreach ($rows as $row) {
                $scoreKey = (string) $row['score'];

                if (!isset($groups[$scoreKey])) {
                    $groups[$scoreKey] = [];
                }

                $groups[$scoreKey][] = $row;
            }

            krsort($groups, SORT_NUMERIC);

            $currentPosition = 1;
            $awardedTotal = 0.0;

            foreach ($groups as $score => $group) {
                $groupSize = count($group);
                $coveredPercent = 0.0;

                for ($i = 0; $i < $groupSize; $i++) {
                    $position = $currentPosition + $i;

                    if (isset($distribution[$position])) {
                        $coveredPercent += (float) $distribution[$position];
                    }
                }

                if ($coveredPercent > 0) {
                    $groupPrizeAmount = round(($coveredPercent / 100) * $jackpotAmount, 2);
                    $groupPrizeAmount = $this->capAmountToRemainingPool($groupPrizeAmount, $jackpotAmount, $awardedTotal);

                    if ($groupPrizeAmount > 0) {
                        $baseEach = floor(($groupPrizeAmount / $groupSize) * 100) / 100;
                        $paidInGroup = 0.0;

                        foreach ($group as $index => $row) {
                            $amount = $baseEach;

                            if ($index === $groupSize - 1) {
                                $amount = round($groupPrizeAmount - $paidInGroup, 2);
                            }

                            $amount = round($amount, 2);
                            $paidInGroup += $amount;

                            $this->payMonthlyPrize(
                                $jackpotId,
                                $row['user_id'],
                                $currentPosition,
                                $coveredPercent,
                                $amount
                            );
                        }

                        $awardedTotal += round($groupPrizeAmount, 2);
                    }
                }

                $currentPosition += $groupSize;
            }

            // 5. Fechar jackpot
            $stmt = $this->db->prepare("
                UPDATE monthly_jackpots_v2
                SET payout_status = 'PAID',
                    status = 'PAID',
                    paid_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $jackpotId);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function payMonthlyPrize($jackpotId, $userId, $position, $percentage, $amount)
    {
        if ($amount <= 0) {
            return;
        }

        list($walletId, $currentBalance) = $this->getWalletDataByUserId($userId);

        $newBalance = round($currentBalance + (float) $amount, 2);

        $stmt = $this->db->prepare("
            UPDATE wallets_v2
            SET balance = ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $newBalance, $walletId);
        $stmt->execute();
        $stmt->close();

        $description = 'Liga Tips monthly jackpot';

        $stmt = $this->db->prepare("
            INSERT INTO transactions_v2
            (wallet_id, user_id, type, amount, description)
            VALUES (?, ?, 'jackpot_payout', ?, ?)
        ");
        $stmt->bind_param("iids", $walletId, $userId, $amount, $description);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("
            INSERT INTO monthly_jackpot_payouts_v2
            (jackpot_id, user_id, position, percentage, amount)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiidd", $jackpotId, $userId, $position, $percentage, $amount);
        $stmt->execute();
        $stmt->close();
    }

    private function getWalletDataByUserId($userId)
    {
        $stmt = $this->db->prepare("
            SELECT id, balance
            FROM wallets_v2
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($walletId, $balance);

        if (!$stmt->fetch()) {
            $stmt->close();
            throw new Exception("Wallet não encontrada para o utilizador " . (int) $userId . ".");
        }

        $stmt->close();

        return [(int) $walletId, (float) $balance];
    }

    private function capAmountToRemainingPool($groupPrizeAmount, $pool, $awardedTotal)
    {
        $remaining = round($pool - $awardedTotal, 2);

        if ($remaining <= 0) {
            return 0.0;
        }

        if ($groupPrizeAmount > $remaining) {
            return $remaining;
        }

        return round($groupPrizeAmount, 2);
    }

    private function getMonthlyJackpotDistribution($players)
    {
        if ($players <= 4) {
            return [1 => 100];
        }

        if ($players <= 8) {
            return [1 => 70, 2 => 30];
        }

        if ($players <= 29) {
            return [1 => 50, 2 => 30, 3 => 20];
        }

        return [1 => 46, 2 => 26, 3 => 16, 4 => 12];
    }
}