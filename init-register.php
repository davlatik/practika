<?php
require 'init.php';
$page = 'register.php';
use src\User;

$user = new User($request, $db);

if($request->isPost){
    $user->load($request->post());
    try{
        $user->validate();
        if($user->save()){
            $_SESSION['flash'] = 'Регистрация успешна';
            header('Location: register.php');
            exit();
        }
    } catch(\src\exceptions\InvalidArgumentException $e){
        $error = $e->getMessage();
    }
}
if(isset($_SESSION['flash'])){
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
