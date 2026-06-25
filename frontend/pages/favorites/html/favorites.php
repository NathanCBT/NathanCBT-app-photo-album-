<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/pages/login-signin/html/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoris</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../../dashboard/css/dashboard.css" />
    <link rel="stylesheet" href="../css/favorites.css">
</head>

<body>

    <header class="main-header">
        <div class="header-left">
            <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora Logo" class="header-logo" />
        </div>

        <nav class="header-center-nav">
            <a href="../../dashboard/html/dashboard.php" class="nav-item" title="Accueil">
                <i class="fa-solid fa-house nav-icon"></i>
            </a>
            <a href="../../search/html/search.php" class="nav-item" title="Recherche">
                <i class="fa-solid fa-magnifying-glass nav-icon"></i>
            </a>
            <a href="../../invitation/html/invitation.php" class="nav-item" title="Invitations">
                <i class="fa-solid fa-user-group nav-icon"></i>
            </a>
        </nav>

        <div class="header-right">
            <a href="../../profile/html/profile-user.php" class="header-avatar-link" title="Mon Profil">
                <div class="user-avatar-small"
                    style="background-image: url('/<?= $_SESSION['avatar'] ?? 'assets/IMG/default-avatar.svg' ?>'); background-size: cover;">
                </div>
            </a>
        </div>
    </header>

    <main class="favorites-main-layout">
        <header class="favorites-header">
            <div class="header-title-row">
                <i class="fa-regular fa-heart heart-icon-header"></i>
                <h1>Mes favoris</h1>
            </div>
            <p class="subtitle">Toutes les photos que vous avez ajoutées à vos favoris.</p>
        </header>

        <hr class="header-separator">

        <section class="favorites-content">
            <h2 class="section-title">Photos (<span id="favorites-count">0</span>)</h2>

            <div id="favorites-grid" class="photos-grid">
            </div>
        </section>
    </main>

    <script src="../js/favorites.js"></script>
</body>

</html>