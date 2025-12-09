<?php
/**
 * Skrypt instalacyjny dla aplikacji Comperia
 */

// Sprawdzenie, czy aplikacja nie jest już zainstalowana
if (file_exists('includes/config.local.php')) {
    die('Aplikacja jest już zainstalowana. Usuń plik includes/config.local.php, aby ponownie uruchomić instalację.');
}

// Funkcja do wyświetlania formularza
function showForm($errors = []) {
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalacja - Comperia</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
            }
            .install-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 30px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        </style>
    </head>
    <body>
        <div class="install-container">
            <h1 class="text-center mb-4">Instalacja Comperia</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5>Błędy:</h5>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <h3>Konfiguracja bazy danych</h3>
                <div class="mb-3">
                    <label for="db_host" class="form-label">Host bazy danych</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label for="db_name" class="form-label">Nazwa bazy danych</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" required>
                </div>
                <div class="mb-3">
                    <label for="db_user" class="form-label">Użytkownik bazy danych</label>
                    <input type="text" class="form-control" id="db_user" name="db_user" required>
                </div>
                <div class="mb-3">
                    <label for="db_pass" class="form-label">Hasło bazy danych</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                </div>
                
                <h3>Konfiguracja API Comperia</h3>
                <div class="mb-3">
                    <label for="api_key" class="form-label">Klucz API Comperia</label>
                    <input type="text" class="form-control" id="api_key" name="api_key" required>
                </div>
                
                <h3>Konfiguracja administratora</h3>
                <div class="mb-3">
                    <label for="admin_email" class="form-label">Email administratora</label>
                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                </div>
                <div class="mb-3">
                    <label for="admin_pass" class="form-label">Hasło administratora</label>
                    <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                </div>
                <div class="mb-3">
                    <label for="admin_pass_confirm" class="form-label">Potwierdź hasło</label>
                    <input type="password" class="form-control" id="admin_pass_confirm" name="admin_pass_confirm" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Zainstaluj</button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
}

// Funkcja do walidacji danych
function validateData($data) {
    $errors = [];
    
    // Walidacja bazy danych
    if (empty($data['db_host'])) $errors[] = 'Host bazy danych jest wymagany';
    if (empty($data['db_name'])) $errors[] = 'Nazwa bazy danych jest wymagana';
    if (empty($data['db_user'])) $errors[] = 'Użytkownik bazy danych jest wymagany';
    
    // Walidacja API
    if (empty($data['api_key'])) $errors[] = 'Klucz API jest wymagany';
    
    // Walidacja administratora
    if (empty($data['admin_email'])) $errors[] = 'Email administratora jest wymagany';
    if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Nieprawidłowy format email';
    if (empty($data['admin_pass'])) $errors[] = 'Hasło administratora jest wymagane';
    if (strlen($data['admin_pass']) < 8) $errors[] = 'Hasło musi mieć co najmniej 8 znaków';
    if ($data['admin_pass'] !== $data['admin_pass_confirm']) $errors[] = 'Hasła nie są identyczne';
    
    return $errors;
}

// Funkcja do testowania połączenia z bazą danych
function testDatabaseConnection($host, $name, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Funkcja do tworzenia tabel w bazie danych
function createTables($pdo) {
    $sql = [
        // Tabela użytkowników
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Tabela produktów (lokalna kopia)
        "CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2),
            status ENUM('publish', 'draft', 'trash', 'pending', 'private') DEFAULT 'draft',
            category_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_category (category_id)
        )",
        
        // Tabela kategorii
        "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            parent_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_parent (parent_id)
        )",
        
        // Tabela logów
        "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        )"
    ];
    
    foreach ($sql as $query) {
        $pdo->exec($query);
    }
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateData($_POST);
    
    if (empty($errors)) {
        // Test połączenia z bazą danych
        if (!testDatabaseConnection($_POST['db_host'], $_POST['db_name'], $_POST['db_user'], $_POST['db_pass'])) {
            $errors[] = 'Nie można połączyć się z bazą danych. Sprawdź dane.';
        }
    }
    
    if (empty($errors)) {
        try {
            // Połączenie z bazą danych
            $pdo = new PDO("mysql:host={$_POST['db_host']};dbname={$_POST['db_name']};charset=utf8", $_POST['db_user'], $_POST['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Tworzenie tabel
            createTables($pdo);
            
            // Dodanie administratora
            $hashedPassword = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$_POST['admin_email'], $hashedPassword]);
            
            // Tworzenie pliku konfiguracyjnego
            $configContent = "<?php\n";
            $configContent .= "// Konfiguracja bazy danych\n";
            $configContent .= "define('DB_HOST', '{$_POST['db_host']}');\n";
            $configContent .= "define('DB_NAME', '{$_POST['db_name']}');\n";
            $configContent .= "define('DB_USER', '{$_POST['db_user']}');\n";
            $configContent .= "define('DB_PASS', '{$_POST['db_pass']}');\n\n";
            $configContent .= "// Konfiguracja API Comperia\n";
            $configContent .= "define('COMPERIA_API_BASE_URL', 'https://www.autoplan24.pl/api/');\n";
            $configContent .= "define('COMPERIA_API_KEY', '{$_POST['api_key']}');\n\n";
            $configContent .= "// Ustawienia aplikacji\n";
            $configContent .= "define('SITE_NAME', 'Autoplan24 - Panel Administracyjny');\n";
            $configContent .= "define('SITE_URL', 'https://' . \$_SERVER['HTTP_HOST']);\n";
            $configContent .= "define('BASE_PATH', '/');\n";
            $configContent .= "define('APP_INSTALLED', true);\n";
            
            file_put_contents('includes/config.local.php', $configContent);
            
            // Przekierowanie do strony logowania
            header('Location: login.php?installed=1');
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Błąd podczas instalacji: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        showForm($errors);
    }
} else {
    showForm();
}
?>
