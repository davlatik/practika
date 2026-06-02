<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/src/init.php'; 

if (is_array($user)) {
    $userObj = new \src\User($request, $db); $userObj->identity(); $user = $userObj;
}
if (!isset($user) || $user->isGuest) { header('Location: login.php'); exit(); }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ОБРАБОТКА ПОЛНОГО УДАЛЕНИЯ ЗАЯВКИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die('CSRF error');
    
    $appId = intval($_POST['app_id']);
    $userId = intval($user->id);
    
    // ИСПРАВЛЕНО: Вместо обновления статуса полностью удаляем строку из таблицы application
    $sql = "DELETE FROM `application` WHERE `id` = {$appId} AND `user_id` = {$userId}";
    
    if ($db->querySql($sql)) {
        // Записываем сообщение об успешном удалении в сессию
        $_SESSION['success_message'] = 'Заявка успешно отменена!';
    }
    
    header('Location: account.php'); 
    exit();
}

$myApplications = (new \src\Application($request, $db))->findByColumn('user_id', (int)$user->id) ?: [];
if (isset($myApplications['id'])) $myApplications = [$myApplications];

include 'src/header.php'; 
$statusNames = ['new' => 'Новая', 'timereserv' => 'Подтверждена', 'timechange' => 'Перенесена', 'provided' => 'Завершена'];
?>
<main class="container mt-5 pt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="fw-bold m-0">Заявки</h1>
        <div>
            <a href="add-application.php" class="btn btn-primary rounded-0">Подать заявку</a>
            <a href="change-password.php" class="btn btn-outline-secondary rounded-0">Сменить пароль</a>
        </div>
    </div>

    <!-- ВЫВОД ЗЕЛЕНОГО СООБЩЕНИЯ ОБ УСПЕШНОЙ ОТМЕНЕ -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success rounded-0 mb-4 fw-bold" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($myApplications)): ?>
        <div class="p-4 border bg-white text-center text-muted">Вы еще не оставляли заявок.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-3">
            <?php foreach ($myApplications as $app): 
                $st = $app['status'] ?? 'new';
                $color = ['timereserv'=>'text-primary', 'timechange'=>'text-warning', 'provided'=>'text-success'][$st] ?? 'text-secondary';
            ?>
                <div class="col">
                    <div class="card h-100 border bg-white rounded-0 p-4 d-flex flex-column justify-content-between" style="aspect-ratio: 1/1;" title="Полное описание: <?= htmlspecialchars($app['text'] ?? '-') ?>">
                        <div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <h4 class="fw-bold m-0 text-truncate" style="max-width: 60%; font-size: 1.1rem;"><?= htmlspecialchars($app['reason'] ?? '-') ?></h4>
                                <span class="small fw-bold <?= $color ?>"><?= $statusNames[$st] ?? 'Новая' ?></span>
                            </div>
                            <p class="text-secondary text-truncate small"><?= htmlspecialchars($app['text'] ?? '-') ?></p>
                        </div>
                        <div>
                            <div class="text-muted small mb-3">Приём: <strong><?= htmlspecialchars($app['date'] ?? '-') ?> в <?= substr($app['time'] ?? '-', 0, 5) ?></strong></div>
                            
                            <form method="post" onsubmit="return confirm('Вы действительно хотите отменить и удалить эту заявку?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-0 py-2">Отменить заявку</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include 'src/footer.php'; ?>
