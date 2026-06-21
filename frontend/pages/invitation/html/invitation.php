<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /frontend/pages/login-signin/html/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invitations</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/invitation.css" />
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
            <a href="../../search/html/search.html" class="nav-item" title="Recherche">
                <i class="fa-solid fa-magnifying-glass nav-icon"></i>
            </a>
            <a href="invitation.php" class="nav-item active" title="Invitations">
                <i class="fa-solid fa-user-group nav-icon"></i>
            </a>
            <button type="button" class="nav-item btn-notif" title="Notifications">
                <i class="fa-solid fa-bell nav-icon"></i>
            </button>
        </nav>

        <div class="header-right">
            <a href="../../profile/html/profile-user.php" class="header-avatar-link" title="Mon Profil">
                <div class="user-avatar-small"
                    style="background-image: url('/<?= $_SESSION['avatar'] ?? 'assets/IMG/default-avatar.svg' ?>'); background-size: cover;">
                </div>
            </a>
        </div>
    </header>

    <main class="invitation-main-layout">
        <section class="invitation-panel">
            <h2>Rechercher des personnes</h2>

            <div class="search-bar-wrapper">
                <i class="fa-solid fa-magnifying-glass search-input-icon"></i>
                <input type="text" id="user-search-input" placeholder="Rechercher un utilisateur..."
                    autocomplete="off" />
            </div>

            <h3 class="section-subtitle">Résultat de recherche</h3>
            <div class="search-results-container" id="search-results-container">
                <p class="info-text">Saisissez un nom pour démarrer la recherche.</p>
            </div>
        </section>

        <section class="invitation-panel">
            <h2>Invitations reçues</h2>
            <div class="invitations-large-list" id="invitations-list-container">
            </div>
        </section>
    </main>

    <script src="../js/invitation.js"></script>
</body>

</html>