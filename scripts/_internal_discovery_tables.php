<?php
/**
 * Discovery Script - Explore v1 and v2 table structures
 * Run: php scripts/_internal_discovery_tables.php
 */

require_once __DIR__ . '/../config/database.php';

class TableDiscovery {
    private $db;
    private $dbName;
    
    public function __construct($database) {
        $this->db = $database;
        $this->dbName = $database->getDbName();
    }
    
    public function run() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "DATABASE STRUCTURE DISCOVERY - {$this->dbName}\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // V1 Tables
        echo "V1 TABLES STRUCTURE:\n";
        echo str_repeat("-", 80) . "\n";
        $this->exploreTables('v1');
        
        echo "\n\nV2 TABLES STRUCTURE:\n";
        echo str_repeat("-", 80) . "\n";
        $this->exploreTables('v2');
        
        // Sample data
        echo "\n\nSAMPLE DATA COUNTS:\n";
        echo str_repeat("-", 80) . "\n";
        $this->showDataCounts();
        
        // Key relationships
        echo "\n\nKEY RELATIONSHIPS CHECK:\n";
        echo str_repeat("-", 80) . "\n";
        $this->checkRelationships();
    }
    
    private function exploreTables($version = 'v1') {
        $tables = $version === 'v1' 
            ? ['apostadores', 'cupoes', 'apostas', 'jogos', 'equipas', 'equipas_api', 'transaccoes']
            : ['users_v2', 'wallets_v2', 'coupons_v2', 'coupon_matches_v2', 'bet_picks_v2', 'bets_v2', 'duels_v2', 'teams_v2', 'competitions_v2', 'matches_v2', 'transactions_v2'];
        
        foreach ($tables as $table) {
            $result = $this->db->query("DESCRIBE {$table}");
            
            if (!$result) {
                echo "[NOT FOUND] {$table}\n";
                continue;
            }
            
            echo "\n[TABLE] {$table}\n";
            echo "  Columns:\n";
            
            while ($row = $result->fetch_assoc()) {
                $nullable = $row['Null'] === 'YES' ? 'nullable' : 'NOT NULL';
                $key = $row['Key'] ? "[{$row['Key']}]" : '';
                $default = $row['Default'] ? "default={$row['Default']}" : '';
                
                echo "    - {$row['Field']}: {$row['Type']} {$nullable} {$key} {$default}\n";
            }
            
            // Count rows
            $countResult = $this->db->query("SELECT COUNT(*) as cnt FROM {$table}");
            $countRow = $countResult->fetch_assoc();
            echo "  Rows: {$countRow['cnt']}\n";
        }
    }
    
    private function showDataCounts() {
        $queries = [
            'apostadores' => "SELECT COUNT(*) as cnt, MIN(criado_em) as first_date, MAX(criado_em) as last_date FROM apostadores",
            'cupoes' => "SELECT COUNT(*) as cnt, MIN(criado_em) as first_date, MAX(criado_em) as last_date FROM cupoes WHERE criado_em > '2020-12-30'",
            'apostas' => "SELECT COUNT(*) as cnt FROM apostas WHERE id_cupao IN (SELECT id FROM cupoes WHERE criado_em > '2020-12-30')",
            'jogos' => "SELECT COUNT(*) as cnt FROM jogos",
            'equipas' => "SELECT COUNT(*) as cnt FROM equipas",
            'equipas_api' => "SELECT COUNT(*) as cnt FROM equipas_api",
            'transaccoes' => "SELECT COUNT(*) as cnt, MIN(criado_em) as first_date FROM transaccoes WHERE id > 629",
            'users_v2' => "SELECT COUNT(*) as cnt FROM users_v2",
            'coupons_v2' => "SELECT COUNT(*) as cnt FROM coupons_v2",
            'bet_picks_v2' => "SELECT COUNT(*) as cnt FROM bet_picks_v2",
            'bets_v2' => "SELECT COUNT(*) as cnt FROM bets_v2",
            'transactions_v2' => "SELECT COUNT(*) as cnt FROM transactions_v2",
        ];
        
        foreach ($queries as $table => $query) {
            $result = $this->db->query($query);
            if ($result) {
                $row = $result->fetch_assoc();
                $display = "  {$table}: {$row['cnt']} rows";
                if (isset($row['first_date'])) {
                    $display .= " (from {$row['first_date']})";
                }
                echo $display . "\n";
            }
        }
    }
    
    private function checkRelationships() {
        // Check apostadores with apostas
        $result = $this->db->query("
            SELECT 
                a.id_apostador,
                COUNT(DISTINCT ap.id_cupao) as cupoes_count,
                COUNT(*) as apostas_count
            FROM apostadores a
            LEFT JOIN apostas ap ON a.id = ap.id_apostador
            WHERE a.criado_em > '2020-12-30'
            GROUP BY a.id_apostador
            ORDER BY apostas_count DESC
            LIMIT 5
        ");
        
        echo "\nTop users by apostas count:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  User {$row['id_apostador']}: {$row['apostas_count']} apostas in {$row['cupoes_count']} cupoes\n";
        }
        
        // Check cupoes with jogos
        $result = $this->db->query("
            SELECT 
                c.id,
                COUNT(DISTINCT c.id_jogo) as jogos_count,
                c.criado_em
            FROM cupoes c
            WHERE c.criado_em > '2020-12-30'
            GROUP BY c.id
            ORDER BY jogos_count DESC
            LIMIT 5
        ");
        
        echo "\nTop cupoes by jogos count:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  Cupao {$row['id']}: {$row['jogos_count']} jogos ({$row['criado_em']})\n";
        }
        
        // Check teams consistency
        $result = $this->db->query("
            SELECT 
                j.id_equipa_casa,
                COUNT(*) as count
            FROM jogos j
            WHERE j.id_equipa_casa IS NOT NULL
            GROUP BY j.id_equipa_casa
            LIMIT 3
        ");
        
        echo "\nSample team references in jogos:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  Team {$row['id_equipa_casa']}: {$row['count']} references\n";
        }
    }
}

// Initialize
$db = new Database();
$discovery = new TableDiscovery($db);
$discovery->run();

echo "\n" . str_repeat("=", 80) . "\n";
echo "Discovery complete!\n";
echo str_repeat("=", 80) . "\n\n";
?>
