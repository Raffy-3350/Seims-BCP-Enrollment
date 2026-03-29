<?php
// We use getenv() to pull these from your Railway Variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD');

try {
    // Supabase uses PostgreSQL, so we use the 'pgsql' driver
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // This will help you see the exact error in your Railway logs
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Please check the logs.");
}
?>
