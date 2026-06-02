<?php
// Инициализация сессии для работы с авторизацией и CSRF-защитой
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/src/init.php'; 

// Проверка прав: доступ разрешен только авторизованному администратору
if ($user->isGuest || !$user->isAdmin()) {
    header('Location: index.php'); 
    exit();
}

// Подключаем шапку сайта
include 'src/header.php'; 

// Генерация защитного CSRF-токена для предотвращения межсайтовых запросов
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$msg = $errorMsg = ''; 

// Обработка POST-запросов (действия администратора над заявками)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die('CSRF token error');
    
    $appId = intval($_POST['app_id'] ?? 0);
    $action = $_POST['admin_action'];

    // Действие: Удаление заявки из базы данных
    if ($action === 'delete') {
        $sql = "DELETE FROM `application` WHERE `id` = $appId";
        if ($db->query($sql)) {
            $msg = 'Заявка успешно удалена';
        } else {
            $errorMsg = 'Ошибка базы данных при удалении';
        }
    
    // Действие: Перенос даты и времени посещения
    } elseif ($action === 'update_time' && !empty($_POST['new_datetime'])) {
        $parts = explode('T', $_POST['new_datetime']);
        if (count($parts) === 2) {
            // ИСПРАВЛЕНО: Явно достаем элементы массива по индексам [0] и [1]
            $newDate = addslashes($parts[0]);
            $newTime = addslashes($parts[1] . ':00');
            
            // Получаем временные метки для точной сверки часов и минут
            $inputTimestamp = strtotime($_POST['new_datetime']);
            $currentTimestamp = time();

            // Проверка на стороне сервера: запрещаем прошедшие дни и часы
            if ($inputTimestamp < $currentTimestamp) {
                $errorMsg = 'Ошибка: Нельзя перенести заявку на прошедшее время';
            } else {
                // ИСПРАВЛЕНО: Вызываем стандартный метод query() напрямую для корректной записи UPDATE
                $sql = "UPDATE `application` SET `date` = '{$newDate}', `time` = '{$newTime}', `status` = 'timechange' WHERE `id` = $appId";
                if ($db->query($sql)) {
                    $msg = 'Время успешно изменено, статус обновлен на "Перенесено"';
                } else {
                    $errorMsg = 'Ошибка СУБД: Не удалось обновить запись';
                }
            }
        } else {
            $errorMsg = 'Ошибка: Неверный формат даты и времени';
        }
    
    // Действие: Смена статуса заявки (подтверждение или завершение)
    } elseif ($action === 'update_status' && !empty($_POST['new_status'])) {
        $newStatus = addslashes($_POST['new_status']);
        // ИСПРАВЛЕНО: Прямой вызов query() вместо querySql()
        $sql = "UPDATE `application` SET `status` = '{$newStatus}' WHERE `id` = $appId";
        if ($db->query($sql)) {
            $msg = $newStatus === 'timereserv' ? 'Заявка успешно подтверждена' : ($newStatus === 'provided' ? 'Заявка успешно завершена' : 'Статус заявки успешно обновлен');
        } else {
            $errorMsg = 'Ошибка базы данных при обновлении статуса';
        }
    }
}

// Получение параметров фильтрации из GET-запроса
$dateFilter = $_GET['date_filter'] ?? 'all';
$statusFilter = trim($_GET['ApplicationSearch']['status'] ?? '');

// Формирование и выполнение SQL-запроса для списка заявок (оставляем querySql, так как это выборка SELECT)
$sql = "SELECT a.*, u.name as user_name FROM `application` a LEFT JOIN `users` u ON a.user_id = u.id";
if ($statusFilter) $sql .= " WHERE a.status = '" . addslashes($statusFilter) . "'";
$sql .= " ORDER BY a.id DESC";
$applications = $db->querySql($sql) ?: [];

