<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем архитектуру вашего проекта
require_once __DIR__ . '/src/services/Db.php';
use src\services\Db;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feedback.php');
    exit;
}

$data = $_POST['Feedback'] ?? null;

// 1. ПРОВЕРКА: Базовая валидация на заполненность полей
if (!$data || empty($data['fio']) || empty($data['phone']) || empty($data['text']) || !isset($data['agree'])) {
    $_SESSION['review_error'] = 'Пожалуйста, заполните все обязательные поля и подтвердите согласие.';
    header('Location: feedback.php');
    exit;
}

// Очистка текстовых данных от лишних пробелов
$name = trim($data['fio']);
$phone = trim($data['phone']);
$feedbackText = trim($data['text']);
$createdAt = date('Y-m-d H:i:s');
$status = 0; // Новый отзыв на модерацию
$imgName = ''; 
$errors = [];

// 2. ГЛУБОКАЯ ВАЛИДАЦИЯ ДАННЫХ

// Длина имени
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'ФИО должно содержать от 2 до 100 символов.';
}

// Формат телефона (базовая проверка на цифры, скобки и плюсы)
if (!preg_match('/^[0-9\-\+\(\)\s]{6,20}$/', $phone)) {
    $errors[] = 'Неверный формат номера телефона.';
}

// Длина текста отзыва
if (mb_strlen($feedbackText) < 10 || mb_strlen($feedbackText) > 2000) {
    $errors[] = 'Текст отзыва должен быть от 10 до 2000 символов.';
}

// 3. БЕЗОПАСНАЯ ОБРАБОТКА ИЗОБРАЖЕНИЯ
if (isset($_FILES['Feedback']['name']['imageFile']) && $_FILES['Feedback']['error']['imageFile'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['Feedback']['tmp_name']['imageFile'];
    $fileName = $_FILES['Feedback']['name']['imageFile'];
    $fileSize = $_FILES['Feedback']['size']['imageFile'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Проверка расширения
    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = 'Недопустимый формат файла. Разрешены только JPG, PNG, WEBP.';
    }
    
    // Проверка реального содержимого (MIME-тип)
    $realMimeType = mime_content_type($fileTmpPath);
    if (!in_array($realMimeType, $allowedMimeTypes)) {
        $errors[] = 'Файл не является валидным изображением.';
    }
    
    // Ограничение по размеру (например, макс. 5 МБ)
    if ($fileSize > 5 * 1024 * 1024) {
        $errors[] = 'Размер изображения не должен превышать 5 МБ.';
    }

    // Если к картинке нет претензий — генерируем имя и сохраняем
    if (empty($errors)) {
        $imgName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
        $uploadFileDir = __DIR__ . '/uploads/';
        
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }
        
        if (!move_uploaded_file($fileTmpPath, $uploadFileDir . $imgName)) {
            $errors[] = 'Не удалось сохранить загруженное изображение.';
        }
    }
}

// Если на этапе глубокой валидации возникли ошибки
if (!empty($errors)) {
    $_SESSION['review_error'] = implode('<br>', $errors);
    header('Location: feedback.php');
    exit;
}

// 4. ЗАПИСЬ В БАЗУ ДАННЫХ через PDO (Prepared Statements)
try {
    // Используем параметры подключения, аналогичные вашему классу Db
    $pdo = new PDO("mysql:host=localhost;dbname=mostamandi_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO `review` (`name`, `phone`, `feedback`, `create_at`, `img`, `status`) 
            VALUES (:name, :phone, :feedback, :create_at, :img, :status)";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':name'      => $name,
        ':phone'     => $phone,
        ':feedback'  => $feedbackText,
        ':create_at' => $createdAt,
        ':img'       => $imgName,
        ':status'    => $status
    ]);

    if ($result) {
        $_SESSION['review_success'] = 'Отзыв успешно отправлен и поступил на модерацию!';
    } else {
        $_SESSION['review_error'] = 'Не удалось записать отзыв в базу данных.';
    }

} catch (PDOException $e) {
    // В продакшене лучше заменить $e->getMessage() на общую фразу, чтобы не раскрывать структуру БД
    $_SESSION['review_error'] = 'Ошибка базы данных при сохранении.';
}

header('Location: feedback.php');
exit;
