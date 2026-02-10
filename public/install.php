<?php
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    die("Installation already completed. Delete install.php for security.");
}

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '';
    $dbName = $_POST['dbname'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        if (isset($_POST['test_connection'])) {
            $message = "✅ Connection successful! Your database settings are correct.";
            $status = "success";
        } elseif (isset($_POST['install'])) {
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';

            if (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
                throw new Exception("Please fill in all Admin Account fields before installing.");
            }

            // 1. Create Database and Tables
            $sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                USE `$dbName`;

                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('user', 'admin') DEFAULT 'user',
                    is_deleted TINYINT(1) DEFAULT 0,
                    last_login DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    content TEXT NOT NULL,
                    category VARCHAR(50) DEFAULT 'public',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );";

            $pdo->exec($sql);

            // 2. Insert Admin Account
            $hashedAdminPass = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminUser, $adminEmail, $hashedAdminPass]);
            $adminId = $pdo->lastInsertId();

            // 3. Seed initial category posts
            $welcomeNotices = [
                ['public', 'Welcome to the Public notice board! Anyone can view this.'],
                ['private', 'Welcome to the Private members-only board. Secure info goes here.'],
                ['other', 'This section is for Other miscellaneous information and building updates.']
            ];
            $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content, category) VALUES (?, ?, ?)");
            foreach ($welcomeNotices as $notice) {
                $postStmt->execute([$adminId, $notice[1], $notice[0]]);
            }

            // 4. Create .env file
            $envContent = "DB_HOST=\"$host\"\nDB_PORT=\"$port\"\nDB_NAME=\"$dbName\"\nDB_USER=\"$user\"\nMARIADB_PASS=\"$pass\"";
            file_put_contents($envPath, $envContent);

            $message = "✅ Installation successful! You can now <a href='index.php?page=login'>Login</a>.";
            $status = "success";
        }
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Installer | Hello Neighbor</title>
    <link rel="stylesheet" href="../config/styles.css">
</head>

<body>
    <div class="container">
        <h2>System Installation</h2>

        <?php if ($message): ?>
            <div style="padding:15px; margin-bottom:20px; border-radius:5px; background:<?php echo $status === 'success' ? '#d4edda' : '#f8d7da'; ?>; color:<?php echo $status === 'success' ? '#155724' : '#721c24'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <legend><strong>1. Database Settings</strong></legend>
                <label>Host</label><br>
                <input type="text" name="host" value="<?php echo htmlspecialchars($_POST['host'] ?? 'localhost'); ?>" required><br>
                <label>Port</label><br>
                <input type="text" name="port" value="<?php echo htmlspecialchars($_POST['port'] ?? '3307'); ?>" required><br>
                <label>DB Name</label><br>
                <input type="text" name="dbname" value="<?php echo htmlspecialchars($_POST['dbname'] ?? 'hello_neighbor'); ?>" required><br>
                <label>User</label><br>
                <input type="text" name="user" value="<?php echo htmlspecialchars($_POST['user'] ?? 'root'); ?>" required><br>
                <label>Password</label><br>
                <input type="password" name="pass" value="<?php echo htmlspecialchars($_POST['pass'] ?? ''); ?>"><br>

                <button type="submit" name="test_connection" style="background: #6c757d; margin-top: 10px;">Test Connection</button>
            </fieldset>

            <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <legend><strong>2. Initial Admin Account</strong></legend>
                <label>Admin Username</label><br>
                <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'Filip'); ?>"><br>
                <label>Admin Email</label><br>
                <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'filip@filip-peev.com'); ?>"><br>
                <label>Admin Password</label><br>
                <input type="password" name="admin_pass">
            </fieldset>

            <button type="submit" name="install" style="width:100%; margin-top:20px; font-weight: bold;">Run Installation</button>
        </form>

    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; color: #888; font-size: 0.85rem;">
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        <p>&copy; <?php echo date('Y'); ?> Hello Neighbor - <em>Unofficial Learning Web App</em></p>
        <a href="mailto:filip@filip-peev.com" style="color: #007bff; text-decoration: none; font-weight: bold;">Feedback</a>
    </footer>

</body>

</html>