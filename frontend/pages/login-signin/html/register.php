<?php
session_start();
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Memora Inscription</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="../../css/style.css" />
</head>

<body>
    <div class="app-container">
        <div class="visual-side">
            <img src="../../../assets/IMG/assets.svg" alt="Photos Memora" class="cards-image" />
        </div>

        <div class="form-side">
            <div class="form-wrapper">
                <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora" class="logo-image" />

                <?php if (isset($_SESSION['error'])): ?>
                <div id="php-error-bridge" data-message="<?= htmlspecialchars($_SESSION['error']); ?>"
                    style="display: none;"></div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="../../../../backend/register_action.php" method="POST" enctype="multipart/form-data"
                    novalidate>
                    <div id="step-1" class="tunnel-step active">
                        <h2>Créer un compte !</h2>
                        <p class="subtitle">
                            Rejoignez Memora, créez vos albums et partagez-les avec vos
                            amis.
                        </p>

                        <div class="input-group">
                            <label for="name">Nom complet</label>
                            <input type="text" id="name" name="display_name" placeholder="Prénom et nom" required />
                        </div>

                        <div class="input-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" placeholder="E-mail" required />
                        </div>

                        <div class="input-group">
                            <label for="reg-password">Mot de passe</label>
                            <div class="password-wrapper">
                                <input type="password" id="reg-password" name="password"
                                    placeholder="Au moins 14 caractères" required />
                                <button type="button" class="toggle-password"></button>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirm-password">Confirmer votre mot de passe</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm-password" placeholder="Mot de passe" required />
                                <button type="button" class="toggle-password"></button>
                            </div>
                        </div>

                        <div class="signup-step-avatar" id="avatar-step">
                            <h3 class="step-title">Choisissez votre photo de profil</h3>
                            <p class="step-subtitle">
                                Ajoutez un visage à vos souvenirs Memora (optionnel)
                            </p>

                            <div class="avatar-picker-container">
                                <div class="avatar-preview-circle" id="avatar-preview">
                                    <i class="fa-solid fa-user placeholder-icon"></i>
                                </div>

                                <button type="button" class="btn-secondary" id="btn-browse-avatar">
                                    Choisir une image
                                </button>
                                <input type="file" id="avatar-file-input" name="avatar" accept="image/png, image/jpeg"
                                    hidden />
                            </div>
                        </div>

                        <button type="button" class="btn-primary" id="btn-to-step-2">
                            Suivant
                        </button>

                        <p class="switch-mode">
                            Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
                        </p>
                    </div>

                    <div id="step-2" class="tunnel-step">
                        <h2>Choisissez votre pseudonyme !</h2>
                        <p class="subtitle">
                            Ce nom unique permettra à vos amis de vous trouver sur
                            l'application.
                        </p>

                        <div class="input-group" style="margin-top: 40px">
                            <label for="username">Pseudonyme</label>
                            <input type="text" id="username" name="username" placeholder="Écrivez votre pseudo..."
                                required />
                        </div>

                        <button type="submit" class="btn-primary">S'inscrire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/validation.js"></script>
    <script src="../js/signin.js"></script>
</body>

</html>