<?php

require_once __DIR__.'/../../core/Database.php';

$db=Database::getConnection();



$res=$db->query("
SELECT COUNT(*) c
FROM duels_v2
");

$row=$res->fetch_assoc();

test("duels table",$row['c']>=0);

$res=$db->query("
SELECT COUNT(*) c
FROM duels_v2
WHERE status='PENDING'
");

$row=$res->fetch_assoc();

test("pending duels",$row['c']>=0);