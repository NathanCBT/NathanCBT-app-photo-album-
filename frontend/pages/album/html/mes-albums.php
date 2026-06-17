<?php
session_start();
// if the user is not logged in then return to the login screen
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
    <title>Mes Albums</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/album.css" />
</head>

<body>
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
            <button type="button" class="nav-item btn-notif" title="Notifications">
                <i class="fa-solid fa-bell nav-icon"></i>
            </button>
        </nav>
        <div class="header-right">
            <a href="../../profile/html/profile-user.php" class="header-avatar-link">
                <div class="user-avatar-small"
                    style="background-image: url('/<?= $_SESSION['avatar'] ?? 'assets/IMG/default-avatar.svg' ?>'); background-size: cover;">
                </div>
            </a>
        </div>
    </header>

    <main class="album-page-layout">
        <h1 class="page-title">Mes albums</h1>

        <div class="action-bar">
            <a href="create-album.php" class="btn-create-album">
                <i class="fa-solid fa-plus"></i> Créer un album
            </a>
        </div>

        <div class="albums-grid" id="all-albums-grid">
        </div>
    </main>

    <script src="../js/album.js"></script>
</body>

</html>