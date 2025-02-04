    <!-- Filtres et recherche -->
    <div class="mb-8 mt-12">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                class="rounded-lg border-gray-300" placeholder="Rechercher un quiz...">
            <select name="status" class="rounded-lg border-gray-300">
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

    <!-- Statistiques globales -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Quiz créés</h3>
            <p class="text-3xl font-bold text-indigo-600"><?php echo $total_quizzes; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Participants totaux</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $total_participants; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Temps moyen de complétion</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo round($average_completion_time / 60, 1); ?> min</p>
        </div>
        <?php if ($best_performing_quiz): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Meilleur quiz</h3>
            <p class="text-xl font-bold text-purple-600"><?php echo htmlspecialchars($best_performing_quiz['quiz_title']); ?></p>
            <p class="text-sm text-gray-600">Score moyen: <?php echo round($best_performing_quiz['average_score'], 1); ?>%</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- Liste des quiz -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php foreach ($created_quizzes as $quiz_item): ?>
            <div class="bg-white rounded-lg shadow p-6">
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
    <?php endif; ?>
