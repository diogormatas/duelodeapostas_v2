<?php

require_once __DIR__ . '/../Repositories/DuelChatRepository.php';

class DuelChatController
{

    public function send()
    {

        if(session_status()===PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['user_id'])){
            die("Login necessário");
        }

        $config = require __DIR__ . '/../../config/app.php';
        $base = $config['base_url'];

        $couponId = (int)($_POST['coupon_id'] ?? 0);
        $message = trim($_POST['message'] ?? "");

        if(!$couponId || $message === ""){
            header("Location: ".$base."/coupon/".$couponId);
            exit;
        }

        $repo = new DuelChatRepository();

        $repo->create(
            $couponId,
            $_SESSION['user_id'],
            $message
        );

        header("Location: ".$base."/coupon/".$couponId);
        exit;

    }

    public function list($couponId)
    {

        $repo = new DuelChatRepository();

        $messages = $repo->getByCoupon($couponId);

        header('Content-Type: application/json');

        echo json_encode($messages);

    }

}