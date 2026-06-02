<?php
// 1. Подключаем единую инициализацию приложения (автозагрузка классов, база данных, проверка сессий и пользователя)
require_once __DIR__ . '/src/init.php';

// Защита: зайти может только авторизованный администратор
if ($user->isGuest || !$user->isAdmin()) {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msgClass = 'alert-success'; // Теперь всегда используется зеленый цвет

// ОБРАБОТКА ИЗМЕНЕНИЯ СТАТУСОВ (1 - Опубликовать, 2 - Отклонить)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token error');
    }

    $reviewId = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
    $newStatus = isset($_POST['review_action']) ? intval($_POST['review_action']) : 0; 

    if ($reviewId > 0 && ($newStatus === 1 || $newStatus === 2)) {
        // Запрос обновления статуса
        $db->querySql("UPDATE `review` SET `status` = {$newStatus} WHERE `id` = {$reviewId}");
        
        // ИСПРАВЛЕНО: Тексты изменены, смайлы удалены, цвет плашки для обоих статусов — зеленый
        if ($newStatus === 1) {
            $msg = 'Отзыв успешно опубликован';
        } elseif ($newStatus === 2) {
            $msg = 'Отзыв успешно отклонен';
        }
    }
}

// Фильтрация отзывов
$onlyNew = isset($_GET['only_new']) && $_GET['only_new'] === '1';
$sql = "SELECT * FROM `review`";
if ($onlyNew) {
    $sql .= " WHERE `status` = 0";
}
$sql .= " ORDER BY `create_at` DESC";

$reviews = $db->querySql($sql) ?: [];

// 2. Подключаем шапку сайта
include 'src/header.php';
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <title>Модерация отзывов (Админка)</title>
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/site.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<main class="container mt-5 pt-5">
    <h1 class="mb-4 fw-bold">Модерация отзывов</h1>

    <!-- Вывод плашки (теперь всегда зеленая alert-success) -->
    <?php if ($msg): ?>
        <div class="alert alert-success fw-bold mb-4"><?= $msg ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <?php if (empty($reviews)): ?>
            <div class="col-12"><p class="text-muted italic">Отзывов не найдено.</p></div>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
                <?php 
                    $status = isset($rev['status']) ? intval($rev['status']) : 0; 
                    $currentId = isset($rev['id']) ? intval($rev['id']) : 0;
                    $cardBorder = '';
                    $badge = '';

                    if ($status === 0) {
                        $cardBorder = 'border-light';
                        $badge = '<span class="text-secondary small">Новый</span>'; 
                    } elseif ($status === 1) {
                        $cardBorder = 'border-light';
                        $badge = '<span class="text-secondary small">Опубликован</span>'; 
                    } elseif ($status === 2) {
                        $cardBorder = 'border-light';
                        $badge = '<span class="text-secondary small">Отклонен</span>'; 
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm <?= $cardBorder ?> bg-white">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0 fw-bold text-dark"><?= htmlspecialchars($rev['name'] ?? 'Клиент') ?></h5>
                                    <?= $badge ?>
                                </div>
                                <p class="card-text text-secondary mb-3" style="font-size: 0.95rem;">
                                    "<?= htmlspecialchars($rev['feedback'] ?? '-') ?>"
                                </p>
                                
                                <?php if (!empty($rev['img'])): ?>
                                    <div class="mb-3 text-center bg-light p-2 rounded">
                                        <img src="/uploads/<?= htmlspecialchars($rev['img']) ?>" class="img-fluid rounded" style="max-height: 150px; object-fit: contain;" alt="Фото">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-muted small mb-2 fw-bold">Тел: <?= htmlspecialchars($rev['phone'] ?? '-') ?></div>
                            </div>

                            <div class="mt-3 pt-2 border-top">
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="review_id" value="<?= $currentId ?>">

                                    <?php if ($status === 0): ?>
                                        <button type="submit" name="review_action" value="1" class="btn btn-sm btn-outline-secondary w-50 py-2">Опубликовать</button>
                                        <button type="submit" name="review_action" value="2" class="btn btn-sm btn-outline-secondary w-50 py-2">Отклонить</button>
                                    <?php elseif ($status === 1): ?>
                                        <button type="submit" name="review_action" value="2" class="btn btn-sm btn-outline-secondary w-100 py-2">Отклонить</button>
                                    <?php elseif ($status === 2): ?>
                                        <button type="submit" name="review_action" value="1" class="btn btn-sm btn-outline-secondary w-100 py-2">Опубликовать</button>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
