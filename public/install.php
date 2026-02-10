<?php
// public/install.php
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    die("Installation already completed. Delete install.php for security.");
}

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database credentials
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '';
    $dbName = $_POST['dbname'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    try {
        // Attempt server connection
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        if (isset($_POST['test_connection'])) {
            $message = "✅ Connection successful! Your database settings are correct.";
            $status = "success";
        } elseif (isset($_POST['install'])) {
            // Admin validation happens ONLY when Install is clicked
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';

            if (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
                throw new Exception("Please fill in all Admin Account fields before installing.");
            }

            // 1. Create Database and Table
            $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                    USE `$dbName`;
                    CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(100) NOT NULL UNIQUE,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password_hash VARCHAR(255) NOT NULL,
                        role ENUM('user', 'admin', 'moderator') DEFAULT 'user', 
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        last_login TIMESTAMP NULL DEFAULT NULL,
                        is_deleted TINYINT(1) DEFAULT 0
                    ) ENGINE=InnoDB;";
            $pdo->exec($sql);

            // 2. Create the Admin User
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
            $adminSql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')";
            $stmt = $pdo->prepare($adminSql);
            $stmt->execute([$adminUser, $adminEmail, $hashedPass]);

            // 3. Write .env
            $envContent = "DB_HOST=\"$host\"\nDB_PORT=\"$port\"\nDB_NAME=\"$dbName\"\nDB_USER=\"$user\"\nMARIADB_PASS=\"$pass\"";
            file_put_contents($envPath, $envContent);

            $message = "✅ Setup complete! Admin '$adminUser' created. <a href='index.php'>Go to Home</a>.";
            $status = "success";
        }
    } catch (Exception $e) {
        $message = "❌ " . $e->getMessage();
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Hello Neighbor Installer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../config/styles.css">
</head>

<body>
    <div class="container" style="max-width: 500px; margin-top: 30px;">
        <h2>App Setup</h2>

        <?php if ($message): ?>
            <div style="padding: 15px; margin-bottom: 20px; border-radius: 4px; background: <?php echo $status === 'success' ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $status === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <legend><strong>1. Database Credentials</strong></legend>
                <label>Host</label><br>
                <input type="text" name="host" value="<?php echo htmlspecialchars($_POST['host'] ?? '127.0.0.1'); ?>" required><br>
                <label>Port</label><br>
                <input type="text" name="port" value="<?php echo htmlspecialchars($_POST['port'] ?? '3307'); ?>" required><br>
                <label>Database Name</label><br>
                <input type="text" name="dbname" value="<?php echo htmlspecialchars($_POST['dbname'] ?? 'hello_neighbor'); ?>" required><br>
                <label>User</label><br>
                <input type="text" name="user" value="<?php echo htmlspecialchars($_POST['user'] ?? 'root'); ?>" required><br>
                <label>Password</label><br>
                <input type="password" name="pass" value="<?php echo htmlspecialchars($_POST['pass'] ?? ''); ?>"><br>

                <button type="submit" name="test_connection" style="background: #6c757d; width: 50%; margin-top: 10px;">Test Connection</button>
            </fieldset>

            <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <legend><strong>2. Initial Admin Account</strong></legend>
                <p style="font-size: 0.8rem; color: #666;">You can leave these empty while testing the connection.</p>
                <label>Admin Username</label><br>
                <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'Filip'); ?>"><br>
                <label>Admin Email</label><br>
                <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'Filip@Filip-Peev.com'); ?>"><br>
                <label>Admin Password</label><br>
                <input type="password" name="admin_pass">
            </fieldset>

            <button type="submit" name="install" style="width: 50%; font-weight:bold">Install & Finalize</button>
        </form>
    </div>
</body>

</html>