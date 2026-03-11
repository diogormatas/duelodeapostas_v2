<?php

require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Services/ActivityService.php';

class DuelsController
{
    private $db;
    private $config;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function index()
    {
        $sql = "
        SELECT

        d.id,
        d.stake,
        d.visibility,
        d.status,
        d.created_at,

        d.challenger_id,
        d.opponent_id,

        c.id AS coupon_id,

        u1.username AS challenger,
        u2.username AS opponent,

        COUNT(cm.match_id) AS matches

        FROM duels_v2 d

        LEFT JOIN coupons_v2 c
        ON c.id = d.coupon_id

        LEFT JOIN coupon_matches_v2 cm
        ON cm.coupon_id = c.id

        LEFT JOIN users_v2 u1
        ON u1.id = d.challenger_id

        LEFT JOIN users_v2 u2
        ON u2.id = d.opponent_id

        GROUP BY d.id

        ORDER BY d.created_at DESC
        LIMIT 50
        ";

        $result = $this->db->query($sql);

        $duels = [];

        while ($row = $result->fetch_assoc()) {
            $duels[] = $row;
        }

        require __DIR__ . '/../../resources/views/duels.php';
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $duelsConfig = $this->config['duels'];

        $minHours = (int)$duelsConfig['min_hours_before_match'];
        $maxDays  = (int)$duelsConfig['max_days_ahead'];

        $sql = "
        SELECT

        m.id,
        m.scheduled_at,

        ht.name AS home_team,
        at.name AS away_team,

        c.name AS competition

        FROM matches_v2 m

        JOIN teams_v2 ht ON ht.id = m.home_team_id
        JOIN teams_v2 at ON at.id = m.away_team_id
        JOIN competitions_v2 c ON c.id = m.competition_id

        WHERE m.status='SCHEDULED'
        AND m.scheduled_at > NOW() + INTERVAL {$minHours} HOUR
        AND m.scheduled_at < NOW() + INTERVAL {$maxDays} DAY

        ORDER BY c.name, m.scheduled_at
        LIMIT 200
        ";

        $result = $this->db->query($sql);

        $matches = [];

        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }

