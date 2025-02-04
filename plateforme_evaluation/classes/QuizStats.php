<?php
namespace Classes;

class QuizStats {
    private \PDO $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtient les statistiques personnelles d'un utilisateur
     */
    public function getUserPersonalStats(int $userId): array {
        try {
            $query = "
                SELECT 
                    COUNT(DISTINCT quiz_id) AS total_quizzes_taken,
                    AVG(score) AS average_score,
                    MAX(score) AS highest_score,
                    MIN(score) AS lowest_score,
                    AVG(temps_pris) AS average_time,
                    COUNT(*) AS total_attempts,
                    COUNT(DISTINCT DATE(date_completion)) AS active_days
                FROM resultats
                WHERE user_id = :user_id
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Récupère les derniers quiz complétés
            $recentQuery = "
                SELECT 
                    r.quiz_id,
                    q.titre,
                    r.score,
                    r.temps_pris,
                    r.date_completion
                FROM resultats r
                JOIN quiz q ON r.quiz_id = q.id
                WHERE r.user_id = :user_id
                ORDER BY r.date_completion DESC
                LIMIT 5
            ";

            $stmt = $this->pdo->prepare($recentQuery);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $stats['recent_quizzes'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $stats ?: [];
        } catch (\PDOException $e) {
            error_log("Erreur récupération statistiques utilisateur: " . $e->getMessage());
            throw new \RuntimeException("Impossible de récupérer les statistiques personnelles.");
        }
    }

    /**
     * Obtient les statistiques détaillées d'un quiz spécifique pour un utilisateur
     */
    public function getUserQuizDetailedStats(int $userId, int $quizId): array {
        try {
            $query = "
                SELECT 
                    r.score,
                    r.temps_pris,
                    r.date_completion,
                    q.titre AS quiz_title,
                    q.difficulte,
                    COUNT(rd.id) AS total_questions,
                    SUM(rd.est_correcte) AS correct_answers,
                    AVG(rd.temps_reponse) AS avg_answer_time
                FROM resultats r
                JOIN quiz q ON r.quiz_id = q.id
                LEFT JOIN reponses_details rd ON r.id = rd.resultat_id
                WHERE r.user_id = :user_id AND r.quiz_id = :quiz_id
                GROUP BY r.id
                ORDER BY r.date_completion DESC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindParam(':quiz_id', $quizId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Erreur récupération statistiques détaillées: " . $e->getMessage());
            throw new \RuntimeException("Impossible de récupérer les statistiques détaillées.");
        }
    }

    /**
     * Obtient les statistiques globales (admin uniquement)
     */
    public function getGlobalStats(): array {
        try {
            $stats = [];

            // Statistiques générales
            $query = "
                SELECT 
                    COUNT(DISTINCT user_id) AS total_users,
                    COUNT(DISTINCT quiz_id) AS total_quizzes_taken,
                    COUNT(*) AS total_attempts,
                    AVG(score) AS global_average_score,
                    AVG(temps_pris) AS global_average_time
                FROM resultats
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['general'] = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Quiz les plus populaires
            $query = "
                SELECT 
                    q.id,
                    q.titre,
                    COUNT(*) AS attempt_count,
                    AVG(r.score) AS average_score
                FROM resultats r
                JOIN quiz q ON r.quiz_id = q.id
                GROUP BY q.id, q.titre
                ORDER BY attempt_count DESC
                LIMIT 10
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['popular_quizzes'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Statistiques par difficulté
            $query = "
                SELECT 
                    q.difficulte,
                    COUNT(*) AS attempts,
                    AVG(r.score) AS average_score,
                    AVG(r.temps_pris) AS average_time
                FROM resultats r
                JOIN quiz q ON r.quiz_id = q.id
                GROUP BY q.difficulte
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['difficulty_stats'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Utilisateurs les plus actifs
            $query = "
                SELECT 
                    u.id,
                    u.nom,
                    COUNT(*) AS quiz_count,
                    AVG(r.score) AS average_score
                FROM resultats r
                JOIN users u ON r.user_id = u.id
                GROUP BY u.id, u.nom
                ORDER BY quiz_count DESC
                LIMIT 10
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['top_users'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return $stats;
        } catch (\PDOException $e) {
            error_log("Erreur récupération statistiques globales: " . $e->getMessage());
            throw new \RuntimeException("Impossible de récupérer les statistiques globales.");
        }
    }

    /**
     * Obtient les statistiques détaillées d'un quiz spécifique (admin)
     */
    public function getQuizAdminStats(int $quizId): array {
        try {
            $stats = [];

            // Statistiques générales du quiz
            $query = "
                SELECT 
                    COUNT(*) AS total_attempts,
                    AVG(score) AS average_score,
                    MIN(score) AS min_score,
                    MAX(score) AS max_score,
                    AVG(temps_pris) AS average_time,
                    COUNT(DISTINCT user_id) AS unique_users
                FROM resultats
                WHERE quiz_id = :quiz_id
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':quiz_id', $quizId, \PDO::PARAM_INT);
            $stmt->execute();
            $stats['general'] = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Statistiques par question
            $query = "
                SELECT 
                    q.id AS question_id,
                    q.question_text,
                    COUNT(*) AS total_attempts,
                    SUM(rd.est_correcte) AS correct_answers,
                    AVG(rd.temps_reponse) AS average_time
                FROM questions q
                LEFT JOIN reponses_details rd ON q.id = rd.question_id
                WHERE q.quiz_id = :quiz_id
                GROUP BY q.id, q.question_text
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':quiz_id', $quizId, \PDO::PARAM_INT);
            $stmt->execute();
            $stats['questions'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return $stats;
        } catch (\PDOException $e) {
            error_log("Erreur récupération statistiques admin quiz: " . $e->getMessage());
            throw new \RuntimeException("Impossible de récupérer les statistiques du quiz.");
        }
    }
}
