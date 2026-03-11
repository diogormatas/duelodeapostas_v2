<?php

$base="https://duelodeapostas.pt/duelo/v2/public";



function http($url){

    $ch=curl_init($url);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,5);

    curl_exec($ch);

    $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $code;

}

$routes=[

"/login",
"/register",
"/dashboard",
"/bets",
"/duels",
"/notifications",
"/admin",
"/admin/system",
"/admin/health",
"/admin/import",
"/admin/system-logs",
"/admin/cron-status",
"/admin/coupons"

];

foreach($routes as $r){

    $code=http($base.$r);

    test($r,$code<500);

}