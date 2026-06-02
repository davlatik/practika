<?php 
require 'init.php';

$user = new \src\User($request, $db);

if($request->isPost){
    $user->load($request->post());
    try{
        $user->validateLogin();
        $user->login();
        header('Location: index.php');
        exit();
    } catch(\src\exceptions\InvalidArgumentException $e){
        $error = $e->getMessage();
    }   
}