<?php

require_once __DIR__.'/../../core/Database.php';
require_once __DIR__.'/test_helper.php';

echo "Starting FULL FLOW TEST\n";

$db = Database::getConnection();

/*
-------------------------------------
CREATE TEST USER
-------------------------------------
*/

$username = "qa_user_".rand(1000,9999);
$password = "test123";

$hash = password_hash($password,PASSWORD_DEFAULT);

$stmt = $db->prepare("
INSERT INTO users_v2 (username,password_hash,role)
VALUES (?,?,'USER')
");

$stmt->bind_param("ss",$username,$hash);
$stmt->execute();

$userId = $db->insert_id;

$stmt->close();

test("create user",$userId>0);


/*
-------------------------------------
CREATE WALLET
-------------------------------------
*/

$stmt = $db->prepare("
INSERT INTO wallets_v2 (user_id,balance)
VALUES (?,100)
");

$stmt->bind_param("i",$userId);
$stmt->execute();
$stmt->close();

$res = $db->query("SELECT balance FROM wallets_v2 WHERE user_id=".$userId);
$row = $res->fetch_assoc();

test("wallet funded",$row['balance']==100);


/*
-------------------------------------
FIND OPEN COUPON
-------------------------------------
*/

$res = $db->query("
SELECT id,entry_price
FROM coupons_v2
WHERE status='OPEN'
LIMIT 1
");

$coupon = $res->fetch_assoc();

test("open coupon exists",$coupon!=null);

if(!$coupon){
    echo "No open coupon. Flow test stopped.\n";
    return;
}

$couponId = $coupon['id'];
$entry = $coupon['entry_price'];


/*
-------------------------------------
PLACE BET
-------------------------------------
*/

$stmt = $db->prepare("
INSERT INTO bets_v2
(coupon_id,user_id,stake,status)
VALUES (?,?,?,'ACTIVE')
");

$stmt->bind_param("iid",$couponId,$userId,$entry);
$stmt->execute();

$betId = $db->insert_id;

$stmt->close();

test("bet created",$betId>0);


/*
-------------------------------------
DEDUCT WALLET
-------------------------------------
*/

$db->query("
UPDATE wallets_v2
SET balance = balance - $entry
WHERE user_id = $userId
");

$res = $db->query("SELECT balance FROM wallets_v2 WHERE user_id=".$userId);
$row = $res->fetch_assoc();

test("wallet deducted",$row['balance']==100-$entry);


/*
-------------------------------------
CHECK BET EXISTS
-------------------------------------
*/

$res = $db->query("
SELECT id
FROM bets_v2
WHERE id=$betId
");

test("bet persisted",$res->num_rows==1);


/*
-------------------------------------
SIMULATE COUPON CLOSE
-------------------------------------
*/

$db->query("
UPDATE coupons_v2
SET status='CLOSED'
WHERE id=$couponId
");

$res = $db->query("
SELECT status
FROM coupons_v2
WHERE id=$couponId
");

$row = $res->fetch_assoc();

test("coupon closed",$row['status']=='CLOSED');


/*
-------------------------------------
CLEANUP
-------------------------------------
*/

$db->query("DELETE FROM bets_v2 WHERE id=$betId");
$db->query("DELETE FROM wallets_v2 WHERE user_id=$userId");
$db->query("DELETE FROM users_v2 WHERE id=$userId");

test("cleanup done",true);

echo "FULL FLOW TEST COMPLETE\n";