<?php

require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

$data = [];

# Matches waiting result

$res = $db->query("
SELECT COUNT(*) c
FROM matches_v2
WHERE status != 'FINISHED'
");

$data['matches_pending'] = $res->fetch_assoc()['c'];

# Open coupons

$res = $db->query("
SELECT COUNT(*) c
FROM coupons_v2
WHERE status='OPEN'
");

$data['open_coupons'] = $res->fetch_assoc()['c'];

# Bets active

$res = $db->query("
SELECT COUNT(*) c
FROM bets_v2
WHERE status='ACTIVE'
");

$data['active_bets'] = $res->fetch_assoc()['c'];

# Pending duels

$res = $db->query("
SELECT COUNT(*) c
FROM duels_v2
WHERE status='PENDING'
");

$data['pending_duels'] = $res->fetch_assoc()['c'];

# Wallet balance total

$res = $db->query("
SELECT SUM(balance) b
FROM wallets_v2
");

$data['total_balance'] = $res->fetch_assoc()['b'] ?? 0;

# Recent logs

$res = $db->query("
SELECT level,category,action,created_at
FROM system_logs_v2
ORDER BY created_at DESC
LIMIT 10
");

$data['logs'] = [];

while($r=$res->fetch_assoc()){
$data['logs'][]=$r;
}

# Cron status (based on log files)

$logDir = __DIR__ . '/../logs';

$cronFiles = [
'sync_matches.log',
'process_results.log',
'auto_settle.log',
'close_coupons.log',
'expire_duels.log'
];

$data['cron'] = [];

foreach($cronFiles as $f){

$path=$logDir.'/'.$f;

if(file_exists($path)){
$data['cron'][$f]=date("Y-m-d H:i:s",filemtime($path));
}else{
$data['cron'][$f]="never";
}

}

return $data;