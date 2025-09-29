
<?php

require __DIR__ . '/../vendor/autoload.php';

$config = new \Esia\Config([
    'clientId' => '230A03',
    'redirectUrl' => 'http://10.20.0.10:8000/response.php',
    'portalUrl' => 'https://esia-portal1.test.gosuslugi.ru/',
    'scope' => ['openid', 'fullname', 'id_doc'], // Добавлен openid
        'certPath' => __DIR__ . '/../resources/ekapusta.gost.test.cer',
        'privateKeyPath' => __DIR__ . '/../resources/ekapusta.gost.test.key',
    'token' => 'secret', // Добавлен секретный ключ
    
]);

$esia = new \Esia\OpenId($config);
$authUrl = $esia->buildUrl();
header('Location: '. $authUrl);
?>