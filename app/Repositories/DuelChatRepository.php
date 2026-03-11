<?php

require_once __DIR__ . '/../../core/Database.php';

class DuelChatRepository
{

    public function create($couponId, $userId, $message)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("

        INSERT INTO duel_chat_v2
        (coupon_id, user_id, message, created_at)

        VALUES (?, ?, ?, NOW())

        ");

        $stmt->bind_param(
            "iis",
            $couponId,
            $userId,
            $message
        );

        $stmt->execute();

        $stmt->close();

    }

    public function getByCoupon($couponId)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("

        SELECT
        u.username,
        c.message,
        c.created_at

        FROM duel_chat_v2 c

        JOIN users_v2 u
        ON u.id = c.user_id

        WHERE c.coupon_id = ?

        ORDER BY c.created_at ASC

        ");

        $stmt->bind_param("i",$couponId);

        $stmt->execute();

        $stmt->bind_result(
            $username,
            $message,
            $createdAt
        );

        $messages=[];

        while($stmt->fetch()){

            $messages[]=[
                "username"=>$username,
                "message"=>$message,
                "time"=>$createdAt
            ];

        }

        $stmt->close();

        return $messages;

    }

}