<?php
    ob_start();
    session_start();
    require_once './header.php';
    require_once '../classes/Auth.php';
    require_once '../classes/Session.php';
    require '../config/Database.php';
    require_once '../fonction/Fonction.php';

    use Classes\Auth;
    use Classes\Session;
    use Config\Database;
    use Fonction\Fonction;
    
    $pdo = Database::getInstance()->getConnection();
    $session = new Session($pdo);
    $auth = new Auth($pdo, $session);
    $fonction = new Fonction();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("CSRF token validation failed.");
        }
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $password_confirm = filter_input(INPUT_POST, 'password_confirm', FILTER_SANITIZE_STRING);
        $rememberMe = isset($_POST['remember_me']);

        if (!($password == $password_confirm)) {
            echo "Les mot de passes doivent etre identique";
            exit;
        }

        // Gestion de l'upload de l'image
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo "Le fichier est trop volumineux.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    echo "Le fichier n'a été que partiellement téléchargé.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo "Aucun fichier n'a été téléchargé.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo "Le dossier temporaire est manquant.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo "Échec de l'écriture du fichier sur le disque.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo "Une extension PHP a arrêté le téléchargement du fichier.";
                    break;
                default:
                    echo "Une erreur inconnue s'est produite lors du téléchargement.";
                    break;
            }
            exit;
        }

        // Validation du type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            echo "Seules les images JPEG, PNG et GIF sont autorisées.";
            exit;
        }

        // Limitation de la taille du fichier
        $maxFileSize = 2 * 1024 * 1024; // 2 Mo
        if ($_FILES['image']['size'] > $maxFileSize) {
            echo "Le fichier est trop volumineux. La taille maximale autorisée est de 2 Mo.";
            exit;
        }

        // Si toutes les vérifications passent, vous pouvez procéder à l'upload
        $image = $fonction->gestionImage($_FILES);

        $loginSuccess = $auth->register($nom, $email, $password, $image);
        var_dump("login Success: ", $loginSuccess);

        try {
            if ($loginSuccess) {
                $role = $_SESSION['user_role'];
                if ($role == 'user') {
                    header('Location: ./user_dashboard.php');
                    exit;
                } else {
                    header('Location: ./admin_dashboard.php');
                    exit;
                }
            } else {
                $error = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
            }
        } catch (\Exception $e) {
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            error_log("Erreur lors de la connexion: " . $e->getMessage());
        }
    }
    ob_end_flush();
?>

        <div class="min-h-screen flex items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg w-96">
                <h1 class="text-2xl font-bold mb-6 text-center">Inscription</h1>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div>
                        <label class="block text-gray-700">Nom</label>
                        <input type="text" name="nom" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700">Mot de passe</label>
                        <input type="password" name="password" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirm" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-gray-700">Télécharger une image</label>
                        <input type="file" name="image" accept="image/*"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>

                    <button type="submit" 
                            class="w-full bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                        S'inscrire
                    </button>
                </form>

                <p class="mt-4 text-center text-gray-600">
                    Déjà inscrit ? 
                    <a href="login.php" class="text-blue-500 hover:text-blue-600">Se connecter</a>
                </p>
            </div>
        </div>
    </body>
</html>