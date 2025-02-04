<?php
namespace Classes;

class Quiz {
    private \PDO $pdo;
    
    // Constantes pour les statuts et difficultés
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_PUBLISHED = 'publié';
    public const STATUS_ARCHIVED = 'archivé';
    
    public const TEMP_LIMITE = 'not defined';

    public const DIFFICULTY_EASY = 'facile';
    public const DIFFICULTY_MEDIUM = 'moyen';
    public const DIFFICULTY_HARD = 'difficile';
    
    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crée un nouveau quiz
    */
    public function createQuiz(
        string $titre, 
        string $description, 
        int $creatorId, 
        string $difficulte = self::DIFFICULTY_MEDIUM,
        ? int $tempsLimite = self::TEMP_LIMITE,
        string $status = self::STATUS_DRAFT
    ): ?int {
        try {
            // var_dump($titre, $description, $creatorId, $difficulte, $tempsLimite, $status);
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz (titre, description, creator_id, difficulte, temps_limite, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $titre, 
                $description, 
                $creatorId,
                $difficulte,
                $tempsLimite,
                $status
            ]);
            // var_dump($stmt);
            
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création quiz: " . $e->getMessage());
            return null;
        }
    }

    public function updateQuiz(
        string $quizId,
        string $titre, 
        string $description, 
        int $creatorId, 
        string $difficulte = self::DIFFICULTY_MEDIUM,
        ?int $tempsLimite = null,
        string $status = self::STATUS_DRAFT
    ) {
        try {
            $stmt = $this->pdo->prepare("UPDATE quiz SET titre = ?, description = ?, creator_id = ?, status = ?, difficulte = ?, temps_limite= ? WHERE id = ?");
            return $stmt->execute([
                $titre,
                $description,
                $creatorId,
                $status,
                $difficulte,
                $tempsLimite,
                $quizId
            ]);

        } catch (\PDOException $e) {
            return false;
        }
    }

    function deleteQuiz( $id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM quiz WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    /**
     * Ajoute une question au quiz
    */
    public function addQuestion(
        int $quizId, 
        string $questionText, 
        string $bonneReponse, 
        array $mauvaisesReponses, 
        ?string $explication = null,
        ?string $imageUrl = null,
        int $points = 1,
        int $ordre = 0
    ): bool {
        try {
            // Récupère le dernier ordre si non spécifié
            if ($ordre === 0) {
                $stmt = $this->pdo->prepare("
                    SELECT COALESCE(MAX(ordre), 0) + 1 
                    FROM questions 
                    WHERE quiz_id = ?
                ");
                $stmt->execute([$quizId]);
                $ordre = (int)$stmt->fetchColumn();
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO questions (
                    quiz_id, question_text, bonne_reponse, mauvaises_reponses, 
                    explication, image_url, points, ordre
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $quizId,
                $questionText,
                $bonneReponse,
                json_encode($mauvaisesReponses),
                $explication,
                $imageUrl,
                $points,
                $ordre
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur ajout question: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Associe des catégories à un quiz
    */
    public function setCategories(int $quizId, array $categoryIds): bool {
        try {
            // Supprime les anciennes associations
            $stmt = $this->pdo->prepare("
                DELETE FROM quiz_categories 
                WHERE quiz_id = ?
            ");
            $stmt->execute([$quizId]);
            
            // Ajoute les nouvelles associations
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_categories (quiz_id, categorie_id) 
                VALUES (?, ?)
            ");
            
            foreach ($categoryIds as $categoryId) {
                $stmt->execute([$quizId, $categoryId]);
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erreur association catégories: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistre le résultat d'un quiz
    */
    public function saveResult(
        int $userId, 
        int $quizId, 
        int $score, 
        int $tempsPris, 
        array $reponses,
        $completionDate
    ): bool {
        try {
            $this->pdo->beginTransaction();
            
            // Enregistre le résultat global
            $stmt = $this->pdo->prepare("
                INSERT INTO resultats (user_id, quiz_id, score, temps_pris, date_completion) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $quizId, $score, $tempsPris, $completionDate]);
            $resultatId = (int)$this->pdo->lastInsertId();
            
            // Récupère les informations des questions pour vérifier les bonnes réponses
            $stmt = $this->pdo->prepare("
                SELECT id, bonne_reponse 
                FROM questions 
                WHERE quiz_id = ?
            ");
            $stmt->execute([$quizId]);
            $questions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Prépare la requête pour les réponses détaillées
            $stmt = $this->pdo->prepare("
                INSERT INTO reponses_details (
                    resultat_id, 
                    question_id, 
                    reponse_donnee, 
                    est_correcte
                ) VALUES (?, ?, ?, ?)
            ");
            
            // Pour chaque question, enregistre la réponse donnée
            foreach ($questions as $question) {
                $questionId = $question['id'];
                $reponseDonnee = $reponses[$questionId] ?? null;
                
                if ($reponseDonnee !== null) {
                    $estCorrecte = ($reponseDonnee === $question['bonne_reponse']);
                    $stmt->execute([
                        $resultatId,
                        $questionId,
                        $reponseDonnee,
                        $estCorrecte ? 1 : 0
                    ]);
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur enregistrement résultat: " . $e->getMessage());
            return false;
        }
    }

    public function getQuizInfo($quizId) {
        try {
            $query = "SELECT id, titre, description, creator_id, 
                             date_creation, status, difficulte, temps_limite 
                      FROM quiz 
                      WHERE id = :quiz_id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':quiz_id', $quizId, \PDO::PARAM_INT);
            $stmt->execute();
            
            $quizInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$quizInfo) {
                return false;
            }
            
            return $quizInfo;
            
        } catch (\PDOException $e) {
            // Log l'erreur pour l'administrateur
            error_log("Erreur lors de la récupération du quiz: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un quiz avec ses questions
    */
    public function getQuizWithQuestions(int $quizId): ?array {
        try {
            // Récupère les informations du quiz
            $stmt = $this->pdo->prepare("
                SELECT q.*, u.nom as creator_name 
                FROM quiz q
                JOIN users u ON q.creator_id = u.id
                WHERE q.id = ?
            ");
            $stmt->execute([$quizId]);
            $quiz = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$quiz) {
                return null;
            }
            
            // Récupère les questions
            $stmt = $this->pdo->prepare("
                SELECT * FROM questions 
                WHERE quiz_id = ? 
                ORDER BY ordre ASC
            ");
            $stmt->execute([$quizId]);
            $quiz['questions'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Récupère les catégories
            $stmt = $this->pdo->prepare("
                SELECT c.* 
                FROM categories c
                JOIN quiz_categories qc ON c.id = qc.categorie_id
                WHERE qc.quiz_id = ?
            ");
            $stmt->execute([$quizId]);
            $quiz['categories'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $quiz;
        } catch (\PDOException $e) {
            error_log("Erreur récupération quiz: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour le statut d'un quiz
    */
    public function updateStatus(int $quizId, string $status): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE quiz 
                SET status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $quizId]);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour statut: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtient les statistiques d'un quiz
    */
    public function getQuizStats(int $userId): ?array {
        try {
            // Première requête pour obtenir les statistiques par quiz
            $stmt = $this->pdo->prepare("
                SELECT 
                    q.id as quiz_id,
                    q.titre as quiz_title,
                    COUNT(r.id) as total_attempts,
                    AVG(r.score) as average_score,
                    AVG(r.temps_pris) as average_time,
                    MIN(r.score) as min_score,
                    MAX(r.score) as max_score,
                    MAX(r.date_completion) as last_attempt_date,
                    (
                        SELECT COUNT(*)
                        FROM questions
                        WHERE quiz_id = q.id
                    ) as total_questions,
                    (
                        SELECT r2.score
                        FROM resultats r2
                        WHERE r2.quiz_id = q.id 
                        AND r2.user_id = ?
                        ORDER BY r2.date_completion DESC
                        LIMIT 1
                    ) as latest_score
                FROM quiz q
                LEFT JOIN resultats r ON q.id = r.quiz_id AND r.user_id = ?
                GROUP BY q.id, q.titre
                ORDER BY last_attempt_date DESC
            ");
            
            $stmt->execute([$userId, $userId]);
            $quizStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Traitement des données pour un format plus propre
            foreach ($quizStats as &$stat) {
                $stat = array_map(function($value) {
                    return is_numeric($value) ? 
                        (strpos($value, '.') !== false ? round(floatval($value), 2) : intval($value)) 
                        : $value;
                }, $stat);
    
                // Ajout de statistiques calculées
                $stat['completion_percentage'] = $stat['total_questions'] > 0 
                    ? round(($stat['latest_score'] / $stat['total_questions']) * 100, 2)
                    : 0;
            }
    
            return $quizStats;
            
        } catch (\PDOException $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtient les détails des réponses pour un quiz spécifique
     */
    public function getQuizResponseDetails(int $userId, int $quizId): ?array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    rd.question_id,
                    q.question_text,
                    rd.reponse_donnee,
                    rd.est_correcte,
                    rd.temps_reponse,
                    r.date_completion
                FROM reponses_details rd
                JOIN resultats r ON rd.resultat_id = r.id
                JOIN questions q ON rd.question_id = q.id
                WHERE r.user_id = ? AND r.quiz_id = ?
                ORDER BY r.date_completion DESC
            ");
            
            $stmt->execute([$userId, $quizId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur récupération détails réponses: " . $e->getMessage());
            return null;
        }
    }

    public function getAllQuizzes() {
        $sql = "SELECT * FROM quiz";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les quiz filtrés selon différents critères
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $statusFilter Filtre par statut ('all' ou status spécifique)
     * @param string $difficultyFilter Filtre par difficulté ('all' ou difficulté spécifique)
     * @param string $search Terme de recherche
     * @param int $limit Nombre de résultats par page
     * @param int $offset Décalage pour la pagination
     * @return array Liste des quiz filtrés
    */
    public function getFilteredQuizzes(
        int $userId,
        string $statusFilter = 'all',
        string $difficultyFilter = 'all',
        string $search = '',
        int $limit = 10,
        int $offset = 0
    ): array {
        try {
            $params = [$userId];
            $conditions = ['q.creator_id = ?'];
            
            // Construction des conditions de filtrage
            if ($statusFilter !== 'all') {
                $conditions[] = 'q.status = ?';
                $params[] = $statusFilter;
            }
            
            if ($difficultyFilter !== 'all') {
                $conditions[] = 'q.difficulte = ?';
                $params[] = $difficultyFilter;
            }
            
            if (!empty($search)) {
                $conditions[] = '(q.titre LIKE ? OR q.description LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $sql = "
                SELECT 
                    q.*,
                    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as questions_count,
                    (SELECT COUNT(*) FROM resultats WHERE quiz_id = q.id) as attempts_count,
                    COALESCE(AVG(r.score), 0) as average_score
                FROM quiz q
                LEFT JOIN resultats r ON q.id = r.quiz_id
                WHERE {$whereClause}
                GROUP BY q.id
                ORDER BY q.date_creation DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur récupération quiz filtrés: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compte le nombre total de quiz selon les filtres
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $statusFilter Filtre par statut ('all' ou status spécifique)
     * @param string $difficultyFilter Filtre par difficulté ('all' ou difficulté spécifique)
     * @param string $search Terme de recherche
     * @return int Nombre total de quiz
     */
    public function countFilteredQuizzes(
        int $userId,
        string $statusFilter = 'all',
        string $difficultyFilter = 'all',
        string $search = ''
    ): int {
        try {
            $params = [$userId];
            $conditions = ['creator_id = ?'];
            
            // Construction des conditions de filtrage
            if ($statusFilter !== 'all') {
                $conditions[] = 'status = ?';
                $params[] = $statusFilter;
            }
            
            if ($difficultyFilter !== 'all') {
                $conditions[] = 'difficulte = ?';
                $params[] = $difficultyFilter;
            }
            
            if (!empty($search)) {
                $conditions[] = '(titre LIKE ? OR description LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $sql = "SELECT COUNT(*) FROM quiz WHERE {$whereClause}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn();
            
        } catch (\PDOException $e) {
            error_log("Erreur comptage quiz filtrés: " . $e->getMessage());
            return 0;
        }
    }
}