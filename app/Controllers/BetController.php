<?php

require_once __DIR__ . '/../Services/BetService.php';
require_once __DIR__ . '/../Repositories/CouponRepository.php';
require_once __DIR__ . '/../../core/Database.php';

class BetController
{

    private $service;

    public function __construct()
    {
        $this->service = new BetService();
    }

    public function store()
    {
    
        if (!isset($_SESSION['user_id'])) {
            die("Não autenticado");
        }
    
        $userId = $_SESSION['user_id'];
    
        $couponId = $_POST['coupon_id'] ?? null;
    
        $predictions = $_POST['predictions'] ?? [];
    
        if (empty($predictions)) {
    
            $_SESSION['error'] = "Tens de escolher os prognósticos.";
    
            header("Location: /duelo/v2/public/coupon/$couponId");
            exit;
    
        }
    
        try {
    
            $this->service->placeBet($userId, $couponId, $predictions);
    
            $_SESSION['success'] = "Aposta registada com sucesso.";
    
        } catch (Exception $e) {
    
            $_SESSION['error'] = $e->getMessage();
    
        }
    
        header("Location: /duelo/v2/public/coupon/$couponId");
        exit;
    
    }

    public function show($betId)
    {

        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT
                t1.name,
                t2.name,
                bp.pick
            FROM bet_picks_v2 bp

            JOIN matches_v2 m
                ON m.id = bp.match_id

            JOIN teams_v2 t1
                ON t1.id = m.home_team_id

            JOIN teams_v2 t2
                ON t2.id = m.away_team_id

            WHERE bp.bet_id=?
        ");

        $stmt->bind_param("i",$betId);

        $stmt->execute();

        $stmt->bind_result($home,$away,$pick);

        $picks = [];

        while ($stmt->fetch()) {

            $picks[] = [
                "home"=>$home,
                "away"=>$away,
                "pick"=>$pick
            ];

        }

        require __DIR__ . '/../../resources/views/bet_view.php';

    }

}