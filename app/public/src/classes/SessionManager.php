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
        $this->createRateLimitTable();
    }   // Создаем таблицу при инициализации

     private function createRateLimitTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                action VARCHAR(50) NOT NULL,
                attempts INT DEFAULT 0,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                INDEX idx_ip_action (ip_address, action),
                INDEX idx_blocked (blocked_until)
            )
        ");
    }
    
    public function checkRateLimit(string $ip, string $action = 'session_verify', int $maxAttempts = 5, int $blockTime = 900): bool
    {
        // Очищаем старые записи
        $this->cleanupRateLimits();
        
        $stmt = $this->pdo->prepare("
            SELECT attempts, blocked_until 
            FROM rate_limits 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ip, $action]);
        $limit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Если заблокирован
        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            return false;
        }
        
        // Если разблокирован, но были попытки
        if ($limit) {
            // Сбрасываем если прошло больше часа с последней попытки
            if (time() - strtotime($limit['last_attempt']) > 3600) {
                $this->resetRateLimit($ip, $action);
                return true;
            }
            
            // Проверяем лимит
            if ($limit['attempts'] >= $maxAttempts) {
                $this->blockIP($ip, $action, $blockTime);
                return false;
            }
        }
        
        return true;
    }
    
    public function incrementRateLimit(string $ip, string $action = 'session_verify'): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO rate_limits (ip_address, action, attempts, last_attempt) 
            VALUES (?, ?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            attempts = attempts + 1, 
            last_attempt = NOW()
        ");
        $stmt->execute([$ip, $action]);
    }
    
    private function blockIP(string $ip, string $action, int $blockTime): void
    {
        $blockUntil = date('Y-m-d H:i:s', time() + $blockTime);
        $stmt = $this->pdo->prepare("
            UPDATE rate_limits 
            SET blocked_until = ? 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$blockUntil, $ip, $action]);
        
        error_log("IP blocked: $ip for action: $action until: $blockUntil");
    }
    
    public function resetRateLimit(string $ip, string $action): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE rate_limits 
            SET attempts = 0, blocked_until = NULL 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ip, $action]);
    }
    
    private function cleanupRateLimits(): void
    {
        $this->pdo->exec("
            DELETE FROM rate_limits 
            WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
    }
    
    public function getRateLimitInfo(string $ip, string $action = 'session_verify'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT attempts, last_attempt, blocked_until 
            FROM rate_limits 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$ip, $action]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
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