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
    <title>Recherche</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/icons/faviconMemora.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="../css/search.css" />
</head>

<body>
    <header class="main-header">
        <div class="header-left">
            <a href="../../dashboard/html/dashboard.php" class="logo-link">
                <img src="../../../assets/IMG/LogoMemora.svg" alt="Memora Logo" class="header-logo" />
            </a>
        </div>

        <nav class="header-center-nav">
            <a href="../../dashboard/html/dashboard.php" class="nav-item" title="Accueil">
                <i class="fa-solid fa-house nav-icon"></i>
            </a>
            <a href="../../search/html/search.php" class="nav-item active" title="Recherche">
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

    <div class="search-subbar-container">
        <div class="search-bar-inner">
            <i class="fa-solid fa-magnifying-glass subbar-search-icon"></i>
            <input type="text" id="global-search-input" placeholder="Rechercher une photo par titre, album..." />
            <button type="button" id="btn-submit-search" class="btn-filter-toggle">
                Filtres <i class="fa-solid fa-sliders"></i>
            </button>
        </div>
    </div>

    <div class="search-layout-container">

        <aside class="search-sidebar">
            <h2>Filtres</h2>

            <form id="form-search-filters">
                <div class="filter-section">
                    <h3>Date</h3>
                    <div class="date-range-group">
                        <div class="date-field">
                            <label for="filter-date-from">Du</label>
                            <input type="date" id="filter-date-from" />
                        </div>
                        <div class="date-field">
                            <label for="filter-date-to">Au</label>
                            <input type="date" id="filter-date-to" />
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Étiquettes</h3>
                    <div class="checkbox-list">
                        <?php
                        $tags = [
                            "Famille", "Amis", "Couple", "Voyage", "Sorties", 
                            "Nature", "Paysages", "Animaux", "Ville", "Plage & Mer", 
                            "Montagne", "Musées & Châteaux", "Parc d'attractions", 
                            "Voitures & Véhicules", "Soirées & Événements"
                        ];
                        foreach ($tags as $tag): 
                        ?>
                        <label class="custom-checkbox">
                            <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag) ?>" />
                            <span class="checkbox-box"></span>
                            <span class="checkbox-label"><?= $tag ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Catégorie</h3>
                    <div class="radio-list">
                        <label class="custom-radio">
                            <input type="radio" name="album_scope" value="owned" checked />
                            <span class="radio-box"></span>
                            <span class="radio-label">Mes Albums</span>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="album_scope" value="all" />
                            <span class="radio-box"></span>
                            <span class="radio-label">Tous les Albums (Publics / Collabs)</span>
                        </label>
                    </div>
                </div>

                <button type="submit" id="btn-apply-filters" class="btn-search-submit">
                    Rechercher et appliquer
                </button>
            </form>
        </aside>

        <main class="search-main-content">
            <div class="results-header">
                <h2>Résultats (<span id="results-count">0</span>)</h2>
            </div>

            <div id="search-results-grid" class="photos-grid">
                <div class="empty-search-state">
                    <p>Saisissez un mot-clé ou sélectionnez des filtres pour démarrer la recherche.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/search.js"></script>
</body>

</html>