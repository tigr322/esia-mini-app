<?php

// test_cookies.php - запустите на обоих портах
$currentPort = $_SERVER['SERVER_PORT'];
$cookieName = 'test_cross_port';

// Проверяем, есть ли уже кука
if (isset($_COOKIE[$cookieName])) {
    echo "✓ Кука найдена: " . $_COOKIE[$cookieName] . "<br>";
    echo "Текущий порт: $currentPort<br>";
} else {
    // Устанавливаем куку
    $cookieValue = "session_" . uniqid() . "_port_$currentPort";
    setcookie($cookieName, $cookieValue, time() + 3600, '/', '.example.com');
    echo "✓ Кука установлена: $cookieValue<br>";
    echo "Порт: $currentPort<br>";
}

// Покажем все куки
echo "<h3>Все куки:</h3>";
foreach ($_COOKIE as $name => $value) {
    echo "$name: $value<br>";
}
