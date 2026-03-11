<?php

class UsersController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function profile($username)
    {

        $stmt = $this->db->prepare("
            SELECT id, username, email
            FROM users_v2
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($userId, $foundUsername, $email);

        if (!$stmt->fetch()) {
            $stmt->close();
            die("Utilizador não encontrado.");
        }

        $stmt->close();


        /*
        --------------------------------
        AVATAR
        --------------------------------
        */

        $avatar = "https://www.gravatar.com/avatar/" .
            md5(strtolower(trim($email))) .
            "?s=120&d=identicon";


        /*
        --------------------------------
        STATS DUELOS
        --------------------------------
        */

        $sql = "

        SELECT
        COUNT(*) AS duels,

        SUM(
            CASE WHEN b.score = (
                SELECT MAX(score)
                FROM bets_v2
                WHERE coupon_id = b.coupon_id
            ) THEN 1 ELSE 0 END
        ) AS wins

        FROM bets_v2 b

        JOIN coupons_v2 c
        ON c.id = b.coupon_id

        WHERE b.user_id = ?
        AND c.type = 'DUELO'

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($duels, $wins);
        $stmt->fetch();
        $stmt->close();

        $duels = $duels ?? 0;
        $wins = $wins ?? 0;

        $winrate = 0;

        if ($duels > 0) {
            $winrate = round(($wins / $duels) * 100);
        }


        /*
        --------------------------------
        PICKS TOTAIS
        --------------------------------
        */

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM bet_picks_v2 bp
            JOIN bets_v2 b ON b.id = bp.bet_id
            WHERE b.user_id = ?
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($totalPicks);
        $stmt->fetch();
        $stmt->close();


        /*
        --------------------------------
        ACERTOS
        --------------------------------
        */

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM bet_picks_v2 bp
            JOIN bets_v2 b ON b.id = bp.bet_id
            JOIN matches_v2 m ON m.id = bp.match_id
            WHERE b.user_id = ?
            AND bp.pick = m.result_code
        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($correctPicks);
        $stmt->fetch();
        $stmt->close();


        $totalPicks = $totalPicks ?? 0;
        $correctPicks = $correctPicks ?? 0;

        $accuracy = 0;

        if ($totalPicks > 0) {
            $accuracy = round(($correctPicks / $totalPicks) * 100);
        }


        /*
        --------------------------------
        TÍTULOS DO JOGADOR
        --------------------------------
        */

        $titles = [];

        // Rei dos Duelos
        if ($wins >= 10) {
            $titles[] = "🥇 Rei dos Duelos";
        }

        // Mestre das Apostas
        if ($totalPicks >= 100) {
            $titles[] = "🎯 Mestre das Apostas";
        }

        /*
        --------------------------------
        STREAK DE VITÓRIAS
        --------------------------------
        */

        $stmt = $this->db->prepare("

        SELECT b.score
        FROM bets_v2 b
        JOIN coupons_v2 c ON c.id = b.coupon_id
        WHERE b.user_id = ?
        AND c.status = 'SETTLED'
        ORDER BY c.settled_at DESC
        LIMIT 5

        ");

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($score);

        $streak = 0;

        while ($stmt->fetch()) {

            if ($score > 0) {
                $streak++;
            } else {
                break;
            }

        }

        $stmt->close();

        if ($streak >= 3) {
            $titles[] = "🔥 Em forma";
        }


        require __DIR__ . '/../../resources/views/profile.php';

    }


    public function history($username)
    {

        $stmt = $this->db->prepare("
            SELECT id, username
            FROM users_v2
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($userId, $foundUsername);

        if (!$stmt->fetch()) {
            $stmt->close();
            die("Utilizador não encontrado.");
        }

        $stmt->close();


        $stmt = $this->db->prepare("

        SELECT

        d.id AS duel_id,
        d.status,
        d.created_at,
        d.coupon_id,

        u1.username AS challenger,
        u2.username AS opponent,

        b1.score AS my_score,
        b2.score AS other_score

        FROM duels_v2 d

        JOIN users_v2 u1 ON u1.id = d.challenger_id
        LEFT JOIN users_v2 u2 ON u2.id = d.opponent_id

        LEFT JOIN bets_v2 b1
            ON b1.coupon_id = d.coupon_id
           AND b1.user_id = ?

        LEFT JOIN bets_v2 b2
            ON b2.coupon_id = d.coupon_id
           AND b2.user_id != ?

        WHERE d.challenger_id = ? OR d.opponent_id = ?

        ORDER BY d.created_at DESC
        LIMIT 100

        ");

        $stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
        $stmt->execute();

        $stmt->bind_result(
            $duelId,
            $status,
            $createdAt,
            $couponId,
            $challenger,
            $opponent,
            $myScore,
            $otherScore
        );

        $history = [];

        while ($stmt->fetch()) {

            $history[] = [
                'duel_id' => $duelId,
                'status' => $status,
                'created_at' => $createdAt,
                'coupon_id' => $couponId,
                'challenger' => $challenger,
                'opponent' => $opponent,
                'my_score' => $myScore,
                'other_score' => $otherScore
            ];

        }

        $stmt->close();

        require __DIR__ . '/../../resources/views/user_history.php';

    }
}