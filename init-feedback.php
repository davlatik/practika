<?php

use src\Feedback;
use src\services\Request;
use src\services\Db;
require 'init.php';

$page = 'feedback';

$feedback = new Feedback($request, $db);

if($request->isPost){
    $feedback->loadFromForm($request->post(), $_FILES['imageFile']);
    try{
        $feedback->validate();
        if($feedback->save()){
            $_SESSION['flash'] = 'Отзыв добавлен';
            header('Location: feedback.php');
            exit();
        }
    } catch(\InvalidArgumentException $e){
        $error = $e->getMessage();
    }
}

if(isset($_SESSION['flash'])){
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$feedbacks = $feedback->findAll();
