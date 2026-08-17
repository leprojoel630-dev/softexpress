<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - DÉTAIL D'UNE ACTUALITÉ
|--------------------------------------------------------------------------
| Table :
| actualites
|
| id
| titre
| contenu
| image
| auteur_id
| date_publication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| FONCTION D'ÉCHAPPEMENT
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| IMAGE ACTUALITÉ
|--------------------------------------------------------------------------
*/

function getImage(?string $image): string
{
    $image = trim((string)$image);

    /*
     * Image par défaut
     */
    $imageDefaut =
        '../assets/images/actualites/actualite1.jpg';


    if ($image === '') {
        return $imageDefaut;
    }


    /*
     * On récupère uniquement le nom du fichier
     */
    $file = basename($image);


    /*
     * Dossier actualités
     */
    $path1 =
        __DIR__ .
        '/../assets/images/actualites/' .
        $file;


    if (is_file($path1)) {

        return
            '../assets/images/actualites/' .
            $file;
    }


    /*
     * Dossier images principal
     */
    $path2 =
        __DIR__ .
        '/../assets/images/' .
        $file;


    if (is_file($path2)) {

        return
            '../assets/images/' .
            $file;
    }


    /*
     * Sécurité
     */
    return $imageDefaut;
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatDate(?string $date): string
{
    if (!$date) {
        return '';
    }


    $time = strtotime($date);


    if ($time === false) {
        return '';
    }


    return date(
        'd/m/Y à H:i',
        $time
    );
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$connecte = false;

$userPrenom = '';
$userNom = '';
$userEmail = '';
$userRole = '';


/*
|--------------------------------------------------------------------------
| SESSION PRINCIPALE
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {

    $connecte = true;

    $userPrenom = trim(
        (string)(
            $_SESSION['user_prenom'] ?? ''
        )
    );

    $userNom = trim(
        (string)(
            $_SESSION['user_nom'] ?? ''
        )
    );

    $userEmail = trim(
        (string)(
            $_SESSION['user_email'] ?? ''
        )
    );

    $userRole = trim(
        (string)(
            $_SESSION['user_role'] ?? ''
        )
    );
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC $_SESSION['user']
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    if (
        isset($_SESSION['user']) &&
        is_array($_SESSION['user'])
    ) {

        $user = $_SESSION['user'];


        $userPrenom = trim(
            (string)(
                $user['prenom'] ?? ''
            )
        );


        $userNom = trim(
            (string)(
                $user['nom'] ?? ''
            )
        );


        $userEmail = trim(
            (string)(
                $user['email'] ?? ''
            )
        );


        $userRole = trim(
            (string)(
                $user['role'] ?? ''
            )
        );


        if (
            $userPrenom !== '' ||
            $userNom !== '' ||
            $userEmail !== ''
        ) {

            $connecte = true;
        }
    }
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC LES ANCIENNES VARIABLES DE SESSION
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    $ancienNom = trim(
        (string)(
            $_SESSION['nom'] ?? ''
        )
    );


    $ancienPrenom = trim(
        (string)(
            $_SESSION['prenom'] ?? ''
        )
    );


    $ancienEmail = trim(
        (string)(
            $_SESSION['email'] ?? ''
        )
    );


    $ancienRole = trim(
        (string)(
            $_SESSION['role'] ?? ''
        )
    );


    if (
        $ancienNom !== '' ||
        $ancienPrenom !== '' ||
        $ancienEmail !== ''
    ) {

        $connecte = true;

        $userNom = $ancienNom;

        $userPrenom = $ancienPrenom;

        $userEmail = $ancienEmail;

        $userRole = $ancienRole;
    }
}


/*
|--------------------------------------------------------------------------
| NOM COMPLET
|--------------------------------------------------------------------------
*/

$nomComplet = trim(
    $userPrenom .
    ' ' .
    $userNom
);


if ($nomComplet === '') {

    $nomComplet = 'Utilisateur';
}


/*
|--------------------------------------------------------------------------
| INITIALES
|--------------------------------------------------------------------------
*/

$initiales = '';


if ($userPrenom !== '') {

    $initiales .= mb_substr(
        $userPrenom,
        0,
        1
    );
}


if ($userNom !== '') {

    $initiales .= mb_substr(
        $userNom,
        0,
        1
    );
}


if ($initiales === '') {

    if ($userEmail !== '') {

        $initiales =
            mb_substr(
                $userEmail,
                0,
                1
            );

    } else {

        $initiales = 'U';
    }
}


$initiales = mb_strtoupper(
    $initiales
);


/*
|--------------------------------------------------------------------------
| ID DE L'ACTUALITÉ
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id || $id <= 0) {

    header(
        'Location: actualites.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$actualite = null;

$erreurChargement = false;


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DE L'ACTUALITÉ
|--------------------------------------------------------------------------
*/

try {


    /*
    |--------------------------------------------------------------------------
    | PDO
    |--------------------------------------------------------------------------
    */

    if (
        isset($pdo) &&
        $pdo instanceof PDO
    ) {

        $sql = "
            SELECT
                a.id,
                a.titre,
                a.contenu,
                a.image,
                a.auteur_id,
                a.date_publication,

                CONCAT(
                    COALESCE(u.prenom, ''),
                    CASE
                        WHEN
                            COALESCE(u.prenom, '') <> ''
                            AND
                            COALESCE(u.nom, '') <> ''
                        THEN ' '
                        ELSE ''
                    END,
                    COALESCE(u.nom, '')
                ) AS auteur

            FROM actualites a

            LEFT JOIN utilisateurs u
                ON u.id = a.auteur_id

            WHERE a.id = ?

            LIMIT 1
        ";


        $stmt = $pdo->prepare(
            $sql
        );


        $stmt->execute([
            $id
        ]);


        $actualite =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MYSQLI
    |--------------------------------------------------------------------------
    */

    elseif (
        isset($conn) &&
        $conn instanceof mysqli
    ) {

        $sql = "
            SELECT
                a.id,
                a.titre,
                a.contenu,
                a.image,
                a.auteur_id,
                a.date_publication,

                CONCAT(
                    COALESCE(u.prenom, ''),
                    CASE
                        WHEN
                            COALESCE(u.prenom, '') <> ''
                            AND
                            COALESCE(u.nom, '') <> ''
                        THEN ' '
                        ELSE ''
                    END,
                    COALESCE(u.nom, '')
                ) AS auteur

            FROM actualites a

            LEFT JOIN utilisateurs u
                ON u.id = a.auteur_id

            WHERE a.id = ?

            LIMIT 1
        ";


        $stmt = $conn->prepare(
            $sql
        );


        if ($stmt) {

            $stmt->bind_param(
                'i',
                $id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if ($result) {

                $actualite =
                    $result->fetch_assoc();
            }


            $stmt->close();
        }
    }


} catch (Throwable $e) {

    /*
     * On ne montre jamais le détail
     * de l'erreur SQL au visiteur.
     */

    $erreurChargement = true;
}


/*
|--------------------------------------------------------------------------
| ACTUALITÉ INTROUVABLE OU ERREUR
|--------------------------------------------------------------------------
*/

if (
    $erreurChargement ||
    !$actualite
) {

    ?>

    <!DOCTYPE html>

    <html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <meta
            name="description"
            content="Actualité SOFTEXPRESS"
        >

        <title>
            Actualité introuvable | SOFTEXPRESS
        </title>


        <link
            rel="stylesheet"
            href="../assets/css/style.css"
        >


        <link
            rel="stylesheet"
            href="../assets/css/actualite-details.css"
        >


        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

    </head>


    <body>


        <header class="site-header">

            <div class="container nav-wrap">

                <a
                    class="brand"
                    href="../index.php"
                >

                    <img
                        src="../assets/images/logo.png"
                        alt="SOFTEXPRESS"
                    >

                </a>

            </div>

        </header>


        <main class="article-not-found">

            <div>

                <div
                    style="
                        font-size:52px;
                        color:#F99D1C;
                        margin-bottom:20px;
                    "
                >

                    <i
                        class="fa-regular fa-newspaper"
                    ></i>

                </div>


                <h1>

                    Actualité introuvable

                </h1>


                <p>

                    Cette actualité n'existe pas
                    ou n'est plus disponible.

                </p>


                <a
                    href="actualites.php"
                    class="article-back"
                >

                    <i
                        class="fa-solid fa-arrow-left"
                    ></i>

                    Retour aux actualités

                </a>

            </div>

        </main>


        <footer>

            <div class="bottom">

                <div class="container">

                    © <?= date('Y') ?>
                    SOFTEXPRESS —
                    Tous droits réservés.

                </div>

            </div>

        </footer>


    </body>

    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| DONNÉES DE L'ARTICLE
|--------------------------------------------------------------------------
*/

$titre = trim(
    (string)(
        $actualite['titre'] ??
        'Actualité'
    )
);


$contenu = (string)(
    $actualite['contenu'] ??
    ''
);


$image = $actualite['image'] ?? null;


$datePublication =
    $actualite['date_publication']
    ?? null;


$auteur = trim(
    (string)(
        $actualite['auteur'] ??
        ''
    )
);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <meta
        name="description"
        content="<?= e($titre) ?>"
    >


    <title>

        <?= e($titre) ?>

        | SOFTEXPRESS

    </title>


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         CSS ACTUALITÉ
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/actualite-details.css"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         PROFIL UTILISATEUR
         Même système que demande-maintenance.php
    ====================================================== -->

    <style>

        .article-page .user-menu {

            position: relative;

            display: flex;

            align-items: center;
        }


        .article-page .user-profile {

            border: none;

            background: transparent;

            padding: 4px;

            display: flex;

            align-items: center;

            gap: 8px;

            cursor: pointer;

            font-family: inherit;
        }


        .article-page .user-avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #F99D1C,
                    #00A3E0
                );

            font-size: 13px;

            font-weight: 800;

            text-transform: uppercase;

            box-shadow:
                0 4px 12px
                rgba(0,163,224,.20);
        }


        .article-page .user-dropdown {

            position: absolute;

            top: calc(100% + 10px);

            right: 0;

            width: 280px;

            padding: 10px;

            background: #ffffff;

            border: 1px solid #e8edf1;

            border-radius: 14px;

            box-shadow:
                0 15px 40px
                rgba(0,0,0,.12);

            z-index: 99999;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-8px);

            transition:
                opacity .2s ease,
                visibility .2s ease,
                transform .2s ease;
        }


        .article-page .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .article-page .user-dropdown-header {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px;
        }


        .article-page .user-avatar-large {

            width: 48px;

            height: 48px;
        }


        .article-page .user-info-text {

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 4px;
        }


        .article-page .user-info-text strong {

            color: #111827;

            font-size: 14px;

            font-weight: 800;
        }


        .article-page .user-info-text span {

            color: #687385;

            font-size: 12px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .article-page .user-info-text small {

            color: #00A3E0;

            font-size: 11px;

            font-weight: 700;
        }


        .article-page .user-dropdown-divider {

            height: 1px;

            background: #edf0f3;

            margin: 6px 4px;
        }


        .article-page .user-dropdown-item {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 11px 12px;

            border-radius: 9px;

            color: #394150;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            transition:
                background .2s ease,
                color .2s ease;
        }


        .article-page .user-dropdown-item i {

            width: 18px;

            text-align: center;

            color: #00A3E0;
        }


        .article-page .user-dropdown-item:hover {

            background: #f3f9fc;

            color: #00A3E0;
        }


        .article-page .profile-admin i {

            color: #F99D1C;
        }


        .article-page .profile-logout {

            color: #d13b3b;
        }


        .article-page .profile-logout i {

            color: #d13b3b;
        }


        .article-page .profile-logout:hover {

            background: #fff1f1;

            color: #c62828;
        }


        @media (max-width: 600px) {

            .article-page .user-dropdown {

                position: fixed;

                top: 75px;

                right: 15px;

                width: calc(100vw - 30px);

                max-width: 280px;
            }

        }

    </style>

</head>


<body class="article-page">


<!-- =========================================================
     NAVIGATION
========================================================= -->

<header class="site-header">

    <div class="container nav-wrap">


        <!-- LOGO -->

        <a
            class="brand"
            href="../index.php"
        >

            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

        </a>


        <!-- MENU MOBILE -->

        <button
            class="nav-toggle"
            type="button"
            aria-label="Ouvrir le menu"
            aria-expanded="false"
        >

            <i></i>

            <i></i>

            <i></i>

        </button>


        <!-- NAVIGATION -->

        <nav class="main-nav">

            <a href="../index.php">
                Accueil
            </a>


            <a href="apropos.php">
                À propos
            </a>


            <a href="formations.php">
                Formations
            </a>


            <a href="produits.php">
                Produits
            </a>


            <a href="maintenance.php">
                Maintenance
            </a>


            <a
                href="actualites.php"
                class="active"
            >
                Actualités
            </a>


            <a href="contact.php">
                Contact
            </a>

        </nav>


        <!-- =================================================
             ESPACE UTILISATEUR
        ================================================== -->

        <div class="auth">


            <?php if ($connecte): ?>


                <!-- UTILISATEUR CONNECTÉ -->

                <div class="user-menu">


                    <!-- BOUTON PROFIL -->

                    <button
                        type="button"
                        class="user-profile"
                        id="articleUserProfileButton"
                        aria-label="Ouvrir mon profil"
                        aria-expanded="false"
                    >

                        <span class="user-avatar">

                            <?= e(
                                $initiales
                            ) ?>

                        </span>

                    </button>


                    <!-- MENU DÉROULANT -->

                    <div
                        class="user-dropdown"
                        id="articleUserDropdown"
                    >


                        <!-- INFORMATIONS UTILISATEUR -->

                        <div class="user-dropdown-header">


                            <span
                                class="user-avatar user-avatar-large"
                            >

                                <?= e(
                                    $initiales
                                ) ?>

                            </span>


                            <div class="user-info-text">


                                <strong>

                                    <?= e(
                                        $nomComplet
                                    ) ?>

                                </strong>


                                <?php if ($userEmail !== ''): ?>

                                    <span>

                                        <?= e(
                                            $userEmail
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if ($userRole !== ''): ?>

                                    <small>

                                        <?= e(
                                            ucfirst(
                                                $userRole
                                            )
                                        ) ?>

                                    </small>

                                <?php endif; ?>


                            </div>

                        </div>


                        <div
                            class="user-dropdown-divider"
                        ></div>


                        <!-- ADMINISTRATION -->

                        <?php if (
                            strtolower(
                                $userRole
                            ) === 'admin'
                        ): ?>

                            <a
                                href="../admin/index.php"
                                class="user-dropdown-item profile-admin"
                            >

                                <i
                                    class="fa-solid fa-gear"
                                ></i>

                                Administration

                            </a>

                        <?php endif; ?>


                        <!-- DÉCONNEXION -->

                        <a
                            href="../auth/deconnexion.php"
                            class="user-dropdown-item profile-logout"
                        >

                            <i
                                class="fa-solid fa-right-from-bracket"
                            ></i>

                            Déconnexion

                        </a>


                    </div>

                </div>


            <?php else: ?>


                <!-- UTILISATEUR NON CONNECTÉ -->

                <a
                    class="btn outline"
                    href="../auth/connexion.php"
                >

                    Connexion

                </a>


                <a
                    class="btn orange"
                    href="../auth/inscription.php"
                >

                    Inscription

                </a>


            <?php endif; ?>


        </div>

    </div>

</header>


<!-- =========================================================
     CONTENU PRINCIPAL
========================================================= -->

<main>


    <section class="article-section">

        <div class="container">


            <div class="article-layout">


                <!-- =================================================
                     ARTICLE PRINCIPAL
                ================================================== -->

                <article class="article-main">


                    <!-- IMAGE -->

                    <div class="article-image">

                        <img
                            src="<?= e(
                                getImage(
                                    $image
                                )
                            ) ?>"
                            alt="<?= e(
                                $titre
                            ) ?>"
                        >

                    </div>


                    <!-- META -->

                    <div class="article-meta">


                        <?php if ($datePublication): ?>

                            <span>

                                <i
                                    class="fa-regular fa-calendar"
                                ></i>

                                <?= e(
                                    formatDate(
                                        $datePublication
                                    )
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($auteur !== ''): ?>

                            <span>

                                <i
                                    class="fa-regular fa-user"
                                ></i>

                                <?= e(
                                    $auteur
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <span class="article-status">

                            <i
                                class="fa-solid fa-circle-check"
                            ></i>

                            Publiée

                        </span>


                    </div>


                    <!-- TITRE -->

                    <h1 class="article-title">

                        <?= e(
                            $titre
                        ) ?>

                    </h1>


                    <!-- CONTENU -->

                    <div class="article-content">

                        <?= nl2br(
                            e(
                                $contenu
                            )
                        ) ?>

                    </div>


                    <!-- RETOUR -->

                    <div class="article-footer-link">

                        <a
                            href="actualites.php"
                            class="article-back"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Retour aux actualités

                        </a>

                    </div>


                </article>


                <!-- =================================================
                     COLONNE DROITE
                ================================================== -->

                <aside class="article-sidebar">


                    <!-- CERCLE / CARTE INFORMATIONS -->

                    <div
                        class="article-sidebar-card"
                    >


                        <div class="sidebar-icon">

                            <i
                                class="fa-solid fa-bullhorn"
                            ></i>

                        </div>


                        <h3>

                            SOFTEXPRESS

                        </h3>


                        <p>

                            Formation, équipements
                            informatiques et maintenance.

                        </p>


                        <a
                            href="contact.php"
                            class="sidebar-btn"
                        >

                            Nous contacter

                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>

                        </a>


                    </div>


                    <!-- À DÉCOUVRIR -->

                    <div
                        class="article-sidebar-card second"
                    >


                        <h3>

                            À découvrir

                        </h3>


                        <a
                            href="formations.php"
                        >

                            <i
                                class="fa-solid fa-graduation-cap"
                            ></i>

                            Nos formations

                        </a>


                        <a
                            href="produits.php"
                        >

                            <i
                                class="fa-solid fa-laptop"
                            ></i>

                            Nos produits

                        </a>


                        <a
                            href="maintenance.php"
                        >

                            <i
                                class="fa-solid fa-screwdriver-wrench"
                            ></i>

                            Maintenance

                        </a>


                    </div>


                </aside>


            </div>

        </div>

    </section>


    <!-- =========================================================
         CTA
    ========================================================== -->

    <section class="article-cta">


        <div
            class="container article-cta-inner"
        >


            <div>


                <span>

                    SOFTEXPRESS

                </span>


                <h2>

                    Besoin de plus d'informations ?

                </h2>


                <p>

                    Notre équipe est à votre disposition.

                </p>


            </div>


            <a
                href="contact.php"
                class="article-cta-btn"
            >

                Nous contacter

                <i
                    class="fa-solid fa-arrow-right"
                ></i>

            </a>


        </div>


    </section>


</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>


    <div class="container footer-grid">


        <!-- COLONNE 1 -->

        <div>


            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >


            <p>

                Formation, équipements
                informatiques et maintenance.

            </p>


        </div>


        <!-- COLONNE 2 -->

        <div>


            <h3>

                Navigation

            </h3>


            <a href="../index.php">

                Accueil

            </a>


            <a href="apropos.php">

                À propos

            </a>


            <a href="formations.php">

                Formations

            </a>


            <a href="produits.php">

                Produits

            </a>


        </div>


        <!-- COLONNE 3 -->

        <div>


            <h3>

                Services

            </h3>


            <a href="maintenance.php">

                Maintenance

            </a>


            <a href="actualites.php">

                Actualités

            </a>


            <a href="contact.php">

                Contact

            </a>


            <a href="../auth/connexion.php">

                Connexion

            </a>


        </div>


    </div>


    <div class="bottom">


        <div class="container">


            © <?= date('Y') ?>
            SOFTEXPRESS —
            Tous droits réservés.


        </div>


    </div>


</footer>


<!-- =========================================================
     JAVASCRIPT PRINCIPAL
========================================================= -->

<script
    src="../assets/js/main.js"
></script>


<!-- =========================================================
     JAVASCRIPT PROFIL
     Même comportement que demande-maintenance.php
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const button =
            document.getElementById(
                'articleUserProfileButton'
            );


        const dropdown =
            document.getElementById(
                'articleUserDropdown'
            );


        /*
        |--------------------------------------------------------------------------
        | Si aucun utilisateur connecté
        |--------------------------------------------------------------------------
        */

        if (
            !button ||
            !dropdown
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | OUVRIR / FERMER LE MENU
        |--------------------------------------------------------------------------
        */

        button.addEventListener(
            'click',
            function (event) {


                event.stopPropagation();


                const ouvert =
                    dropdown.classList.toggle(
                        'show'
                    );


                button.setAttribute(
                    'aria-expanded',
                    ouvert
                        ? 'true'
                        : 'false'
                );


            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLIC EN DEHORS
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {


                if (
                    !dropdown.contains(
                        event.target
                    )
                    &&
                    !button.contains(
                        event.target
                    )
                ) {


                    dropdown.classList.remove(
                        'show'
                    );


                    button.setAttribute(
                        'aria-expanded',
                        'false'
                    );


                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | TOUCHE ESC
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {


                if (
                    event.key === 'Escape'
                ) {


                    dropdown.classList.remove(
                        'show'
                    );


                    button.setAttribute(
                        'aria-expanded',
                        'false'
                    );


                }

            }
        );


    }
);

</script>


</body>

</html>