<?php

require_once __DIR__ . '/../Services/ResultService.php';

class ResultsController
{
    private $resultService;

    public function __construct()
    {
        $this->resultService = new ResultService();
    }

    public function process()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        try {

            // Proteção básica: apenas admin pode correr
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Acesso negado.'
                ]);
                return;
            }

            // Executar processamento
            $processed = $this->resultService->processFinishedMatches();

            echo json_encode([
                'success' => true,
                'message' => 'Resultados processados com sucesso.',
                'processed_matches' => $processed
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar resultados.',
                'error' => $e->getMessage()
            ]);

        }
    }
}