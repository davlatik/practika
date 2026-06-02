<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Выходим из папки src/ на уровень выше, чтобы подключить init.php
require_once __DIR__ . '/../init.php'; 

// Если $user определен как массив, принудительно трансформируем в объект
if (is_array($user)) {
    $userObj = new \src\User($request, $db); 
    $userObj->identity(); 
    $user = $userObj;
}

// Защита от гостей: если не авторизован, отправляем на страницу входа
if (!isset($user) || $user->isGuest) { 
    header('Location: login.php'); 
    exit(); 
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $text = trim($_POST['text'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $userId = intval($user->id);

    // ИСПРАВЛЕНО: Сначала проверяем физическое наличие данных во всех полях, 
    // и только если дата НЕ пустая — сверяем её с текущим днём.
    if (empty($reason) || empty($text) || empty($date) || empty($time)) {
        $error = 'Пожалуйста, заполните все поля формы!';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Нельзя записаться на прошедшую дату!';
    } else {
        // Экранируем данные перед записью
        $safeReason = addslashes($reason);
        $safeText = addslashes($text);
        $safeDate = addslashes($date);
        $safeTime = addslashes($time);

        // Формируем SQL-запрос для записи новой строки в таблицу
        $sql = "INSERT INTO `application` (`user_id`, `reason`, `text`, `date`, `time`, `status`) 
                VALUES ($userId, '{$safeReason}', '{$safeText}', '{$safeDate}', '{$safeTime}', 'new')";
        
        if ($db->querySql($sql)) {
            // После успешного сохранения перенаправляем обратно в личный кабинет к квадратам
            header('Location: account.php'); 
            exit();
        } else {
            $error = 'Ошибка при добавлении записи в базу данных.';
        }
    }
}
