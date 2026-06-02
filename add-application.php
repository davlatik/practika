<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Подключаем единое ядро вашей системы
require_once __DIR__ . '/src/init.php'; 

// Если объект $user определен как массив, принудительно преобразуем в объект
if (is_array($user)) {
    $userObj = new \src\User($request, $db); 
    $userObj->identity(); 
    $user = $userObj;
}

// 2. Защита от гостей: если не авторизован — отправляем на login.php
if (!isset($user) || $user->isGuest) { 
    header('Location: login.php'); 
    exit(); 
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// 3. ОБРАБОТКА ОТПРАВКИ ФОРМЫ ЧЕРЕЗ ВАШ КЛАСС REQUEST
if ($request->isPost) {
    // Получаем очищенные от тегов данные через ваш метод $request->post()
    $postData = $request->post();
    
    $reason = $postData['reason'] ?? '';
    $text = $postData['text'] ?? '';
    $date = $postData['date'] ?? '';
    $time = $postData['time'] ?? '';
    $userId = intval($user->id ?? 0);

    // Строгая серверная проверка каждого поля на пустоту
    if (empty($date)) {
        $error = 'Пожалуйста, выберите дату посещения!';
    } elseif (empty($time)) {
        $error = 'Пожалуйста, выберите время посещения!';
    } elseif (empty($reason)) {
        $error = 'Пожалуйста, укажите краткую причину посещения!';
    } elseif (empty($text)) {
        $error = 'Пожалуйста, подробно опишите причину посещения!';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Нельзя записаться на прошедшую дату!';
    } elseif ($userId === 0) {
        $error = 'Ошибка авторизации. Пожалуйста, перезайдите в аккаунт.';
    } else {
        $safeReason = addslashes($reason);
        $safeText = addslashes($text);
        $safeDate = addslashes($date);
        $safeTime = addslashes($time);

        $sql = "INSERT INTO `application` (`user_id`, `reason`, `text`, `date`, `time`, `status`) 
                VALUES ($userId, '{$safeReason}', '{$safeText}', '{$safeDate}', '{$safeTime}', 'new')";
        
        if ($db->querySql($sql)) {
            // После успешного сохранения перенаправляем строго в личный кабинет к квадратам заявок
            header('Location: account.php'); 
            exit();
        } else {
            $error = 'Ошибка базы данных при добавлении записи.';
        }
    }
}

// 4. ПОДКЛЮЧАЕМ ШАПКУ СТИЛЯ СЕЙЧАС (После обработки POST, чтобы избежать заголовков)
include 'src/header.php'; 
?>
<main id="main" class="container mt-5 pt-5" role="main">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 border bg-white mt-4 rounded-0 shadow-sm">
                <h3 class="mb-4 fw-bold text-dark">Новая заявка</h3>
                
                <!-- НАСТОЯЩИЙ ВЫВОД КРАСНОЙ СЕРВЕРНОЙ ПЛАШКИ ОШИБКИ -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-0 mb-3" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Атрибут novalidate отключает всплывающие подсказки браузера -->
                <form action="" method="post" class="d-flex flex-column gap-3" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Выберите дату</label>
                            <input type="date" class="form-control rounded-0" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Время посещения</label>
                            <input type="time" class="form-control rounded-0" name="time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Причина посещения (кратко)</label>
                        <input type="text" class="form-control rounded-0" name="reason" placeholder="Например: Осмотр / Консультация" value="<?= htmlspecialchars($_POST['reason'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Причина посещения (подробно)</label>
                        <textarea class="form-control rounded-0" name="text" rows="4" placeholder="Опишите ваши симптомы..."><?= htmlspecialchars($_POST['text'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary rounded-0 px-4">Отправить заявку</button>
                        <a href="account.php" class="btn btn-outline-secondary rounded-0">Назад</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include 'src/footer.php'; ?>
