<?php
// Pulls connection details from Railway Environment Variables
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '6543'; // Default to 6543 for Supabase Pooler
$dbname   = getenv('DB_NAME') ?: 'postgres';
$user     = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    // The 'pgsql:' prefix tells PHP to use the PostgreSQL driver
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Log the error internally and show a generic message to the user
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Please contact the administrator.");
}
?>
