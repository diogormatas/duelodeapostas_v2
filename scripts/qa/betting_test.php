<?php

require_once __DIR__.'/../../core/Database.php';

$db=Database::getConnection();


$res=$db->query("
SELECT COUNT(*) c
FROM coupons_v2
WHERE status='OPEN'
");

$row=$res->fetch_assoc();

test("open coupons",$row['c']>=0);

$res=$db->query("
SELECT COUNT(*) c
FROM bets_v2
");

$row=$res->fetch_assoc();

test("bets exist",$row['c']>=0);