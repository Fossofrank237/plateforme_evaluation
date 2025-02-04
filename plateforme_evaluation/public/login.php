<?php
    session_start();

    require_once './header.php';
    require_once '../classes/Auth.php';
    require_once '../classes/Session.php';
    require '../config/Database.php';

    use Classes\Auth;
    use Classes\Session;
    use Config\Database;

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $pdo = Database::getInstance()->getConnection();
    $session = new Session($pdo);
    $auth = new Auth($pdo, $session);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("CSRF token validation failed.");
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $rememberMe = isset($_POST['remember_me']);

        $loginSuccess = $auth->login($email, $password, $rememberMe);
        var_dump("login Success: ", $loginSuccess);
        try {
            if ($loginSuccess) {

                $role = $auth->currentUser()["role"];
                var_dump($role);
                if ($role == 'user') {
                    header('Location: ./user_dashboard.php');
                    exit;
                } else {
                    header('Location: ./admin_dashboard.php');
                    exit;
                }
            } else {
                $error = "Identifiants incorrects. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
            error_log("Erreur lors de la connexion: " . $e->getMessage());
        }
    }
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-2xl font-bold mb-6 text-center">Connexion</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" id="email" name="email" required
                    class="mt-1 block w-full rounded-md border-solid border-2 border-slate-900 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div>
                <label for="password" class="block text-gray-700">Mot de passe</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full rounded-md border-solid border-2 border-slate-900 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="remember_me" name="remember_me"
                    class="rounded border-gray-300 text-blue-500 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                <label for="remember_me" class="ml-2 text-gray-700">Se souvenir de moi</label>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                Se connecter
            </button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Pas encore inscrit ? 
            <a href="register.php" class="text-blue-500 hover:text-blue-600">Créer un compte</a>
        </p>
    </div>
</div>
</body>
</html>