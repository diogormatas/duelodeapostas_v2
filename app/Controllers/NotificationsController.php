<?php

require_once __DIR__ . '/../Repositories/NotificationRepository.php';

class NotificationsController
{

    public function index()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['user_id'])){
            die("Login necessário");
        }

        $repo = new NotificationRepository();

        $notifications = $repo->getByUser($_SESSION['user_id']);

        require __DIR__ . '/../../resources/views/notifications.php';

    }

    public function markRead($id)
    {

        $repo = new NotificationRepository();

        $repo->markAsRead((int)$id);

        header("Location: /notifications");
        exit;

    }

}