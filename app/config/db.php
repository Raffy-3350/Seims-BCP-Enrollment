<?php
// Get these from Railway Variables (see step below)
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // For compatibility with your existing code, we can call it $mysqli 
    // though it is now a PDO object
    $mysqli = $pdo; 
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
