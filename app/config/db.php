<?php
// These pull from your Railway "Variables" tab
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    // This 'pgsql' prefix matches the 'ext-pdo_pgsql' driver we just added
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // If it fails again, this will tell us WHY (e.g., wrong password)
    die("Connection Failed: " . $e->getMessage());
}
?>
