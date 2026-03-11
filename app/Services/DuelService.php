<?php

require_once __DIR__ . '/../Repositories/NotificationRepository.php';

class DuelService
{
    public function createChallenge($challengerId, $opponentId, $stake)
    {
        $db = Database::getConnection();

        $db->begin_transaction();

        try {

            // criar coupon
            $stmt = $db->prepare("
                INSERT INTO coupons_v2
                (type, entry_price, max_players, status, prize_status, visibility, created_at)
                VALUES ('DUELO', ?, 2, 'OPEN', 'PENDING', 'PRIVATE', NOW())
            ");

            $stmt->bind_param("d", $stake);
            $stmt->execute();

            $couponId = $db->insert_id;
            $stmt->close();


            // criar duelo
            $stmt = $db->prepare("
                INSERT INTO duels_v2
                (coupon_id, challenger_id, opponent_id, stake, visibility, status)
                VALUES (?, ?, ?, ?, 'PRIVATE', 'PENDING')
            ");

            $stmt->bind_param("iiid", $couponId, $challengerId, $opponentId, $stake);
            $stmt->execute();

            $duelId = $db->insert_id;
            $stmt->close();


            // notificação
            $notificationRepo = new NotificationRepository();

            $notificationRepo->create(
                $opponentId,
                'DUEL_CHALLENGE',
                [
                    'duel_id' => $duelId,
                    'challenger_id' => $challengerId
                ]
            );

            $db->commit();

            return [
                'id' => $duelId,
                'coupon_id' => $couponId
            ];

        } catch (Exception $e) {

            $db->rollback();
            throw $e;

        }
    }
}