<?php

require_once __DIR__ . '/../../core/Database.php';

class LoggerService
{

    private static function getRequestId()
    {

        if (!isset($_SERVER['REQUEST_ID'])) {
            $_SERVER['REQUEST_ID'] = bin2hex(random_bytes(8));
        }

        return $_SERVER['REQUEST_ID'];

    }

    private static function getUserId()
    {

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        return $_SESSION['user_id'] ?? null;

    }

    public static function log($category, $action, $message = null, $context = null, $level = 'INFO')
    {

        try {

            $db = Database::getConnection();

            $contextJson = $context ? json_encode($context) : null;

            $requestId = self::getRequestId();
            $userId = self::getUserId();
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $url = $_SERVER['REQUEST_URI'] ?? null;
            $method = $_SERVER['REQUEST_METHOD'] ?? null;

            $stmt = $db->prepare("
                INSERT INTO system_logs_v2
                (
                    category,
                    action,
                    message,
                    context,
                    level,
                    request_id,
                    user_id,
                    ip,
                    url,
                    method
                )
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "ssssssisss",
                $category,
                $action,
                $message,
                $contextJson,
                $level,
                $requestId,
                $userId,
                $ip,
                $url,
                $method
            );

            $stmt->execute();

        } catch (Exception $e) {

            // nunca deixar logger quebrar o sistema

        }

    }

    public static function info($category, $action, $message = null, $context = null)
    {
        self::log($category, $action, $message, $context, 'INFO');
    }

    public static function warning($category, $action, $message = null, $context = null)
    {
        self::log($category, $action, $message, $context, 'WARNING');
    }

    public static function error($category, $action, $message = null, $context = null)
    {
        self::log($category, $action, $message, $context, 'ERROR');
    }

}