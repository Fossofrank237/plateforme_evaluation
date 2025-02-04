<?php
// require_once '../header.php';
require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';

use Config\Database;
use Classes\User;

// // Security check
// if (!isAdmin()) {
//     $_SESSION['error'] = "Accès non autorisé";
//     header('Location: ../index.php');
//     exit;
// }

$pdo = Database::getInstance()->getConnection();
$user = new User($pdo);
$message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new \Exception('Token CSRF invalide');
        }

        $action = $_POST['action'] ?? '';
        
        switch($action) {
            case 'add':
                $user->validateUserData($_POST);
                if (empty($errors)) {
                    $result = $user->createUser(
                        trim($_POST['nom']),
                        trim($_POST['email']),
                        $_POST['mot_de_passe'],
                        $_POST['role']
                    );
                    
                    if ($result) {
                        $message = displayAlert('Utilisateur ajouté avec succès');
                    } else {
                        $errors[] = 'Erreur lors de l\'ajout de l\'utilisateur';
                    }
                }
                break;

            case 'edit':
                if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                    throw new \Exception('ID utilisateur invalide');
                }
                
                $userData = [
                    'nom' => trim($_POST['nom']),
                    'email' => trim($_POST['email']),
                    'role' => $_POST['role'],
                    'actif' => isset($_POST['actif']) ? 1 : 0
                ];
                
                if ($user->updateUser((int)$_POST['id'], $userData)) {
                    $message = displayAlert('Utilisateur modifié avec succès');
                } else {
                    $errors[] = 'Erreur lors de la modification de l\'utilisateur';
                }
                break;

            case 'delete':
                if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                    throw new \Exception('ID utilisateur invalide');
                }
                
                if ($user->deleteUser((int)$_POST['id'])) {
                    $message = displayAlert('Utilisateur supprimé avec succès');
                } else {
                    $errors[] = 'Erreur lors de la suppression de l\'utilisateur';
                }
                break;
                
            default:
                throw new \Exception('Action invalide');
        }
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Fetch users
try {
    $users = $user->getAllUsers();
} catch (\Exception $e) {
    $errors[] = 'Erreur lors de la récupération des utilisateurs';
    $users = [];
}

// Display errors if any
if (!empty($errors)) {
    $message = displayAlert(implode('<br>', $errors), 'error');
}
?>

        <!-- Page Content -->
        <div class="container mx-auto p-2">
            <h1 class="text-3xl font-bold mb-8 text-gray-800">Gestion des Utilisateurs</h1>
            
            <?= $message ?>

            <!-- Add User Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Ajouter un Utilisateur</h2>
                <form method="POST" class="space-y-4" onsubmit="return validateForm(this)">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required minlength="2" maxlength="50" pattern="[A-Za-z0-9\s-]+" 
                                class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" required 
                                class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input type="password" name="mot_de_passe" required minlength="8"
                                class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 caractères</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select name="role" required 
                                    class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Ajouter
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscription</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $userData): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($userData['nom']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($userData['email']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $userData['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= htmlspecialchars($userData['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($userData['date_inscription'] ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $userData['actif'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $userData['actif'] === 1 ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button onclick="openEditModal(<?= $userData['id'] ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900">
                                            Modifier
                                        </button>
                                        <form method="POST" class="inline-block" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $userData['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Modifier l'utilisateur</h3>
                    <form method="POST" id="editForm" onsubmit="return validateForm(this)">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editUserId">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" id="editNom" required minlength="2" maxlength="50" pattern="[A-Za-z0-9\s-]+"
                                class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="editEmail" required
                                class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select name="role" id="editRole" required
                                    class="mt-1 block w-full rounded-md border-slate-800 border-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="actif" id="editActif"
                                    class="rounded border-slate-800 border-2 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">Actif</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeEditModal()" 
                                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">
                                Annuler
                            </button>
                            <button type="submit" 
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Close modal when clicking outside
            document.getElementById('editModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });

            // Prevent modal close when clicking inside the modal content
            document.querySelector('#editModal > div').addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Form validation enhancement
            function validateForm(form) {
                const password = form.querySelector('input[name="mot_de_passe"]');
                const email = form.querySelector('input[name="email"]');
                const nom = form.querySelector('input[name="nom"]');
                
                // Only check password for new user form
                if (password && password.value.length < 8) {
                    alert('Le mot de passe doit contenir au moins 8 caractères');
                    return false;
                }

                // Validate email format
                if (email && !isValidEmail(email.value)) {
                    alert('Veuillez entrer une adresse email valide');
                    return false;
                }

                // Validate name
                if (nom && !isValidName(nom.value)) {
                    alert('Le nom ne doit contenir que des lettres, chiffres, espaces et tirets');
                    return false;
                }

                return true;
            }

            // Email validation helper
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Name validation helper
            function isValidName(name) {
                const nameRegex = /^[A-Za-z0-9\s-]+$/;
                return nameRegex.test(name);
            }

            // Enhanced modal management
            function openEditModal(userId) {
                const user = <?= json_encode($users) ?>.find(u => u.id === userId);
                if (!user) {
                    alert('Utilisateur non trouvé');
                    return;
                }

                // Sanitize data before inserting into DOM
                document.getElementById('editUserId').value = sanitizeInput(user.id);
                document.getElementById('editNom').value = sanitizeInput(user.nom);
                document.getElementById('editEmail').value = sanitizeInput(user.email);
                document.getElementById('editRole').value = sanitizeInput(user.role);
                document.getElementById('editActif').checked = user.actif === "1";
                document.getElementById('editModal').classList.remove('hidden');

                // Add escape key listener
                document.addEventListener('keydown', handleEscapeKey);
            }

            // Sanitize input helper
            function sanitizeInput(input) {
                if (typeof input !== 'string') {
                    input = String(input);
                }
                return input.replace(/[<>"'&]/g, function(match) {
                    const entities = {
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;',
                        '&': '&amp;'
                    };
                    return entities[match];
                });
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                // Remove escape key listener
                document.removeEventListener('keydown', handleEscapeKey);
                // Reset form
                document.getElementById('editForm').reset();
            }

            function handleEscapeKey(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            }

            function confirmDelete() {
                return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');
            }

            // Initialize event listeners when DOM is loaded
            document.addEventListener('DOMContentLoaded', function() {
                // Add form submission handlers
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    form.addEventListener('submit', function(e) {
                        if (!validateForm(this)) {
                            e.preventDefault();
                        }
                    });
                });

                // Add input validation on blur
                const inputs = document.querySelectorAll('input[type="email"], input[name="nom"]');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.type === 'email' && !isValidEmail(this.value)) {
                            this.classList.add('border-red-500');
                        } else if (this.name === 'nom' && !isValidName(this.value)) {
                            this.classList.add('border-red-500');
                        } else {
                            this.classList.remove('border-red-500');
                        }
                    });
                });
            });
        </script>
    </body>
</html>