<?php
require 'init.php';

if($user->isGuest){
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if($id === 0){
    header('Location: account.php');
    exit();
}

$applications = new \src\Application($request, $db);
$applicationData = $applications->getById($id);
if(empty($applicationData)){
    $error = 'Заявка не найдена';
}else{
    $applicationData = $applicationData[0];
}if($applicationData['user_id'] != $user->id){
    $error = 'Вы не можите просматривать эту страницу';
    $applicationData = null;
}
?>