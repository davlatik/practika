<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Очищаем и уничтожаем сессию (исправлена оетка с точкой)
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 2. УДАЛЯЕМ КУКУ ТОКЕНА (без этого авторизация не сбросится!)
if (isset($_COOKIE['token'])) {
    setcookie('token', '', time() - 3600, '/', '', false, true);
}

// 3. Перенаправляем на главную страницу
header("Location: index.php");
exit;
