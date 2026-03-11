<?php

require_once __DIR__ . '/../../core/Database.php';

class AuthController
{

    public function loginForm()
    {
        require __DIR__ . '/../../resources/views/login.php';
    }

    public function registerForm()
    {
        require __DIR__ . '/../../resources/views/register.php';
    }

    public function register()
    {

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo "Username e password obrigatórios.";
            return;
        }

        $db = Database::getConnection();

        # verificar se username existe

        $stmt = $db->prepare("
        SELECT id
        FROM users_v2
        WHERE username = ?
        ");

        $stmt->bind_param("s",$username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            echo "Username já existe.";
            return;

        }

        $stmt->close();

        # hash password

        $hash = password_hash($password,PASSWORD_DEFAULT);

        # criar utilizador

        $stmt = $db->prepare("
        INSERT INTO users_v2 (username,password_hash,role)
        VALUES (?,?,'USER')
        ");

        $stmt->bind_param("ss",$username,$hash);
        $stmt->execute();

        $userId = $db->insert_id;

        $stmt->close();

        # criar wallet

        $stmt = $db->prepare("
        INSERT INTO wallets_v2 (user_id,balance)
        VALUES (?,0)
        ");

        $stmt->bind_param("i",$userId);
        $stmt->execute();
        $stmt->close();

        header("Location: /duelo/v2/public/login");
        exit;

    }


    public function login()
    {

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = Database::getConnection();

        $stmt = $db->prepare("
        SELECT id,password_hash,role
        FROM users_v2
        WHERE username = ?
        ");

        $stmt->bind_param("s",$username);
        $stmt->execute();

        $stmt->bind_result($id,$hash,$role);

        if ($stmt->fetch()) {

            if (password_verify($password,$hash)) {

                if(session_status() === PHP_SESSION_NONE){
                    session_start();
                }

                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                header("Location: /duelo/v2/public/dashboard");
                exit;

            }

        }

        echo "Login inválido.";

    }


    public function logout()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        session_destroy();

        header("Location: /duelo/v2/public/login");
        exit;

    }

}