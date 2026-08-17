<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - DÉTAIL PRODUIT
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

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$connecte = isset($_SESSION['user_id']);

$userPrenom = trim(
    (string)($_SESSION['user_prenom'] ?? '')
);

$userNom = trim(
    (string)($_SESSION['user_nom'] ?? '')
);

$userEmail = trim(
    (string)($_SESSION['user_email'] ?? '')
);

$userRole = trim(
    (string)($_SESSION['user_role'] ?? '')
);


/*
|--------------------------------------------------------------------------
| NOM COMPLET
|--------------------------------------------------------------------------
*/

$nomUtilisateur = trim(
    $userPrenom . ' ' . $userNom
);

if ($nomUtilisateur === '') {
    $nomUtilisateur = 'Utilisateur';
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
    $initiales = 'U';
}

$initiales = mb_strtoupper($initiales);


/*
|--------------------------------------------------------------------------
| PRIX
|--------------------------------------------------------------------------
*/

function formatPrixProduit($prix): string
{
    return number_format(
        (float)$prix,
        0,
        ',',
        ' '
    ) . ' FCFA';
}


/*
|--------------------------------------------------------------------------
| IMAGE PRODUIT
|--------------------------------------------------------------------------
*/

function imageProduit(?string $image): string
{
    $image = basename(
        trim((string)$image)
    );

    $dossier = __DIR__ .
        '/../assets/images/produits/';

    if (
        $image !== ''
        &&
        is_file($dossier . $image)
    ) {
        return '../assets/images/produits/' . $image;
    }

    return '../assets/images/produits/accessoires.jpg';
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION DE L'ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id'])
    ||
    !is_numeric($_GET['id'])
) {
    header('Location: produits.php');
    exit;
}

$id = (int)$_GET['id'];


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU PRODUIT
|--------------------------------------------------------------------------
*/

$produit = null;


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

    try {

        $sql = "
            SELECT
                id,
                nom,
                description,
                prix,
                stock,
                image,
                date_creation
            FROM produits
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$id]);

        $produit = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

    } catch (Throwable $e) {

        $produit = null;
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

    try {

        $sql = "
            SELECT
                id,
                nom,
                description,
                prix,
                stock,
                image,
                date_creation
            FROM produits
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result) {

                $produit =
                    $result->fetch_assoc();
            }

            $stmt->close();
        }

    } catch (Throwable $e) {

        $produit = null;
    }
}


/*
|--------------------------------------------------------------------------
| PRODUIT INTROUVABLE
|--------------------------------------------------------------------------
*/

