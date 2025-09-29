<?php
// test_compare.php
use src\classes\Database;
use src\classes\SessionManager;
require_once __DIR__ . '/src/classes/Database.php';

echo "PHP Version: " . PHP_VERSION . "\n\n";
$sessionManager = new SessionManager();

// Проверка расширений
echo "=== Extensions ===\n";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli'];
foreach ($required_extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✓ LOADED" : "✗ MISSING") . "\n";
}

if (extension_loaded('pdo')) {
    echo "PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
}

echo "\n=== Test 1: Direct PDO connection ===\n";
try {
    $pdo_direct = new PDO('mysql:host=10.20.0.10;dbname=cms', 'root', '!QAZxdr5');
    $pdo_direct->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Direct connection: SUCCESS\n";

    // Проверим версию MySQL
    $version = $pdo_direct->query('SELECT VERSION() as version')->fetchColumn();
    echo "MySQL version: " . $version . "\n";
} catch (Exception $e) {
    echo "✗ Direct connection FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== Test 2: Through database class ===\n";
try {
    $db = Database::getInstance();
    $pdo_class = $db->getConnection();
    echo "✓ Class connection: SUCCESS\n";
    echo "=== Data from sessions table ===\n";
    $stmt = $pdo_class->query("SELECT * FROM sessions WHERE sess_id =  '1h4ar35dcgo6b20phd9c5e01vu'");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($sessions) > 0) {
        foreach ($sessions as $session) {
            echo "Session ID: " . $session['sess_id'] . "\n";
            echo "Data: " . $session['sess_data'] . "\n";
            echo "Time: " . date('Y-m-d H:i:s', $session['sess_time']) . "\n";
            echo "Lifetime: " . $session['sess_lifetime'] . " seconds\n";
            echo "---\n";
        }
        echo "Total sessions: " . count($sessions) . "\n";
    } else {
        echo "Table 'sessions' is empty\n";
    }
    // Проверим соединение через класс
    $version = $pdo_class->query('SELECT VERSION() as version')->fetchColumn();
    echo "MySQL version via class: " . $version . "\n";
} catch (Exception $e) {
    echo "✗ Class connection FAILED: " . $e->getMessage() . "\n";
}
?>