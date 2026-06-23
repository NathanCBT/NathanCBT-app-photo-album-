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
    <title>Paramètres</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/settings.css" />
</head>

<body>
    <div class="settings-layout-container">
        <div class="settings-sidebar">
            <h2>Paramètres</h2>
            <a href="../../profile/html/profile-user.php" class="btn-back-profile">
                <span class="chevron-left-icon"></span> Revenir au profil
            </a>
            <button type="button" id="btn-logout" class="btn-logout-sidebar">Déconnexion</button>
        </div>

        <div class="settings-main-content">
            <div id="settings-alert-container"></div>

            <section class="settings-section">
                <h3>Identifiants & Compte</h3>
                <p class="section-description">Modifiez votre nom d'utilisateur ou votre adresse e-mail.</p>

                <form id="form-update-identifiers" novalidate>
                    <div class="input-group">
                        <label for="settings-username">Nom d'utilisateur (@handle)</label>
                        <input type="text" id="settings-username" required placeholder="Nouveau pseudonyme..." />
                        <span class="input-hint">Utilisé pour la recherche et les invitations.</span>
                    </div>

                    <div class="input-group">
                        <label for="settings-email">Adresse e-mail</label>
                        <input type="email" id="settings-email" required placeholder="Nouvelle adresse e-mail..." />
                    </div>

                    <button type="submit" class="btn-save-settings">Mettre à jour les identifiants</button>
                </form>
            </section>

            <hr class="settings-divider" />

            <section class="settings-section">
                <h3>Sécurité</h3>
                <p class="section-description">Mettez à jour votre mot de passe régulièrement.</p>

                <form id="form-update-password" novalidate>
                    <div class="input-group">
                        <label for="current-password">Mot de passe actuel</label>
                        <div class="password-wrapper">
                            <input type="password" id="current-password" required placeholder="••••••••" />
                            <button type="button" class="toggle-password"></button>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="new-password">Nouveau mot de passe</label>
                        <div class="password-wrapper">
                            <input type="password" id="new-password" required placeholder="••••••••" />
                            <button type="button" class="toggle-password"></button>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm-password">Confirmer le nouveau mot de passe</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm-password" required placeholder="••••••••" />
                            <button type="button" class="toggle-password"></button>
                        </div>
                    </div>

                    <button type="submit" class="btn-save-settings">Modifier le mot de passe</button>
                </form>
            </section>

            <hr class="settings-divider" />

            <section class="settings-section danger-zone">
                <div class="danger-action-block">
                    <div class="danger-text">
                        <h3>Supprimer mon compte</h3>
                        <p>Cette action effacera définitivement votre profil, tous vos albums ainsi que vos photos
                            téléchargées.</p>
                    </div>
                    <button type="button" id="btn-delete-account" class="btn-danger">Supprimer définitivement</button>
                </div>
            </section>
        </div>
    </div>

    <script src="../js/settings.js"></script>
</body>

</html>