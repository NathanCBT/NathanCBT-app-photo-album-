<?php
session_start();
require_once __DIR__ . '/../../../../backend/src/models/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/pages/login-signin/html/login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// detect if someone is visiting another person's profile
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;
$isOwnProfile = ($targetUserId === $currentUserId);

try {
    $pdo = Database::getConnection();

    // retrieve the user information to display
    $stmtUser = $pdo->prepare("
    SELECT id, username, bio, avatar_url, banner_url 
    FROM users 
    WHERE id = ?
    ");
    $stmtUser->execute([$targetUserId]);
    $profileUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$profileUser) {
        // if the requested user does not exist we will be redirected to their profile
        header('Location: profile-user.php');
        exit;
    }

    $userId = (int)$profileUser['id'];
    $username = $profileUser['username'] ?? 'Utilisateur';
    $bio = $profileUser['bio'] ?? 'Aucune biographie pour le moment.';
    $avatar = $profileUser['avatar_url'] ?? 'frontend/assets/IMG/default-avatar.svg';
    $banner = $profileUser['banner_url'] ?? '';

    // if this isn't our profile check if we're already following this user
    $isFollowing = false;
    if (!$isOwnProfile) {
        $stmtCheckFollow = $pdo->prepare("
        SELECT 1 
        FROM follows 
        WHERE follower_id = ? 
        AND following_id = ?
        ");
        $stmtCheckFollow = $pdo->prepare("
        SELECT 1 
        FROM follows 
        WHERE follower_id = ? 
        AND following_id = ?
        ");
        $stmtCheckFollow->execute([$currentUserId, $targetUserId]);
        $isFollowing = (bool)$stmtCheckFollow->fetchColumn();
    }

} catch (Exception $e) {
    die("Erreur de chargement du profil.");
}
?>

<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mon Profil</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/profile.css" />
</head>

<body>

    <div id="profile-bridge" data-user-id="<?= $userId ?>"></div>

    <div class="profile-container">
        <div class="profile-banner" id="profile-banner-display"
            style="background-image: <?= $banner ? "url('/" . htmlspecialchars(ltrim($banner, '/')) . "')" : 'none' ?>;">
            <a href="../../dashboard/html/dashboard.php" class="back-arrow-btn" title="Retour au Dashboard">
                <i class="fa-solid fa-chevron-left" style="color: rgb(255, 255, 255);"></i>
            </a>
        </div>

        <div class="profile-content-wrapper">
            <div class="profile-header-main">
                <div class="profile-avatar-large" id="profile-avatar-display"
                    style="background-image: url('/<?= htmlspecialchars(ltrim($avatar, '/')) ?>');"></div>

                <div class="profile-identity-box">
                    <div class="profile-name-row">
                        <h1 id="profile-username-display"><?= htmlspecialchars($username) ?></h1>
                        <div class="profile-actions-group">
                            <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn-cancel" id="btn-edit-profile-trigger">
                                <i class="fa-solid fa-pen-to-square"></i> Modifier
                            </button>
                            <button type="button" class="btn-more-options">
                                <a href="../../settings/html/settings.php" class="btn-cancel">
                                    <i class="fa-solid fa-gear"></i>
                                </a>
                            </button>
                            <?php else: ?>
                            <?php if ($isFollowing): ?>
                            <button type="button" id="btn-toggle-follow" class="btn-cancel"
                                data-user-id="<?= $userId ?>" data-status="unfollow">
                                <i class="fa-solid fa-user-minus"></i> Ne plus suivre
                            </button>
                            <?php else: ?>
                            <button type="button" id="btn-toggle-follow" class="btn-primary"
                                data-user-id="<?= $userId ?>" data-status="follow">
                                <i class="fa-solid fa-user-plus"></i> Suivre
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="profile-handle">@<?= htmlspecialchars(strtolower(str_replace(' ', '', $username))) ?></p>
                    <p class="profile-bio" id="profile-bio-display"><?= nl2br(htmlspecialchars($bio)) ?></p>
                </div>
            </div>

            <div class="profile-stats-row">
                <div class="stat-item"><strong id="stat-albums-count">0</strong><span>Albums</span></div>
                <div class="stat-item"><strong id="stat-photos-count">0</strong><span>Photos</span></div>
                <div class="stat-item clickable-stat" id="stat-followers-trigger"><strong
                        id="stat-followers-count">0</strong><span>Abonnés</span></div>
                <div class="stat-item clickable-stat" id="stat-following-trigger"><strong
                        id="stat-following-count">0</strong><span>Abonnements</span></div>
            </div>

            <hr class="profile-divider" />

            <div class="albums-section-header">
                <h2>Collections</h2>
                <?php if ($isOwnProfile): ?>
                <a href="../../album/html/mes-albums.php" class="btn-cancel">
                    <i class="fa-solid fa-pen-to-square"></i> Gérer mes albums
                </a>
                <?php endif; ?>
            </div>

            <div class="albums-grid" id="user-albums-grid"></div>
        </div>
    </div>

    <div id="edit-profile-modal" class="modal hidden">
        <div class="modal-content">
            <h2><i class="fa-solid fa-user-gear"></i> Modifier mon profil</h2>
            <form id="form-edit-profile" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Photo de profil</label>
                    <input type="file" name="avatar" accept="image/*" class="file-input" />
                </div>

                <div class="form-group">
                    <label>Bannière de profil</label>
                    <input type="file" name="banner" accept="image/*" class="file-input" />
                </div>

                <div class="form-group">
                    <label>Biographie</label>
                    <textarea name="bio" id="textarea-bio" rows="4" class="textarea-input"
                        maxlength="160"><?= htmlspecialchars($bio) ?></textarea>
                    <small id="bio-counter">160 caractères restants</small>
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-close-edit-modal" class="btn-cancel">Annuler</button>
                    <button type="submit" id="btn-submit-profile" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div id="followers-modal" class="modal hidden">
        <div class="modal-content">
            <h2><i class="fa-solid fa-users"></i> Abonnés</h2>
            <div class="users-list" id="followers-list-container">
            </div>
            <div class="modal-actions">
                <button type="button" id="btn-close-followers" class="btn-cancel">Fermer</button>
            </div>
        </div>
    </div>

    <div id="following-modal" class="modal hidden">
        <div class="modal-content">
            <h2><i class="fa-solid fa-user-plus"></i> Abonnements</h2>
            <div class="users-list" id="following-list-container">
            </div>
            <div class="modal-actions">
                <button type="button" id="btn-close-following" class="btn-cancel">Fermer</button>
            </div>
        </div>
    </div>

    <script src="../js/follow.js"></script>
    <script src="../js/profile.js"></script>
</body>

</html>