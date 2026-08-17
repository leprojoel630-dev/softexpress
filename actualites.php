<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - ACTUALITÉS
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

if (session_status() === PHP_SESSION_NONE) {
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

function imageActualite(?string $image): string
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
     * On récupère uniquement le nom du fichier.
     */

    $nomFichier = basename($image);


    /*
     * Dossier principal des actualités
     */

    $cheminActualite =
        __DIR__
        . '/../assets/images/actualites/'
        . $nomFichier;


    if (is_file($cheminActualite)) {

        return
            '../assets/images/actualites/'
            . $nomFichier;
    }


    /*
     * Deuxième emplacement possible
     */

    $cheminImages =
        __DIR__
        . '/../assets/images/'
        . $nomFichier;


    if (is_file($cheminImages)) {

        return
            '../assets/images/'
            . $nomFichier;
    }


    /*
     * Image de sécurité
     */

    return $imageDefaut;
}


/*
|--------------------------------------------------------------------------
| EXTRAIT
|--------------------------------------------------------------------------
*/

function extrait(
    ?string $texte,
    int $longueur = 180
): string {

    $texte = trim(
        strip_tags((string)$texte)
    );


    if ($texte === '') {

        return 'Aucune description disponible.';
    }


    if (mb_strlen($texte) <= $longueur) {

        return $texte;
    }


    return
        mb_substr(
            $texte,
            0,
            $longueur
        )
        . '...';
}


/*
|--------------------------------------------------------------------------
| DATE
|--------------------------------------------------------------------------
*/

