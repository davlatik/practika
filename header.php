<!DOCTYPE html>
<html lang="ru-RU" class="h-100">

<head>
    <title>Заказ услуги</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/site.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <?php 
    
    $current_page = basename($_SERVER['SCRIPT_NAME']); 
    ?>

    <header id="header">
        <nav class="navbar-expand-md navbar-dark bg-dark fixed-top navbar">
            <div class="container">
                <a class="navbar-brand" href="index.php">Ремонт Компьютеров</a>
                <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav-collapse"
                    aria-controls="nav-collapse" aria-expanded="false" aria-label="Переключить навигацию">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="nav-collapse" class="collapse navbar-collapse">
                    <ul class="navbar-nav nav">
                        <li class="nav-item"><a class="nav-link <?= $current_page == 'feedback.php' ? 'active' : '' ?>" href="feedback.php">отзывы</a></li>
                        
                        <?php if($user->isGuest): ?>
                            <li class="nav-item"><a class="nav-link <?= $current_page == 'login.php' ? 'active' : '' ?>" href="login.php">войти</a></li>
                            <li class="nav-item"><a class="nav-link <?= $current_page == 'register.php' ? 'active' : '' ?>" href="register.php">регистрация</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php"><?= htmlspecialchars($user->getLogin()) ?> выйти</a></li>
                            
                            <?php if(!$user->isAdmin()): ?>
                                <li class="nav-item"><a class="nav-link <?= $current_page == 'account.php' ? 'active' : '' ?>" href="account.php">личный кабинет</a></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if(!$user->isGuest && $user->isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link <?= $current_page == 'admin-panel.php' ? 'active' : '' ?>" href="admin-panel.php">админка</a></li>
                            <li class="nav-item"><a class="nav-link <?= $current_page == 'admin-reviews.php' ? 'active' : '' ?>" href="admin-reviews.php">модерация отзывов</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container">
    <?php if(isset($flash)) : ?>
        <div class="bg-success"><?= $flash ?></div>
    <?php endif ?>
    </div>
