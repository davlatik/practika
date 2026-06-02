<?php 
// Подключаем ваш бэкенд обработки логина
require_once __DIR__ . '/src/init-login.php'; 
// Подключаем шапку сайта
include 'src/header.php'; 
?>
<!DOCTYPE html>
<html lang="ru-RU" class="h-100">
<head>
    <title>Вход</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/site.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">

    <main id="main" class="flex-shrink-0 mt-5 pt-5" role="main">
        <div class="container">
            <div class="site-login p-3" style="max-width: 400px;">
                
                <h1 class="mb-4">Вход</h1>

                <!-- Вывод ошибок авторизации, если они есть -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Форма с исправленными именами полей -->
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="login" class="form-label">логин</label>
                        <!-- ИСПРАВЛЕНО: name="login" вместо массива -->
                        <input type="text" id="login" name="login" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">пароль</label>
                        <!-- ИСПРАВЛЕНО: name="password" вместо массива -->
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>

            </div>
        </div>
    </main>

</body>
</html>
<?php include 'src/footer.php' ?>
