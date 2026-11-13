<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');
echo "PHP version: " . phpversion() . "\n";

function loadEnv($file) {
    $out = array();
    if (!is_file($file)) { echo "NO .env at $file\n"; return $out; }
    foreach (file($file) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $k = trim($parts[0]); $v = trim($parts[1]);
        $out[$k] = $v;
        $_ENV[$k] = $v;
    }
    return $out;
}
$env = loadEnv(__DIR__ . '/../.env');

$h = isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost';
$p = isset($env['DB_PORT']) ? $env['DB_PORT'] : '3306';
$db = isset($env['DB_NAME']) ? $env['DB_NAME'] : '';
$u = isset($env['DB_USER']) ? $env['DB_USER'] : '';
$pw = isset($env['DB_PASS']) ? (string)$env['DB_PASS'] : '';

echo "DB_HOST=$h DB_PORT=$p DB_NAME=$db DB_USER=$u DB_PASS=" . ($pw === '' ? '(empty)' : '(set, len ' . strlen($pw) . ')') . "\n";

try {
    $pdo = new PDO(
        "mysql:host=$h;port=$p;dbname=$db;charset=utf8mb4",
        $u, $pw,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    echo "DB CONNECT: OK\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "TABLES (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    $need = array('users','videos','comments','channels','categories','tags','playlists','subscriptions','ai_cache','ai_requests','settings','watch_history','reports');
    $missing = array_diff($need, $tables);
    if ($missing) {
        echo "MISSING TABLES: " . implode(', ', $missing) . "  -> import DATABASE.sql\n";
    } else {
        echo "ALL REQUIRED TABLES PRESENT\n";
    }
} catch (\Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
echo "ENV APP_DEBUG=" . (isset($env['APP_DEBUG']) ? $env['APP_DEBUG'] : 'unset') . "\n";
echo "done\n";
