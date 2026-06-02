<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/src/init.php'; 

if (is_array($user)) {
    $userObj = new \src\User($request, $db); $userObj->identity(); $user = $userObj;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$error = $successMsg = ''; $old = [];

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Feedback'])) {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) die('CSRF error');
    
    $old = $_POST['Feedback'];
    $fio = trim($old['fio'] ?? ''); 
    $phone = trim($old['phone'] ?? ''); 
    $text = trim($old['text'] ?? ''); 
    $agree = isset($old['agree']);

    // Очищаем телефон от лишних символов (пробелы, скобки, тире), оставляя только цифры и плюс
    $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

    // ВАЛИДАЦИЯ ПОЛЕЙ И НОМЕРА ТЕЛЕФОНА
    if (!$fio) {
        $error = 'Пожалуйста, введите ваше ФИО!';
    } elseif (!$phone) {
        $error = 'Пожалуйста, укажите контактный телефон!';
    } elseif (!preg_match('/^\+?[78]\d{10}$/', $cleanPhone)) {
        // Проверяет, что номер начинается с +7, 7 или 8 и содержит ровно 11 цифр
        $error = 'Неверный формат телефона!';
    } elseif (!$text) {
        $error = 'Пожалуйста, напишите текст отзыва!';
    } elseif (!$agree) {
        $error = 'Необходимо отметить согласие на обработку данных!';
    } else {
        
        // ОБРАБОТКА ЗАГРУЗКИ ФОТО
        $imgName = '';
        if (isset($_FILES['Feedback']['name']['imageFile']) && $_FILES['Feedback']['error']['imageFile'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['Feedback']['tmp_name']['imageFile'];
            $fileName = $_FILES['Feedback']['name']['imageFile'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Разрешенные форматы картинок
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Неверный формат изображения! Разрешены только JPG, JPEG, PNG, WEBP.';
            } else {
                // Создаем уникальное имя файла для защиты от перезаписи
                $imgName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/uploads/';
                
                // Создаем папку uploads, если её вдруг нет
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $imgName;
                if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                    $error = 'Ошибка при загрузке фотографии на сервер.';
                    $imgName = ''; // Сбрасываем при ошибке
                }
            }
        }

        // Если при загрузке фото возникла ошибка, INSERT не выполнится
        if (empty($error)) {
            $sql = "INSERT INTO `review` (`name`, `feedback`, `phone`, `status`, `create_at`, `img`) 
                    VALUES ('".addslashes($fio)."', '".addslashes($text)."', '".addslashes($cleanPhone)."', 0, NOW(), '".addslashes($imgName)."')";
            
            if ($db->querySql($sql)) {
                $successMsg = 'Ваш отзыв успешно отправлен!'; 
                $old = []; // Очищаем форму
            } else {
                $error = 'Ошибка базы данных при сохранении.';
            }
        }
    }
}

include 'src/header.php'; 
?>
<main class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($successMsg): ?><div class="alert alert-success rounded-0 mb-4 fw-bold"> <?= htmlspecialchars($successMsg) ?></div><?php endif; ?>

            <div class="card border bg-white mb-5 rounded-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="mb-4 fw-bold text-dark">Оставить отзыв</h2>
                    
                    <?php if ($error): ?><div class="alert alert-danger rounded-0 mb-4 fw-bold"> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                    
                    <!-- ИСПРАВЛЕНО: Добавлен enctype="multipart/form-data" для поддержки загрузки файлов -->
                    <form action="" method="post" enctype="multipart/form-data" class="d-flex flex-column gap-3" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div>
                            <label class="form-label small fw-bold text-secondary">Ваше ФИО</label>
                            <input type="text" name="Feedback[fio]" class="form-control rounded-0" value="<?= htmlspecialchars($old['fio'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-bold text-secondary">Телефон</label>
                            <input type="text" name="Feedback[phone]" class="form-control rounded-0" placeholder="+7 (999) 123-45-67" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-bold text-secondary">Текст отзыва</label>
                            <textarea name="Feedback[text]" class="form-control rounded-0" rows="4"><?= htmlspecialchars($old['text'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- ИСПРАВЛЕНО: Возвращено поле выбора файла фотографии -->
                        <div>
                            <label class="form-label small fw-bold text-secondary">Изображение</label>
                            <input type="file" name="Feedback[imageFile]" class="form-control rounded-0">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input rounded-0" type="checkbox" name="Feedback[agree]" id="agreeCheck" <?= isset($old['agree']) ? 'checked' : '' ?>>
                            <label class="form-check-label small text-secondary" for="agreeCheck">Я даю согласие на обработку персональных данных</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary fw-bold align-self-start px-4 rounded-0">Отправить отзыв</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'src/footer.php'; ?>
