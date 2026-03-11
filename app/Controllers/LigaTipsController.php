<?php

require_once __DIR__ . '/../Services/LigaTipsMonthlyService.php';

class LigaTipsController
{
    private $service;

    public function __construct()
    {
        $this->service = new LigaTipsMonthlyService();
    }

    public function show($year, $month)
    {
        $year = (int) $year;
        $month = (int) $month;

        try {
            $data = $this->service->getMonthlyRanking($year, $month);

            $ranking = $data['ranking'];
            $jackpot = $data['jackpot'];

            require __DIR__ . '/../../resources/views/liga_tips_monthly.php';
        } catch (Exception $e) {
            die('Erro: ' . htmlspecialchars($e->getMessage()));
        }
    }
}