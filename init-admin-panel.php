<?php

require 'init.php';

if($user->isGuest){
    header('Location: login.php');
    exit();
}
if(!$user->isAdmin()){
    header('Location: login.php');
    exit();
}

$application = new \src\Application($request, $db);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0 && isset($_GET['submit'])) {
    $application->id = $id;
    $application->update(['status' => 'timereserv']);
    header('Location: admin-panel.php');
    exit();
}

if ($id > 0 && isset($_GET['finish'])) {
    $application->id = $id;
    $application->update(['status' => 'provided']);
    header('Location: admin-panel.php');
    exit();
}

$applications = $application->findAll();
if ($applications === null) {
    $applications = [];
}

if (isset($_GET['today'])) {
    $today = date('Y-m-d');
    $applications = array_filter($applications, function ($app) use ($today) {
        return isset($app['date']) && $app['date'] === $today;
    });
    $applications = array_values($applications);
}

$statusFilter = $_GET['status_id'] ?? '';
if (!empty($statusFilter)) {
    $applications = array_filter($applications, function ($app) use ($statusFilter) {
        return isset($app['status']) && $app['status'] === $statusFilter;
    });
    $applications = array_values($applications);
}
