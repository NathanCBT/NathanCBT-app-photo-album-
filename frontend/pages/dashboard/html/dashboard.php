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
    <title>Tableau de bord</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/dashboard.css" />
</head>

<body>
    <header class="main-header">
        <div class="header-left">
            <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora Logo" class="header-logo" />
        </div>

        <nav class="header-center-nav">
            <a href="dashboard.php" class="nav-item active" title="Accueil">
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

    <main class="dashboard-main-layout">
        <div class="dashboard-top-grid">
            <section class="dashboard-panel panel-albums">
                <div class="panel-header">
                    <h3>Albums récents</h3>
                    <a href="../../album/html/mes-albums.php" class="link-see-all">Voir tout &gt;</a>
                </div>
                <div class="albums-row-scroll" id="recent-albums-container"></div>
            </section>

            <section class="dashboard-panel panel-invitations">
                <div class="panel-header">
                    <h3>Invitations</h3>
                    <a href="../../invitation/html/invitation.php" class="link-see-all">Voir tout &gt;</a>
                </div>
                <div class="invitations-list-vertical" id="invitations-container"></div>
            </section>
        </div>

        <section class="dashboard-panel panel-favorites">
            <div class="panel-header">
                <h3>Photos favorites</h3>
                <a href="../../favorites/html/favorites.php" class="link-see-all">Voir tout &gt;</a>
            </div>
            <div class="favorites-grid-layout" id="favorites-photos-container"></div>
        </section>
    </main>

    <script src="../js/dashboard.js"></script>
</body>

</html>