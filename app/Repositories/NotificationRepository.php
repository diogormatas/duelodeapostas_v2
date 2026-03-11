<?php

class NotificationRepository
{

    public function create($userId, $type, $data = [])
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO notifications_v2 (user_id, type, data, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $json = json_encode($data);

        $stmt->bind_param("iss", $userId, $type, $json);

        $stmt->execute();
        $stmt->close();

    }


    public function countUnreadByUser($userId)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM notifications_v2
            WHERE user_id = ?
            AND read_at IS NULL
        ");

        $stmt->bind_param("i", $userId);

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

        $stmt->close();

        return $count ?? 0;

    }


    public function getByUser($userId)
    {
    
        $db = Database::getConnection();
    
        $sql = "
            SELECT id, type, data, created_at
            FROM notifications_v2
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ";
    
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    
        $result = $stmt->store_result();
    
        $stmt->bind_result($id, $type, $data, $created_at);
    
        $notifications = [];
    
        while ($stmt->fetch()) {
    
            $decoded = [];
    
            if ($data) {
                $decoded = json_decode($data, true);
            }
    
            $notifications[] = [
                "id" => $id,
                "type" => $type,
                "data" => $decoded,
                "created_at" => $created_at
            ];
    
        }
    
        $stmt->close();
    
        return $notifications;
    
    }


    public function markAsRead($notificationId)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE notifications_v2
            SET read_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("i", $notificationId);

        $stmt->execute();
        $stmt->close();

    }

}