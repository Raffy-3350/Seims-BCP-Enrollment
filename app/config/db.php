<?php
// Fetches the variables you just added to Railway
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    // Supabase requires pgsql and sslmode=require
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // This will help you debug if the connection still fails
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Check your Railway logs.");
}
?>
