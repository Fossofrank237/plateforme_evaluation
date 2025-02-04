<?php
    session_start(); // Start the session

    require './header.php';
    require_once '../classes/Auth.php';
    require_once '../config/Database.php';

    use Classes\Auth;
    use Config\Database;
    $auth = new Auth();
    
    $db = Database::getInstance()->getConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM quiz WHERE creator_id = ?");
    $stmt->execute([$user_id]);
    $created_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT q.titre, r.score, r.date_completion, 
            (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions
        FROM resultats r
        JOIN quiz q ON r.quiz_id = q.id
        WHERE r.user_id = ?
        ORDER BY r.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $quiz_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$_SESSION['user_id']) {
       header('Location: login.php');
    }
?>

        <nav class="bg-white shadow-lg">
            <div class="max-w-6xl mx-auto px-4">
                <div class="flex justify-between">
                    <div class="flex space-x-7">
                        <a href="index.php" class="flex items-center py-4">
                            <span class="font-semibold text-gray-500 text-lg">QuizSystem</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <?php if ($auth->isAdmin()): ?>
                            <a href="admin.php" class="py-2 px-4 bg-purple-500 text-white rounded hover:bg-purple-600">
                                Administration
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600">
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-6xl mx-auto mt-10 px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Mon Profil</h2>
                    <div class="space-y-2">
                        <p>
                            <span class="font-medium">Nom:</span> 
                            <?php echo htmlspecialchars($user['nom']); ?>
                        </p>
                        <p>
                            <span class="font-medium">Email:</span> 
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <p>
                            <span class="font-medium">Membre depuis:</span> 
                            <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                        </p>
                    </div>
                    <a href="edit_profile.php" class="mt-4 inline-block text-blue-500 hover:text-blue-600">
                        Modifier mon profil
                    </a>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Créer un Quiz</h2>
                    <form action="create_quiz.php" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-700">Titre du quiz</label>
                            <input type="text" name="titre" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        </div>
                        <div>
                            <label class="block text-gray-700">Description</label>
                            <textarea name="description" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            </textarea>
                        </div>
                        <button type="submit" class="w-full bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                            Créer le quiz
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Mes Statistiques</h2>
                    <div class="space-y-4">
                        <div class="border-b pb-2">
                            <span class="font-medium">Quiz créés:</span>
                            <span class="float-right"><?php echo count($created_quizzes); ?></span>
                        </div>
                        <div class="border-b pb-2">
                            <span class="font-medium">Quiz complétés:</span>
                            <span class="float-right"><?php echo count($quiz_results); ?></span>
                        </div>
                        <?php if (count($quiz_results) > 0): ?>
                            <div class="border-b pb-2">
                                <span class="font-medium">Score moyen:</span>
                                <span class="float-right">
                                    <?php
                                    $total_score = 0;
                                    foreach ($quiz_results as $result) {
                                        $total_score += ($result['score'] / $result['total_questions']) * 100;
                                    }
                                    echo number_format($total_score / count($quiz_results), 1) . '%';
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-2xl font-semibold mb-4">Mes Quiz</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($created_quizzes as $quiz): ?>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-2">
                                <?php echo htmlspecialchars($quiz['titre']); ?>
                            </h3>
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($quiz['description']); ?>
                            </p>
                            <div class="flex space-x-2">
                                <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" 
                                class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                                    Modifier
                                </a>
                                <a href="quiz_stats.php?id=<?php echo $quiz['id']; ?>" 
                                class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">
                                    Statistiques
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-2xl font-semibold mb-4">Historique des Quiz</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quiz
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Score
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($quiz_results as $result): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <?php echo htmlspecialchars($result['titre']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $percentage = ($result['score'] / $result['total_questions']) * 100;
                                        echo number_format($percentage, 1) . '%';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo date('d/m/Y H:i', strtotime($result['date_completion'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="review_quiz.php?result_id=<?php echo $result['id']; ?>" 
                                        class="text-blue-500 hover:text-blue-600">
                                            Revoir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <script>
        // Script pour la gestion des notifications et des interactions dynamiques
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des formulaires avec validation côté client
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.classList.add('border-red-500');
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Veuillez remplir tous les champs requis.');
                    }
                });
            });
        });
        </script>
    </body>
</html>