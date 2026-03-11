<?php

require_once __DIR__.'/../../core/Database.php';

$db=Database::getConnection();



$jobs=[

"process_results",
"close_coupons",
"auto_settle",
"expire_duels"

];

foreach($jobs as $job){

$stmt=$db->prepare("
SELECT created_at
FROM system_logs_v2
WHERE action=?
ORDER BY id DESC
LIMIT 1
");

$stmt->bind_param("s",$job);
$stmt->execute();
$stmt->bind_result($date);
$stmt->fetch();
$stmt->close();

test("cron $job",$date!=null);

}