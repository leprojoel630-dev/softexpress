<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - HEADER GLOBAL
|--------------------------------------------------------------------------
| Header commun à toutes les pages du site.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| RACINE DU SITE
|--------------------------------------------------------------------------
*/

$siteUrl = '/SOFTEXPRESS';


/*
|--------------------------------------------------------------------------
| PAGE ACTUELLE
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$isConnected = isset($_SESSION['user_id']);

$userPrenom = trim(
    (string) ($_SESSION['user_prenom'] ?? '')
);

$userNom = trim(
    (string) ($_SESSION['user_nom'] ?? '')
);

$userEmail = trim(
    (string) ($_SESSION['user_email'] ?? '')
);

$userRole = strtolower(
    trim(
        (string) ($_SESSION['user_role'] ?? 'user')
    )
);


/*
|--------------------------------------------------------------------------
| NOM COMPLET
|--------------------------------------------------------------------------
*/

$userFullName = trim(
    $userPrenom . ' ' . $userNom
);

if ($userFullName === '') {
    $userFullName = 'Utilisateur';
}


/*
|--------------------------------------------------------------------------
| INITIALES
|--------------------------------------------------------------------------
*/

$userInitials = '';

if ($userPrenom !== '') {
    $userInitials .= strtoupper(
        mb_substr(
            $userPrenom,
            0,
            1
        )
    );
}

if ($userNom !== '') {
    $userInitials .= strtoupper(
        mb_substr(
            $userNom,
            0,
            1
        )
    );
}

if ($userInitials === '') {
    $userInitials = 'U';
}


/*
|--------------------------------------------------------------------------
| FONCTION D'ÉCHAPPEMENT
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {

    function e(?string $value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

?>


<!-- =========================================================
     CSS PRINCIPAL
     IMPORTANT : chemin absolu depuis la racine SOFTEXPRESS
========================================================= -->

<link
    rel="stylesheet"
    href="<?= $siteUrl ?>/css/style.css"
>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="site-header">

    <div class="container nav-wrap">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            class="brand"
            href="<?= $siteUrl ?>/index.php"
            aria-label="SOFTEXPRESS - Accueil"
        >

            <img
                src="<?= $siteUrl ?>/assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

        </a>


        <!-- =====================================================
             BOUTON MENU MOBILE
        ====================================================== -->

        <button
            class="nav-toggle"
            type="button"
            aria-label="Ouvrir le menu"
            aria-expanded="false"
            aria-controls="main-navigation"
        >

            <i></i>
            <i></i>
            <i></i>

        </button>


        <!-- =====================================================
             NAVIGATION PRINCIPALE
        ====================================================== -->

        <nav
            class="main-nav"
            id="main-navigation"
            aria-label="Navigation principale"
        >


            <!-- ACCUEIL -->

            <a
                href="<?= $siteUrl ?>/index.php"
                class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
            >
                Accueil
            </a>


            <!-- À PROPOS -->

            <a
                href="<?= $siteUrl ?>/pages/apropos.php"
                class="<?= $currentPage === 'apropos.php' ? 'active' : '' ?>"
            >
                À propos
            </a>


            <!-- FORMATIONS -->

            <a
                href="<?= $siteUrl ?>/pages/formations.php"
                class="<?= in_array($currentPage, ['formations.php', 'formation-details.php'], true) ? 'active' : '' ?>"
            >
                Formations
            </a>


            <!-- PRODUITS -->

            <a
                href="<?= $siteUrl ?>/pages/produits.php"
                class="<?= in_array($currentPage, ['produits.php', 'produit-details.php'], true) ? 'active' : '' ?>"
            >
                Produits
            </a>


            <!-- MAINTENANCE -->

            <a
                href="<?= $siteUrl ?>/pages/maintenance.php"
                class="<?= $currentPage === 'maintenance.php' ? 'active' : '' ?>"
            >
                Maintenance
            </a>


            <!-- ACTUALITÉS -->

            <a
                href="<?= $siteUrl ?>/pages/actualites.php"
                class="<?= in_array($currentPage, ['actualites.php', 'actualite-details.php'], true) ? 'active' : '' ?>"
            >
                Actualités
            </a>


            <!-- CONTACT -->

            <a
                href="<?= $siteUrl ?>/pages/contact.php"
                class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>"
            >
                Contact
            </a>

        </nav>


        <!-- =====================================================
             ESPACE UTILISATEUR
        ====================================================== -->

        <div class="auth">


            <?php if ($isConnected): ?>


                <!-- =================================================
                     UTILISATEUR CONNECTÉ
                ================================================== -->

                <div class="user-menu">


                    <!-- =================================================
                         BOUTON PROFIL
                    ================================================== -->

                    <button
                        type="button"
                        class="user-profile"
                        aria-label="Ouvrir mon profil"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >

                        <span
                            class="user-avatar"
                            aria-hidden="true"
                        >
                            <?= e($userInitials) ?>
                        </span>

                    </button>


                    <!-- =================================================
                         MENU DÉROULANT
                    ================================================== -->

                    <div
                        class="user-dropdown"
                        role="menu"
                    >


                        <!-- INFORMATIONS UTILISATEUR -->

                        <div class="user-dropdown-header">

                            <span
                                class="user-avatar"
                                aria-hidden="true"
                            >
                                <?= e($userInitials) ?>
                            </span>


                            <div class="user-info-text">

                                <strong>
                                    <?= e($userFullName) ?>
                                </strong>


                                <?php if ($userEmail !== ''): ?>

                                    <small>
                                        <?= e($userEmail) ?>
                                    </small>

                                <?php endif; ?>


                                <small class="user-role">

                                    <?php if ($userRole === 'admin'): ?>

                                        Administrateur

                                    <?php else: ?>

                                        Utilisateur

                                    <?php endif; ?>

                                </small>

                            </div>

                        </div>


                        <!-- =================================================
                             ADMINISTRATION
                        ================================================== -->

                        <?php if ($userRole === 'admin'): ?>

                            <a
                                href="<?= $siteUrl ?>/admin/index.php"
                                class="profile-admin"
                                role="menuitem"
                            >

                                <span aria-hidden="true">
                                    ⚙
                                </span>

                                Administration

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             DÉCONNEXION
                        ================================================== -->

                        <a
                            href="<?= $siteUrl ?>/auth/deconnexion.php"
                            class="profile-logout"
                            role="menuitem"
                        >

                            <span aria-hidden="true">
                                ↪
                            </span>

                            Déconnexion

                        </a>

                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     VISITEUR
                ================================================== -->

                <a
                    class="btn outline"
                    href="<?= $siteUrl ?>/auth/connexion.php"
                >
                    Connexion
                </a>


                <a
                    class="btn orange"
                    href="<?= $siteUrl ?>/auth/inscription.php"
                >
                    Inscription
                </a>


            <?php endif; ?>


        </div>

    </div>

</header>