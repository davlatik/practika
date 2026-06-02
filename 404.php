<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Устанавливаем правильный HTTP-статус ответа 404 Not Found
http_response_code(404);

// ПОДКЛЮЧАЕМ БАЗУ ДАННЫХ (чтобы переменная $db была доступна для src/header.php)
require_once __DIR__ . '/src/services/Request.php';
require_once __DIR__ . '/src/services/Db.php';
use src\services\Request;
use src\services\Db;

$request = new Request();
$db = new Db(['hostname' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'mostamandi_db']);

// Теперь подключаем шапку — она отработает без ошибок
include 'src/header.php'; 
?>
<!DOCTYPE html>
<html lang="ru-RU" class="h-100">
<head>
    <title>Ошибка 404 — Страница не найдена</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/site.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100 bg-light">

<main class="container flex-shrink-0 d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="text-center py-5" style="max-width: 500px;">
        <h1 class="display-1 fw-bold text-dark mb-2">404</h1>
        
         <p class="text-secondary mb-4">
            Page not found
        </p>

        <div class="d-flex gap-2 justify-content-center">
     
            
            
        </div>
    </div>
</main>

</body>
</html>