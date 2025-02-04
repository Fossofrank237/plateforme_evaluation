<?php

namespace Classes;

require __DIR__ . '/Session.php';
require __DIR__ . '/User.php';
// use Config\Database;
use Classes\Session;
use Classes\User;

class Auth {
    private Session $session;
    private User $user;
    private \PDO $pdo;
    private const REMEMBER_TOKEN_NAME = 'remember_token';

    public function __construct(\PDO $pdo, Session $session) {
        $this->pdo = $pdo;
        $this->session = $session;
        $this->user = new User($pdo);
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function register(string $nom, string $email, string $password, string $image, bool $remember = false): bool {
        // var_dump($nom, $email, $password, $image, $remember);
        $userId = $this->user->createUser($nom, $email, $password, $image);
        // var_dump($userId);
        if (!$userId) {
            return false;
        }

        return $this->loginById($userId, $remember);
    }

    /**
     * Authentifier un utilisateur
     */
    public function login(string $email, string $password, bool $remember = false): bool {
        $user = $this->user->findByCredentials($email, $password);
        if (!$user) {
            return false;
        }

        return $this->loginById((int)$user['id'], $remember);
    }

    /**
     * Connexion par ID utilisateur
     */
    private function loginById(int $userId, bool $remember = false): mixed {
        try {
            $sessionToken = $this->session->create($userId);
            
            if ($remember) {
                $this->createRememberToken($userId);
            }

            return $sessionToken;
        } catch (\Exception $e) {
            error_log("Erreur lors de la connexion : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Déconnexion de l'utilisateur courant
     */
    public function logout(): bool {
        // $token = $this->session->getCurrentUserToken();
        $userToken = $this->session->getCurrentUserToken();
        if ($userToken && isset($userToken['session_token'])) {
            $this->session->end($userToken['session_token']); // Passer le session_token
        }

        $this->deleteRememberToken();

        return true;

    }

    /**
     * Obtenir l'utilisateur actuellement connecté
     */
    public function currentUser(): ?array {
        $userData = $this->session->getCurrentUserToken();
        if ($userData) {
            return $userData;
        }

        // Vérifier le token de persistance
        $userId = $this->checkRememberToken();
        if ($userId) {
            return $this->loginById($userId) ? $this->session->getCurrentUserToken() : null;
        }

        return null;
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool {
        $user = $this->currentUser();
        return $user && $user['role'] === $role;
    }

    /**
     * Exiger un rôle spécifique
     */
    public function requireRole(string $role): void {
        if (!$this->hasRole($role)) {
            throw new \Exception("Permissions insuffisantes");
        }
    }

    /**
     * Créer un token de persistance (Remember Me)
     */
    private function createRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 jours

        $sql = "INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $token, $expiration]);

        setcookie(
            self::REMEMBER_TOKEN_NAME,
            $token,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Vérifier le token de persistance
     */
    private function checkRememberToken(): ?int {
        if (empty($_COOKIE[self::REMEMBER_TOKEN_NAME])) {
            return null;
        }

        try {
            $sql = "SELECT user_id FROM auth_tokens WHERE token = ? AND expires_at > NOW()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$_COOKIE[self::REMEMBER_TOKEN_NAME]]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            // var_dump($result);

            return $result ? (int)$result['user_id'] : null;
        } catch (\PDOException $e) {
            error_log("Erreur de vérification du token : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprimer le token de persistance
     */
    private function deleteRememberToken(): void {
        if (isset($_COOKIE[self::REMEMBER_TOKEN_NAME])) {
            $sql = "DELETE FROM auth_tokens WHERE token = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$_COOKIE[self::REMEMBER_TOKEN_NAME]]);

            setcookie(self::REMEMBER_TOKEN_NAME, '', time() - 3600, '/');
        }
    }
}


?>