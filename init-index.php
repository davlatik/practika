<?php

require 'init.php';
// require_once 'Feedback.php';

$feedbackModel = new \src\Feedback($request, $db);

$reviews = $feedbackModel->findAll();
if ($reviews === null) {
    $reviews = [];
}

$reviews = array_filter($reviews, function ($item) {
    return isset($item['status']) && $item['status'] === 'approved';
});
$reviews = array_values($reviews);
