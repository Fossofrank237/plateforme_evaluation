<?php
    session_start();

    require './header.php';
    require_once '../classes/Auth.php';
    require_once '../classes/Session.php';
    require '../config/Database.php';
    require_once "../classes/Quiz.php";
    require_once "../classes/QuizStats.php";

    use Classes\Auth;
    use Classes\Session;
    use Config\Database;
    use Classes\Quiz;
    use Classes\QuizStats;

    $pdo = Database::getInstance()->getConnection();
    $session = new Session($pdo);
    $auth = new Auth($pdo, $session);
    $quiz = new Quiz($pdo);
    $quizStats = new QuizStats($pdo);

    $user = $auth->currentUser();
    if (!$user["id"]) {
        header('Location: login.php');
        exit;
    }

    // Get current page from URL
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'stats';

    // Fonction pour charger le contenu en fonction de la page
    function loadContent($page, $user, $quiz, $quizStats) {
        switch($page) {
            case 'stats':
                return loadStatsContent($quizStats);
            case 'quizzes':
                return loadQuizzesContent($user, $quiz);
            case 'test':
                return loadTestsContent($quiz);
            case 'history':
                return loadHistoryContent($user, $quiz);
            case 'users':
                return loadUsersContent();
            case 'quiz-management':
                return loadQuizManagementContent($quiz);
            case 'qa':
                return loadQAContent();
            case 'startQuiz':
                return startQuiz();
            case 'quizResults':
                return quizResult();
            default:
                return loadStatsContent($quizStats);
        }
    }

    function loadStatsContent($quizStats) {
        // Récupération des statistiques globales
        $global_stats = $quizStats->getGlobalStats();
        $general_stats = $global_stats['general'] ?? [];
        $popular_quizzes = $global_stats['popular_quizzes'] ?? [];
        $difficulty_stats = $global_stats['difficulty_stats'] ?? [];
        $top_users = $global_stats['top_users'] ?? [];
    
        $total_users = $general_stats['total_users'] ?? 0;
        $total_attempts = $general_stats['total_attempts'] ?? 0;
        $global_average_score = $general_stats['global_average_score'] ?? 0;
        $global_average_time = $general_stats['global_average_time'] ?? 0;
    
        ob_start();
        ?>
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total des utilisateurs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Utilisateurs actifs</h3>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $total_users; ?></p>
            </div>
            <!-- Total des tentatives -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Tentatives totales</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $total_attempts; ?></p>
            </div>
            <!-- Score moyen global -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Score moyen global</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo round($global_average_score, 1); ?>%</p>
            </div>
            <!-- Temps moyen global -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Temps moyen par tentative</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo round($global_average_time / 60, 1); ?> min</p>
            </div>
        </section>
    
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Quiz les plus populaires</h2>
            <ul class="space-y-4">
                <?php foreach ($popular_quizzes as $quiz): ?>
                    <li class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
                        <span class="font-medium"><?php echo htmlspecialchars($quiz['titre']); ?></span>
                        <span class="text-gray-600">Tentatives: <?php echo $quiz['attempt_count']; ?></span>
                        <span class="text-indigo-600">Score moyen: <?php echo round($quiz['average_score'], 1); ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Statistiques par difficulté</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($difficulty_stats as $difficulty): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-2"><?php echo ucfirst($difficulty['difficulte']); ?></h3>
                        <p class="text-gray-600">Tentatives: <?php echo $difficulty['attempts']; ?></p>
                        <p class="text-gray-600">Score moyen: <?php echo round($difficulty['average_score'], 1); ?>%</p>
                        <p class="text-gray-600">Temps moyen: <?php echo round($difficulty['average_time'] / 60, 1); ?> min</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Utilisateurs les plus actifs</h2>
            <ul class="space-y-4">
                <?php foreach ($top_users as $user): ?>
                    <li class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
                        <span class="font-medium"><?php echo htmlspecialchars($user['nom']); ?></span>
                        <span class="text-gray-600">Quiz joués: <?php echo $user['quiz_count']; ?></span>
                        <span class="text-indigo-600">Score moyen: <?php echo round($user['average_score'], 1); ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
        return ob_get_clean();
    }    

    function loadQuizzesContent($user, $quiz) {
        // Filtrage et pagination des quiz
        $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $status_filter = $_GET['status'] ?? 'all';
        $difficulty_filter = $_GET['difficulty'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $created_quizzes = $quiz->getFilteredQuizzes(
            $user['id'],
            $status_filter,
            $difficulty_filter,
            $search,
            $limit,
            $offset
        );

        $total_quizzes = $quiz->countFilteredQuizzes(
            $user['id'],
            $status_filter,
            $difficulty_filter,
            $search
        );

        $total_pages = ceil($total_quizzes / $limit);

        ob_start();
        ?>
        <div class="mb-8">
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="page" value="quizzes">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    class="rounded-lg border-gray-300" placeholder="Rechercher un quiz...">
                <!-- Filtres status et difficulté -->
                <select name="status" class="rounded-lg border-gray-300">
                    <!-- Options de status -->
                    <option value="all">Tous les statuts</option>
                    <option value="<?php echo Quiz::STATUS_DRAFT; ?>" <?php echo $status_filter === Quiz::STATUS_DRAFT ? 'selected' : ''; ?>>
                        Brouillons
                    </option>
                    <option value="<?php echo Quiz::STATUS_PUBLISHED; ?>" <?php echo $status_filter === Quiz::STATUS_PUBLISHED ? 'selected' : ''; ?>>
                        Publiés
                    </option>
                    <option value="<?php echo Quiz::STATUS_ARCHIVED; ?>" <?php echo $status_filter === Quiz::STATUS_ARCHIVED ? 'selected' : ''; ?>>
                        Archivés
                    </option>
                </select>
                <select name="difficulty" class="rounded-lg border-gray-300">
                    <!-- Options de difficulté -->
                    <option value="all">Toutes les difficultés</option>
                    <option value="<?php echo Quiz::DIFFICULTY_EASY; ?>" <?php echo $difficulty_filter === Quiz::DIFFICULTY_EASY ? 'selected' : ''; ?>>
                        Facile
                    </option>
                    <option value="<?php echo Quiz::DIFFICULTY_MEDIUM; ?>" <?php echo $difficulty_filter === Quiz::DIFFICULTY_MEDIUM ? 'selected' : ''; ?>>
                        Moyen
                    </option>
                    <option value="<?php echo Quiz::DIFFICULTY_HARD; ?>" <?php echo $difficulty_filter === Quiz::DIFFICULTY_HARD ? 'selected' : ''; ?>>
                        Difficile
                    </option>
                </select>
                <button type="submit" class="bg-indigo-500 text-white rounded-lg py-2 px-4 hover:bg-indigo-600">
                    Filtrer
                </button>
            </form>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <?php foreach ($created_quizzes as $quiz_item): ?>
                <!-- Affichage des quiz -->
                <div class="bg-white rounded-lg shadow p-6">
                    <!-- Contenu du quiz -->
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($quiz_item['titre']); ?></h3>
                        <span class="px-2 py-1 text-sm rounded-full <?php 
                            echo $quiz_item['status'] === Quiz::STATUS_PUBLISHED 
                                ? 'bg-green-100 text-green-800'
                                : ($quiz_item['status'] === Quiz::STATUS_DRAFT 
                                    ? 'bg-yellow-100 text-yellow-800'
                                    : 'bg-gray-100 text-gray-800');
                        ?>">
                            <?php echo ucfirst($quiz_item['status']); ?>
                        </span>
                    </div>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($quiz_item['description']); ?></p>
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                        <span>Difficulté: <?php echo ucfirst($quiz_item['difficulte']); ?></span>
                        <span><?php echo $quiz_item['questions_count'] ?? 0; ?> questions</span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="edit_quiz.php?id=<?php echo $quiz_item['id']; ?>"
                        class="flex-1 bg-blue-500 text-white py-2 px-4 rounded text-center hover:bg-blue-600">
                            Modifier
                        </a>
                        <button onclick="showQuizStats(<?php echo $quiz_item['id']; ?>)"
                                class="flex-1 bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600">
                            Statistiques
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center space-x-2 mb-8">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&search=<?php echo urlencode($search); ?>"
                    class="px-4 py-2 rounded-lg <?php echo $page === $i ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php 
            endif;
            return ob_get_clean();
    }

    function loadTestsContent($quiz)
    {
        ob_start();
        $quizzes = $quiz->getAllQuizzes();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">
            <h2 class="text-xl font-bold text-black mb-3">Évaluations disponibles</h2>
            <?php if (!empty($quizzes)) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($quizzes as $quizItem) : ?>
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($quizItem['titre']); ?></h3>
                            <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($quizItem['description']); ?></p>
                            <div class="text-sm text-gray-500 mb-2">
                                <span>Difficulté : <?php echo ucfirst($quizItem['difficulte']); ?></span>
                            </div>
                            <div class="text-sm text-gray-500 mb-2">
                                <span>Durée : <?php echo $quizItem['temps_limite'] ? $quizItem['temps_limite'] . ' minutes' : 'Illimitée'; ?></span>
                            </div>
                            <div class="text-sm text-gray-500 mb-2">
                                <span>Créé le : <?php echo date('d/m/Y H:i', strtotime($quizItem['date_creation'])); ?></span>
                            </div>
                            <a href="?page=startQuiz&id=<?php echo $quizItem['id']; ?>" class="text-blue-500 hover:underline">
                                Commencer le test
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-white">Aucun quiz disponible pour le moment.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    function startQuiz() {
        ob_start();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">

        <!-- Contenu de la gestion des utilisateurs -->
            <?php
                require './pages/startQuiz.php';
            ?>
        </div>
        <?php
        ob_end_flush();
    }

    function quizResult() {
        ob_start();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">

        <!-- Contenu des résultats des quizzes -->
            <?php
                require './pages/quizResult.php';
            ?>
        </div>
        <?php
        ob_end_flush();
    }

    function loadHistoryContent($user, $quiz) {
        ob_start();
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Historique des activités</h2>
            <!-- Contenu de l'historique -->
        </div>
        <?php
        return ob_get_clean();
    }

    function loadUsersContent() {
        ob_start();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">

        <!-- Contenu de la gestion des utilisateurs -->
            <?php
                require './pages/userManagement.php';
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    function loadQuizManagementContent($quiz) {
        ob_start();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">
            <!-- Contenu de la gestion des quiz -->
            <?php
                require './pages/quizManagement.php';
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    function loadQAContent() {
        ob_start();
        ?>
        <div class="bg-slate-700 rounded-lg shadow p-3">
            <!-- Contenu Q&R -->
            <?php
                require './pages/qaManagement.php';
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

?>

        <div class="flex">
            <!-- Sidebar -->
            <?php 
                require_once './sidebar.php'; 
            ?>

            <main class="transition-all duration-300 ml-48 w-full p-2" id="mainContent">
                <!-- Zone de la photo de profil -->
                <div class="absolute top-4 right-4 ">
                    <img src="<?php echo $user['image'] ?>" alt="Photo de profil" class="w-12 h-12 rounded-full cursor-pointer" id="profilePicture">
                </div>

                <!-- Mini page de profil -->
                <div id="profileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
                    <div class="bg-white rounded-lg p-6 w-100">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold">Mon Profil</h2>
                            <button id="closeModal" class="text-gray-500 hover:text-gray-700">&times;</button>
                        </div>
                        <img src="<?php echo $user['image'] ?>" alt="Photo de profil" class="w-full h-32 object-cover rounded-lg mb-4">
                        <div class="mb-4">
                            <p><strong>Nom: </strong><?= $user['nom'] ?></p>
                            <p><strong>Email: </strong><?= $user['email'] ?></p>
                            <p><strong>Date d'inscription: </strong><?= $user['date_inscription'] ?></p>
                            <p><strong>Role: </strong><?= $user['role'] ?></p>
                        </div>
                        <div class="flex justify-between">
                            <button class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Voir le Profil</button>
                            <button class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600" onclick="logout()">Déconnexion</button>
                        </div>
                    </div>
                </div>

                <div class="mt-12">
                    <?php echo loadContent($current_page, $user, $quiz, $quizStats); ?>
                </div>
            </main>

            <!-- Modal pour les statistiques -->
            <div id="statsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                        <div class="p-6" id="statsContent">
                            <!-- Le contenu sera chargé dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
                // Afficher la mini page de profil
                document.getElementById('profilePicture').onclick = function() {
                    document.getElementById('profileModal').classList.remove('hidden');
                };

                // Fermer la mini page de profil
                document.getElementById('closeModal').onclick = function() {
                    document.getElementById('profileModal').classList.add('hidden');
                };

                // Fonction de déconnexion
                function logout() {
                    // Logique de déconnexion ici (par exemple, redirection vers une page de déconnexion)
                    window.location.href = './logout.php'; // Remplacez par votre page de déconnexion
                }
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');
                const toggleButton = document.getElementById('toggleSidebar');
                const sidebarTexts = document.querySelectorAll('.sidebar-text');
                const sidebarTitle = document.getElementById('sidebarTitle');
                let isCollapsed = false;

                // Validation des formulaires
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

                // Récupérer l'état du sidebar depuis le localStorage
                const savedState = localStorage.getItem('sidebarCollapsed');
                if (savedState === 'true') {
                    collapseSidebar();
                }

                toggleButton.addEventListener('click', function() {
                    if (isCollapsed) {
                        expandSidebar();
                    } else {
                        collapseSidebar();
                    }
                })

                // Modal des statistiques
                const modal = document.getElementById('statsModal');
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });

                function collapseSidebar() {
                    sidebar.style.width = '5rem';
                    mainContent.style.marginLeft = '5rem';
                    sidebarTexts.forEach(text => text.style.display = 'none');
                    sidebarTitle.style.display = 'none';
                    toggleButton.querySelector('svg').style.transform = 'rotate(180deg)';
                    isCollapsed = true;
                    localStorage.setItem('sidebarCollapsed', 'true');
                }

                function expandSidebar() {
                    sidebar.style.width = '12.7rem';
                    mainContent.style.marginLeft = '12.7rem';
                    sidebarTexts.forEach(text => text.style.display = 'block');
                    sidebarTitle.style.display = 'block';
                    toggleButton.querySelector('svg').style.transform = 'rotate(0deg)';
                    isCollapsed = false;
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            });

            function showQuizStats(quizId) {
                const modal = document.getElementById('statsModal');
                const content = document.getElementById('statsContent');
                
                // Afficher un loader
                content.innerHTML = '<div class="text-center"><div class="spinner"></div>Chargement...</div>';
                modal.classList.remove('hidden');
                
                // Charger les statistiques
                fetch(`quiz_stats_ajax.php?id=${quizId}`)
                    .then(response => response.json())
                    .then(data => {
                        content.innerHTML = `
                            <h2 class="text-2xl font-semibold mb-4">Statistiques du quiz</h2>
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <h3 class="font-semibold">Participants</h3>
                                    <p class="text-2xl">${data.total_attempts}</p>
                                </div>
                                <div>
                                    <h3 class="font-semibold">Score moyen</h3>
                                    <p class="text-2xl">${data.average_score}%</p>
                                </div>
                            </div>
                            <div class="mb-6">
                                <h3 class="font-semibold mb-2">Distribution des scores</h3>
                                <div class="bg-gray-200 rounded-full h-4">
                                    ${data.score_distribution}
                                </div>
                            </div>
                            <button onclick="document.getElementById('statsModal').classList.add('hidden')"
                                    class="w-full bg-gray-500 text-white py-2 rounded hover:bg-gray-600">
                                Fermer
                            </button>
                        `;
                    })
                    .catch(error => {
                        content.innerHTML = `
                            <div class="text-red-600 p-4">
                                <p class="font-semibold">Une erreur est survenue</p>
                                <p class="text-sm">Impossible de charger les statistiques. Veuillez réessayer plus tard.</p>
                                <button onclick="document.getElementById('statsModal').classList.add('hidden')"
                                        class="mt-4 w-full bg-gray-500 text-white py-2 rounded hover:bg-gray-600">
                                    Fermer
                                </button>
                            </div>
                        `;
                    });
            }

            // Gestionnaire de recherche dynamique
            let searchTimeout;
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const form = e.target.closest('form');
                        if (form) {
                            form.submit();
                        }
                    }, 500);
                });
            }

            // Animation du spinner de chargement
            const style = document.createElement('style');
            style.textContent = `
                .spinner {
                    width: 40px;
                    height: 40px;
                    margin: 20px auto;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3498db;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);

            // Fonction utilitaire pour formater les nombres
            function formatNumber(number) {
                return new Intl.NumberFormat().format(number);
            }

            // Gestionnaire d'exportation des statistiques
            function exportQuizStats(quizId) {
                const exportUrl = `export_stats.php?quiz_id=${quizId}`;
                window.location.href = exportUrl;
            }

            // Confirmation de suppression
            function confirmDelete(quizId) {
                if (confirm('Êtes-vous sûr de vouloir supprimer ce quiz ? Cette action est irréversible.')) {
                    window.location.href = `delete_quiz.php?id=${quizId}`;
                }
            }

            // Gestionnaire de changement de status
            function updateQuizStatus(quizId, newStatus) {
                fetch('update_quiz_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `quiz_id=${quizId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Une erreur est survenue lors de la mise à jour du status.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la mise à jour du status.');
                });
            }

            // Initialisation des tooltips
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                element.addEventListener('mouseenter', e => {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'absolute bg-gray-800 text-white text-sm rounded px-2 py-1 mt-1';
                    tooltip.textContent = e.target.dataset.tooltip;
                    e.target.appendChild(tooltip);
                });
                
                element.addEventListener('mouseleave', e => {
                    const tooltip = e.target.querySelector('.bg-gray-800');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            });
        </script>
    </body>
</html>

