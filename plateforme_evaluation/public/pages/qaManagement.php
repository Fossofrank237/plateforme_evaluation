<?php
// require_once '../header.php';
require_once '../Classes/Quiz.php';
require_once '../config/Database.php';
require_once '../includes/functions.php';

use Config\Database;
use Classes\Quiz;

$pdo = Database::getInstance()->getConnection();
$quizManager = new Quiz($pdo);

// Initialize response array for AJAX feedback
$response = ['success' => false, 'message' => '', 'data' => null];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add':
                validateQuestionData($_POST);
                $mauvaisesReponses = array_filter(explode('|', $_POST['mauvaises_reponses']));
                
                $success = $quizManager->addQuestion(
                    intval($_POST['quiz_id']),
                    trim($_POST['question_text']),
                    trim($_POST['bonne_reponse']),
                    $mauvaisesReponses,
                    trim($_POST['explication']),
                    trim($_POST['image_url']),
                    intval($_POST['points']),
                    intval($_POST['ordre'])
                );
                
                $response['success'] = $success;
                $response['message'] = $success ? 'Question added successfully' : 'Failed to add question';
                break;
                
            case 'edit':
                validateQuestionData($_POST);
                $stmt = $pdo->prepare("UPDATE questions SET 
                    question_text = ?, 
                    bonne_reponse = ?, 
                    mauvaises_reponses = ?, 
                    explication = ?, 
                    image_url = ?, 
                    points = ?, 
                    ordre = ? 
                    WHERE id = ?");
                    
                $success = $stmt->execute([
                    trim($_POST['question_text']),
                    trim($_POST['bonne_reponse']),
                    json_encode(array_filter(explode('|', $_POST['mauvaises_reponses']))),
                    trim($_POST['explication']),
                    trim($_POST['image_url']),
                    intval($_POST['points']),
                    intval($_POST['ordre']),
                    intval($_POST['id'])
                ]);
                
                $response['success'] = $success;
                $response['message'] = $success ? 'Question updated successfully' : 'Failed to update question';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                $success = $stmt->execute([intval($_POST['id'])]);
                
                $response['success'] = $success;
                $response['message'] = $success ? 'Question deleted successfully' : 'Failed to delete question';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Get available quizzes for dropdown
$quizzes = $quizManager->getAllQuizzes();

// Get questions with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT q.*, qu.titre as quiz_titre 
    FROM questions q 
    JOIN quiz qu ON q.quiz_id = qu.id 
    ORDER BY q.ordre ASC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$questions = $stmt->fetchAll();

// Get total questions for pagination
$totalQuestions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalPages = ceil($totalQuestions / $perPage);

function validateQuestionData($data) {
    if (empty($data['question_text'])) {
        throw new Exception('Question text is required');
    }
    if (empty($data['bonne_reponse'])) {
        throw new Exception('Correct answer is required');
    }
    if (empty($data['mauvaises_reponses'])) {
        throw new Exception('Wrong answers are required');
    }
    if (!is_numeric($data['points']) || $data['points'] < 1) {
        throw new Exception('Points must be a positive number');
    }
    if (!is_numeric($data['ordre']) || $data['ordre'] < 0) {
        throw new Exception('Order must be a non-negative number');
    }
}
?>


        <div class="container mx-auto p-2">
            <h1 class="text-3xl font-bold mb-8 text-gray-800">Gestion Questions/Réponses</h1>

            <!-- Question Form -->
            <form id="questionForm" class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-semibold mb-4">Add New Question</h2>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="ajax" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Quiz</label>
                        <select name="quiz_id" class="w-full border-gray-300 rounded-md" required>
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?= htmlspecialchars($quiz['id']) ?>">
                                    <?= htmlspecialchars($quiz['titre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Points</label>
                        <input type="number" name="points" class="w-full border-gray-300 rounded-md" value="1" min="1" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Question Text</label>
                    <textarea name="question_text" class="w-full border-gray-300 rounded-md" rows="3" required></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Correct Answer</label>
                    <input type="text" name="bonne_reponse" class="w-full border-gray-300 rounded-md" required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Wrong Answers (separate with |)</label>
                    <input type="text" name="mauvaises_reponses" class="w-full border-gray-300 rounded-md" 
                        placeholder="Wrong answer 1|Wrong answer 2|Wrong answer 3" required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Explanation (optional)</label>
                    <textarea name="explication" class="w-full border-gray-300 rounded-md" rows="2"></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Image URL (optional)</label>
                    <input type="url" name="image_url" class="w-full border-gray-300 rounded-md">
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Order</label>
                    <input type="number" name="ordre" class="w-full border-gray-300 rounded-md" value="0" min="0" required>
                </div>

                <button type="submit" class="mt-6 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Add Question
                </button>
            </form>

            <!-- Questions Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Quiz</th>
                            <th class="px-4 py-2 text-left">Question</th>
                            <th class="px-4 py-2 text-left">Correct Answer</th>
                            <th class="px-4 py-2 text-left">Wrong Answers</th>
                            <th class="px-4 py-2 text-center">Points</th>
                            <th class="px-4 py-2 text-center">Order</th>
                            <th class="px-4 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2"><?= htmlspecialchars($question['quiz_titre']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($question['question_text']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($question['bonne_reponse']) ?></td>
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars(implode(', ', json_decode($question['mauvaises_reponses'], true))) ?>
                                </td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($question['points']) ?></td>
                                <td class="px-4 py-2 text-center"><?= htmlspecialchars($question['ordre']) ?></td>
                                <td class="px-4 py-2 text-center">
                                    <button onclick="editQuestion(<?= htmlspecialchars(json_encode($question)) ?>)" 
                                            class="bg-yellow-500 text-white px-2 py-1 rounded-md hover:bg-yellow-600">
                                        Edit
                                    </button>
                                    <button onclick="deleteQuestion(<?= $question['id'] ?>)" 
                                            class="bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-600 ml-2">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <div class="flex space-x-2">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>" 
                            class="px-4 py-2 rounded-md <?= $page === $i ? 'bg-blue-500 text-white' : 'bg-white' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
            // Form submission handler
            document.getElementById('questionForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(this);
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 1500
                        }).then(() => window.location.reload());
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                }
            });

            // Edit question
            function editQuestion(question) {
                const form = document.getElementById('questionForm');
                form.querySelector('[name="action"]').value = 'edit';
                form.querySelector('[name="quiz_id"]').value = question.quiz_id;
                form.querySelector('[name="question_text"]').value = question.question_text;
                form.querySelector('[name="bonne_reponse"]').value = question.bonne_reponse;
                form.querySelector('[name="mauvaises_reponses"]').value = 
                    JSON.parse(question.mauvaises_reponses).join('|');
                form.querySelector('[name="explication"]').value = question.explication || '';
                form.querySelector('[name="image_url"]').value = question.image_url || '';
                form.querySelector('[name="points"]').value = question.points;
                form.querySelector('[name="ordre"]').value = question.ordre;
                
                // Add hidden input for question ID
                let idInput = form.querySelector('[name="id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    form.appendChild(idInput);
                }
                idInput.value = question.id;
                
                // Update submit button text
                form.querySelector('button[type="submit"]').textContent = 'Update Question';
                
                // Scroll to form
                form.scrollIntoView({ behavior: 'smooth' });
            }

            // Delete question
            async function deleteQuestion(id) {
                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                });
                
                if (result.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('ajax', '1');
                        formData.append('id', id);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: result.message,
                                timer: 1500
                            });
                            window.location.reload();
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message
                        });
                    }
                }
            }

            // Reset form function
            function resetForm() {
                const form = document.getElementById('questionForm');
                form.reset();
                form.querySelector('[name="action"]').value = 'add';
                form.querySelector('button[type="submit"]').textContent = 'Add Question';
                
                // Remove question ID if it exists
                const idInput = form.querySelector('[name="id"]');
                if (idInput) {
                    idInput.remove();
                }
            }

            // Add reset button event listener
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('questionForm');
                const resetButton = document.createElement('button');
                resetButton.type = 'button';
                resetButton.className = 'mt-6 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 ml-2';
                resetButton.textContent = 'Reset Form';
                resetButton.onclick = resetForm;
                
                form.querySelector('button[type="submit"]').after(resetButton);
            });

            // Form validation enhancement
            function validateForm(form) {
                const mauvaisesReponses = form.querySelector('[name="mauvaises_reponses"]').value.split('|')
                    .filter(answer => answer.trim() !== '');
                
                if (mauvaisesReponses.length < 1) {
                    throw new Error('At least one wrong answer is required');
                }
                
                const bonneReponse = form.querySelector('[name="bonne_reponse"]').value.trim();
                if (mauvaisesReponses.includes(bonneReponse)) {
                    throw new Error('Correct answer cannot be in the wrong answers list');
                }
            }

            // Update form submit handler to include validation
            document.getElementById('questionForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    validateForm(this);
                    const formData = new FormData(this);
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 1500
                        });
                        window.location.reload();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                }
            });
        </script>
    </body>
</html>