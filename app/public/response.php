<?php
use Esia\Config;
use Esia\Http\GuzzleHttpClient;
use Esia\OpenId;
use GuzzleHttp\Client;
use src\classes\SessionManager;

require_once __DIR__ . '/src/classes/Database.php';
require_once __DIR__ . '/src/classes/SessionManager.php';
require __DIR__ . '/../vendor/autoload.php';

$correctTime = time() - 3600;
$_SERVER['REQUEST_TIME'] = $correctTime;
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$config = new Config([
    'clientId' => '230A03',
    'redirectUrl' => 'http://localhost:8000/response.php',
    'portalUrl' => 'https://esia-portal1.test.gosuslugi.ru/',
    'scope' => ['openid', 'fullname', 'id_doc'],
    'certPath' => __DIR__ . '/../resources/ekapusta.gost.test.cer',
    'privateKeyPath' => __DIR__ . '/../resources/ekapusta.gost.test.key',
    'token' => 'secret',
]);

function setVerificationCookie(string $verificationId, int $lifetime = 8100): void
{
    setcookie(
        'session_verify',
        $verificationId,
        [
            'expires' => time() + $lifetime,
            'path' => '/',
            'domain' => '.localhost',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

$client = new GuzzleHttpClient(
    new Client([
        'verify' => false,
        'timeout' => 60,
        'connect_timeout' => 30,
    ])
);

$esia = new OpenId($config, $client);

try {
    $sessionManager = new SessionManager();
    
    if (!$sessionManager->checkRateLimit($clientIP, 'esia_auth', 3, 1800)) {
        header('HTTP/1.1 429 Too Many Requests');
        echo "Слишком много попыток аутентификации. Попробуйте позже.";
        exit;
    }

    // Пытаемся получить токен (это может занять время)
    $token = $esia->getToken($_GET['code']);
    
    $sessionManager->resetRateLimit($clientIP, 'esia_auth');

    $personInfo = $esia->getPersonInfo();
    $documentInfo = $esia->getDocInfo();

    if ($personInfo && $documentInfo) {
        $data = [
            'p' => $personInfo,
            'd' => $documentInfo,
            't' => time()
        ];

        $sessionId = bin2hex(random_bytes(64)); // 64 символа достаточно

        // Сохраняем данные с верификацией
        $sessionInfo = $sessionManager->writeSessionWithVerification($sessionId, $data, 8100);

        // Устанавливаем куку
        setVerificationCookie($sessionInfo['verification_id']);

        // Редирект
        header('Location: http://localhost/vp.html?sid=' . urlencode($sessionId));
        exit();
    }

} catch (Exception $e) {
    // ❌ ОШИБКА аутентификации - увеличиваем счетчик
    if (isset($sessionManager)) {
        $sessionManager->incrementRateLimit($clientIP, 'esia_auth');
    }

    // Логируем ошибку, но не показываем детали пользователю
    error_log("ESIA Auth Error [IP: $clientIP]: " . $e->getMessage());
    
    // Общее сообщение об ошибке
    echo "Произошла ошибка при авторизации. Попробуйте еще раз.";
}
?>