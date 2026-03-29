$host     = getenv('DB_HOST');     // e.g., aws-0-us-east-1.pooler.supabase.com
$port     = getenv('DB_PORT');     // 5432 or 6543
$db_name  = getenv('DB_NAME');     // usually 'postgres'
$user     = getenv('DB_USER');     // usually 'postgres.your-project-id'
$password = getenv('DB_PASSWORD'); // Your DATABASE password (not login password)

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";
    $mysqli = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // This will help you see the REAL error in your browser temporarily
    die("Connection Failed: " . $e->getMessage()); 
}
