<?php
// Load Composer's autoloader (PHPMailer + Postgres Driver)
require_once __DIR__ . '/../../vendor/autoload.php';

$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '6543';
$dbname   = getenv('DB_NAME') ?: 'postgres';
$user     = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Check Railway logs.");
}
?>
