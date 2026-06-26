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

$albumTags = $albumRepository->getAlbumTags($albumId);
$albumTagsJson = htmlspecialchars(json_encode($albumTags), ENT_QUOTES, 'UTF-8');

$db = Database::getConnection();
$stmtGlobalTags = $db->query("SELECT id, name FROM tags ORDER BY name ASC");
$allTags = $stmtGlobalTags->fetchAll(PDO::FETCH_ASSOC);

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
$canComment = $isOwner || in_array($contributorRight, ['Peut modifier', 'Peut commenter']) || ($album['visibility'] === 'public');
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($album['title']) ?></title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/album.css" />
</head>

<body class="view-album-body">

    <div id="album-bridge" data-id="<?php echo $album['id']; ?>" data-can-modify="<?php echo $canModify ? '1' : '0'; ?>"
        data-can-comment="<?php echo $canComment ? '1' : '0'; ?>"
        data-user-id="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>"
        data-is-private="<?php echo ($album['visibility'] === 'privé') ? '1' : '0'; ?>"
        data-album-tags="<?php echo htmlspecialchars(json_encode($albumTags)); ?>">
    </div>

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
                    class="fa-solid fa-user-group nav-icon"></i>
            </a>
        </nav>
        <div class="header-right">
            <a href="../../profile/html/profile-user.php" class="header-avatar-link">
                <div class="user-avatar-small"
                    style="background-image: url('/<?= $_SESSION['avatar'] ?? 'assets/IMG/default-avatar.svg' ?>'); background-size: cover;">
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
                    <button class="btn-share-album" title="Copier le lien de l'album"><i
                            class="fa-solid fa-link"></i></button>
                </div>
            </div>

            <p class="album-meta">
                <span id="photo-count-span">0 photo</span> . Créé le
                <?= date('d/m/Y', strtotime($album['created_at'])) ?>
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

    <?php if ($canModify): ?>
    <div id="edit-album-modal" class="modal hidden">
        <div class="modal-content modal-upload-large">
            <div class="upload-modal-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Modifier les informations de l'album</h3>
                <button type="button" id="btn-close-edit-modal" class="btn-close-icon">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-edit-album" enctype="multipart/form-data">
                <input type="hidden" name="album_id" value="<?= $album['id'] ?>">

                <div class="preview-fields-container">
                    <div class="form-control-group">
                        <label for="edit-album-title">Titre de l'album *</label>
                        <input type="text" id="edit-album-title" name="title"
                            value="<?= htmlspecialchars($album['title']) ?>" required />
                    </div>

                    <div class="form-control-group">
                        <label for="edit-album-description">Description</label>
                        <textarea id="edit-album-description" name="description"
                            rows="3"><?= htmlspecialchars($album['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-control-group" id="edit-tags-trigger">
                        <label>Étiquettes de l'album</label>
                        <div class="edit-tags-picker-zone">
                            <span class="ghost-label-edit"><i class="fa-solid fa-tags"></i> Gérer les étiquettes</span>
                            <div class="selected-tags-container" id="edit-selected-tags">
                            </div>
                        </div>
                        <div id="edit-hidden-tags-inputs"></div>
                    </div>

                    <div class="form-control-group">
                        <label for="edit-album-visibility">Visibilité</label>
                        <select id="edit-album-visibility" name="visibility">
                            <option value="privé" <?= $album['visibility'] === 'privé' ? 'selected' : '' ?>>Privé
                            </option>
                            <option value="restreint" <?= $album['visibility'] === 'restreint' ? 'selected' : '' ?>>
                                Restreint (Contributeurs uniquement)</option>
                            <option value="public" <?= $album['visibility'] === 'public' ? 'selected' : '' ?>>Public
                            </option>
                        </select>
                    </div>

                    <div class="form-control-group">
                        <label>Nouvelle couverture de l'album (Optionnel)</label>
                        <input type="file" id="edit-album-cover" name="cover_file" accept="image/*" />
                    </div>
                </div>

                <div class="edit-invitation-container">
                    <label class="edit-section-label">Gérer les contributeurs</label>
                    <div class="edit-search-invite-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="edit-user-search-input"
                            placeholder="Rechercher un pseudonyme à inviter..." autocomplete="off" />
                    </div>
                    <div class="edit-invited-list" id="edit-invited-users"></div>
                </div>

                <div class="upload-modal-actions">
                    <button type="button" id="btn-cancel-edit" class="btn-secondary-cancel">Annuler</button>
                    <button type="submit" id="btn-submit-edit-album" class="btn-primary">Enregistrer les
                        modifications</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal hidden" id="edit-tags-modal">
        <div class="modal-content">
            <h3>Choisir des étiquettes</h3>
            <div class="tags-cloud" id="edit-tags-cloud">
                <?php foreach ($allTags as $gTag): ?>
                <span class="tag-item" data-id="<?= $gTag['id'] ?>"><?= htmlspecialchars($gTag['name']) ?></span>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-primary" id="btn-close-edit-tags">Valider</button>
        </div>
    </div>
    <?php endif; ?>

    <script src="../js/view-album.js"></script>
    <script src="../js/edit-album.js"></script>
    <script src="../js/copy-link.js"></script>
    <script src="../js/edit-invitations.js"></script>
</body>

</html>