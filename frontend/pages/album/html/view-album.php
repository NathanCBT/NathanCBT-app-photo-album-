<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/pages/login-signin/html/login.php');
    exit;
}

require_once __DIR__ . '/../../../../backend/src/repositories/AlbumRepository.php';

$albumId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$albumId) {
    die("Album introuvable ou ID invalide.");
}

$userId = (int)$_SESSION['user_id'];
$albumRepository = new AlbumRepository();
$album = $albumRepository->getAlbumById($albumId);

if (!$album) {
    die("Cet album n'existe pas.");
}

// access rights
$isOwner = ((int)$album['user_id'] === $userId);
$contributorRight = $albumRepository->getContributorRights($albumId, $userId);

// if the album is private and the user is not the creator
if ($album['visibility'] === 'privé' && !$isOwner) {
    die("Cet album est privé. Vous n'avez pas l'autorisation de le consulter.");
}

if ($album['visibility'] === 'restreint' && !$isOwner && !$contributorRight) {
    die("Vous n'avez pas été invité à collaborer sur cet album.");
}

$canModify = $isOwner || ($contributorRight === 'Peut modifier');
$canComment = $isOwner || in_array($contributorRight, ['Peut modifier', 'Peut commenter', 'Peut voir']); // 'Peut voir' bloque les comms selon tes règles
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($album['title']) ?> - Memora</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/album.css" />
</head>

<body class="view-album-body">

    <div id="album-bridge" data-id="<?= $album['id'] ?>" data-can-modify="<?= $canModify ? '1' : '0' ?>"
        data-can-comment="<?= $canComment ? '1' : '0' ?>"></div>

    <header class="main-header">
        <div class="header-left">
            <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora Logo" class="header-logo" />
        </div>
        <nav class="header-center-nav">
            <a href="../../dashboard/html/dashboard.php" class="nav-item" title="Accueil"><i
                    class="fa-solid fa-house nav-icon"></i></a>
            <a href="../../search/html/search.php" class="nav-item" title="Recherche"><i
                    class="fa-solid fa-magnifying-glass nav-icon"></i></a>
            <a href="../../invitation/html/invitation.php" class="nav-item" title="Invitations"><i
                    class="fa-solid fa-user-group nav-icon"></i></a>
            <button type="button" class="nav-item btn-notif" title="Notifications"><i
                    class="fa-solid fa-bell nav-icon"></i></button>
        </nav>
        <div class="header-right">
            <a href="../../profile/html/profile-user.php" class="header-avatar-link">
                <div class="user-avatar-small"
                    style="background-image: url('/<?= $_SESSION['avatar_url'] ?? 'assets/IMG/default-avatar.svg' ?>'); background-size: cover;">
                </div>
            </a>
        </div>
    </header>

    <div class="album-banner-hero"
        style="background-image: url('/<?= $album['cover_url'] ?? 'frontend/assets/IMG/default-cover.jpg' ?>');">
        <a href="mes-albums.php" class="btn-back-circle" title="Retour à mes albums">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    </div>

    <main class="album-detail-layout">
        <section class="album-header-details">
            <div class="title-actions-row">
                <h1 class="album-view-title"><?= htmlspecialchars($album['title']) ?></h1>

                <div class="album-action-buttons">
                    <?php if ($canModify): ?>
                    <button class="btn-edit-album-details"><i class="fa-solid fa-pen"></i> Modifier</button>
                    <?php endif; ?>
                    <button class="btn-more-options"><i class="fa-solid fa-ellipsis"></i></button>
                </div>
            </div>

            <p class="album-meta">
                <span id="photo-count-span">0 photo</span> . Créé le
                <?= date('d m Y', strtotime($album['created_at'])) ?>
            </p>

            <p class="album-view-description">
                <?= nl2br(htmlspecialchars($album['description'] ?? 'Aucune description pour cet album.')) ?>
            </p>

            <div class="album-tags-row" id="album-tags-container"></div>
        </section>

        <hr class="album-separator" />

        <?php if ($canModify): ?>
        <button id="btn-add-photos-trigger" class="btn-add-photos-trigger">
            <i class="fa-solid fa-plus"></i> Ajouter des photos
        </button>
        <?php endif; ?>

        <div class="photos-grid" id="album-photos-grid"></div>
    </main>

    <div id="photo-detail-modal" class="photo-modal hidden">
        <div class="photo-modal-overlay"></div>
        <div class="photo-modal-wrapper">
            <button class="btn-close-modal"><i class="fa-solid fa-xmark"></i></button>

            <div class="photo-modal-content">
                <div class="modal-left-pane">
                    <button class="nav-arrow prev-arrow"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="modal-image-container">
                        <img src="" id="modal-target-img" alt="Souvenir" />
                    </div>
                    <button class="nav-arrow next-arrow"><i class="fa-solid fa-chevron-right"></i></button>

                    <div class="photo-utility-bar">
                        <button id="btn-favorite-photo" title="Ajouter aux favoris"><i
                                class="fa-regular fa-heart"></i></button>
                        <button id="btn-copy-link" title="Copier le lien de la photo"><i
                                class="fa-solid fa-link"></i></button>
                    </div>
                </div>

                <div class="modal-right-pane">
                    <h2 id="modal-album-title"><?= htmlspecialchars($album['title']) ?></h2>
                    <p id="modal-photo-date" class="photo-date-sub"></p>
                    <p id="modal-photo-description" class="photo-desc-text"></p>

                    <div class="photo-modal-tags" id="modal-photo-tags-container"></div>

                    <hr />

                    <h3>Commentaires</h3>
                    <div class="comments-list-flow" id="modal-comments-list"></div>

                    <div class="comment-input-area" id="comment-form-container">
                        <input type="text" id="input-new-comment" placeholder="Ajouter un commentaire..." />
                        <button id="btn-submit-comment"><i class="fa-solid fa-arrow-up"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="upload-photos-modal" class="modal hidden">
        <div class="modal-content modal-upload-large">

            <div class="upload-modal-header">
                <h3><i class="fa-solid fa-images"></i> Ajouter des souvenirs à l'album</h3>
                <button type="button" id="btn-close-upload-modal" class="btn-close-icon">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="dropzone-area" class="upload-dropzone">
                <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                <p class="dropzone-text">Glissez-déposez vos photos ici ou <span class="dropzone-highlight">parcourez
                        vos fichiers</span></p>
                <input type="file" id="input-hidden-file" multiple accept="image/*" class="hidden">
            </div>

            <form id="form-upload-photos-gallery" enctype="multipart/form-data">
                <div id="upload-preview-container" class="upload-preview-grid">
                </div>

                <div class="upload-modal-actions">
                    <button type="button" id="btn-cancel-upload" class="btn-secondary-cancel">Annuler</button>
                    <button type="submit" id="btn-submit-upload-gallery" class="btn-primary btn-submit-upload">Importer
                        les photos</button>
                </div>
            </form>

        </div>
    </div>

    <script src="../js/view-album.js"></script>
</body>

</html>