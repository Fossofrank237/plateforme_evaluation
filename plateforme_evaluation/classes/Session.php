<?php
namespace classes;

use PDO;

class Session {
    private const SESSION_LIFETIME = 86400;
    private const COOKIE_NAME = 'session_token';
    
    private static array $cookieOptions = [
        'expires' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Creates a new session for a user
     * @param int $userId
     * @return string Session token
     */
    public function create(int $userId): string {
        $sessionToken = $this->generateToken();
        $expiration = $this->calculateExpiration();
        
        $this->deleteUserSessions($userId);
        $this->insertSession($userId, $sessionToken, $expiration);
        $this->setCookie($sessionToken);
        
        return $sessionToken;
    }
    
    /**
     * Validates and refreshes a session
     * @param string $sessionToken
     * @return array|false User data if valid, false otherwise
     */
    public function validate(string $sessionToken) {
        $sql = "SELECT s.*, u.* 
                FROM sessions s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.session_token = ? 
                AND s.expiration > NOW() 
                AND u.actif = 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$session) {
                return false;
            }
        } catch (\PDOException $e) {
            return false;
        }
        
        $this->extend($sessionToken);
        return $session;
    }
    
    /**
     * Gets current authenticated user
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUserToken(): ?array {
        $token = $this->getCookie();
        if (!$token) {
            return null;
        }
        
        $session = $this->validate($token);
        // var_dump("logged user", $session);
        return $session ?: null;
    }
    
    /**
     * Ends a specific session
     * @param string $sessionToken
     */
    public function end(string $sessionToken): void {
        $sql = "DELETE FROM sessions WHERE session_token = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionToken]);
        } catch (\PDOException $e) {
            // Handle the exception
            error_log(message: $e->getMessage());
        }
            
        $this->removeCookie();
    }
    
    /**
     * Cleans expired sessions
     */
    public function cleanExpired(): void {
        try {
            $sql = "DELETE FROM sessions WHERE expiration < NOW()";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            error_log("Failed to execute query: " . $e->getMessage());
        }
        
    }
    
    // Private helper methods
    
    private function generateToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    private function calculateExpiration(): string {
        return date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME);
    }
    
    private function extend(string $sessionToken): void {
        $expiration = $this->calculateExpiration();
        $sql = "UPDATE sessions SET expiration = ? WHERE session_token = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$expiration, $sessionToken]);
        } catch (\PDOException $e) {
            error_log("Failed to execute query: " . $e->getMessage());
        }
    }
    
    private function deleteUserSessions(int $userId): void {
        $sql = "DELETE FROM sessions WHERE user_id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Failed to execute query: " . $e->getMessage());
        }
    }
    
    private function insertSession(int $userId, string $sessionToken, string $expiration): void {
        $sql = "INSERT INTO sessions (user_id, session_token, expiration) VALUES (?, ?, ?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $sessionToken, $expiration]);
        } catch (\PDOException $e) {
            error_log("Failed to execute query: " . $e->getMessage());
        }
    }
    
    private function setCookie(string $value): void {
        setcookie(
            self::COOKIE_NAME,
            $value,
            time() + self::SESSION_LIFETIME,
            '/',
            '',
            true,
            true
        );
    }    
    
    private function getCookie(): ?string {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }
    
    private function removeCookie(): void {
        setcookie(
            self::COOKIE_NAME,
            '',
            time() - 3600,
            '/'
        );
    }
}

?>