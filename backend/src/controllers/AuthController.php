<?php
session_start();
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // recovery and basic cleaning of entrances
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $displayName = trim($_POST['display_name'] ?? $username);

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
            header('Location: ../../../frontend/pages/login-signin/html/register.php');
            exit;
        }

        // if the user is already exists
        if ($this->userRepository->findByEmailOrUsername($email) || $this->userRepository->findByEmailOrUsername($username)) {
            $_SESSION['error'] = "Le pseudonyme ou l'adresse email est déjà utilisé.";
            header('Location: ../../../frontend/pages/login-signin/html/register.php');
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            // database creation
            $userId = $this->userRepository->create($username, $email, $hashedPassword, $displayName);

            // profile photo upload 
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = "avatar_" . $userId . "." . $fileExtension;
                    $uploadTargetDir = __DIR__ . '/../../../frontend/uploads/avatars/';
                    
                    // create the folder if it doesn't already exist
                    if (!is_dir($uploadTargetDir)) {
                        mkdir($uploadTargetDir, 0755, true);
                    }

                    $fullTargetPath = $uploadTargetDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $fullTargetPath)) {
                        // storing the path for the src attribute in HTML
                        $relativeUrl = "frontend/uploads/avatars/" . $newFileName;
                        $this->userRepository->updateAvatar($userId, $relativeUrl);
                    }
                }
            }

            // registration successful, user automatically logged in
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: ../../../frontend/pages/dashboard/html/dashboard.html');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Une erreur est survenue lors de l'inscription.";
            header('Location: ../../../frontend/pages/login-signin/html/register.php');
            exit;
        }
    }

    // login
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $identifier = trim($_POST['identifier'] ?? ''); // email ou le username
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $_SESSION['error'] = "Veuillez remplir tous les champs.";
            header('Location: ../../../frontend/pages/login-signin/html/login.php');
            exit;
        }

        // research the user
        $user = $this->userRepository->findByEmailOrUsername($identifier);

        // account and hashed password verification
        if ($user && password_verify($password, $user['password'])) {
            // user session creation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar_url'];

            header('Location: ../../../frontend/pages/dashboard/html/dashboard.html');
            exit;
        } else {
            $_SESSION['error'] = "Identifiants incorrects.";
            header('Location: ../../../frontend/pages/login-signin/html/login.php');
            exit;
        }
    }
}