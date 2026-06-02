<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем файлы ядра из папки src
@include_once __DIR__ . '/src/exceptions/DbExceptions.php'; 
@include_once __DIR__ . '/src/exceptions/DbException.php'; 

require_once __DIR__ . '/src/services/Request.php';
require_once __DIR__ . '/src/services/Db.php';
require_once __DIR__ . '/src/Entity.php';
require_once __DIR__ . '/src/User.php';

use src\User;
use src\services\Request;
use src\services\Db;

$dbConfig = [
    'hostname' => 'localhost',
    'username' => 'root',         
    'password' => '',             
    'database' => 'mostamandi_db'  
];

$request = new Request();
$db = new Db($dbConfig); 

$user = new User($request, $db);

$error = '';
$flash = '';

// Проверяем прямую отправку формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Передаем данные напрямую в объект пользователя
    $user->loadFromForm($_POST);

    try {
        $user->validate();

        if ($user->save()) {
            $flash = "Регистрация успешно завершена!";
        } else {
            $error = "Ошибка при сохранении пользователя в базу данных.";
        }

    } catch (\InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (\Exception $e) {
        $error = "Системная ошибка: " . $e->getMessage();
    }
}

// Подключаем шапку
include 'src/header.php'; 
?>
<!DOCTYPE html>
<html lang="ru-RU" class="h-100">
<head>
    <title>Регистрация</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/site.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">

    <main id="main" class="flex-shrink-0 mt-5 pt-5" role="main">
        <div class="container">
            <div class="site-register p-3" style="max-width: 500px;">
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Главная</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Регистрация</li>
                    </ol>
                </nav>

                <h1 class="mb-4">Регистрация</h1>

                <!-- Вывод ошибок -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Вывод успеха -->
                <?php if (!empty($flash)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <!-- Упрощенные чистые имена полей (name) -->
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="login" class="form-label">логин</label>
                        <input type="text" id="login" name="login" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">пароль</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="fio" class="form-label">фио</label>
                        <input type="text" id="fio" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">телефон</label>
                        <input type="text" id="phone" name="phone" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Зарегистрировать</button>
                </form>

            </div>
        </div>
    </main>

</body>
</html>
<?php include 'src/footer.php' ?>
