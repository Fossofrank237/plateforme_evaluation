<?php
    // Inclusion des dépendances et configuration
    require_once '../config/Database.php';
    require_once '../classes/Quiz.php';
    require_once '../classes/Session.php';
    require_once '../classes/Auth.php';    

    use Config\Database;
    use Classes\Quiz;
    use Classes\Session;
    use Classes\Auth;

    // Initialisation des objets
    $pdo = Database::getInstance()->getConnection();
    $quiz = new Quiz($pdo);
    $session = new Session($pdo);
    $auth = new Auth($pdo, $session);

    // Démarrage de la session si nécessaire
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Validation et sécurisation de l'ID du quiz
    $quizId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$quizId) {
        exit('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur : Quiz non spécifié ou invalide.</div>');
    }

    // Récupération des informations du quiz
    $quizInfo = $quiz->getQuizInfo($quizId);
    if (!$quizInfo) {
        exit('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Quiz introuvable.</div>');
    }

    // Récupération des résultats de la session
    $sessionData = $_SESSION['quiz'][$quizId] ?? null;
    if (!$sessionData) {
        exit('<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">Aucun résultat trouvé pour ce quiz.</div>');
    }

    $responses = $sessionData['responses'] ?? [];
    $timeTaken = time() - $sessionData['started_at'];
    $completionDate = $sessionData['completed_at'] ?? time();
    $correctAnswers = 0;
    $questions = $quiz->getQuizWithQuestions($quizId)['questions'] ?? [];
    
    foreach ($responses as $questionId => $response) {
        if (isset($questions[$questionId]) && $questions[$questionId]['bonne_reponse'] === $response) {
            $correctAnswers++;
        }
    }

    $totalQuestions = count($questions);
    $score = $correctAnswers;

    // Sauvegarde des résultats dans la base de données
    $userId = $auth->currentUser()['user_id'] ?? null;
    var_dump($userId);
    if ($userId) {
        $quiz->saveResult($userId, $quizId, $score, $timeTaken, $responses, $completionDate);

        // Nettoyage de la session
        unset($_SESSION['quiz'][$quizId]);
    }

    // Calcul du pourcentage
    $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;

    // // Affichage des résultats
    // echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
    // echo '<h1>Résultats</h1>';
    // echo '<p>Score : ' . round($percentage, 2) . '%</p>';
    // echo '<p>Temps pris : ' . gmdate("H:i:s", $timeTaken) . '</p>';
    // echo '<p>Date de complétion : ' . date("Y-m-d H:i:s", $completionDate) . '</p>';
    // echo '</div>';
?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-4">Résultats du Quiz : <?= htmlspecialchars($quizInfo['titre']) ?></h1>

            <div class="mb-6">
                <p class="text-lg">Score : <span class="font-bold"><?= $score ?>/<?= $totalQuestions ?></span></p>
                <p class="text-lg">Pourcentage : <span class="font-bold"><?= round($percentage, 2) ?>%</span></p>
                <p class="text-lg">Temps pris : <span class="font-bold"><?php
                    echo'' . gmdate("H:i:s", $timeTaken) . '';
                ?></span></p>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2">Détails des questions :</h2>
                <ul class="list-disc pl-5">
                    <?php foreach ($questions as $questionId => $question): ?>
                        <li class="mb-4">
                            <p class="text-lg font-medium">Question : <?= htmlspecialchars($question['question_text']) ?></p>
                            <p class="text-sm <?php if (($responses[$questionId] ?? '') === $question['bonne_reponse']) echo 'text-green-600'; else echo 'text-red-600'; ?>">
                                Votre réponse : <?= htmlspecialchars($responses[$questionId] ?? 'Non répondu') ?>
                            </p>
                            <p class="text-sm text-gray-600">Bonne réponse : <?= htmlspecialchars($question['bonne_reponse']) ?></p>
                            <?php if (!empty($question['explication'])): ?>
                                <p class="text-sm text-gray-600">Explication : <?= htmlspecialchars($question['explication']) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="mt-6">
                <a href="?page=test" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Retour aux quiz</a>
            </div>
        </div>
    </div>
</body>
</html>
