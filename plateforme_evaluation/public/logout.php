<?php
    session_start();

    require_once '../classes/Auth.php';
    require_once '../config/Database.php';
    require_once '../classes/Session.php';

    use Classes\Session;
    use Classes\Auth;
    use Config\Database;


    $pdo = Database::getInstance()->getConnection();
    $session = new Session($pdo);
    $auth = new Auth($pdo, $session);
    $loggedOut = $auth->logout();
    if ($loggedOut) {
        header('Location: login.php');
    } else {
        header('./index.php');
    }
?>