// Фильтрация полученного массива заявок, если выбран показ только на «сегодня»
if ($dateFilter === 'today') {
    $today = date('Y-m-d');
    $applications = array_filter($applications, function($app) use ($today) { return ($app['date'] ?? '') === $today; });
}

// Справочник человекопонятных названий для статусов
$statusNames = ['new' => 'Новая', 'timereserv' => 'Время забронировано', 'timechange' => 'Перенесено', 'provided' => 'Завершено'];
?>

<main class="container mt-5 pt-5">
    <h1 class="mb-4 fw-bold">Заявки</h1>
    
    <!-- ВЫВОД СООБЩЕНИЙ В СТРОГИХ ПРЯМОУГОЛЬНИКАХ -->
    <?php if ($msg): ?>
        <div class="alert alert-success rounded-0 fw-bold mb-4"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger rounded-0 fw-bold mb-4"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <form method="get" class="mb-4 d-flex gap-2 align-items-end" style="max-width: 700px;">
        <div class="w-100"><label class="form-label small mb-1 fw-bold text-secondary">Фильтр по дате</label>
            <select class="form-control rounded-0" name="date_filter">
                <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>Все дни</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Только на сегодня</option>
            </select>
        </div>
        <div class="w-100"><label class="form-label small mb-1 fw-bold text-secondary">Сортировка по статусу</label>
            <select class="form-control rounded-0" name="ApplicationSearch[status]"><option value="">Все статусы</option>
                <?php foreach($statusNames as $key => $name): ?>
                    <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary rounded-0">Показать</button>
        <a class="btn btn-outline-secondary rounded-0" href="admin-panel.php">Сбросить</a>
    </form>

    <div class="d-flex flex-wrap gap-3">
        <?php if (empty($applications)): ?>
            <p class="text-muted">Заявок не найдено.</p>
        <?php else: ?>
            <?php foreach($applications as $app): $currentStatus = $app['status'] ?? 'new'; ?>
                <div class="card rounded-0 border shadow-sm" style="width: 18rem;">
                    <div class="card-body d-flex flex-column gap-2" style="font-size: 0.9rem;">
                        <h5 class="card-title mb-0 fw-bold"><?= htmlspecialchars($app['reason'] ?? '-') ?></h5>
                        <p class="card-text text-secondary mb-1"><?= htmlspecialchars($app['text'] ?? '-') ?></p>
                        <div><span class="opacity-50 small">дата и время посещения:</span><br><strong><?= htmlspecialchars($app['date'] ?? '-') ?> в <?= htmlspecialchars(substr($app['time'] ?? '-', 0, 5)) ?></strong></div>
                        <div><span class="opacity-50 small">дата и время создания:</span><br><?= htmlspecialchars($app['create_at'] ?? '-') ?></div>
                        <div><span class="opacity-50 small">отправитель:</span> <?= htmlspecialchars($app['user_name'] ?? 'Удален') ?></div>
                        <div><span class="opacity-50 small">статус:</span> <strong><?= htmlspecialchars($statusNames[$currentStatus] ?? 'Новая') ?></strong></div>

                        <!-- Форма изменения даты и времени -->
                        <form method="post" class="mt-2" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="admin_action" value="update_time">
                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                            <div class="input-group input-group-sm">
                                <input type="datetime-local" name="new_datetime" class="form-control rounded-0" required>
                                <button type="submit" class="btn btn-primary rounded-0">OK</button>
                            </div>
                        </form>

                        <?php if (in_array($currentStatus, ['new', 'timechange', 'timereserv'])): ?>
                            <form method="post" class="mt-2 w-100">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="admin_action" value="update_status">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $currentStatus === 'new' ? 'timereserv' : 'provided' ?>">
                                <button type="submit" class="btn btn-sm w-100 fw-bold rounded-0 <?= $currentStatus === 'new' ? 'btn-primary' : 'btn-success' ?>">
                                    <?= $currentStatus === 'new' ? 'Подтвердить' : 'Завершить' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'src/footer.php'; ?>
