
<?php
    require './header.php';

    require '../config/Database.php';
    use Config\Database;
    // Check if the Database class exists
        // if (!class_exists(class: 'Database')) {
        //     die('Database class not found.');
        // }
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
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="./login.php" class="py-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">Connexion</a>
                            <a href="./register.php" class="py-2 px-4 bg-green-500 text-white rounded hover:bg-green-600">Inscription</a>
                        <?php else: ?>
                            <a href="user_dashboard.php" class="py-2 px-4 text-gray-500 hover:text-gray-600">Tableau de bord</a>
                            <a href="logout.php" class="py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600">Déconnexion</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-6xl mx-auto mt-10 px-4">
            <h1 class="text-3xl font-bold mb-8">Bienvenue sur notre plateforme de Quiz</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                    try {
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT * FROM quiz ORDER BY date_creation");
                        while ($quiz = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($quiz['titre']); ?></h2>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <a href="quiz.php?id=<?php echo $quiz['id']; ?>" 
                    class="inline-block bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                        Commencer le quiz
                    </a>
                </div>
                <?php endwhile; ?>
                <?php
                    } catch (Exception $e) {
                        echo "Erreur: " . $e->getMessage();
                    }
                ?>
            </div>
        </main>
    </body>
</html>
