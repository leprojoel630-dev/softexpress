<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - PAGE MAINTENANCE
|--------------------------------------------------------------------------
| VERSION DÉFINITIVE
|
| Cette page :
| - utilise la session existante
| - affiche les initiales de l'utilisateur connecté
| - affiche son nom
| - affiche son email
| - affiche son rôle
| - affiche le bouton Administration pour admin
| - affiche le bouton Déconnexion
| - conserve le CSS existant
|
| IMPORTANT :
| Aucun changement dans style.css
| Aucun changement dans maintenance.css
| Aucun changement dans main.js
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
| FORMAT PRIX
|--------------------------------------------------------------------------
*/

function formatPrixMaintenance($prix): string
{
    $prix = (float)$prix;

    if ($prix <= 0) {
        return 'Sur devis';
    }

    return number_format(
        $prix,
        0,
        ',',
        ' '
    ) . ' FCFA';
}


/*
|--------------------------------------------------------------------------
| DESCRIPTION COURTE
|--------------------------------------------------------------------------
*/

function descriptionCourteMaintenance(
    ?string $texte,
    int $longueur = 150
): string {

    $texte = trim(
        strip_tags(
            (string)$texte
        )
    );

    if (mb_strlen($texte) <= $longueur) {
        return $texte;
    }

    return mb_substr(
        $texte,
        0,
        $longueur
    ) . '...';
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
|
| On utilise en priorité les variables de session du projet :
|
| $_SESSION['user_id']
| $_SESSION['user_nom']
| $_SESSION['user_prenom']
| $_SESSION['user_email']
| $_SESSION['user_role']
|
| Une compatibilité est conservée avec :
|
| $_SESSION['user']
| $_SESSION['nom']
| $_SESSION['prenom']
| $_SESSION['email']
| $_SESSION['role']
|--------------------------------------------------------------------------
*/

$utilisateurConnecte = false;

$userId = $_SESSION['user_id'] ?? null;

$userNom = trim(
    (string)($_SESSION['user_nom'] ?? '')
);

$userPrenom = trim(
    (string)($_SESSION['user_prenom'] ?? '')
);

$userEmail = trim(
    (string)($_SESSION['user_email'] ?? '')
);

$userRole = trim(
    (string)($_SESSION['user_role'] ?? '')
);


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC $_SESSION['user']
|--------------------------------------------------------------------------
*/

if (
    $userNom === ''
    && $userPrenom === ''
    && $userEmail === ''
    && isset($_SESSION['user'])
    && is_array($_SESSION['user'])
) {

    $user = $_SESSION['user'];

    $userId = $user['id']
        ?? $user['user_id']
        ?? $userId;

    $userNom = trim(
        (string)($user['nom'] ?? '')
    );

    $userPrenom = trim(
        (string)($user['prenom'] ?? '')
    );

    $userEmail = trim(
        (string)($user['email'] ?? '')
    );

    $userRole = trim(
        (string)($user['role'] ?? '')
    );
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ AVEC LES VARIABLES DIRECTES
|--------------------------------------------------------------------------
*/

if ($userNom === '') {

    $userNom = trim(
        (string)($_SESSION['nom'] ?? '')
    );
}

if ($userPrenom === '') {

    $userPrenom = trim(
        (string)($_SESSION['prenom'] ?? '')
    );
}

if ($userEmail === '') {

    $userEmail = trim(
        (string)($_SESSION['email'] ?? '')
    );
}

if ($userRole === '') {

    $userRole = trim(
        (string)($_SESSION['role'] ?? '')
    );
}


/*
|--------------------------------------------------------------------------
| DÉTECTION UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

if (
    $userId !== null
    || $userNom !== ''
    || $userPrenom !== ''
    || $userEmail !== ''
) {
    $utilisateurConnecte = true;
}


/*
|--------------------------------------------------------------------------
| NOM COMPLET
|--------------------------------------------------------------------------
*/

$nomComplet = trim(
    $userPrenom . ' ' . $userNom
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

    $initiales .= mb_strtoupper(
        mb_substr(
            $userPrenom,
            0,
            1
        )
    );
}

if ($userNom !== '') {

    $initiales .= mb_strtoupper(
        mb_substr(
            $userNom,
            0,
            1
        )
    );
}


/*
|--------------------------------------------------------------------------
| SI PAS D'INITIALES
|--------------------------------------------------------------------------
*/

if ($initiales === '') {

    if ($userEmail !== '') {

        $initiales = mb_strtoupper(
            mb_substr(
                $userEmail,
                0,
                1
            )
        );

    } else {

        $initiales = 'U';
    }
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES SERVICES
|--------------------------------------------------------------------------
*/

$services = [];


/*
|--------------------------------------------------------------------------
| PDO
|--------------------------------------------------------------------------
*/

if (
    isset($pdo)
    && $pdo instanceof PDO
) {

    try {

        $sql = "
            SELECT
                id,
                nom,
                description,
                prix,
                date_creation
            FROM services_maintenance
            ORDER BY id DESC
        ";

        $stmt = $pdo->query($sql);

        if ($stmt !== false) {

            $services = $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
        }

    } catch (PDOException $e) {

        $services = [];
    }
}


/*
|--------------------------------------------------------------------------
| MYSQLI
|--------------------------------------------------------------------------
*/

elseif (
    isset($conn)
    && $conn instanceof mysqli
) {

    try {

        $sql = "
            SELECT
                id,
                nom,
                description,
                prix,
                date_creation
            FROM services_maintenance
            ORDER BY id DESC
        ";

        $result = $conn->query($sql);

        if ($result !== false) {

            while ($row = $result->fetch_assoc()) {

                $services[] = $row;
            }

            $result->free();
        }

    } catch (Throwable $e) {

        $services = [];
    }
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
        content="Services de maintenance informatique SOFTEXPRESS."
    >

    <title>
        Maintenance | SOFTEXPRESS
    </title>


    <!-- CSS PRINCIPAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS MAINTENANCE -->

    <link
        rel="stylesheet"
        href="../assets/css/maintenance.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!--
    ==================================================================
    CSS DU PROFIL UTILISATEUR
    ==================================================================
    LOCAL À CETTE PAGE UNIQUEMENT.

    NE MODIFIE PAS :
    - style.css
    - maintenance.css
    ==================================================================
    -->

    <style>

        /* ==========================================================
           MENU UTILISATEUR
        ========================================================== */

        .sx-user-menu {

            position: relative;

            display: flex;

            align-items: center;

        }


        /* ==========================================================
           BOUTON / CERCLE
        ========================================================== */

        .sx-user-button {

            width: 46px;

            height: 46px;

            padding: 0;

            border: 2px solid #00A3E0;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #00A3E0,
                    #F99D1C
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 4px;

            cursor: pointer;

            font-family: inherit;

            font-size: 13px;

            font-weight: 800;

            text-transform: uppercase;

            box-shadow:
                0 5px 15px
                rgba(0, 163, 224, 0.18);

            transition:
                transform .25s ease,
                box-shadow .25s ease;

        }


        .sx-user-button:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(0, 163, 224, 0.25);

        }


        .sx-user-button i {

            font-size: 9px;

            transition:
                transform .2s ease;

        }


        .sx-user-menu.open
        .sx-user-button i {

            transform:
                rotate(180deg);

        }


        /* ==========================================================
           MENU DÉROULANT
        ========================================================== */

        .sx-user-dropdown {

            position: absolute;

            top: calc(100% + 12px);

            right: 0;

            width: 290px;

            background: #ffffff;

            border: 1px solid #e8edf1;

            border-radius: 14px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, .12);

            padding: 18px;

            z-index: 99999;

            opacity: 0;

            visibility: hidden;

            transform:
                translateY(-8px);

            transition:
                opacity .2s ease,
                visibility .2s ease,
                transform .2s ease;

        }


        .sx-user-menu.open
        .sx-user-dropdown {

            opacity: 1;

            visibility: visible;

            transform:
                translateY(0);

        }


        /* ==========================================================
           EN-TÊTE DU PROFIL
        ========================================================== */

        .sx-user-header {

            display: flex;

            align-items: center;

            gap: 12px;

            padding-bottom: 15px;

            border-bottom:
                1px solid #edf0f3;

        }


        /* ==========================================================
           GRAND AVATAR
        ========================================================== */

        .sx-user-avatar {

            width: 48px;

            height: 48px;

            flex: 0 0 48px;

            border-radius: 50%;

            background:
                linear-gradient(
                    135deg,
                    #00A3E0,
                    #F99D1C
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;

            font-weight: 800;

            text-transform: uppercase;

        }


        /* ==========================================================
           NOM
        ========================================================== */

        .sx-user-name {

            margin: 0;

            color: #111827;

            font-size: 15px;

            font-weight: 800;

            word-break: break-word;

        }


        /* ==========================================================
           RÔLE
        ========================================================== */

        .sx-user-role {

            display: inline-block;

            margin-top: 4px;

            color: #00A3E0;

            font-size: 11px;

            font-weight: 700;

        }


        /* ==========================================================
           INFORMATIONS
        ========================================================== */

        .sx-user-info {

            padding: 15px 0;

        }


        .sx-user-info-row {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 8px 0;

            color: #596575;

            font-size: 13px;

        }


        .sx-user-info-row i {

            width: 18px;

            flex: 0 0 18px;

            color: #00A3E0;

            margin-top: 2px;

            text-align: center;

        }


        .sx-user-info-row span {

            word-break: break-word;

        }


        /* ==========================================================
           ADMINISTRATION
        ========================================================== */

        .sx-user-admin {

            width: 100%;

            min-height: 42px;

            margin-bottom: 8px;

            border-radius: 8px;

            background: #fff8ee;

            color: #d98200;

            border: 1px solid #ffe4bd;

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            font-size: 13px;

            font-weight: 700;

            transition: .25s ease;

        }


        .sx-user-admin:hover {

            background: #F99D1C;

            color: #ffffff;

        }


        /* ==========================================================
           DÉCONNEXION
        ========================================================== */

        .sx-user-logout {

            width: 100%;

            min-height: 42px;

            border-radius: 8px;

            background: #fff3f3;

            color: #d93636;

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            font-size: 13px;

            font-weight: 700;

            transition: .25s ease;

        }


        .sx-user-logout:hover {

            background: #d93636;

            color: #ffffff;

        }


        /* ==========================================================
           MOBILE
        ========================================================== */

        @media (max-width: 600px) {

            .sx-user-dropdown {

                position: fixed;

                top: 75px;

                right: 15px;

                width:
                    calc(100vw - 30px);

                max-width: 290px;

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


        <!-- MENU -->

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

            <a
                href="maintenance.php"
                class="active"
            >
                Maintenance
            </a>

            <a href="actualites.php">
                Actualités
            </a>

            <a href="contact.php">
                Contact
            </a>

        </nav>


        <!-- =====================================================
             AUTHENTIFICATION
        ====================================================== -->

        <div class="auth">


            <?php if ($utilisateurConnecte): ?>


                <!-- =================================================
                     UTILISATEUR CONNECTÉ
                ================================================== -->

                <div
                    class="sx-user-menu"
                    id="sxUserMenu"
                >


                    <!-- CERCLE AVEC INITIALES -->

                    <button
                        type="button"
                        class="sx-user-button"
                        id="sxUserButton"
                        aria-label="Ouvrir le profil"
                        aria-expanded="false"
                    >

                        <?= e($initiales) ?>

                        <i
                            class="fa-solid fa-chevron-down"
                            aria-hidden="true"
                        ></i>

                    </button>


                    <!-- =================================================
                         MENU PROFIL
                    ================================================== -->

                    <div
                        class="sx-user-dropdown"
                        id="sxUserDropdown"
                    >


                        <!-- EN-TÊTE -->

                        <div class="sx-user-header">


                            <div class="sx-user-avatar">

                                <?= e($initiales) ?>

                            </div>


                            <div>

                                <p class="sx-user-name">

                                    <?= e($nomComplet) ?>

                                </p>


                                <?php if ($userRole !== ''): ?>

                                    <span class="sx-user-role">

                                        <?= e(
                                            ucfirst(
                                                strtolower(
                                                    $userRole
                                                )
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                        </div>


                        <!-- INFORMATIONS -->

                        <div class="sx-user-info">


                            <div class="sx-user-info-row">

                                <i
                                    class="fa-solid fa-user"
                                    aria-hidden="true"
                                ></i>

                                <span>

                                    <?= e($nomComplet) ?>

                                </span>

                            </div>


                            <?php if ($userEmail !== ''): ?>

                                <div class="sx-user-info-row">

                                    <i
                                        class="fa-solid fa-envelope"
                                        aria-hidden="true"
                                    ></i>

                                    <span>

                                        <?= e($userEmail) ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if ($userRole !== ''): ?>

                                <div class="sx-user-info-row">

                                    <i
                                        class="fa-solid fa-shield-halved"
                                        aria-hidden="true"
                                    ></i>

                                    <span>

                                        <?= e(
                                            ucfirst(
                                                strtolower(
                                                    $userRole
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             ADMINISTRATION
                        ================================================== -->

                        <?php if (
                            strtolower($userRole) === 'admin'
                        ): ?>

                            <a
                                href="../admin/index.php"
                                class="sx-user-admin"
                            >

                                <i
                                    class="fa-solid fa-gear"
                                    aria-hidden="true"
                                ></i>

                                Administration

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             DÉCONNEXION
                        ================================================== -->

                        <a
                            href="../auth/deconnexion.php"
                            class="sx-user-logout"
                        >

                            <i
                                class="fa-solid fa-right-from-bracket"
                                aria-hidden="true"
                            ></i>

                            Déconnexion

                        </a>


                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     UTILISATEUR NON CONNECTÉ
                ================================================== -->

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

<section class="maintenance-banner">

    <div class="maintenance-banner-content container">

        <p class="maintenance-eyebrow">

            ASSISTANCE • DIAGNOSTIC • RÉPARATION

        </p>


        <h1>

            MAINTENANCE

            <span>
                INFORMATIQUE
            </span>

        </h1>


        <p>

            Confiez vos équipements à notre équipe
            pour un diagnostic et une maintenance
            professionnelle.

        </p>


        <div class="maintenance-breadcrumb">

            <a href="../index.php">

                Accueil

            </a>

            <span>›</span>

            <strong>

                Maintenance

            </strong>

        </div>

    </div>

</section>


<!-- =========================================================
     PRÉSENTATION
========================================================= -->

<section class="maintenance-intro">

    <div class="container">

        <div class="maintenance-intro-grid">


            <div class="maintenance-intro-text">

                <p class="maintenance-section-label">

                    NOTRE EXPERTISE

                </p>


                <h2>

                    Votre matériel informatique
                    mérite le meilleur entretien.

                </h2>


                <p>

                    SOFTEXPRESS vous accompagne dans
                    le diagnostic, la réparation et
                    l'entretien de vos équipements
                    informatiques.

                </p>


                <p>

                    Notre objectif est de vous permettre
                    de retrouver rapidement un matériel
                    fiable et performant.

                </p>


                <a
                    href="#services"
                    class="maintenance-main-btn"
                >

                    Découvrir nos services

                    <i
                        class="fa-solid fa-arrow-down"
                    ></i>

                </a>

            </div>


            <div class="maintenance-intro-visual">

                <div class="maintenance-circle">

                    <i
                        class="fa-solid fa-screwdriver-wrench"
                    ></i>

                </div>


                <div class="maintenance-floating-card">

                    <i
                        class="fa-solid fa-circle-check"
                    ></i>


                    <div>

                        <strong>

                            Assistance professionnelle

                        </strong>

                        <span>

                            Diagnostic et réparation

                        </span>

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- =========================================================
     SERVICES
========================================================= -->

<section
    class="maintenance-services"
    id="services"
>

    <div class="container">


        <div class="maintenance-heading">

            <p class="maintenance-section-label">

                NOS SERVICES

            </p>


            <h2>

                Des solutions pour vos équipements

            </h2>


            <p>

                Découvrez les services de maintenance
                proposés par SOFTEXPRESS.

            </p>

        </div>


        <?php if (!empty($services)): ?>


            <div class="maintenance-grid">


                <?php foreach ($services as $service): ?>


                    <article class="maintenance-card">


                        <!-- ICÔNE -->

                        <div class="maintenance-card-icon">

                            <i
                                class="fa-solid fa-screwdriver-wrench"
                            ></i>

                        </div>


                        <!-- CONTENU -->

                        <div class="maintenance-card-content">


                            <span
                                class="maintenance-card-number"
                            >

                                SERVICE
                                #<?= (int)$service['id'] ?>

                            </span>


                            <h3>

                                <?= e(
                                    $service['nom']
                                ) ?>

                            </h3>


                            <p>

                                <?= e(
                                    descriptionCourteMaintenance(
                                        $service['description']
                                    )
                                ) ?>

                            </p>


                            <!-- PRIX -->

                            <div class="maintenance-card-price">

                                <span>

                                    Tarif

                                </span>


                                <strong>

                                    <?= formatPrixMaintenance(
                                        $service['prix']
                                    ) ?>

                                </strong>

                            </div>


                            <!-- DEMANDE -->

                            <a
                                href="demande-maintenance.php?service_id=<?= (int)$service['id'] ?>"
                                class="maintenance-card-btn"
                            >

                                Demander ce service

                                <i
                                    class="fa-solid fa-arrow-right"
                                ></i>

                            </a>

                        </div>

                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="maintenance-empty">


                <div class="maintenance-empty-icon">

                    <i
                        class="fa-solid fa-screwdriver-wrench"
                    ></i>

                </div>


                <h3>

                    Aucun service disponible

                </h3>


                <p>

                    Les services de maintenance ajoutés
                    depuis l'administration apparaîtront
                    automatiquement ici.

                </p>


                <a
                    href="../index.php"
                    class="maintenance-main-btn"
                >

                    Retour à l'accueil

                </a>


            </div>


        <?php endif; ?>


    </div>

</section>


<!-- =========================================================
     PROCESSUS
========================================================= -->

<section class="maintenance-process">

    <div class="container">


        <div class="maintenance-heading light">

            <p class="maintenance-section-label">

                PROCESSUS

            </p>


            <h2>

                Une prise en charge simple

            </h2>

        </div>


        <div class="maintenance-steps">


            <div class="maintenance-step">

                <div class="maintenance-step-number">

                    01

                </div>


                <i
                    class="fa-solid fa-file-circle-plus"
                ></i>


                <h3>

                    Demande

                </h3>


                <p>

                    Envoyez votre demande de maintenance.

                </p>

            </div>


            <div class="maintenance-step">

                <div class="maintenance-step-number">

                    02

                </div>


                <i
                    class="fa-solid fa-magnifying-glass"
                ></i>


                <h3>

                    Diagnostic

                </h3>


                <p>

                    Nous identifions l'origine du problème.

                </p>

            </div>


            <div class="maintenance-step">

                <div class="maintenance-step-number">

                    03

                </div>


                <i
                    class="fa-solid fa-screwdriver-wrench"
                ></i>


                <h3>

                    Réparation

                </h3>


                <p>

                    Notre équipe intervient sur votre équipement.

                </p>

            </div>


            <div class="maintenance-step">

                <div class="maintenance-step-number">

                    04

                </div>


                <i
                    class="fa-solid fa-circle-check"
                ></i>


                <h3>

                    Restitution

                </h3>


                <p>

                    Votre équipement vous est rendu après intervention.

                </p>

            </div>


        </div>

    </div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="maintenance-cta">

    <div class="container maintenance-cta-inner">


        <div>

            <p class="maintenance-eyebrow">

                BESOIN D'UNE INTERVENTION ?

            </p>


            <h2>

                Votre ordinateur rencontre un problème ?

            </h2>


            <p>

                Faites-nous parvenir votre demande et
                notre équipe pourra vous accompagner.

            </p>

        </div>


        <div>

            <a
                href="demande-maintenance.php"
                class="maintenance-cta-btn orange"
            >

                Demander une maintenance

            </a>


            <a
                href="contact.php"
                class="maintenance-cta-btn white"
            >

                Nous contacter

            </a>

        </div>


    </div>

</section>


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
     JAVASCRIPT DU PROFIL
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const userMenu =
            document.getElementById(
                'sxUserMenu'
            );

        const userButton =
            document.getElementById(
                'sxUserButton'
            );

        const userDropdown =
            document.getElementById(
                'sxUserDropdown'
            );


        /*
        ----------------------------------------------------------
        SI UTILISATEUR NON CONNECTÉ
        ----------------------------------------------------------
        */

        if (
            !userMenu
            ||
            !userButton
            ||
            !userDropdown
        ) {

            return;

        }


        /*
        ----------------------------------------------------------
        OUVERTURE / FERMETURE
        ----------------------------------------------------------
        */

        userButton.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                const ouvert =
                    userMenu.classList.toggle(
                        'open'
                    );

                userButton.setAttribute(
                    'aria-expanded',
                    ouvert
                        ? 'true'
                        : 'false'
                );

            }
        );


        /*
        ----------------------------------------------------------
        CLIC EN DEHORS
        ----------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function (event) {

                if (
                    !userMenu.contains(
                        event.target
                    )
                ) {

                    userMenu.classList.remove(
                        'open'
                    );

                    userButton.setAttribute(
                        'aria-expanded',
                        'false'
                    );

                }

            }
        );


        /*
        ----------------------------------------------------------
        TOUCHE ESCAPE
        ----------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    userMenu.classList.remove(
                        'open'
                    );

                    userButton.setAttribute(
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