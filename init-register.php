<?php

@include_once __DIR__ . '/exceptions/DbExceptions.php'; 
@include_once __DIR__ . '/exceptions/DbException.php'; 
@include_once __DIR__ . '/exceptions/InvalidException.php'; 

require_once __DIR__ . '/services/Request.php';
require_once __DIR__ . '/services/Db.php';
require_once __DIR__ . '/Entity.php';
require_once __DIR__ . '/User.php';


use src\User;
use src\services\Request;
use src\services\Db;
use src\exceptions\InvalidException;

$dbConfig = [
    'hostname' => 'localhost',
    'username' => 'root',         
    'password' => '',             
    'database' => 'mostamandi_db'  
];

$request = new Request();
$db = new Db($dbConfig); 

$user = new User($request, $db);

$error = '';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RegisterForm'])) {
    
    $user->loadFromForm($_POST['RegisterForm']);

    try {
        $user->validate();

        if ($user->save()) {
            $flash = "Регистрация успешно завершена!";
        } else {
            $error = "Ошибка при сохранении пользователя в базу данных.";
        }

    } catch (InvalidException $e) {
        // Перехватываем вашу кастомную ошибку валидации
        $error = $e->getMessage();
    } catch (\Exception $e) {
        // Перехватываем любые другие непредвиденные системные ошибки
        $error = "Системная ошибка: " . $e->getMessage();
    }
}
