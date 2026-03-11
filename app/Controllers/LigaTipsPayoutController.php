<?php

require_once __DIR__ . '/../Services/LigaTipsMonthlyService.php';

class LigaTipsPayoutController
{
    private $service;

    public function __construct()
    {
        $this->service = new LigaTipsMonthlyService();
    }

    public function pay($year, $month)
    {
        $year = (int) $year;
        $month = (int) $month;

        try {
            $this->service->payMonthlyJackpot($year, $month);
            echo "Jackpot mensal pago com sucesso.";
        } catch (Exception $e) {
            echo "Erro ao pagar jackpot mensal: " . htmlspecialchars($e->getMessage());
        }
    }
}