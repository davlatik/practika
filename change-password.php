<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// 1. Подключаем единое ядро системы
require_once __DIR__ . '/src/init.php'; 

// Если $user определен как массив, преобразуем его обратно в нужный объект
if (is_array($user)) {
    $userObj = new \src\User($request, $db); $userObj->identity(); $user = $userObj;
}
// 2. Проверка авторизации: если гость — отправляем на страницу входа
if (!isset($user) || $user->isGuest) { header('Location: login.php'); exit(); }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['currentPassword'])) {
    $curPass = $_POST['currentPassword'] ?? '';
    $newPass = $_POST['newPassword'] ?? '';
    $retPass = $_POST['retypePassword'] ?? '';
    
    if (empty($curPass) || empty($newPass) || empty($retPass)) {
        $error = "Заполните все поля!";
    } elseif ($newPass !== $retPass) {
        $error = "Пароли не совпадают!";
    } elseif (strlen($newPass) < 6) {
        $error = "Пароль должен быть от 6 символов!";
    } else {
        $userId = intval($user->id);
        // Используем уже готовое и настроенное подключение $db из init.php
        $res = $db->querySql("SELECT * FROM `users` WHERE `id` = {$userId} LIMIT 1");
        $userData = isset($res[0]) ? $res[0] : $res;
        
        if ($userData && ($curPass === $userData['password'] || password_verify($curPass, $userData['password']))) {
            $sql = "UPDATE `users` SET `password` = '" . addslashes($newPass) . "' WHERE `id` = {$userId}";
            if ($db->querySql($sql)) {
                $success = "Пароль успешно изменен!";
            } else {
                $error = "Ошибка базы данных при обновлении.";
            }
        } else {
            $error = "Текущий пароль указан неверно!";
        }
    }
}
// 3. Подключаем шапку (внутри неё уже есть DOCTYPE, head и body)
include 'src/header.php'; 
?>
<main id="main" class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Стиль изменен на строгий прямоугольник (rounded-0) в тон личного кабинета -->
            <div class="card p-4 border bg-white mt-4 rounded-0 shadow-sm">
                <h3 class="mb-4 fw-bold text-dark">Смена пароля</h3>

                <?php if ($error): ?><div class="alert alert-danger rounded-0"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success rounded-0"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                <form action="" method="post" class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small fw-bold">Текущий пароль</label>
                        <input type="password" class="form-control rounded-0" name="currentPassword" required>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Новый пароль</label>
                        <input type="password" class="form-control rounded-0" name="newPassword" required>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Подтвердите пароль</label>
                        <input type="password" class="form-control rounded-0" name="retypePassword" required>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary rounded-0 px-4">Изменить пароль</button>
                        <a href="account.php" class="btn btn-outline-secondary rounded-0">Назад в кабинет</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include 'src/footer.php'; ?>
