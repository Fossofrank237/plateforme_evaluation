<?php
// require_once '../header.php';
require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../classes/Quiz.php';
require_once '../classes/User.php';
require_once '../classes/Session.php';
// require_once '../fonction/Fonction.php';

use Config\Database;
use Classes\Quiz;
use Classes\User;
use Classes\Session;
// use Fonction\Fonction;

$pdo = Database::getInstance()->getConnection();
$quiz = new Quiz($pdo);
$user = new User($pdo);
$session = new Session($pdo);
// $fonction = new Fonction();


// if (!isAdmin()) {
//     header('Location: ../index.php');
//     exit;
// }

$message = '';
$optionsTemps = [];
$loggedUserId = $session->getCurrentUserToken()['user_id'];
// var_dump($loggedUserId);
$quizzes = $quiz->getAllQuizzes();
for ($i = 0; $i <= 10; $i++) {
    $minutes = $i * 30; // 0, 30, 60, ..., 300 minutes
    $heures = floor($minutes / 60);
    $minutesRestantes = $minutes % 60;
    $label = sprintf('%02d:%02d', $heures, $minutesRestantes);
    $optionsTemps[] = $label;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        $difficulte = $_POST['difficulte'];
        $tempsLimite = convertTempLimiteToMinutes($_POST['temp_limite']);
        $creatorId = (int) $_POST['userId'];
        $quizId = $_POST['id'] ?? null;
        
        switch($action) {
            case 'add':
                $creatorId = (int) $loggedUserId;
                if ($quiz->createQuiz($titre, $description, $creatorId, $difficulte, $tempsLimite, $status)) {
                    $message = displayAlert('Quiz ajouté avec succès');
                } else {
                    $message = displayAlert('Erreur lors de l\'ajout du quiz', 'error');
                }
                break;
            case 'edit':
                if ($quiz->updateQuiz($quizId, $titre, $description, $creatorId, $difficulte, $tempsLimite, $status)) {
                    $message = displayAlert('Quiz modifié avec succès');
                } else {
                    $message = displayAlert('Erreur lors de la modification du quiz', 'error');
                }
                break;
            case 'delete':
                if ($quiz->deleteQuiz( $quizId)) {
                    $message = displayAlert('Quiz supprimé avec succès');
                } else {
                    $message = displayAlert('Erreur lors de la suppression du quiz', 'error');
                }
                break;
        }
    }
}

function convertTempLimiteToMinutes($tempLimite) {
    // Séparer les heures et les minutes
    list($heures, $minutes) = explode(':', $tempLimite);

    // Convertir les heures en minutes
    $totalMinutes = ($heures * 60) + $minutes;

    return $totalMinutes;
}

?>

<script>
    document.getElementById('editUserId').value = $;
</script>

<div class="container mx-auto p-2">
    <h1 class="text-3xl font-bold mb-8 text-gray-800">Gestion des Quiz</h1>
    
    <?= $message ?>

    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Ajouter un Quiz</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="userId" id="editUserId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Titre</label>
                    <input type="text" name="titre" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Difficulté</label>
                    <select name="difficulte" class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-50 focus:ring-indigo-500">
                        <option value="facile">Facile</option>
                        <option value="moyen">Moyen</option>
                        <option value="difficile">Difficile</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Temps Limite</label>
                <select name="temp_limite" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <?php foreach ($optionsTemps as $temps): ?>
                        <option value="<?= $temps ?>"><?= $temps ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="brouillon">Brouillon</option>
                    <option value="publié">Publié</option>
                    <option value="archivé">Archivé</option>
                </select>
            </div>

            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Ajouter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difficulté</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temp limite</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($quizzes as $quiz): ?>
                    <?php 
                        $currentUser = $user->findById($quiz['creator_id']);
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= sanitizeInput($quiz['titre']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= sanitizeInput($quiz['description']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= sanitizeInput($currentUser['nom']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $quiz['status'] === 'publié' ? 'bg-green-100 text-green-800' : 
                                    ($quiz['status'] === 'archivé' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                <?= sanitizeInput($quiz['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= sanitizeInput($quiz['difficulte']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= sanitizeInput(data: $quiz['temps_limite']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick="openEditQuizModal(<?= $quiz['id'] ?>, '<?= addslashes($currentUser['nom']) ?>')" class="text-indigo-600 hover:text-indigo-900">Modifier</button>
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal d'édition -->
<div id="editQuizModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Modifier le quiz</h3>
            <form method="POST" id="editQuizForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editQuizId">
                <input type="hidden" name="userId" id="editUserId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Titre</label>
                    <input type="text" name="titre" id="editTitre" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="editDescription" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Creator's name</label>
                    <input type="text" name="creator_name" id="editCreator_name" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Statut</label>
                    <select name="status" id="editStatus" class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="brouillon">Brouillon</option>
                        <option value="publié">Publié</option>
                        <option value="archivé">Archivé</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Temps Limite</label>
                    <select name="temp_limite" id="editTempLimite" required class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <?php foreach ($optionsTemps as $temps): ?>
                            <option value="<?= $temps ?>"><?= $temps ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Difficulté</label>
                    <select name="difficulte" id="editDifficulte" class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="facile">Facile</option>
                        <option value="moyen">Moyen</option>
                        <option value="difficile">Difficile</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditQuizModal()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">Annuler</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

    function openEditQuizModal(quizId, userName) {
        const quiz = <?= json_encode($quizzes) ?>.find(q => q.id === quizId);

        if (quiz) {
            document.getElementById('editQuizId').value = quiz.id;
            document.getElementById('editUserId').value = quiz.creator_id;
            document.getElementById('editTitre').value = quiz.titre;
            document.getElementById('editDescription').value = quiz.description;
            document.getElementById('editTempLimite').value = quiz.temps_limite;
            document.getElementById('editCreator_name').value = userName;
            document.getElementById('editStatus').value = quiz.status;
            document.getElementById('editDifficulte').value = quiz.difficulte;
            document.getElementById('editQuizModal').classList.remove('hidden');
        }
    }

    function closeEditQuizModal() {
        document.getElementById('editQuizModal').classList.add('hidden');
    }

</script>