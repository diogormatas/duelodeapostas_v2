<?php

class UserOnlineRepository
{
    public function update($userId)
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO users_online (user_id,last_seen)
            VALUES (?,NOW())
            ON DUPLICATE KEY UPDATE last_seen = NOW()
        ");

        $stmt->bind_param("i",$userId);
        $stmt->execute();
        $stmt->close();
    }

    public function getOnlineUsers()
    {
        $db = Database::getConnection();

        $sql = "

        SELECT u.username

        FROM users_online o

        JOIN users_v2 u
        ON u.id = o.user_id

        WHERE o.last_seen > NOW() - INTERVAL 5 MINUTE

        ORDER BY o.last_seen DESC

        LIMIT 20

        ";

        $result = $db->query($sql);

        $users = [];

        while($row = $result->fetch_assoc()){
            $users[] = $row['username'];
        }

        return $users;
    }
}