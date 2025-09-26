<?php
// src/classes/SessionManager.php
namespace src\classes;
use PDO;
class SessionManager
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Сохраняет сессию с идентификатором для верификации
     */
    public function writeSessionWithVerification(string $sessionId, array $data, int $lifetime = 3600): array
    {
        $serializedData = serialize($data);
        $time = time();
        $verificationId = bin2hex(random_bytes(16)); // Уникальный идентификатор для верификации

        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (sess_id, sess_data, sess_time, sess_lifetime, verification_id) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            sess_data = VALUES(sess_data), 
            sess_time = VALUES(sess_time), 
            sess_lifetime = VALUES(sess_lifetime),
            verification_id = VALUES(verification_id)
        ");

        $stmt->execute([$sessionId, $serializedData, $time, $lifetime, $verificationId]);

        return [
            'session_id' => $sessionId,
            'verification_id' => $verificationId,
            'expires' => $time + $lifetime
        ];
    }

    /**
     * Проверяет соответствие session_id и verification_id
     */
    public function verifySession(string $sessionId, string $verificationId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT verification_id FROM sessions 
            WHERE sess_id = ? AND sess_time + sess_lifetime > ?
        ");

        $stmt->execute([$sessionId, time()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && hash_equals($result['verification_id'], $verificationId);
    }

    /**
     * Получает данные сессии после успешной верификации
     */
    public function getVerifiedSessionData(string $sessionId, string $verificationId): ?array
    {
        if (!$this->verifySession($sessionId, $verificationId)) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT sess_data FROM sessions WHERE sess_id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        return $session ? unserialize($session['sess_data']) : null;
    }


    public function readSession(string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT sess_data, sess_time, sess_lifetime FROM sessions WHERE sess_id = ?");
        $stmt->execute([$sessionId]);

        if ($session = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($session['sess_time'] + $session['sess_lifetime'] > time()) {
                return [
                    'data' => unserialize($session['sess_data']),
                    'time' => $session['sess_time'],
                    'lifetime' => $session['sess_lifetime']
                ];
            } else {
                $this->destroySession($sessionId);
            }
        }

        return null;
    }

    public function writeSession(string $sessionId, array $data, int $lifetime = 3600): bool
    {
        $serializedData = serialize($data);
        $time = time();

        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (sess_id, sess_data, sess_time, sess_lifetime) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            sess_data = VALUES(sess_data), 
            sess_time = VALUES(sess_time), 
            sess_lifetime = VALUES(sess_lifetime)
        ");

        return $stmt->execute([$sessionId, $serializedData, $time, $lifetime]);
    }

    public function destroySession(string $sessionId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE sess_id = ?");
        return $stmt->execute([$sessionId]);
    }

    public function gc(int $maxLifetime): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE sess_time + sess_lifetime < ?");
        return $stmt->execute([time()]);
    }
}
?>