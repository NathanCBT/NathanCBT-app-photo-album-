<?php
session_start();
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Memora Connexion</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="../../css/style.css" />
</head>

<body>
    <?php if (isset($_SESSION['error'])): ?>
    <div id="php-error-bridge" data-message="<?= htmlspecialchars($_SESSION['error']); ?>" style="display: none;"></div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="app-container">
        <div class="visual-side">
            <img src="../../../assets/IMG/assets.svg" alt="Photos Memora" class="cards-image" />
        </div>

        <div class="form-side">
            <div class="form-wrapper">
                <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora" class="logo-image" />

                <h2>Bienvenue !</h2>
                <p class="subtitle">Connectez-vous pour retrouver tous vos albums.</p>

                <form id="form-login" action="../../../../backend/login_action.php" method="POST" novalidate>
                    <div class="input-group">
                        <label for="email">E-mail ou Pseudonyme</label>
                        <input type="text" id="email" name="identifier" placeholder="E-mail ou pseudo" required />
                    </div>

                    <div class="input-group">
                        <label for="password">Mot de passe</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Mot de passe" required />
                            <button type="button" class="toggle-password"></button>
                        </div>
                    </div>

                    <div class="forgot-password-container">
                        <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="btn-primary">Se connecter</button>
                </form>

                <p class="switch-mode">
                    Pas encore de compte ? <a href="register.php">S'inscrire</a>
                </p>
            </div>
        </div>
    </div>

    <script src="../js/validation.js"></script>
    <script src="../js/login.js"></script>
</body>

</html>