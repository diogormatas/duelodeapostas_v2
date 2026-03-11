<?php

class Database
{
    private static $connection = null;

    private static $queries = [];
    private static $totalTime = 0;

    public static function getConnection()
    {
        if (self::$connection === null) {

            $config = require __DIR__ . '/../config/database.php';

            self::$connection = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );

            if (self::$connection->connect_error) {
                die("Database connection failed: " . self::$connection->connect_error);
            }

            self::$connection->set_charset($config['charset']);
        }

        return self::$connection;
    }

    /*
    ------------------------------------------------
    DEBUG HELPERS
    ------------------------------------------------
    */

    public static function logQuery($sql, $time)
    {

        self::$totalTime += $time;

        self::$queries[] = [
            'sql' => $sql,
            'time' => $time
        ];

    }

    public static function getQueries()
    {
        return self::$queries;
    }

    public static function getTotalTime()
    {
        return self::$totalTime;
    }

}