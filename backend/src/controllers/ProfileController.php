<?php
session_start();

require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AlbumRepository.php';

class ProfileController {
    private $userRepository;
    private $albumRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->albumRepository = new AlbumRepository();
    }

    public function getProfileData() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé."]);
            return;
        }

        $currentUserId = (int)$_SESSION['user_id'];
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                http_response_code(404);
                echo json_encode(["error" => "Utilisateur introuvable."]);
                return;
            }

            $albums = $this->albumRepository->getAlbumsByUserId($userId);
            $totalPhotos = 0;
            $isProfileOwner = ($currentUserId === $userId);
            foreach ($albums as &$album) {
                $photos = $this->albumRepository->getPhotosByAlbumId((int)$album['id']);
                $totalPhotos += count($photos);
                $album['is_invited'] = $isProfileOwner ? true : (bool)$this->albumRepository->getContributorRights((int)$album['id'], $currentUserId);
            }
            unset($album);

            $userData = [
                "bio" => $user['bio'] ?? "Aucune biographie pour le moment.",
                "banner" => $user['banner_url'] ?? "",
                "avatar" => $user['avatar_url'] ?? "frontend/assets/IMG/default-avatar.svg"
            ];

            echo json_encode([
                "success" => true,
                "user" => $userData,
                "stats" => [
                    "albums_count" => count($albums),
                    "photos_count" => $totalPhotos,
                    "followers_count" => $this->userRepository->getFollowersCount($userId),
                    "following_count" => $this->userRepository->getFollowingCount($userId)
                ],
                "albums" => $albums
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function updateProfile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
        if (mb_strlen($bio) > 160) {
            $bio = mb_substr($bio, 0, 160);
        }

        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Utilisateur introuvable.']);
                return;
            }

            $avatarPath = $user['avatar_url'] ?? $_SESSION['avatar'] ?? null;
            $bannerPath = $user['banner_url'] ?? $_SESSION['banner'] ?? null;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $newAvatar = $this->saveUploadedFile($_FILES['avatar'], __DIR__ . '/../../../frontend/uploads/avatars/', 'avatar_' . $userId . '_');
                if ($newAvatar !== null) {
                    $avatarPath = $newAvatar;
                    Logger::info($userId, 'profile_avatar_upload', 'avatar_url=' . $avatarPath);
                }
            }

            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $newBanner = $this->saveUploadedFile($_FILES['banner'], __DIR__ . '/../../../frontend/uploads/banners/', 'banner_' . $userId . '_');
                if ($newBanner !== null) {
                    $bannerPath = $newBanner;
                    Logger::info($userId, 'profile_banner_upload', 'banner_url=' . $bannerPath);
                }
            }

            $this->userRepository->updateProfile($userId, $bio, $avatarPath, $bannerPath);
            Logger::info($userId, 'profile_update', 'bio=' . ($bio !== '' ? '1' : '0') . '; avatar_updated=' . ($avatarPath !== $user['avatar_url'] ? '1' : '0') . '; banner_updated=' . ($bannerPath !== $user['banner_url'] ? '1' : '0'));

            $_SESSION['bio'] = $bio;
            if ($avatarPath !== null) {
                $_SESSION['avatar'] = $avatarPath;
            }
            if ($bannerPath !== null) {
                $_SESSION['banner'] = $bannerPath;
            }

            echo json_encode([
                'success' => true,
                'bio' => $bio,
                'avatar' => $avatarPath,
                'banner' => $bannerPath
            ]);
        } catch (Exception $e) {
            Logger::error('Erreur mise à jour profile', $e);
            http_response_code(500);
            echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    public function updateAccountIdentifiers() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $username = isset($data['username']) ? trim($data['username']) : null;
        $email = isset($data['email']) ? trim($data['email']) : null;

        if ($username === null && $email === null) {
            echo json_encode(['error' => 'Aucune donnée à modifier n\'a été transmise.']);
            return;
        }

        if ($username !== null) {
            if (strlen($username) < 3) {
                echo json_encode(['error' => 'Le pseudonyme doit contenir au moins 3 caractères.']);
                return;
            }
            if (!$this->userRepository->isUsernameUnique($username, $userId)) {
                echo json_encode(['error' => 'Ce pseudonyme est déjà utilisé par un autre compte.']);
                return;
            }
        }

        if ($email !== null) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['error' => 'Le format de l\'adresse e-mail est invalide.']);
                return;
            }
            if (!$this->userRepository->isEmailUnique($email, $userId)) {
                echo json_encode(['error' => 'Cette adresse e-mail est déjà associée à un autre compte.']);
                return;
            }
        }

        try {
            $this->userRepository->updateUsernameOrEmail($userId, $username, $email);
            $details = [];
            if ($username !== null) {
                $_SESSION['username'] = $username;
                $details[] = 'username_updated=1';
            }
            if ($email !== null) {
                $details[] = 'email_updated=1';
            }
            Logger::info($userId, 'profile_identifiers_update', implode('; ', $details));
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            Logger::error('Erreur mise à jour identifiants', $e);
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    }

    public function updatePassword() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (strlen($newPassword) < 14) {
            echo json_encode(['error' => 'Le nouveau mot de passe doit faire au moins 14 caractères.']);
            return;
        }

        try {
            $user = $this->userRepository->findById($userId);
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                echo json_encode(['error' => 'Le mot de passe actuel est incorrect.']);
                return;
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->userRepository->updatePassword($userId, $newHash);
            Logger::info($userId, 'profile_password_update', 'password_changed=1');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            Logger::error('Erreur mise à jour mot de passe', $e);
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de mise à jour du mot de passe.']);
        }
    }

    public function deleteAccount() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $password = $data['password'] ?? '';

        try {
            $user = $this->userRepository->findById($userId);
            if (!$user || !password_verify($password, $user['password'])) {
                echo json_encode(['error' => 'Mot de passe incorrect. Impossible de supprimer le compte.']);
                return;
            }

            $basePath = __DIR__ . '/../../../';
            $filesToDelete = [];

            if (!empty($user['avatar_url'])) {
                $filesToDelete[] = $basePath . ltrim($user['avatar_url'], '/');
            }
            if (!empty($user['banner_url'])) {
                $filesToDelete[] = $basePath . ltrim($user['banner_url'], '/');
            }

            $albumCoverUrls = $this->albumRepository->getAlbumCoverUrlsByUserId($userId);
            foreach ($albumCoverUrls as $coverUrl) {
                if (!empty($coverUrl)) {
                    $filesToDelete[] = $basePath . ltrim($coverUrl, '/');
                }
            }

            $photos = $this->albumRepository->getPhotosByUserId($userId);
            foreach ($photos as $photo) {
                if (!empty($photo['file_path'])) {
                    $filesToDelete[] = $basePath . ltrim($photo['file_path'], '/');
                }
            }

            foreach ($filesToDelete as $filePath) {
                if ($filePath && file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }

            $deleted = $this->userRepository->deleteById($userId);
            if ($deleted) {
                session_destroy();
                echo json_encode(['success' => true]);
                return;
            }

            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression du compte.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }

    private function saveUploadedFile(array $file, string $targetDir, string $prefix): ?string {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $prefix . time() . '.' . $extension;
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $subdir = basename(rtrim($targetDir, '/'));
            return 'frontend/uploads/' . $subdir . '/' . $filename;
        }

        return null;
    }
}