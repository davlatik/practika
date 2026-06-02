<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/src/init.php'; 

if (is_array($user)) {
    $userObj = new \src\User($request, $db); $userObj->identity(); $user = $userObj;
}

// Запрос опубликованных отзывов из БД
$reviews = $db->querySql("
    SELECT * FROM `review` 
    WHERE `status` = 1 
    ORDER BY `create_at` DESC
") ?: [];

include 'src/header.php'; 
?>
<main id="main" class="flex-shrink-0 mt-5 pt-5" role="main">
    <div class="container">
        <div class="site-index p-3">
            
            <h1 class="display-4 fw-bold mb-4">Ремонт Компьютеров</h1>
            <p class="lead mb-5">Качественный и быстрый ремонт компьютерной техники любой сложности.</p>

            <h2 class="text-start mb-4 fw-bold">Отзывы клиентов</h2>
            
            <div class="row g-4">
                <?php if (empty($reviews)): ?>
                    <div class="col-12">
                        <p class="text-muted italic">Пока нет ни одного одобренного отзыва.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 rounded-0 bg-white">
                                
                                <?php if (!empty($rev['img'])): ?>
                                    <img src="/uploads/<?= htmlspecialchars($rev['img']) ?>" 
                                         class="card-img-top rounded-0" 
                                         style="height: 200px; object-fit: cover;" 
                                         alt="Изображение к отзыву">
                                <?php else: ?>
                                    <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <span class="text-muted small">Без фото</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body p-4">
                                    <h5 class="card-title fw-bold text-primary mb-2">
                                        <?= htmlspecialchars($rev['name'] ?? 'Клиент') ?>
                                    </h5>
                                    <p class="card-text text-secondary mb-0" style="font-size: 0.95rem; line-height: 1.5;">
                                        "<?= htmlspecialchars($rev['feedback'] ?? '-') ?>"
                                    </p>
                                    <div class="text-muted text-end small opacity-50 mt-3">
                                        <?= htmlspecialchars($rev['create_at'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>
<?php include 'src/footer.php'; ?>
