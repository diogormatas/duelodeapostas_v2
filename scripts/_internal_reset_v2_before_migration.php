<?php

require_once __DIR__.'/../core/Database.php';

$db = Database::getConnection();

echo "====================================\n";
echo "RESET V2 DATABASE (SAFE)\n";
echo "====================================\n\n";

$db->begin_transaction();

try{

echo "Disabling FK checks\n";

$db->query("SET FOREIGN_KEY_CHECKS=0");

/*
--------------------------------
BETTING
--------------------------------
*/

echo "Clearing bet_picks_v2\n";
$db->query("TRUNCATE bet_picks_v2");

echo "Clearing bets_v2\n";
$db->query("TRUNCATE bets_v2");

echo "Clearing coupon_matches_v2\n";
$db->query("TRUNCATE coupon_matches_v2");

echo "Clearing coupons_v2\n";
$db->query("TRUNCATE coupons_v2");

/*
--------------------------------
FINANCIAL
--------------------------------
*/

echo "Clearing transactions_v2\n";
$db->query("TRUNCATE transactions_v2");

echo "Clearing wallets_v2\n";
$db->query("TRUNCATE wallets_v2");

/*
--------------------------------
USERS
--------------------------------
*/

echo "Clearing users_v2\n";
$db->query("TRUNCATE users_v2");

$db->query("SET FOREIGN_KEY_CHECKS=1");

echo "\nFK checks restored\n";

$db->commit();

echo "\n====================================\n";
echo "V2 CLEAN RESET COMPLETE\n";
echo "====================================\n";

}catch(Exception $e){

$db->rollback();

echo "RESET FAILED\n";
echo $e->getMessage();

}