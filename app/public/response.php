<?php

use Esia\Http\GuzzleHttpClient;
use GuzzleHttp\Client;

require __DIR__ . '/../vendor/autoload.php';

$correctTime = time() - 3600; 
$_SERVER['REQUEST_TIME'] = $correctTime;

print_r($_GET);

$config = new \Esia\Config([
    'clientId' => '230A03',
    'redirectUrl' => 'http://localhost:8000/response.php', 
    'portalUrl' => 'https://esia-portal1.test.gosuslugi.ru/',
    'scope' => ['openid', 'fullname', 'id_doc'], 
    'certPath' => __DIR__ . '/../resources/ekapusta.gost.test.cer',
    'privateKeyPath' => __DIR__ . '/../resources/ekapusta.gost.test.key',
    'token' => 'secret', 
]);

echo "Server time: " . date('Y-m-d H:i:s') . "<br>";
echo "Token expiry time: " . date('Y-m-d H:i:s', 1758096452) . "<br>";

$client = new GuzzleHttpClient(
    new Client([
        'verify' => false,
        'timeout' => 60,
        'connect_timeout' => 30,
    ])
);

$esia = new \Esia\OpenId($config, $client);

try {
    $token = $esia->getToken($_GET['code']);
    $personInfo = $esia->getPersonInfo();
    $documentInfo = $esia->getDocInfo();
  
    /*Array ( [code] => eyJ2ZXIiOjEsInR5cCI6IkpXVCIsIng1dCNTdDI1NiI6IjJENjE3RkU2OTI0OEI2Qzk0MDVDMjNCMTVBQTRGQzNEMkQ4N0QxQzFFRTlFODQzQzdFOUI1MUUxNjkxMjI3N0EiLCJzYnQiOiJhdXRob3JpemF0aW9uX2NvZGUiLCJhbGciOiJSUzI1NiJ9.eyJuYmYiOjE3NTgxNzY5MzYsInNjb3BlIjoiZnVsbG5hbWU_b2lkPTEwMDAzMjg2ODYgb3BlbmlkIGlkX2RvYz9vaWQ9MTAwMDMyODY4NiIsImF1dGhfdGltZSI6MTc1ODE3NjkzNiwiaXNzIjoiaHR0cDpcL1wvZXNpYS1wb3J0YWwxLnRlc3QuZ29zdXNsdWdpLnJ1XC8iLCJ1cm46ZXNpYTpzaWQiOiJmYTNlOTUyOC01MTVjLTdkZDQtYjVlMC1jYzUxNTIwMzU1MzkiLCJ1cm46ZXNpYTpjbGllbnQ6c3RhdGUiOiIyOTkwOTBhNy0xYzcxLTRiYWEtOGZkZC02MGQ2ZWFkYjlmODAiLCJhdXRoX210aGQiOiJQV0QiLCJ1cm46ZXNpYTpzYmoiOnsidXJuOmVzaWE6c2JqOmx2bCI6IlBSIiwidXJuOmVzaWE6c2JqOnR5cCI6IlAiLCJ1cm46ZXNpYTpzYmo6aXNfdHJ1Ijp0cnVlLCJ1cm46ZXNpYTpzYmo6b2lkIjoxMDAwMzI4Njg2LCJ1cm46ZXNpYTpzYmo6bmFtIjoiT0lELjEwMDAzMjg2ODYifSwiZXhwIjoxNzU4MTc3MTc2LCJwYXJhbXMiOnt9LCJpYXQiOjE3NTgxNzY5MzYsImNsaWVudF9pZCI6IjIzMEEwMyJ9.HkSODStuA-jqUYL1RYiIr3GUdqt4mCQGw_KPo9J2zwctEyeyHX9kg0---yKYQN5h6w9tKrB5L89H6v73JqXafLLVKkRws_pzdlUiUIY-nOK_fj-Qe1rDtMlnSZJ2dvdqRyEuauGmAj1WkmU_0wDVaUGHoI80yx1Oiuuqtt8ncn2UPqRo-40aaW6-tdQiIuCg_uQS7tzjJvY0tSR_B7EHBGH4cQAXIhTt5b3bgnN6JipGIb_5hFKktlNLS4rV0WIQs_EgVJLJXylfw-siOKeW44Pej4s-nOEuzIguuP_zEZHfsgRwMsBhrA3Z7enx8rbC15yi7sWLYyfPlk4qVLchNw [state] => 299090a7-1c71-4baa-8fdd-60d6eadb9f80 ) Server time: 2025-09-18 06:28:56
Token expiry time: 2025-09-17 08:07:32
Array
(
    [stateFacts] => Array
        (
            [0] => EntityRoot
        )

    [firstName] => ДМИТРИЙ
    [lastName] => ЗАСЯДЬКО
    [trusted] => 1
    [citizenship] => RUS
    [snils] => 000-000-600 31
    [inn] => 625717832522
    [updatedOn] => 1757949857
    [rfgUOperatorCheck] => 
    [status] => REGISTERED
    [verifying] => 
    [rIdDoc] => 26075
    [containsUpCfmCode] => 
    [kidAccCreatedByParent] => 
    [eTag] => 98BFEA9F9DC1F19D9EE01BE6B02ADEFE04790F5F
)
Array
(
    [0] => Array
        (
            [stateFacts] => Array
                (
                    [0] => EntityRoot
                )

            [id] => 26075
            [type] => RF_PASSPORT
            [vrfStu] => VERIFIED
            [series] => 0006
            [number] => 000192
            [issueDate] => 31.01.2001
            [issueId] => 006031
            [issuedBy] => УФМС031
            [eTag] => B20394612D0741A69F761E1AD240C15003D3065A
        )

)*/ 
       if ($personInfo && $documentInfo) {
    $data = [
        'p' => $personInfo,
        'd' => $documentInfo,
        
        't' => time()
    ];
    
    // Добавьте JSON_UNESCAPED_UNICODE
    $encoded = base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE));
    header('Location: http://localhost/vp.html?data=' . urlencode($encoded));
    exit();
}
        
            
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    
    // Для отладки подписи
    if (strpos($e->getMessage(), 'signature') !== false) {
        echo "Signature issue detected. Check keys and GOST support.<br>";
    }
}
?>