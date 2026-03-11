<?php

class UserRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password_hash, role
             FROM users_v2
             WHERE username = ?
             LIMIT 1"
        );
    
        $stmt->bind_param("s", $username);
        $stmt->execute();
    
        $stmt->bind_result($id, $user, $email, $passwordHash, $role);
    
        if ($stmt->fetch()) {
    
            return [
                'id' => $id,
                'username' => $user,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role
            ];
    
        }
    
        return null;
    }

    public function create($username, $email, $passwordHash)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users_v2 (username, email, password_hash)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $username, $email, $passwordHash);
        $stmt->execute();

        return $this->db->insert_id;
    }
}