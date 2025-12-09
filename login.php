<?php
session_start();

// Sprawdzenie, czy aplikacja jest zainstalowana
if (!file_exists('includes/config.local.php')) {
    header('Location: install.php');
    exit();
}

require_once 'includes/config.local.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Przekierowanie jeśli użytkownik jest już zalogowany
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (login($email, $password)) {
        $_SESSION['message'] = 'Zostałeś pomyślnie zalogowany.';
        header('Location: index.php');
        exit();
    } else {
        $error = 'Nieprawidłowy email lub hasło.';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form method="POST" action="">
            <h1 class="h3 mb-3 fw-normal">Zaloguj się</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required autofocus>
                <label for="email">Email</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Hasło" required>
                <label for="password">Hasło</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Zaloguj się</button>
            <p class="mt-3 mb-3 text-muted">© <?php echo date('Y'); ?></p>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
