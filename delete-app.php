<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/src/services/Db.php';
use src\services\Db;

if (!empty($_SESSION['user_id']) && isset($_GET['id'])) {
    $appId = intval($_GET['id']);
    $userId = intval($_SESSION['user_id']);

    $db = new Db(['hostname' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'mostamandi_db']);

    // ИСПРАВЛЕНО: Изменили applications на application (в единственном числе)
    $db->querySql("DELETE FROM `application` WHERE `id` = {$appId} AND `user_id` = {$userId}");
}

// Заново открываем страницу личного кабинета
header('Location: account.php');
exit;
