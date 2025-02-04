<?php
    // Inclusion des dépendances et configuration
    require_once '../config/Database.php';
    require_once '../classes/Quiz.php';
    require_once '../classes/Session.php';

    use Config\Database;
    use Classes\Quiz;
    use Classes\Session;

    // Initialisation des objets
    $pdo = Database::getInstance()->getConnection();
    $quiz = new Quiz($pdo);
    $session = new Session($pdo);

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

    // Récupération des questions du quiz
    $questions = $quiz->getQuizWithQuestions($quizId);
    $questionsList = $questions['questions'] ?? [];
    $totalQuestions = count($questionsList);
    if ($totalQuestions === 0) {
        exit('<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">Ce quiz ne contient aucune question pour le moment.</div>');
    }

    // Initialisation de la session pour le quiz
    if (!isset($_SESSION['quiz'][$quizId])) {
        $_SESSION['quiz'][$quizId] = [
            'started_at' => time(),
            'current_question' => 0,
            'responses' => [],
            'time_per_question' => [],
            'question_start_time' => time(),
            'shuffled_order' => array_keys($questionsList)
        ];
        shuffle($_SESSION['quiz'][$quizId]['shuffled_order']);
    }

    // Validation de l'existence de shuffled_order
    if (empty($_SESSION['quiz'][$quizId]['shuffled_order'])) {
        $_SESSION['quiz'][$quizId]['shuffled_order'] = array_keys($questionsList);
        shuffle($_SESSION['quiz'][$quizId]['shuffled_order']);
        if (empty($_SESSION['quiz'][$quizId]['shuffled_order'])) {
            exit('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur : L\\’ordre des questions n\\’a pas pu être défini.</div>');
        }
    }

    // Gestion de la progression dans le quiz
    $currentQuestionIndex = $_SESSION['quiz'][$quizId]['current_question'];
    if ($currentQuestionIndex >= $totalQuestions) {
        $currentQuestionIndex = $totalQuestions - 1;
        $_SESSION['quiz'][$quizId]['current_question'] = $currentQuestionIndex;
    }

    $shuffledOrder = $_SESSION['quiz'][$quizId]['shuffled_order'];
    $currentQuestionId = $shuffledOrder[$currentQuestionIndex];
    $currentQuestion = $questionsList[$currentQuestionId] ?? null;
    if (!$currentQuestion) {
        exit('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur : Question introuvable.</div>');
    }

    // Validation du temps limite
    $timeElapsed = time() - $_SESSION['quiz'][$quizId]['started_at'];
    $timeLimit = $quizInfo['temps_limite'] ?? 0;
    $timeLimitInSeconds = $timeLimit * 60;

    if ($timeLimitInSeconds && $timeElapsed > $timeLimitInSeconds) {
        $_SESSION['quiz'][$quizId]['timeout'] = true;
        echo '<script>window.location.href = "?page=quizResults&id=' . $quizId . '&timeout=true";</script>';
        exit;
    }

    // Gestion de la soumission des réponses
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
        $timeSpent = time() - $_SESSION['quiz'][$quizId]['question_start_time'];

        if ($response !== null) {
            $_SESSION['quiz'][$quizId]['responses'][$currentQuestionId] = $response;
            $_SESSION['quiz'][$quizId]['time_per_question'][$currentQuestionId] = $timeSpent;
        }

        if ($currentQuestionIndex + 1 < $totalQuestions) {
            $_SESSION['quiz'][$quizId]['current_question']++;
            $_SESSION['quiz'][$quizId]['question_start_time'] = time();
            echo '<script>window.location.href = "?page=startQuiz&id=' . $quizId . '";</script>';
            exit;
        } else {
            // Ajout de la date de complétion
            $_SESSION['quiz'][$quizId]['completed_at'] = time();
            echo '<script>window.location.href = "?page=quizResults&id=' . $quizId . '";</script>';
            exit;
        }        
    }

    $timeRemaining = $timeLimitInSeconds ? ($timeLimitInSeconds - $timeElapsed) : null;
?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-2">
                    <?= htmlspecialchars($quizInfo['titre']) ?>
                </h1>
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-600">
                        Question <?= $currentQuestionIndex + 1 ?> sur <?= $totalQuestions ?>
                    </span>
                    <?php if ($timeRemaining !== null): ?>
                        <span class="text-gray-600" id="timer" data-time="<?= $timeRemaining ?>">
                            Temps restant : <?= floor($timeRemaining / 60) ?>:<?= str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="h-2 bg-gray-200 rounded">
                    <div class="h-2 bg-blue-500 rounded" style="width: <?= ($currentQuestionIndex / $totalQuestions) * 100 ?>%"></div>
                </div>
            </div>

            <form method="POST" id="quizForm" class="space-y-6">
                <div class="bg-gray-50 rounded p-4 mb-4">
                    <p class="text-lg font-medium">
                        <?= htmlspecialchars($currentQuestion['question_text']) ?>
                    </p>
                </div>

                <div class="space-y-3">
                    <?php
                    $answers = json_decode($currentQuestion['mauvaises_reponses'], true);
                    $answers[] = $currentQuestion['bonne_reponse'];
                    shuffle($answers);

                    foreach ($answers as $answer): ?>
                        <label class="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="response" value="<?= htmlspecialchars($answer) ?>" required class="h-4 w-4 text-blue-600">
                            <span class="ml-3">
                                <?= htmlspecialchars($answer) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <?php if ($currentQuestionIndex > 0): ?>
                        <button type="button" onclick="history.back()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            &larr; Question précédente
                        </button>
                    <?php endif; ?>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <?= $currentQuestionIndex + 1 === $totalQuestions ? 'Terminer le quiz' : 'Question suivante &rarr;' ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($timeRemaining !== null): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let timeLeft = parseInt(document.getElementById('timer').dataset.time);
                const timerElement = document.getElementById('timer');

                const timer = setInterval(function () {
                    timeLeft--;
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        document.getElementById('quizForm').submit();
                    }

                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `Temps restant : ${minutes}:${seconds.toString().padStart(2, '0')}`;
                }, 1000);
            });
        </script>
        <?php endif; ?>
    </body>
</html>
