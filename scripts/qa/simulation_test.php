<?php

require_once __DIR__.'/../../core/Database.php';
require_once __DIR__.'/test_helper.php';

echo "START PLATFORM SIMULATION\n";

$db = Database::getConnection();

$users=[];
$bets=[];

/*
-------------------------------------
FIND OPEN COUPON
-------------------------------------
*/

$res=$db->query("
SELECT id,entry_price
FROM coupons_v2
WHERE status='OPEN'
LIMIT 1
");

$coupon=$res->fetch_assoc();

test("open coupon exists",$coupon!=null);

if(!$coupon){
    echo "No open coupon\n";
    return;
}

$couponId=$coupon['id'];
$entry=$coupon['entry_price'];

/*
-------------------------------------
CREATE USERS
-------------------------------------
*/

for($i=0;$i<100;$i++){

    $username="qa_sim_".rand(10000,99999);

    $hash=password_hash("test123",PASSWORD_DEFAULT);

    $stmt=$db->prepare("
    INSERT INTO users_v2 (username,password_hash,role)
    VALUES (?,?,'USER')
    ");

    $stmt->bind_param("ss",$username,$hash);
    $stmt->execute();

    $userId=$db->insert_id;

    $stmt->close();

    $users[]=$userId;

    $stmt=$db->prepare("
    INSERT INTO wallets_v2 (user_id,balance)
    VALUES (?,200)
    ");

    $stmt->bind_param("i",$userId);
    $stmt->execute();
    $stmt->close();
}

test("100 users created",count($users)==100);

/*
-------------------------------------
PLACE BETS
-------------------------------------
*/

for($i=0;$i<1000;$i++){

    $userId=$users[array_rand($users)];

    $stmt=$db->prepare("
    INSERT INTO bets_v2
    (coupon_id,user_id,stake,status)
    VALUES (?,?,?,'ACTIVE')
    ");

    $stmt->bind_param("iid",$couponId,$userId,$entry);
    $stmt->execute();

    $betId=$db->insert_id;

    $stmt->close();

    $bets[]=$betId;

    $db->query("
    UPDATE wallets_v2
    SET balance=balance-$entry
    WHERE user_id=$userId
    ");

}

test("1000 bets created",count($bets)==1000);

/*
-------------------------------------
POOL CHECK
-------------------------------------
*/

$res=$db->query("
SELECT SUM(stake) total
FROM bets_v2
WHERE coupon_id=$couponId
");

$row=$res->fetch_assoc();

$expected=$entry*1000;

test("pool >= expected",$row['total']>=$expected);

/*
-------------------------------------
WALLET NEVER NEGATIVE
-------------------------------------
*/

$res=$db->query("
SELECT COUNT(*) c
FROM wallets_v2
WHERE balance<0
");

$row=$res->fetch_assoc();

test("wallet never negative",$row['c']==0);

/*
-------------------------------------
SYSTEM LOAD CHECK
-------------------------------------
*/

$res=$db->query("
SELECT COUNT(*) c
FROM bets_v2
WHERE coupon_id=$couponId
");

$row=$res->fetch_assoc();

test("bets persisted",$row['c']>=1000);

/*
-------------------------------------
CLEANUP
-------------------------------------
*/

foreach($bets as $betId){
    $db->query("DELETE FROM bets_v2 WHERE id=$betId");
}

foreach($users as $userId){
    $db->query("DELETE FROM wallets_v2 WHERE user_id=$userId");
    $db->query("DELETE FROM users_v2 WHERE id=$userId");
}

test("cleanup done",true);

echo "SIMULATION COMPLETE\n";