<?php

class AuthMiddleware
{
    public static function check()
    {
        if (!isset($_SESSION['user_id'])) {

            header("Location: /duelo/v2/public/login");
            exit();

        }
    }
}