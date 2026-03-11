<?php

require_once __DIR__ . '/../Services/PrizeService.php';

class SettleController
{
    private $prizeService;

    public function __construct()
    {
        $this->prizeService = new PrizeService();
    }

    public function show($id)
    {
        $couponId = (int) $id;

        try {
            $this->prizeService->settleCoupon($couponId);
            echo "Cupão liquidado com sucesso.";
        } catch (Exception $e) {
            echo "Erro ao liquidar cupão: " . htmlspecialchars($e->getMessage());
        }
    }
}