<?php

namespace Classes;

class User
{
    private const TABLE_NAME = 'users';
    private const COL_ID = 'id';
    private const COL_NAME = 'nom';
    private const COL_EMAIL = 'email';
    private const COL_PASSWORD = 'mot_de_passe';
    private const COL_IMAGE = 'image';
    private const COL_ROLE = 'role';
    private const COL_ACTIVE = 'actif';

    private const ALLOWED_FIELDS = [
        self::COL_NAME,
        self::COL_EMAIL,
        self::COL_IMAGE,
        self::COL_ROLE,
        self::COL_ACTIVE
    ];

    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new user
     * @throws \InvalidArgumentException if email is invalid
     * @return int|null User ID if successful, null if failed
     */
    public function createUser(string $nom, string $email, string $password, string $image, string $role = 'user'): ?int
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if ($this->findByEmail($email)) {
            return null; // Email already exists
        }

        // var_dump($nom, $email, $password, $image, $role);
        try {
            $sql = "INSERT INTO " . self::TABLE_NAME . " (
                    " . self::COL_NAME . ", 
                    " . self::COL_EMAIL . ", 
                    " . self::COL_PASSWORD . ", 
                    " . self::COL_ROLE . ", 
                    " . self::COL_IMAGE . ",
                    " . self::COL_ACTIVE . "
                ) VALUES (?, ?, ?, ?, ?, 1)";
                
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                trim($nom),
                strtolower(trim($email)),
                password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]),
                $role,
                $image
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            $this->logError('User creation error', $e);
            return null;
        }
    }

    /**
     * Find user by email and password
     * @return array|null User data if found and password matches, null otherwise
     */
    public function findByCredentials(string $email, string $password): ?array
    {
        try {
            $user = $this->findByEmail($email);
            
            if ($user && password_verify($password, $user[self::COL_PASSWORD])) {
                // Check if password needs rehash
                if (password_needs_rehash($user[self::COL_PASSWORD], PASSWORD_DEFAULT, ['cost' => 12])) {
                    $this->updateUser($user[self::COL_ID], ['password' => $password]);
                }
                unset($user[self::COL_PASSWORD]); // Don't return password hash
                return $user;
            }
            return null;
        } catch (\PDOException $e) {
            $this->logError('Login error', $e);
            return null;
        }
    }

    /**
     * Get all active users
     * @return array List of users
     */
    public function getAllUsers(): array
    {
        try {
            $sql = "SELECT " . self::COL_ID . ", " . self::COL_NAME . ", " . self::COL_EMAIL . ", " . self::COL_ROLE . ", " .self::COL_ACTIVE .
                " FROM " . self::TABLE_NAME . " WHERE " . self::COL_ACTIVE . " = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logError('Fetch all users error', $e);
            return [];
        }
    }

    /**
     * Update user data
     * @param int $id User ID
     * @param array $data Associative array of fields to update
     * @throws \InvalidArgumentException if email is invalid
     * @return bool True if successful, false otherwise
     */
    public function updateUser(int $id, array $data): bool
    {
        try {
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email format');
            }

            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                if ($key === 'password' && $value) {
                    $fields[] = self::COL_PASSWORD . " = ?";
                    $values[] = password_hash($value, PASSWORD_DEFAULT, ['cost' => 12]);
                } elseif (in_array($key, self::ALLOWED_FIELDS, true)) {
                    $fields[] = "$key = ?";
                    $values[] = $key === 'email' ? strtolower(trim($value)) : trim($value);
                }
            }

            if (empty($fields)) {
                return false;
            }

            $values[] = $id;
            $sql = "UPDATE " . self::TABLE_NAME . " 
                   SET " . implode(", ", $fields) . " 
                   WHERE " . self::COL_ID . " = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            $this->logError('User update error', $e);
            return false;
        }
    }

    /**
     * Soft delete a user by setting active status to 0
     */
    public function deleteUser(int $id): bool
    {
        try {
            $sql = "UPDATE " . self::TABLE_NAME . " 
                   SET " . self::COL_ACTIVE . " = 0 
                   WHERE " . self::COL_ID . " = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logError('User delete error', $e);
            return false;
        }
    }

    // Helper methods remain the same but with improved return type declarations
    public function findById(int $id): ?array 
    {
        return $this->findByField(self::COL_ID, $id);
    }

    public function findByEmail(string $email): ?array 
    {
        return $this->findByField(self::COL_EMAIL, strtolower(trim($email)));
    }

    public function findByUsername(string $username): ?array 
    {
        return $this->findByField(self::COL_NAME, trim($username));
    }

    /**
     * Generic method to find a user by a field value
     */
    private function findByField(string $field, mixed $value): ?array
    {
        try {
            $sql = "SELECT * FROM " . self::TABLE_NAME . " 
                   WHERE $field = ? AND " . self::COL_ACTIVE . " = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$value]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logError("User fetch error for field $field", $e);
            return null;
        }
    }

    /**
     * Validates user input data and populates the global $errors array
     * 
     * @param array $data The POST data to validate
     * @return void
    */
    function validateUserData(array $data): void {
        global $errors;
        
        // Validate name
        if (empty($data['nom'])) {
            $errors[] = 'Le nom est requis';
        } elseif (strlen($data['nom']) < 2 || strlen($data['nom']) > 50) {
            $errors[] = 'Le nom doit contenir entre 2 et 50 caractères';
        } elseif (!preg_match('/^[A-Za-z0-9\s-]+$/', $data['nom'])) {
            $errors[] = 'Le nom ne peut contenir que des lettres, des chiffres, des espaces et des tirets';
        }
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = 'L\'email est requis';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'email invalide';
        } elseif (strlen($data['email']) > 255) {
            $errors[] = 'L\'email ne peut pas dépasser 255 caractères';
        } else {
            // Check if email already exists (only for new users)
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            if ($stmt->fetch() && (!isset($data['id']) || $data['id'] !== $stmt->fetch()['id'])) {
                $errors[] = 'Cet email est déjà utilisé';
            }
        }
        
        // Validate password (only for new users or password changes)
        if (!isset($data['id']) || !empty($data['mot_de_passe'])) {
            if (empty($data['mot_de_passe'])) {
                $errors[] = 'Le mot de passe est requis';
            } elseif (strlen($data['mot_de_passe']) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
            } elseif (!preg_match('/[A-Z]/', $data['mot_de_passe'])) {
                $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
            } elseif (!preg_match('/[a-z]/', $data['mot_de_passe'])) {
                $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
            } elseif (!preg_match('/[0-9]/', $data['mot_de_passe'])) {
                $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
            }
        }
        
        // Validate role
        $allowedRoles = ['user', 'admin'];
        if (empty($data['role'])) {
            $errors[] = 'Le rôle est requis';
        } elseif (!in_array($data['role'], $allowedRoles)) {
            $errors[] = 'Rôle invalide';
        }
        
        // Validate status (for existing users)
        if (isset($data['actif']) && !in_array($data['actif'], ['0', '1'], true)) {
            $errors[] = 'Statut invalide';
        }
        
        // Additional security checks
        if (isset($data['id'])) {
            if (!is_numeric($data['id']) || $data['id'] < 1) {
                $errors[] = 'ID utilisateur invalide';
            }
            
            // Prevent self-deactivation for admins
            if (isset($_SESSION['user_id']) && 
                (int)$_SESSION['user_id'] === (int)$data['id'] && 
                isset($data['actif']) && 
                $data['actif'] === '0') {
                $errors[] = 'Vous ne pouvez pas désactiver votre propre compte';
            }
            
            // Prevent self-role-change for admins
            if (isset($_SESSION['user_id']) && 
                (int)$_SESSION['user_id'] === (int)$data['id'] && 
                isset($data['role']) && 
                $data['role'] !== 'admin') {
                $errors[] = 'Vous ne pouvez pas modifier votre propre rôle d\'administrateur';
            }
        }
        
        // // Trim whitespace from string inputs
        // foreach (['nom', 'email'] as $field) {
        //     if (isset($data[$field])) {
        //         $data[$field] = trim($data[$field]);
        //     }
        // }
    }

    /**
     * Log database errors
     */
    private function logError(string $message, \PDOException $exception): void
    {
        error_log(sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $message,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }
}