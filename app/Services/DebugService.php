<?php

class DebugService
{

    private static $startTime;
    private static $startMemory;

    public static function start()
    {

        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();

    }

    public static function report()
    {

        $time = microtime(true) - self::$startTime;
        $memory = memory_get_usage() - self::$startMemory;

        return [

            "runtime" => round($time,4),
            "memory" => round($memory/1024/1024,2),
            "queries" => count(Database::getQueries()),
            "db_time" => round(Database::getTotalTime(),4)

        ];

    }

}