if (!$produit) {

    header('Location: produits.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DONNÉES DU PRODUIT
|--------------------------------------------------------------------------
*/

$nom = e(
    (string)($produit['nom'] ?? '')
);

$description = nl2br(
    e(
        (string)($produit['description'] ?? '')
    )
);

$prix = formatPrixProduit(
    $produit['prix'] ?? 0
);

$stock = (int)($produit['stock'] ?? 0);

$image = imageProduit(
    $produit['image'] ?? ''
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
        content="Découvrez les détails du produit <?= $nom ?> proposé par SOFTEXPRESS."
    >

    <title>
        <?= $nom ?> | SOFTEXPRESS
    </title>


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         CSS SPÉCIFIQUE À LA PAGE
    ====================================================== -->

    <style>

        /* =================================================
           DÉTAIL PRODUIT
        ================================================= */

        .product-detail-page {
            background: #f7fafc;
            min-height: 100vh;
        }


        .product-detail-breadcrumb {
            padding: 25px 0;

            display: flex;
            align-items: center;

            gap: 10px;

            color: #8a94a6;

            font-size: 13px;
        }


        .product-detail-breadcrumb a {
            color: #00A3E0;

            text-decoration: none;

            font-weight: 600;
        }


        .product-detail-section {
            padding: 25px 0 90px;
        }


        .product-detail-box {

            display: grid;

            grid-template-columns:
                minmax(400px, 1fr)
                minmax(400px, 1fr);

            gap: 65px;

            align-items: center;

            background: #ffffff;

            padding: 45px;

            border-radius: 20px;

            box-shadow:
                0 12px 40px rgba(0,0,0,0.06);

            border: 1px solid #e8edf1;
        }


        .product-detail-image {

            height: 450px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f5f8fa;

            border-radius: 16px;

            overflow: hidden;

            position: relative;
        }


        .product-detail-image::before {

            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            border-radius: 50%;

            background:
                rgba(0,163,224,0.06);

            top: -100px;
            right: -80px;
        }


        .product-detail-image img {

            position: relative;

            z-index: 2;

            display: block;

            width: 100%;
            height: 100%;

            object-fit: contain;

            padding: 30px;

            transition:
                transform .3s ease;
        }


        .product-detail-image:hover img {

            transform: scale(1.03);
        }


        .product-detail-content {

            max-width: 600px;
        }


        .product-detail-label {

            display: inline-block;

            margin-bottom: 12px;

            color: #00A3E0;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 1.5px;
        }


        .product-detail-content h1 {

            margin: 0 0 18px;

            color: #111827;

            font-size:
                clamp(32px, 4vw, 48px);

            line-height: 1.15;

            font-weight: 800;
        }


        .product-detail-line {

            width: 60px;
            height: 4px;

            margin-bottom: 25px;

            border-radius: 5px;

            background:
                linear-gradient(
                    90deg,
                    #F99D1C,
                    #00A3E0
                );
        }


        .product-detail-description {

            margin-bottom: 30px;

            color: #596575;

            font-size: 15px;

            line-height: 1.9;
        }


        .product-detail-price {

            padding: 20px;

            margin-bottom: 20px;

            border-radius: 12px;

            background: #fff8ee;

            border: 1px solid #ffe5c0;
        }


        .product-detail-price span {

            display: block;

            margin-bottom: 5px;

            color: #8a94a6;

            font-size: 12px;
        }


        .product-detail-price strong {

            color: #F99D1C;

            font-size: 28px;
        }


        .product-detail-stock {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 30px;

            padding: 14px 16px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;
        }


        .product-detail-stock.available {

            color: #168746;

            background: #edf9f2;
        }


        .product-detail-stock.unavailable {

            color: #cf3b3b;

            background: #fff0f0;
        }


        .product-detail-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 12px;
        }


        .product-detail-btn {

            min-height: 50px;

            padding: 0 22px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            transition: .25s ease;
        }


        .product-detail-btn.primary {

            color: #ffffff;

            background: #F99D1C;
        }


        .product-detail-btn.primary:hover {

            background: #e58b0b;

            transform: translateY(-2px);
        }


        .product-detail-btn.secondary {

            color: #00A3E0;

            background: #ffffff;

            border: 1px solid #bce2ef;
        }


        .product-detail-btn.secondary:hover {

            color: #ffffff;

            background: #00A3E0;
        }


        .product-extra-info {

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f3;

            display: flex;

            flex-wrap: wrap;

            gap: 25px;
        }


        .product-extra-item {

            display: flex;

            align-items: center;

            gap: 9px;

            color: #687385;

            font-size: 12px;
        }


        .product-extra-item i {

            color: #00A3E0;
        }


        /* =================================================
           PROFIL UTILISATEUR
        ================================================= */

        .product-detail-page .user-menu {

            position: relative;

            display: flex;

            align-items: center;
        }


        .product-detail-page .user-profile {

            border: none;

            background: transparent;

            padding: 4px;

            display: flex;

            align-items: center;

            gap: 8px;

            cursor: pointer;

            font-family: inherit;
        }


        .product-detail-page .user-avatar {

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


        .product-detail-page
        .user-profile-name {

            max-width: 140px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #111827;

            font-size: 14px;

            font-weight: 700;
        }


        .product-detail-page
        .user-profile-arrow {

            color: #687385;

            font-size: 10px;

            transition:
                transform .2s ease;
        }


        .product-detail-page
        .user-profile[aria-expanded="true"]
        .user-profile-arrow {

            transform: rotate(180deg);
        }


        /* =================================================
           MENU PROFIL
        ================================================= */

        .product-detail-page .user-dropdown {

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

            transform:
                translateY(-8px);

            transition:
                opacity .2s ease,
                visibility .2s ease,
                transform .2s ease;
        }


        .product-detail-page
        .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform:
                translateY(0);
        }


        .product-detail-page
        .user-dropdown-header {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px;
        }


        .product-detail-page
        .user-avatar-large {

            width: 48px;
            height: 48px;

            font-size: 14px;
        }


        .product-detail-page
        .user-info-text {

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 3px;
        }


        .product-detail-page
        .user-info-text strong {

            color: #111827;

            font-size: 14px;

            font-weight: 800;
        }


        .product-detail-page
        .user-info-text small {

            color: #687385;

            font-size: 12px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .product-detail-page
        .user-dropdown-divider {

            height: 1px;

            background: #edf0f3;

            margin: 6px 4px;
        }


        .product-detail-page
        .user-dropdown a {

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


        .product-detail-page
        .user-dropdown a:hover {

            background: #f3f9fc;

            color: #00A3E0;
        }


        .product-detail-page
        .profile-admin {

            color: #394150;
        }


        .product-detail-page
        .profile-admin i {

            color: #F99D1C;
        }


        .product-detail-page
        .profile-logout {

            color: #d13b3b !important;
        }


        .product-detail-page
        .profile-logout:hover {

            background: #fff1f1 !important;

            color: #c62828 !important;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .product-detail-box {

                grid-template-columns: 1fr;

                gap: 40px;

                padding: 30px;
            }


            .product-detail-image {

                height: 380px;
            }
        }


        @media (max-width: 600px) {

            .product-detail-section {

                padding: 10px 0 60px;
            }


            .product-detail-box {

                padding: 18px;

                border-radius: 14px;
            }


            .product-detail-image {

                height: 280px;
            }


            .product-detail-image img {

                padding: 20px;
            }


            .product-detail-content h1 {

                font-size: 32px;
            }


            .product-detail-actions {

                flex-direction: column;
            }


            .product-detail-btn {

                width: 100%;
            }


            .product-detail-page
            .user-profile-name,

            .product-detail-page
            .user-profile-arrow {

                display: none;
            }


            .product-detail-page
            .user-dropdown {

                right: -5px;

                width: 270px;
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

            <a
                href="produits.php"
                class="active"
            >
                Produits
            </a>

            <a href="maintenance.php">
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
             ESPACE UTILISATEUR
        ====================================================== -->

        <div class="auth">


            <?php if ($connecte): ?>


                <!-- UTILISATEUR CONNECTÉ -->

                <div class="user-menu">


                    <!-- =================================================
                         PROFIL
                    ================================================== -->

                    <button
                        type="button"
                        id="productUserProfileButton"
                        class="user-profile"
                        aria-label="Ouvrir mon profil"
                        aria-expanded="false"
                    >

                        <span class="user-avatar">

                            <?= e($initiales) ?>

                        </span>

                    </button>


                    <!-- =================================================
                         MENU DÉROULANT
                    ================================================== -->

                    <div
                        class="user-dropdown"
                        id="productUserDropdown"
                    >


                        <!-- INFORMATIONS UTILISATEUR -->

                        <div class="user-dropdown-header">

                            <span class="user-avatar">

                                <?= e($initiales) ?>

                            </span>


                            <div class="user-info-text">

                                <strong>
                                    <?= e($nomUtilisateur) ?>
                                </strong>

                                <small>
                                    <?= e($userEmail) ?>
                                </small>

                            </div>

                        </div>


                        <!-- SÉPARATION -->

                        <div class="user-dropdown-divider"></div>


                        <!-- ADMINISTRATION -->

                        <?php if (
                            strtolower($userRole) === 'admin'
                        ): ?>

                            <a
                                href="../admin/index.php"
                                class="profile-admin"
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
                            class="profile-logout"
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
     PAGE
========================================================= -->

<main class="product-detail-page">


    <div class="container">


        <!-- =================================================
             BREADCRUMB
        ================================================== -->

        <div class="product-detail-breadcrumb">

            <a href="../index.php">
                Accueil
            </a>

            <span>
                ›
            </span>

            <a href="produits.php">
                Produits
            </a>

            <span>
                ›
            </span>

            <strong>
                <?= $nom ?>
            </strong>

        </div>


        <!-- =================================================
             PRODUIT
        ================================================== -->

        <section class="product-detail-section">

            <div class="product-detail-box">


                <!-- =================================================
                     IMAGE
                ================================================== -->

                <div class="product-detail-image">

                    <img
                        src="<?= e($image) ?>"
                        alt="<?= $nom ?>"
                    >

                </div>


                <!-- =================================================
                     INFORMATIONS
                ================================================== -->

                <div class="product-detail-content">


                    <span class="product-detail-label">
                        PRODUIT SOFTEXPRESS
                    </span>


                    <h1>
                        <?= $nom ?>
                    </h1>


                    <div class="product-detail-line"></div>


                    <!-- DESCRIPTION -->

                    <div class="product-detail-description">

                        <?= $description ?>

                    </div>


                    <!-- PRIX -->

                    <div class="product-detail-price">

                        <span>
                            Prix
                        </span>

                        <strong>
                            <?= e($prix) ?>
                        </strong>

                    </div>


                    <!-- STOCK -->

                    <?php if ($stock > 0): ?>

                        <div
                            class="product-detail-stock available"
                        >

                            <i
                                class="fa-solid fa-circle-check"
                            ></i>

                            Produit disponible —
                            <?= $stock ?> unité(s) en stock

                        </div>

                    <?php else: ?>

                        <div
                            class="product-detail-stock unavailable"
                        >

                            <i
                                class="fa-solid fa-circle-xmark"
                            ></i>

                            Produit actuellement indisponible

                        </div>

                    <?php endif; ?>


                    <!-- ACTIONS -->

                    <div class="product-detail-actions">


                        <?php if ($stock > 0): ?>

                            <a
                                href="contact.php?produit_id=<?= (int)$produit['id'] ?>"
                                class="product-detail-btn primary"
                            >

                                <i
                                    class="fa-solid fa-cart-shopping"
                                ></i>

                                Demander ce produit

                            </a>

                        <?php endif; ?>


                        <a
                            href="produits.php"
                            class="product-detail-btn secondary"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Retour aux produits

                        </a>


                    </div>


                    <!-- INFORMATIONS SUPPLÉMENTAIRES -->

                    <div class="product-extra-info">


                        <div class="product-extra-item">

                            <i
                                class="fa-solid fa-shield-halved"
                            ></i>

                            Service SOFTEXPRESS

                        </div>


                        <div class="product-extra-item">

                            <i
                                class="fa-solid fa-headset"
                            ></i>

                            Assistance disponible

                        </div>


                        <div class="product-extra-item">

                            <i
                                class="fa-solid fa-computer"
                            ></i>

                            Équipement informatique

                        </div>


                    </div>

                </div>

            </div>

        </section>

    </div>

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
     JAVASCRIPT DU PROFIL
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const button =
            document.getElementById(
                'productUserProfileButton'
            );

        const dropdown =
            document.getElementById(
                'productUserDropdown'
            );


        if (!button || !dropdown) {
            return;
        }


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