        require __DIR__ . '/../../resources/views/duel_create.php';
    }

    public function generateMatches()
    {
        $duels = $this->config['duels'];

        $count = (int) ($_GET['count'] ?? $duels['matches_per_duel']);

        if ($count < $duels['min_matches']) {
            $count = $duels['min_matches'];
        }

        if ($count > $duels['max_matches']) {
            $count = $duels['max_matches'];
        }

        $matchIds = $this->getRandomMatchIds($count);

        $matches = [];

        if (!empty($matchIds)) {

            $ids = implode(',', array_map('intval', $matchIds));

            $result = $this->db->query("
                SELECT
                    m.id,
                    m.scheduled_at,
                    ht.name AS home_team,
                    at.name AS away_team,
                    c.name AS competition
                FROM matches_v2 m
                JOIN teams_v2 ht ON ht.id = m.home_team_id
                JOIN teams_v2 at ON at.id = m.away_team_id
                JOIN competitions_v2 c ON c.id = m.competition_id
                WHERE m.id IN ({$ids})
                ORDER BY RAND()
            ");

            while ($row = $result->fetch_assoc()) {
                $matches[] = [
                    'id' => $row['id'],
                    'date' => $row['scheduled_at'],
                    'home' => $row['home_team'],
                    'away' => $row['away_team'],
                    'competition' => $row['competition']
                ];
            }
        }

        header('Content-Type: application/json');

        echo json_encode([
            'matches' => $matches
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            die("Login necessário");
        }

        $userId = (int)$_SESSION['user_id'];
        $duelsConfig = $this->config['duels'];

        $stakeOption = $_POST['stake_option'] ?? null;

        if ($stakeOption === 'custom') {
            $stake = (float) ($_POST['stake_custom'] ?? 0);
        } else {
            $stake = (float) $stakeOption;
        }

        $minStake = (float)$duelsConfig['min_stake'];
        $maxStake = (float)$duelsConfig['max_stake'];

        if ($stake < $minStake || $stake > $maxStake) {
            die("Stake inválida.");
        }

        // Anti-spam
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM duels_v2
            WHERE challenger_id = ?
            AND status = 'PENDING'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($openCount);
        $stmt->fetch();
        $stmt->close();

        if ($openCount >= (int)$duelsConfig['max_open_duels_per_user']) {
            die("Limite de desafios pendentes atingido.");
        }

        $selectedMatches = $_POST['matches'] ?? [];

        if (empty($selectedMatches)) {
            $selectedMatches = $this->getRandomMatchIds((int)$duelsConfig['matches_per_duel']);
        }

        $selectedMatches = array_map('intval', $selectedMatches);
        $selectedMatches = array_values(array_unique($selectedMatches));

        $countMatches = count($selectedMatches);

        if (
            $countMatches < (int)$duelsConfig['min_matches'] ||
            $countMatches > (int)$duelsConfig['max_matches']
        ) {
            die("Número de jogos inválido.");
        }

        $bettingClosesAt = $this->calculateBettingClosesAt($selectedMatches);

        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO coupons_v2
                (type, entry_price, max_players, status, prize_status, visibility, betting_closes_at, created_at)
                VALUES ('DUELO', ?, 2, 'OPEN', 'PENDING', 'PUBLIC', ?, NOW())
            ");

            $stmt->bind_param("ds", $stake, $bettingClosesAt);
            $stmt->execute();

            $couponId = $this->db->insert_id;

            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO coupon_matches_v2 (coupon_id, match_id)
                VALUES (?, ?)
            ");

            foreach ($selectedMatches as $m) {
                $stmt->bind_param("ii", $couponId, $m);
                $stmt->execute();
            }

            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO duels_v2
                (coupon_id, challenger_id, stake, visibility, status)
                VALUES (?, ?, ?, 'PUBLIC', 'PENDING')
            ");

            $stmt->bind_param("iid", $couponId, $userId, $stake);
            $stmt->execute();
            $stmt->close();

            $activity = new ActivityService();
            $activity->log(
                "duel_created",
                [
                    "coupon_id" => $couponId,
                    "stake" => $stake
                ],
                $userId
            );

            $this->db->commit();

            header("Location: " . $this->config['base_url'] . "/duels");
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            die($e->getMessage());
        }
    }

    public function quick()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            die("Login necessário");
        }

        $userId = (int)$_SESSION['user_id'];
        $duelsConfig = $this->config['duels'];

        $stake = 5;
        $matches = $this->getRandomMatchIds((int)$duelsConfig['matches_per_duel']);

        if (empty($matches)) {
            die("Não existem jogos disponíveis.");
        }

        $bettingClosesAt = $this->calculateBettingClosesAt($matches);

        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO coupons_v2
                (type, entry_price, max_players, status, prize_status, visibility, betting_closes_at, created_at)
                VALUES ('DUELO', ?, 2, 'OPEN', 'PENDING', 'PUBLIC', ?, NOW())
            ");

            $stmt->bind_param("ds", $stake, $bettingClosesAt);
            $stmt->execute();

            $couponId = $this->db->insert_id;
            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO coupon_matches_v2 (coupon_id, match_id)
                VALUES (?, ?)
            ");

            foreach ($matches as $m) {
                $stmt->bind_param("ii", $couponId, $m);
                $stmt->execute();
            }

            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO duels_v2
                (coupon_id, challenger_id, stake, visibility, status)
                VALUES (?, ?, ?, 'PUBLIC', 'PENDING')
            ");

            $stmt->bind_param("iid", $couponId, $userId, $stake);
            $stmt->execute();
            $stmt->close();

            $activity = new ActivityService();
            $activity->log(
                "duel_created",
                [
                    "coupon_id" => $couponId,
                    "stake" => $stake
                ],
                $userId
            );

            $this->db->commit();

            header("Location: " . $this->config['base_url'] . "/duels");
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            die($e->getMessage());
        }
    }

    public function accept($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            die("Login necessário.");
        }

        $userId = (int)$_SESSION['user_id'];
        $id = (int)$id;

        $stmt = $this->db->prepare("
            SELECT challenger_id, opponent_id, status
            FROM duels_v2
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($challengerId, $opponentId, $status);

        if (!$stmt->fetch()) {
            $stmt->close();
            die("Duelo não encontrado.");
        }

        $stmt->close();

        if ($status !== 'PENDING') {
            die("Duelo já foi aceite ou terminou.");
        }

        if ((int)$challengerId === $userId) {
            die("Não podes aceitar o teu próprio desafio.");
        }

        if ($opponentId !== null && (int)$opponentId !== $userId) {
            die("Este desafio é privado.");
        }

        $stmt = $this->db->prepare("
            UPDATE duels_v2
            SET opponent_id = ?, status = 'ACCEPTED'
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $userId, $id);
        $stmt->execute();
        $stmt->close();

        $activity = new ActivityService();
        $activity->log(
            "duel_accepted",
            [
                "duel_id" => $id
            ],
            $userId
        );

        header("Location: " . $this->config['base_url'] . "/duels");
        exit;
    }

    public function headToHead($u1, $u2)
    {
        $u1 = (int)$u1;
        $u2 = (int)$u2;

        $stmt = $this->db->prepare("
            SELECT username
            FROM users_v2
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $u1);
        $stmt->execute();
        $stmt->bind_result($user1);
        $stmt->fetch();
        $stmt->close();

        $stmt = $this->db->prepare("
            SELECT username
            FROM users_v2
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $u2);
        $stmt->execute();
        $stmt->bind_result($user2);
        $stmt->fetch();
        $stmt->close();

        $stmt = $this->db->prepare("
            SELECT
            b1.score AS s1,
            b2.score AS s2

            FROM duels_v2 d

            JOIN bets_v2 b1
            ON b1.coupon_id = d.coupon_id AND b1.user_id = ?

            JOIN bets_v2 b2
            ON b2.coupon_id = d.coupon_id AND b2.user_id = ?

            WHERE d.status = 'ACCEPTED'
        ");

        $stmt->bind_param("ii", $u1, $u2);
        $stmt->execute();
        $stmt->bind_result($s1, $s2);

        $stats = [
            'total' => 0,
            'p1' => 0,
            'p2' => 0,
            'draw' => 0
        ];

        while ($stmt->fetch()) {
            $stats['total']++;

            if ($s1 > $s2) {
                $stats['p1']++;
            } elseif ($s2 > $s1) {
                $stats['p2']++;
            } else {
                $stats['draw']++;
            }
        }

        $stmt->close();

        require __DIR__ . '/../../resources/views/head_to_head.php';
    }

    public function ranking()
    {
        $sql = "
        SELECT

        u.username,
        COUNT(*) AS duels,

        SUM(
            CASE WHEN b.score = (
                SELECT MAX(score)
                FROM bets_v2
                WHERE coupon_id = b.coupon_id
            ) THEN 1 ELSE 0 END
        ) AS wins

        FROM bets_v2 b

        JOIN users_v2 u
        ON u.id = b.user_id

        JOIN coupons_v2 c
        ON c.id = b.coupon_id

        WHERE c.type = 'DUELO'

        GROUP BY b.user_id

        ORDER BY wins DESC, duels DESC, u.username ASC

        LIMIT 20
        ";

        $result = $this->db->query($sql);

        $ranking = [];

        while ($r = $result->fetch_assoc()) {
            $ranking[] = $r;
        }

        require __DIR__ . '/../../resources/views/duel_ranking.php';
    }

    public function challenge()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            die("Login necessário");
        }

        $challengerId = (int)$_SESSION['user_id'];
        $opponentId = isset($_POST['opponent_id']) ? (int)$_POST['opponent_id'] : 0;
        $stake = isset($_POST['stake']) ? (float)$_POST['stake'] : 5;

        if ($opponentId <= 0) {
            die("Opponent ID inválido.");
        }

        if ($stake <= 0) {
            die("Stake inválida.");
        }

        $this->createPrivateChallenge($challengerId, $opponentId, $stake);

        header("Location: " . $this->config['base_url'] . "/duels");
        exit;
    }

    public function rankingWeekly()
    {
        $sql = "
        SELECT

        u.username,

        COUNT(*) AS duels,

        SUM(
            CASE WHEN b.score = (
                SELECT MAX(score)
                FROM bets_v2
                WHERE coupon_id = b.coupon_id
            ) THEN 1 ELSE 0 END
        ) AS wins

        FROM bets_v2 b

        JOIN users_v2 u
        ON u.id = b.user_id

        JOIN coupons_v2 c
        ON c.id = b.coupon_id

        WHERE c.type = 'DUELO'
        AND YEARWEEK(c.created_at,1) = YEARWEEK(NOW(),1)

        GROUP BY b.user_id

        ORDER BY wins DESC, duels DESC

        LIMIT 20
        ";

        $result = $this->db->query($sql);

        $ranking = [];

        while ($row = $result->fetch_assoc()) {
            $ranking[] = $row;
        }

        require __DIR__ . '/../../resources/views/duel_ranking_weekly.php';
    }

    public function challengeUser($username)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            die("Login necessário");
        }

        $challengerId = (int)$_SESSION['user_id'];

        $stmt = $this->db->prepare("
            SELECT id
            FROM users_v2
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($opponentId);

        if (!$stmt->fetch()) {
            $stmt->close();
            die("Utilizador não encontrado");
        }

        $stmt->close();

        if ($opponentId == $challengerId) {
            die("Não podes desafiar-te a ti próprio");
        }

        $stake = 5;

        $this->createPrivateChallenge($challengerId, $opponentId, $stake);

        header("Location: " . $this->config['base_url'] . "/duels");
        exit;
    }

    private function getRandomMatchIds($limit)
    {
        $duelsConfig = $this->config['duels'];

        $minHours = (int)$duelsConfig['min_hours_before_match'];
        $maxDays  = (int)$duelsConfig['max_days_ahead'];

        $stmt = $this->db->prepare("
            SELECT id
            FROM matches_v2
            WHERE status='SCHEDULED'
            AND scheduled_at > NOW() + INTERVAL {$minHours} HOUR
            AND scheduled_at < NOW() + INTERVAL {$maxDays} DAY
            ORDER BY RAND()
            LIMIT ?
        ");

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $stmt->bind_result($matchId);

        $matches = [];

        while ($stmt->fetch()) {
            $matches[] = (int)$matchId;
        }

        $stmt->close();

        return $matches;
    }

    private function calculateBettingClosesAt(array $matchIds)
    {
        if (empty($matchIds)) {
            return null;
        }

        $ids = implode(',', array_map('intval', $matchIds));

        $result = $this->db->query("
            SELECT MIN(scheduled_at) AS first_match
            FROM matches_v2
            WHERE id IN ({$ids})
        ");

        $row = $result->fetch_assoc();

        if (empty($row['first_match'])) {
            return null;
        }

        $closeMinutes = (int)$this->config['coupon_close_minutes_before_match'];

        return date(
            'Y-m-d H:i:s',
            strtotime($row['first_match'] . " -{$closeMinutes} minutes")
        );
    }

    private function createPrivateChallenge($challengerId, $opponentId, $stake)
    {
        $duelsConfig = $this->config['duels'];

        $matches = $this->getRandomMatchIds((int)$duelsConfig['matches_per_duel']);

        if (empty($matches)) {
            throw new Exception("Não existem jogos disponíveis para desafio privado.");
        }

        $bettingClosesAt = $this->calculateBettingClosesAt($matches);

        $this->db->begin_transaction();

        try {

            $stmt = $this->db->prepare("
                INSERT INTO coupons_v2
                (type, entry_price, max_players, status, prize_status, visibility, betting_closes_at, created_at)
                VALUES ('DUELO', ?, 2, 'OPEN', 'PENDING', 'PRIVATE', ?, NOW())
            ");

            $stmt->bind_param("ds", $stake, $bettingClosesAt);
            $stmt->execute();

            $couponId = $this->db->insert_id;
            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO coupon_matches_v2 (coupon_id, match_id)
                VALUES (?, ?)
            ");

            foreach ($matches as $m) {
                $stmt->bind_param("ii", $couponId, $m);
                $stmt->execute();
            }

            $stmt->close();

            $stmt = $this->db->prepare("
                INSERT INTO duels_v2
                (coupon_id, challenger_id, opponent_id, stake, visibility, status)
                VALUES (?, ?, ?, ?, 'PRIVATE', 'PENDING')
            ");

            $stmt->bind_param("iiid", $couponId, $challengerId, $opponentId, $stake);
            $stmt->execute();

            $duelId = $this->db->insert_id;
            $stmt->close();

            $notifications = new NotificationRepository();

            $notifications->create(
                $opponentId,
                'DUEL_CHALLENGE',
                [
                    'duel_id' => $duelId,
                    'challenger_id' => $challengerId
                ]
            );

            $activity = new ActivityService();
            $activity->log(
                "duel_created",
                [
                    "coupon_id" => $couponId,
                    "stake" => $stake
                ],
                $challengerId
            );

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}