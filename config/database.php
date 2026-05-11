<?php
function env($name, $default = null) {
    $value = getenv($name);
    return $value !== false ? $value : $default;
}

$databaseUrl = env('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? 'localhost';
    $dbname = ltrim($parts['path'] ?? '', '/');
    $username = $parts['user'] ?? '';
    $password = $parts['pass'] ?? '';
    $port = $parts['port'] ?? 3306;
} else {
    $host = env('DB_HOST', env('MYSQL_HOST', 'localhost'));
    $dbname = env('DB_NAME', env('MYSQL_DATABASE', 'gym_management'));
    $username = env('DB_USER', env('MYSQL_USER', 'root'));
    $password = env('DB_PASS', env('MYSQL_PASSWORD', ''));
    $port = env('DB_PORT', env('MYSQL_PORT', 3306));
}

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>