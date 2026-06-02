<?php
include 'init.php';
$user->logout();
header('Location: index.php');
exit;