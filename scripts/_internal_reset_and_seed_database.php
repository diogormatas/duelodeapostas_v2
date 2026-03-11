<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../core/Database.php';

$db = Database::getConnection();

echo "===== RESET + SEED =====\n";

$db->query("SET FOREIGN_KEY_CHECKS=0");

$tables=[
"activity_feed_v2",
"bet_picks_v2",
"bets_v2",
"coupon_matches_v2",
"coupon_prizes_v2",
"coupons_v2",
"duel_chat_v2",
"duels_v2",
"notifications_v2",
"transactions_v2",
"wallets_v2"
];

foreach($tables as $t){

$db->query("TRUNCATE $t");

echo "Cleared $t\n";

}

$db->query("SET FOREIGN_KEY_CHECKS=1");


/*
USERS
*/

$users=[];

$r=$db->query("SELECT id FROM users_v2");

while($row=$r->fetch_assoc())
$users[]=$row['id'];

echo "Users: ".count($users)."\n";


/*
WALLETS
*/

foreach($users as $u){

$balance=rand(50,200);

$stmt=$db->prepare("
INSERT INTO wallets_v2
(user_id,balance)
VALUES (?,?)
");

$stmt->bind_param("id",$u,$balance);
$stmt->execute();

}


/*
MATCHES
*/

$matches=[];

$r=$db->query("SELECT id FROM matches_v2 LIMIT 500");

while($row=$r->fetch_assoc())
$matches[]=$row['id'];

echo "Matches: ".count($matches)."\n";


/*
COUPONS
*/

$couponIds=[];

for($i=0;$i<500;$i++){

$entry=rand(2,10);

$maxPlayers=rand(2,8);

$rand=rand(1,100);

if($rand<=60)
$status="OPEN";
elseif($rand<=80)
$status="CLOSED";
else
$status="SETTLED";

$stmt=$db->prepare("
INSERT INTO coupons_v2
(type,entry_price,max_players,status,prize_status,visibility,created_at)
VALUES ('DUELO',?,?,?,'PENDING','PUBLIC',NOW())
");

$stmt->bind_param("dis",$entry,$maxPlayers,$status);
$stmt->execute();

$couponId=$db->insert_id;

$couponIds[]=$couponId;


shuffle($matches);

$games=array_slice($matches,0,rand(5,10));

foreach($games as $m){

$stmt=$db->prepare("
INSERT INTO coupon_matches_v2
(coupon_id,match_id)
VALUES (?,?)
");

$stmt->bind_param("ii",$couponId,$m);
$stmt->execute();

}

}

echo "Coupons created\n";


/*
BETS
*/

$betCount=0;

foreach($couponIds as $c){

shuffle($users);

$players=array_slice($users,0,rand(2,6));

foreach($players as $u){

$stake=rand(2,10);

$score=rand(0,10);

$r=rand(1,100);

if($r<=70)
$status="ACTIVE";
elseif($r<=90)
$status="WON";
else
$status="LOST";

$stmt=$db->prepare("
INSERT INTO bets_v2
(coupon_id,user_id,stake,score,status)
VALUES (?,?,?,?,?)
");

$stmt->bind_param("iidis",$c,$u,$stake,$score,$status);
$stmt->execute();

$betId=$db->insert_id;

$betCount++;


/*
PICKS
*/

$r=$db->query("
SELECT match_id
FROM coupon_matches_v2
WHERE coupon_id=$c
");

while($row=$r->fetch_assoc()){

$pick=["1","X","2"][rand(0,2)];

$isCorrect=rand(0,1);

$stmt=$db->prepare("
INSERT INTO bet_picks_v2
(bet_id,match_id,pick,is_correct)
VALUES (?,?,?,?)
");

$stmt->bind_param(
"iisi",
$betId,
$row['match_id'],
$pick,
$isCorrect
);

$stmt->execute();

}


/*
TRANSACTION
*/

$stmt=$db->prepare("
INSERT INTO transactions_v2
(user_id,type,amount,description)
VALUES (?, 'bet', ?, 'Coupon bet')
");

$amount=-$stake;

$stmt->bind_param("id",$u,$amount);
$stmt->execute();

}

}

echo "Bets created: $betCount\n";


/*
DUELS
*/

for($i=0;$i<300;$i++){

$challenger=$users[array_rand($users)];

$opponent=$users[array_rand($users)];

if($challenger==$opponent)
continue;

$stake=rand(2,10);

$stmt=$db->prepare("
INSERT INTO duels_v2
(challenger_id,opponent_id,stake,visibility,status,created_at)
VALUES (?,?,?,'PUBLIC','ACCEPTED',NOW())
");

$stmt->bind_param("iid",$challenger,$opponent,$stake);
$stmt->execute();

}

echo "Duels created\n";


/*
ACTIVITY
*/

for($i=0;$i<2000;$i++){

$user=$users[array_rand($users)];

$type=["duel_created","duel_accepted","bet_placed"][rand(0,2)];

$stmt=$db->prepare("
INSERT INTO activity_feed_v2
(user_id,type,data)
VALUES (?,?, '{}')
");

$stmt->bind_param("is",$user,$type);
$stmt->execute();

}

echo "Activity generated\n";


echo "===== DONE =====\n";