<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/Database.php';
use Config\Database;

class QuizManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function insertData() {
        $conn = $this->db->getConnection();

        try {
            // Début de la transaction
            $conn->beginTransaction();

            // Insertion des utilisateurs
            $users = [
                ['Jean Dupont', 'jean.dupont@example.com', 'password123', 'admin'],
                ['Marie Curie', 'marie.curie@example.com', 'password456', 'user'],
                ['Albert Einstein', 'albert.einstein@example.com', 'password789', 'user']
            ];

            foreach ($users as $user) {
                $hashedPassword = password_hash($user[2], PASSWORD_BCRYPT);
                $stmt = $conn->prepare('INSERT INTO users (nom, email, mot_de_passe, role, image) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$user[0], $user[1], $hashedPassword, $user[3], '']);
            }

            // Insertion des catégories
            $categories = [
                ['Mathématiques', 'Quiz sur divers sujets mathématiques.'],
                ['Physique', 'Quiz couvrant les lois fondamentales de la physique.'],
                ['Histoire', 'Quiz sur les grands événements historiques.']
            ];

            foreach ($categories as $category) {
                $stmt = $conn->prepare('INSERT INTO categories (nom, description) VALUES (?, ?)');
                $stmt->execute([$category[0], $category[1]]);
            }

            // Insertion des quiz
            $quiz = [
                ['Quiz sur les nombres premiers', 'Testez vos connaissances sur les nombres premiers.', 1, 'publié', 'moyen', 30],
                ['Les lois de Newton', 'Un quiz sur les lois du mouvement de Newton.', 1, 'publié', 'difficile', 20],
                ['Révolutions historiques', 'Quiz sur les révolutions qui ont changé le monde.', 2, 'publié', 'facile', 40]
            ];

            foreach ($quiz as $q) {
                $stmt = $conn->prepare('INSERT INTO quiz (titre, description, creator_id, status, difficulte, temps_limite) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$q[0], $q[1], $q[2], $q[3], $q[4], $q[5]]);
            }

            // Liaison quiz-catégories
            $quizCategories = [
                [1, 1], // Quiz sur les nombres premiers lié à la catégorie Mathématiques
                [2, 2], // Les lois de Newton lié à la catégorie Physique
                [3, 3]  // Révolutions historiques lié à la catégorie Histoire
            ];

            foreach ($quizCategories as $qc) {
                $stmt = $conn->prepare('INSERT INTO quiz_categories (quiz_id, categorie_id) VALUES (?, ?)');
                $stmt->execute([$qc[0], $qc[1]]);
            }

            // Insertion des questions
            $questions = [
                [1, 'Quel est le plus petit nombre premier ?', '2', json_encode(['1', '0', '4']), 'Un nombre premier est divisible uniquement par 1 et lui-même.', 2, 1],
                [1, 'Le nombre 17 est-il un nombre premier ?', 'Oui', json_encode(['Non']), '17 est divisible uniquement par 1 et lui-même.', 1, 2],
                [2, 'Quelle est la première loi de Newton ?', 'Principe d’inertie', json_encode(['Principe d’action', 'Principe de conservation']), 'La première loi de Newton est également appelée principe d’inertie.', 3, 1],
                [3, 'En quelle année a eu lieu la Révolution française ?', '1789', json_encode(['1776', '1917', '1848']), 'La Révolution française a commencé en 1789.', 2, 1]
            ];

            foreach ($questions as $question) {
                $stmt = $conn->prepare('INSERT INTO questions (quiz_id, question_text, bonne_reponse, mauvaises_reponses, explication, points, ordre) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$question[0], $question[1], $question[2], $question[3], $question[4], $question[5], $question[6]]);
            }

            // Validation de la transaction
            $conn->commit();
            echo "Données insérées avec succès.";
        } catch (Exception $e) {
            // Annulation de la transaction en cas d'erreur
            $conn->rollBack();
            echo "Erreur lors de l'insertion des données : " . $e->getMessage();
        }
    }
}

$db = Database::getInstance(); // Instanciation de la classe Database
$quizManager = new QuizManager($db);
$quizManager->insertData();

?>
