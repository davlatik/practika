<?php

require 'init.php';
class_exists('\src\Feedback');

if($user->isGuest){
    header('Location: login.php');
    exit();
}
if(!$user->isAdmin()){
    header('Location: login.php');
    exit();
}

$feedbackModel = new \src\Feedback($request, $db);

if ($request->isPost) {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($id > 0) {
        $feedbackModel->id = $id;
        if ($action === 'publish') {
            $feedbackModel->update(['status' => 'approved']);
        }
        if ($action === 'reject') {
            $feedbackModel->update(['status' => 'rejected']);
        }
        
        header('Location: admin-reviews.php');
        exit();
    }
}

$allReviews = $feedbackModel->findAll();
if ($allReviews === null) {
    $allReviews = [];
}

$activeReviews = [];
foreach ($allReviews as $item) {
    $statusValue = $item['status'] ?? 'pending';
    if ($statusValue !== 'rejected') {
        $activeReviews[] = $item;
    }
}

if (isset($_GET['only_new'])) {
    $reviews = [];
    foreach ($activeReviews as $item) {
        $statusValue = $item['status'] ?? 'pending';
        if ($statusValue === 'pending') {
            $reviews[] = $item;
        }
    }
} else {
    $reviews = $activeReviews;
}
