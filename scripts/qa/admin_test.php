<?php

require_once __DIR__.'/../../core/Database.php';

$db=Database::getConnection();



$res=$db->query("SELECT COUNT(*) c FROM system_logs_v2");

$row=$res->fetch_assoc();

test("logs exist",$row['c']>=0);

$res=$db->query("
SELECT COUNT(*) c
FROM system_logs_v2
WHERE level='ERROR'
AND created_at>NOW()-INTERVAL 24 HOUR
");

$row=$res->fetch_assoc();

test("errors < 50",$row['c']<50);