<?php

require 'init.php';

if($user->isGuest){
    header('Location: login.php');
    exit();
}

if($request->isPost){
    $data = $request->post();
    try{
        if(empty($data['old_password'])){
            throw new \src\exceptions\InvalidArgumentException('Введите старый пароль');
        }
        if(empty($data['new_password'])){
            throw new \src\exceptions\InvalidArgumentException('Введите новый пароль');
        }
        if(empty($data['confirm_password'])){
            throw new \src\exceptions\InvalidArgumentException('Подтвердите новый пароль');
        }
        if($data['new_password'] !== $data['confirm_password']){
            throw new \src\exceptions\InvalidArgumentException('Пароли не совпадают');
        }
        if(strlen($data['new_password']) < 6 || strlen($data['new_password']) > 32){
            throw new \src\exceptions\InvalidArgumentException('Пароль должен быть от 6 до 32 символов');
        }
        if($user->password !== $data['old_password']){
            throw new \src\exceptions\InvalidArgumentException('Неверный старый пароль');
        }
        $user->update(['password' => $data['new_password']]);
        $flash = 'Пароль успешно изменен';
    } catch (\src\exceptions\InvalidArgumentException $e){
        $error = $e->getMessage();  
    }
}
