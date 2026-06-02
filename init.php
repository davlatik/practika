<?php
require 'autoLoad.php';
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $request = new src\services\Request();
    $db = new src\services\Db($dbOptions);
    $user = new src\User($request, $db);
    $identity = $user->identity();
    
    if ($identity !== null) {
        $user->load($identity);
        $user->isGuest = false;
    }
} catch (\src\exceptions\DbExceptions $e) {
    echo $e->getMessage();
    exit();
}

