<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/test_helper.php';

echo "\n=====================================\n";
echo "DUEL PLATFORM QA SUITE\n";
echo "=====================================\n\n";

$tests = [

"http_test.php",
"database_test.php",
"cron_test.php",
"admin_test.php",
"betting_test.php",
"duel_test.php",
"full_flow_test.php",
"load_test.php",
"simulation_test.php"

];

foreach($tests as $test){

    echo "\n-------------------------------------\n";
    echo strtoupper($test)."\n";
    echo "-------------------------------------\n\n";

    try{

        require __DIR__.'/'.$test;

    }catch(Throwable $e){

        echo "[FATAL] ".$e->getMessage()."\n";
        echo $e->getFile().":".$e->getLine()."\n";

    }

}

echo "\n=====================================\n";
echo "QA COMPLETE\n";
echo "=====================================\n\n";