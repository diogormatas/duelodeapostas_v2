<?php

class AdminImportController
{

    private function checkAdmin()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'ADMIN'){

            http_response_code(403);
            echo "Access denied";
            exit;

        }

    }

    public function index()
    {

        $this->checkAdmin();

        require __DIR__ . '/../../resources/views/admin/import.php';

    }

    public function importCompetitions()
    {

        $this->checkAdmin();

        exec('php '.__DIR__.'/../../scripts/sync_competitions.php');

        header("Location: /admin/import");
        exit;

    }

    public function importTeams()
    {

        $this->checkAdmin();

        exec('php '.__DIR__.'/../../scripts/sync_teams.php');

        header("Location: /admin/import");
        exit;

    }

    public function importMatches()
    {

        $this->checkAdmin();

        exec('php '.__DIR__.'/../../scripts/sync_matches.php');

        header("Location: /admin/import");
        exit;

    }

}