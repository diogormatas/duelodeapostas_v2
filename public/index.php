<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

/*
-------------------------------------------------
CORE
-------------------------------------------------
*/

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Database.php';

/*
-------------------------------------------------
SERVICES
-------------------------------------------------
*/

require_once __DIR__ . '/../app/Services/LoggerService.php';
require_once __DIR__ . '/../app/Services/DebugService.php';

/*
-------------------------------------------------
REQUEST DEBUG START
-------------------------------------------------
*/

DebugService::start();

/*
-------------------------------------------------
GLOBAL ERROR HANDLER
-------------------------------------------------
*/

set_error_handler(function($severity,$message,$file,$line){

    LoggerService::error(
        "php",
        "runtime_error",
        $message,
        [
            "file"=>$file,
            "line"=>$line,
            "severity"=>$severity
        ]
    );

});

/*
-------------------------------------------------
GLOBAL EXCEPTION HANDLER
-------------------------------------------------
*/

set_exception_handler(function($exception){

    LoggerService::error(
        "php",
        "uncaught_exception",
        $exception->getMessage(),
        [
            "file"=>$exception->getFile(),
            "line"=>$exception->getLine(),
            "trace"=>$exception->getTraceAsString()
        ]
    );

    http_response_code(500);

    echo "<pre>";
    echo "EXCEPTION\n\n";
    echo $exception->getMessage()."\n\n";
    echo "File: ".$exception->getFile()."\n";
    echo "Line: ".$exception->getLine()."\n\n";
    echo $exception->getTraceAsString();
    echo "</pre>";

});

/*
-------------------------------------------------
FATAL ERROR HANDLER
-------------------------------------------------
*/

register_shutdown_function(function(){

    $error = error_get_last();

    if($error){

        LoggerService::error(
            "php",
            "fatal_error",
            $error['message'],
            [
                "file"=>$error['file'],
                "line"=>$error['line']
            ]
        );

    }

});

/*
-------------------------------------------------
ONLINE USERS
-------------------------------------------------
*/

require_once __DIR__ . '/../app/Repositories/UserOnlineRepository.php';

if(isset($_SESSION['user_id'])){

    $onlineRepo = new UserOnlineRepository();
    $onlineRepo->update($_SESSION['user_id']);

}

/*
-------------------------------------------------
ROUTER
-------------------------------------------------
*/

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$router->dispatch();

/*
-------------------------------------------------
DEBUG REPORT
-------------------------------------------------
*/

if(isset($_SESSION['role']) && $_SESSION['role']==='ADMIN'){

    $debug = DebugService::report();

    echo "<div style='
    position:fixed;
    bottom:10px;
    right:10px;
    background:#111;
    color:#0f0;
    padding:10px;
    font-size:12px;
    border-radius:6px;
    font-family:monospace;
    z-index:9999;
    '>";

    echo "runtime: ".$debug['runtime']."s<br>";
    echo "memory: ".$debug['memory']." MB<br>";
    echo "queries: ".$debug['queries']."<br>";
    echo "db time: ".$debug['db_time']."s";

    echo "</div>";

}