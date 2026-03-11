<?php

require_once __DIR__.'/../../core/Database.php';

$db=Database::getConnection();



test("DB connection",$db!=null);

$tables=[

"users_v2",
"matches_v2",
"coupons_v2",
"bets_v2",
"wallets_v2"

];

foreach($tables as $t){

$res=$db->query("SELECT COUNT(*) c FROM $t");

test("table $t",$res!=false);

}