function dateActualite(?string $date): string
{
    if (!$date) {
        return '';
    }


    $timestamp = strtotime($date);


    if ($timestamp === false) {
        return '';
    }


    return date(
        'd/m/Y',
        $timestamp
    );
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$actualites = [];

$erreur = false;


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$connecte = isset(
    $_SESSION['user_id']
);


$userPrenom = trim(
    (string)(
        $_SESSION['user_prenom']
        ?? ''
    )
);


$userNom = trim(
    (string)(
        $_SESSION['user_nom']
        ?? ''
    )
);


$userEmail = trim(
    (string)(
        $_SESSION['user_email']
        ?? ''
    )
);


$userRole = trim(
    (string)(
        $_SESSION['user_role']
        ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC $_SESSION['user']
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    if (
        isset($_SESSION['user'])
        &&
        is_array($_SESSION['user'])
    ) {

        $user = $_SESSION['user'];


        $userPrenom = trim(
            (string)(
                $user['prenom']
                ?? ''
            )
        );


        $userNom = trim(
            (string)(
                $user['nom']
                ?? ''
            )
        );


        $userEmail = trim(
            (string)(
                $user['email']
                ?? ''
            )
        );


        $userRole = trim(
            (string)(
                $user['role']
                ?? ''
            )
        );


        if (
            $userPrenom !== ''
            ||
            $userNom !== ''
            ||
            $userEmail !== ''
        ) {

            $connecte = true;
        }
    }
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC LES ANCIENNES VARIABLES DIRECTES
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    $ancienNom = trim(
        (string)(
            $_SESSION['nom']
            ?? ''
        )
    );


    $ancienPrenom = trim(
        (string)(
            $_SESSION['prenom']
            ?? ''
        )
    );


    $ancienEmail = trim(
        (string)(
            $_SESSION['email']
            ?? ''
        )
    );


    $ancienRole = trim(
        (string)(
            $_SESSION['role']
            ?? ''
        )
    );


    if (
        $ancienNom !== ''
        ||
        $ancienPrenom !== ''
        ||
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

$nomUtilisateur = trim(
    $userPrenom
    . ' '
    . $userNom
);


if ($nomUtilisateur === '') {

    $nomUtilisateur =
        'Utilisateur';
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


/*
|--------------------------------------------------------------------------
| SI AUCUNE INITIALE
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| MAJUSCULES
|--------------------------------------------------------------------------
*/

$initiales =
    mb_strtoupper(
        $initiales
    );


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES ACTUALITÉS
|--------------------------------------------------------------------------
*/

try {


    /*
    |--------------------------------------------------------------------------
    | PDO
    |--------------------------------------------------------------------------
    */

    if (
        isset($pdo)
        &&
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

            ORDER BY
                a.date_publication DESC,
                a.id DESC
        ";


        $stmt = $pdo->query(
            $sql
        );


        if ($stmt !== false) {

            $actualites =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MYSQLI
    |--------------------------------------------------------------------------
    */

    elseif (
        isset($conn)
        &&
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

            ORDER BY
                a.date_publication DESC,
                a.id DESC
        ";


        $result =
            $conn->query(
                $sql
            );


        if ($result !== false) {

            while (
                $row =
                    $result->fetch_assoc()
            ) {

                $actualites[] =
                    $row;
            }


            $result->free();
        }
    }


} catch (Throwable $e) {

    /*
     * On ne montre jamais
     * l'erreur SQL au visiteur.
     */

    $actualites = [];

    $erreur = true;
}

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
        content="Découvrez les actualités de SOFTEXPRESS."
    >


    <title>
        Actualités | SOFTEXPRESS
    </title>


    <!-- CSS PRINCIPAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS ACTUALITÉS -->

    <link
        rel="stylesheet"
        href="../assets/css/actualites.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         STYLE DU PROFIL UTILISATEUR
    ====================================================== -->

    <style>

        .request-page .user-menu {

            position: relative;

            display: flex;

            align-items: center;
        }


        .request-page .user-profile {

            border: none;

            background: transparent;

            padding: 4px;

            display: flex;

            align-items: center;

            gap: 8px;

            cursor: pointer;

            font-family: inherit;
        }


        .request-page .user-avatar {

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


        .request-page .user-dropdown {

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


        .request-page .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .request-page .user-dropdown-header {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px;
        }


        .request-page .user-avatar-large {

            width: 48px;

            height: 48px;
        }


        .request-page .user-info-text {

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 4px;
        }


        .request-page .user-info-text strong {

            color: #111827;

            font-size: 14px;

            font-weight: 800;
        }


        .request-page .user-info-text span {

            color: #687385;

            font-size: 12px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .request-page .user-info-text small {

            color: #00A3E0;

            font-size: 11px;

            font-weight: 700;
        }


        .request-page .user-dropdown-divider {

            height: 1px;

            background: #edf0f3;

            margin: 6px 4px;
        }


        .request-page .user-dropdown-item {

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


        .request-page .user-dropdown-item i {

            width: 18px;

            text-align: center;

            color: #00A3E0;
        }


        .request-page .user-dropdown-item:hover {

            background: #f3f9fc;

            color: #00A3E0;
        }


        .request-page .profile-admin i {

            color: #F99D1C;
        }


        .request-page .profile-logout {

            color: #d13b3b;
        }


        .request-page .profile-logout i {

            color: #d13b3b;
        }


        .request-page .profile-logout:hover {

            background: #fff1f1;

            color: #c62828;
        }


        @media (max-width: 600px) {

            .request-page .user-dropdown {

                position: fixed;

                top: 75px;

                right: 15px;

                width: calc(100vw - 30px);

                max-width: 280px;
            }
        }

    </style>

</head>


<body>


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
             AUTHENTIFICATION
        ================================================== -->

        <div class="auth">


            <?php if ($connecte): ?>


                <!-- UTILISATEUR CONNECTÉ -->

                <div class="user-menu">


                    <!-- BOUTON PROFIL -->

                    <button
                        type="button"
                        class="user-profile"
                        id="actualitesUserProfileButton"
                        aria-label="Ouvrir mon profil"
                        aria-expanded="false"
                    >

                        <span class="user-avatar">

                            <?= e($initiales) ?>

                        </span>

                    </button>


                    <!-- MENU DÉROULANT -->

                    <div
                        class="user-dropdown"
                        id="actualitesUserDropdown"
                    >


                        <!-- INFORMATIONS -->

                        <div class="user-dropdown-header">


                            <span class="user-avatar user-avatar-large">

                                <?= e($initiales) ?>

                            </span>


                            <div class="user-info-text">


                                <strong>

                                    <?= e(
                                        $nomUtilisateur
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


                        <div class="user-dropdown-divider"></div>


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
     BANNIÈRE
========================================================= -->

<section class="news-banner">


    <div
        class="news-banner-decoration decoration-one"
    ></div>


    <div
        class="news-banner-decoration decoration-two"
    ></div>


    <div class="container news-banner-content">


        <p class="news-eyebrow">

            SOFTEXPRESS • INFORMATIONS

        </p>


        <h1>

            NOS
            <span>ACTUALITÉS</span>

        </h1>


        <p>

            Retrouvez les dernières nouvelles,
            annonces et informations de SOFTEXPRESS.

        </p>


        <div class="news-breadcrumb">


            <a href="../index.php">

                Accueil

            </a>


            <span>›</span>


            <strong>

                Actualités

            </strong>


        </div>

    </div>

</section>


<!-- =========================================================
     CONTENU
========================================================= -->

<main>


    <section class="news-section">

        <div class="container">


            <!-- EN-TÊTE -->

            <div class="news-heading">


                <div>

                    <p class="news-label">

                        DERNIÈRES INFORMATIONS

                    </p>


                    <h2>

                        Découvrez nos actualités

                    </h2>


                    <p>

                        Restez informé des nouveautés,
                        formations, services et événements
                        proposés par SOFTEXPRESS.

                    </p>

                </div>


                <div class="news-count">

                    <strong>

                        <?= count($actualites) ?>

                    </strong>


                    <span>

                        actualité<?=

                            count($actualites) > 1
                                ? 's'
                                : ''

                        ?>

                    </span>

                </div>

            </div>


            <?php if ($erreur): ?>


                <!-- =================================================
                     ERREUR
                ================================================== -->

                <div class="news-empty">


                    <div class="empty-icon">

                        <i
                            class="fa-solid fa-circle-exclamation"
                        ></i>

                    </div>


                    <h2>

                        Impossible de charger les actualités

                    </h2>


                    <p>

                        Une erreur est survenue lors du
                        chargement des informations.

                    </p>


                    <a
                        href="actualites.php"
                        class="news-btn orange"
                    >

                        Réessayer

                    </a>

                </div>


            <?php elseif (!empty($actualites)): ?>


                <!-- =================================================
                     GRILLE
                ================================================== -->

                <div class="news-grid">


                    <?php foreach (
                        $actualites
                        as $actualite
                    ): ?>


                        <?php

                        $id = (int)(
                            $actualite['id']
                            ?? 0
                        );


                        $titre = trim(
                            (string)(
                                $actualite['titre']
                                ?? 'Actualité'
                            )
                        );


                        $contenu =
                            (string)(
                                $actualite['contenu']
                                ?? ''
                            );


                        $image =
                            $actualite['image']
                            ?? null;


                        $date =
                            $actualite[
                                'date_publication'
                            ]
                            ?? null;


                        $auteur = trim(
                            (string)(
                                $actualite['auteur']
                                ?? ''
                            )
                        );

                        ?>


                        <article
                            class="news-card"
                        >


                            <!-- IMAGE -->

                            <a
                                href="actualite-details.php?id=<?= $id ?>"
                                class="news-image"
                            >


                                <img
                                    src="<?= e(
                                        imageActualite(
                                            $image
                                        )
                                    ) ?>"
                                    alt="<?= e(
                                        $titre
                                    ) ?>"
                                    loading="lazy"
                                >


                                <span
                                    class="news-category"
                                >

                                    SOFTEXPRESS

                                </span>


                                <span
                                    class="news-image-overlay"
                                >

                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </span>


                            </a>


                            <!-- CONTENU -->

                            <div
                                class="news-content"
                            >


                                <!-- DATE / AUTEUR -->

                                <div
                                    class="news-meta"
                                >


                                    <span>

                                        <i
                                            class="fa-regular fa-calendar"
                                        ></i>


                                        <?= e(
                                            dateActualite(
                                                $date
                                            )
                                        ) ?>

                                    </span>


                                    <?php if (
                                        $auteur !== ''
                                    ): ?>

                                        <span>

                                            <i
                                                class="fa-regular fa-user"
                                            ></i>


                                            <?= e(
                                                $auteur
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                </div>


                                <!-- TITRE -->

                                <h3>


                                    <a
                                        href="actualite-details.php?id=<?= $id ?>"
                                    >

                                        <?= e(
                                            $titre
                                        ) ?>

                                    </a>


                                </h3>


                                <!-- EXTRAIT -->

                                <p>

                                    <?= e(
                                        extrait(
                                            $contenu
                                        )
                                    ) ?>

                                </p>


                                <!-- LIEN -->

                                <a
                                    href="actualite-details.php?id=<?= $id ?>"
                                    class="news-read-more"
                                >

                                    Lire l'article

                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </a>


                            </div>

                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- =================================================
                     AUCUNE ACTUALITÉ
                ================================================== -->

                <div class="news-empty">


                    <div class="empty-icon">

                        <i
                            class="fa-regular fa-newspaper"
                        ></i>

                    </div>


                    <p class="news-label">

                        ACTUALITÉS

                    </p>


                    <h2>

                        Aucune actualité pour le moment

                    </h2>


                    <p>

                        Les nouvelles publications
                        de SOFTEXPRESS apparaîtront
                        automatiquement ici.

                    </p>


                    <a
                        href="../index.php"
                        class="news-btn orange"
                    >

                        Retour à l'accueil

                    </a>


                </div>


            <?php endif; ?>


        </div>

    </section>


    <!-- =========================================================
         CTA
    ========================================================= -->

    <section class="news-cta">


        <div class="container news-cta-inner">


            <div>


                <p>

                    SOFTEXPRESS

                </p>


                <h2>

                    Une question sur nos services ?

                </h2>


                <span>

                    Notre équipe est disponible pour
                    vous accompagner.

                </span>


            </div>


            <div class="news-cta-actions">


                <a
                    href="contact.php"
                    class="news-btn orange"
                >

                    <i
                        class="fa-regular fa-envelope"
                    ></i>

                    Nous contacter

                </a>


                <a
                    href="formations.php"
                    class="news-btn white"
                >

                    Découvrir nos formations

                </a>


            </div>


        </div>

    </section>


</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>


    <div class="container footer-grid">


        <div>


            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >


            <p>

                Formation, équipements informatiques
                et maintenance.

            </p>


        </div>


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


            © <?= date('Y') ?> SOFTEXPRESS —
            Tous droits réservés.


        </div>


    </div>


</footer>


<!-- =========================================================
     JAVASCRIPT PRINCIPAL
========================================================= -->

<script src="../assets/js/main.js"></script>


<!-- =========================================================
     JAVASCRIPT PROFIL
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const button =
            document.getElementById(
                'actualitesUserProfileButton'
            );


        const dropdown =
            document.getElementById(
                'actualitesUserDropdown'
            );


        /*
        |--------------------------------------------------------------------------
        | Si aucun utilisateur connecté
        |--------------------------------------------------------------------------
        */

        if (
            !button
            ||
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
        | CLIC À L'EXTÉRIEUR
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