<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/pages/login-signin/html/login.php');
    exit;
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Créer un Album</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/album.css" />
</head>

<body>
    <?php if (isset($_SESSION['error'])): ?>
    <div id="php-error-bridge" data-message="<?= htmlspecialchars($_SESSION['error']); ?>" style="display: none;"></div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form id="form-create-album" action="/backend/create_album_action.php" method="POST" enctype="multipart/form-data">

        <main class="create-album-layout">
            <div class="create-header">
                <a href="mes-albums.php" class="back-btn"><i class="fa-solid fa-chevron-left"></i></a>

                <div class="banner-upload-placeholder" id="banner-preview">
                    <span>Cliquez pour ajouter une couverture d'album</span>
                    <input type="file" id="banner-input" name="cover_image" accept="image/png, image/jpeg" hidden />
                </div>
            </div>

            <div class="create-content-grid">
                <div class="create-column">
                    <div class="input-editable-group">
                        <input type="text" name="title" placeholder="Entrez un nom d'album"
                            class="ghost-input title-input" required />
                        <i class="fa-solid fa-pen pencil-icon"></i>
                    </div>

                    <div class="input-editable-group">
                        <textarea name="description" placeholder="Mettez une description" class="ghost-input desc-input"
                            rows="2"></textarea>
                        <i class="fa-solid fa-pen pencil-icon"></i>
                    </div>

                    <div class="input-editable-group" id="tags-trigger">
                        <span class="ghost-label">Ajoutez des étiquettes</span>
                        <i class="fa-solid fa-pen pencil-icon"></i>
                        <div class="selected-tags-container" id="selected-tags"></div>
                        <div id="hidden-tags-inputs"></div>
                    </div>
                </div>

                <div class="create-column">
                    <h3 class="section-title">Visibilité</h3>
                    <div class="radio-group">
                        <label class="custom-radio">
                            <input type="radio" name="visibility" value="privé" checked />
                            <span class="radio-mark"></span>
                            <div class="radio-text">
                                <strong>Privé</strong><span>Visible par vous uniquement</span>
                            </div>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="visibility" value="restreint" />
                            <span class="radio-mark"></span>
                            <div class="radio-text">
                                <strong>Restreint</strong><span>Visible par vous et les personnes invitées</span>
                            </div>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="visibility" value="public" />
                            <span class="radio-mark"></span>
                            <div class="radio-text">
                                <strong>Public</strong><span>Visible par toute la communauté Memora</span>
                            </div>
                        </label>
                    </div>

                    <div id="invitation-section" class="hidden">
                        <h3 class="section-title">Inviter des personnes</h3>
                        <div class="search-invite-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="user-search-input" placeholder="Rechercher un pseudonyme..."
                                autocomplete="off" />
                        </div>
                        <div class="invited-list" id="invited-users">
                        </div>
                    </div>

                    <button type="submit" class="btn-save-album">Enregistrer l'album</button>
                </div>
            </div>

            <div class="upload-section">
                <hr class="divider" />
                <button type="button" class="btn-add-photos" id="upload-trigger">
                    <i class="fa-solid fa-plus"></i> Ajouter des photos
                </button>
                <input type="file" id="photo-upload" name="photos[]" multiple accept="image/png, image/jpeg" hidden />

                <div class="upload-preview-grid" id="upload-preview"></div>
            </div>
        </main>
    </form>

    <div class="modal hidden" id="tags-modal">
        <div class="modal-content">
            <h3>Choisir des étiquettes</h3>
            <div class="tags-cloud" id="tags-cloud"></div>
            <button type="button" class="btn-primary" id="close-tags">Valider</button>
        </div>
    </div>

    <script src="../../login-signin/js/validation.js"></script>
    <script src="../js/album.js"></script>
    <script src="../js/invitations.js"></script>
</body>

</html>