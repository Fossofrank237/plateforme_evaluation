<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST");

    require './header.php';
    require_once '../classes/Auth.php';
    require_once '../config/Database.php';

    use Classes\Auth;
    use Config\Database;



    $quiz_id = $_GET['id'] ?? null;
    if (!$quiz_id) {
        header('Location: index.php');
        exit();
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM quiz WHERE id = ? AND status = 'publié'");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        header('Location: index.php');
        exit();
    }

    $stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre ASC");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$_SESSION['user_id']) {
        header('Location: login.php');
    }
?>

    <div class="max-w-6xl mx-auto mt-10 px-6">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4 text-center text-gray-800">
                <?php echo htmlspecialchars($quiz['titre']); ?>
            </h1>
            <p class="text-lg text-gray-600 text-center mb-6">
                <?php echo htmlspecialchars($quiz['description']); ?>
            </p>

            <div id="quiz-container">
                <div id="question-container" class="space-y-6"></div>
                <div id="results" class="hidden text-center">
                    <h2 class="text-2xl font-bold mb-4 text-green-600">Résultats</h2>
                    <p class="text-lg font-semibold">
                        Score: <span id="score">0</span>/<span id="total-questions">0</span>
                    </p>
                    <button onclick="restartQuiz()" 
                            class="mt-6 bg-blue-500 text-white py-3 px-6 rounded-lg shadow-md hover:bg-blue-600">
                        Recommencer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const quizId = <?php echo $quiz_id; ?>;
        const questions = <?php echo json_encode($questions); ?>;
        let currentQuestionIndex = 0;
        let score = 0;

        function initQuiz() {
            displayQuestion();
        }

        function displayQuestion() {
            const questionContainer = document.getElementById('question-container');
            const resultsContainer = document.getElementById('results');

            if (currentQuestionIndex >= questions.length) {
                showResults();
                return;
            }

            const question = questions[currentQuestionIndex];
            let answers = [question.bonne_reponse, ...JSON.parse(question.mauvaises_reponses)];
            answers = shuffleArray(answers);

            questionContainer.innerHTML = `
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <p class="text-lg font-semibold text-gray-800 mb-4">
                        ${question.question_text}
                    </p>
                    ${question.image_url ? 
                        `<img src="${question.image_url}" class="mb-4 max-w-full h-auto rounded-lg">` 
                        : ''}
                    <div class="space-y-3">
                        ${answers.map(answer => `
                            <button onclick="checkAnswer('${answer}')"
                                    class="w-full text-left p-3 rounded-lg border hover:bg-blue-50">
                                ${answer}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        async function checkAnswer(selectedAnswer) {
            const question = questions[currentQuestionIndex];
            const isCorrect = selectedAnswer === question.bonne_reponse;

            if (isCorrect) {
                score++;
            }

            try {
                await fetch('api/submit_answer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        quiz_id: quizId,
                        question_id: question.id,
                        answer: selectedAnswer,
                        is_correct: isCorrect
                    })
                });
            } catch (error) {
                console.error('Erreur lors de l\'envoi de la réponse:', error);
            }

            currentQuestionIndex++;
            displayQuestion();
        }

        function showResults() {
            document.getElementById('question-container').classList.add('hidden');
            const resultsContainer = document.getElementById('results');
            resultsContainer.classList.remove('hidden');
            
            document.getElementById('score').textContent = score;
            document.getElementById('total-questions').textContent = questions.length;

            fetch('api/save_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    quiz_id: quizId,
                    score: score,
                    total_questions: questions.length
                })
            });
        }

        function restartQuiz() {
            currentQuestionIndex = 0;
            score = 0;
            document.getElementById('results').classList.add('hidden');
            document.getElementById('question-container').classList.remove('hidden');
            displayQuestion();
        }

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        // Initialize quiz when page loads
        document.addEventListener('DOMContentLoaded', initQuiz);
    </script>
</body>
</html>