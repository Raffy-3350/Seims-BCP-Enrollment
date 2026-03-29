<?php
// Replace these with your actual Supabase Database credentials
define('DB_SERVER', 'aws-0-your-region.pooler.supabase.com'); // e.g., db.yourproject.supabase.co
define('DB_PORT', '6543'); // Use 6543 for IPv4 connection pooling, or 5432 for direct IPv6
define('DB_USERNAME', 'postgres.your_project_ref'); 
define('DB_PASSWORD', 'your_database_password');
define('DB_NAME', 'postgres'); // Default Supabase database name

try {
    // Set up the PostgreSQL Data Source Name (DSN)
    $dsn = "pgsql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    
    // Create a new PDO instance
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    
    // Set PDO to throw exceptions on errors (highly recommended)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("ERROR: Could not connect to Supabase. " . $e->getMessage());
}
?>
