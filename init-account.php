<?php

require 'init.php';

if ($user->isGuest) {
    header('Location: login.php');
    exit();
}

$application = new \src\Application($request, $db);

$applications = $application->findByColumn('user_id', $user->id);
if ($applications === null) {
    $applications = [];
}

$statusFilter = $_GET['status_id'] ?? '';
if (!empty($statusFilter)) {
    $applications = array_filter($applications, function ($app) use ($statusFilter) {
        return isset($app['status']) && $app['status'] === $statusFilter;
    });
    $applications = array_values($applications);
}
