<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - PAGE PRODUITS
|--------------------------------------------------------------------------
| Version définitive
|
| - Session utilisateur
| - Profil connecté identique aux autres pages
| - Initiales automatiques
| - Menu profil
| - Accès administration pour admin
| - Déconnexion
| - Compatible PDO / MySQLi
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
| CONNEXION BASE DE DONNÉES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ PDO / MYSQLI
|--------------------------------------------------------------------------
*/

if (
    !isset($pdo) &&
    isset($conn) &&
    $conn instanceof PDO
) {
    $pdo = $conn;
}


/*
|--------------------------------------------------------------------------
| FONCTIONS
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


function formatPrixProduit($prix): string
{
    return number_format(
        (float)$prix,
        0,
        ',',
        ' '
    ) . ' FCFA';
}


function imageProduit(?string $image): string
{
    $image = basename(
        trim((string)$image)
    );

    $dossier =
        __DIR__ .
        '/../assets/images/produits/';

    if (
        $image !== '' &&
        is_file($dossier . $image)
    ) {
        return '../assets/images/produits/' . $image;
    }

    return '../assets/images/produits/accessoires.jpg';
}


function descriptionCourteProduit(
    ?string $texte,
    int $longueur = 110
): string {

    $texte = trim(
        strip_tags((string)$texte)
    );

    if (
        mb_strlen($texte) <= $longueur
    ) {
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
| INFORMATIONS UTILISATEUR
|--------------------------------------------------------------------------
*/

$connecte = isset($_SESSION['user_id']);


/*
|--------------------------------------------------------------------------
| NOM DU PROFIL
|--------------------------------------------------------------------------
*/

$nomProfil = '';

if ($connecte) {

    $prenom = trim(
        (string)(
            $_SESSION['user_prenom'] ?? ''
        )
    );

    $nom = trim(
        (string)(
            $_SESSION['user_nom'] ?? ''
        )
    );

    $nomProfil = trim(
        $prenom . ' ' . $nom
    );
}


/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/

$emailProfil = $connecte
    ? (string)(
        $_SESSION['user_email'] ?? ''
    )
    : '';


/*
|--------------------------------------------------------------------------
| RÔLE
|--------------------------------------------------------------------------
*/

$roleProfil = $connecte
    ? (string)(
        $_SESSION['user_role'] ?? 'user'
    )
    : 'user';


/*
|--------------------------------------------------------------------------
| INITIALES
|--------------------------------------------------------------------------
*/

$initiales = 'U';

if ($connecte) {

    $initiales = '';


    /*
    | Prénom
    */

    if (
        !empty($_SESSION['user_prenom'])
    ) {

        $initiales .= strtoupper(
            mb_substr(
                (string)
                $_SESSION['user_prenom'],
                0,
                1
            )
        );
    }


    /*
    | Nom
    */

    if (
        !empty($_SESSION['user_nom'])
    ) {

        $initiales .= strtoupper(
            mb_substr(
                (string)
                $_SESSION['user_nom'],
                0,
                1
            )
        );
    }


    /*
    | Si aucune initiale
    */

    if ($initiales === '') {
        $initiales = 'U';
    }
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES PRODUITS
|--------------------------------------------------------------------------
*/

$produits = [];


/*
|--------------------------------------------------------------------------
| PDO
|--------------------------------------------------------------------------
*/

if (
    isset($pdo) &&
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
            ORDER BY id DESC
        ";

        $stmt = $pdo->query($sql);

        if ($stmt !== false) {

            $produits =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }

    } catch (Throwable $e) {

        $produits = [];
    }
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
            ORDER BY id DESC
        ";

        $result = $conn->query($sql);

        if ($result !== false) {

            while (
                $row =
                $result->fetch_assoc()
            ) {

                $produits[] = $row;
            }

            $result->free();
        }

    } catch (Throwable $e) {

        $produits = [];
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
        content="Découvrez les équipements informatiques proposés par SOFTEXPRESS."
    >

    <title>
        Produits | SOFTEXPRESS
    </title>


    <!-- CSS GLOBAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS PRODUITS -->

    <link
        rel="stylesheet"
        href="../assets/css/produits.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<!-- =========================================================
     NAVIGATION
========================================================= -->

<header class="site-header">

    <div class="container nav-wrap">


        <!-- =====================================================
             LOGO
        ====================================================== -->

        <a
            class="brand"
            href="../index.php"
        >

            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

        </a>


        <!-- =====================================================
             MENU MOBILE
        ====================================================== -->

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


        <!-- =====================================================
             NAVIGATION
        ====================================================== -->

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


                <!-- =================================================
                     UTILISATEUR CONNECTÉ
                ================================================== -->

                <div class="user-menu">


                    <!-- =================================================
                         CERCLE PROFIL
                    ================================================== -->

                    <button
                        type="button"
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

                    <div class="user-dropdown">


                        <!-- =================================================
                             INFORMATIONS UTILISATEUR
                        ================================================== -->

                        <div class="user-dropdown-header">


                            <span class="user-avatar">

                                <?= e($initiales) ?>

                            </span>


                            <div class="user-info-text">


                                <strong>

                                    <?= e(
                                        $nomProfil !== ''
                                            ? $nomProfil
                                            : 'Utilisateur'
                                    ) ?>

                                </strong>


                                <small>

                                    <?= e(
                                        $emailProfil
                                    ) ?>

                                </small>


                            </div>

                        </div>


                        <!-- =================================================
                             ADMINISTRATION
                        ================================================== -->

                        <?php if (
                            strtolower(
                                $roleProfil
                            ) === 'admin'
                        ): ?>

                            <a
                                href="../admin/index.php"
                                class="profile-admin"
                            >

                                ⚙ Administration

                            </a>

                        <?php endif; ?>


                        <!-- =================================================
                             DÉCONNEXION
                        ================================================== -->

                        <a
                            href="../auth/deconnexion.php"
                            class="profile-logout"
                        >

                            ↪ Déconnexion

                        </a>


                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     VISITEUR
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

<section class="products-banner">

    <div class="products-banner-overlay"></div>

    <div class="container products-banner-content">


        <p class="products-eyebrow">

            ÉQUIPEMENTS INFORMATIQUES

        </p>


        <h1>

            NOS <span>PRODUITS</span>

        </h1>


        <p>

            Découvrez notre sélection d'équipements
            informatiques pour vos besoins professionnels
            et personnels.

        </p>


        <div class="products-breadcrumb">


            <a href="../index.php">

                Accueil

            </a>


            <span>›</span>


            <strong>

                Produits

            </strong>


        </div>

    </div>

</section>


<!-- =========================================================
     PRODUITS
========================================================= -->

<main>


<section class="products-section">

    <div class="container">


        <!-- =====================================================
             TITRE
        ====================================================== -->

        <div class="products-heading">

            <div>

                <p class="products-eyebrow-dark">

                    NOTRE CATALOGUE

                </p>


                <h2>

                    Découvrez nos produits

                </h2>


                <p>

                    Des équipements informatiques sélectionnés
                    pour répondre à vos besoins.

                </p>

            </div>

        </div>


        <!-- =====================================================
             PRODUITS DISPONIBLES
        ====================================================== -->

        <?php if (!empty($produits)): ?>


            <div class="products-grid">


                <?php foreach (
                    $produits as $produit
                ): ?>


                    <article
                        class="product-card"
                    >


                        <!-- =================================================
                             IMAGE
                        ================================================== -->

                        <a
                            href="produit-details.php?id=<?= (int)$produit['id'] ?>"
                            class="product-image"
                        >

                            <img
                                src="<?= e(
                                    imageProduit(
                                        $produit['image']
                                    )
                                ) ?>"
                                alt="<?= e(
                                    $produit['nom']
                                ) ?>"
                            >


                            <span
                                class="product-image-overlay"
                            >

                                <i
                                    class="fa-solid fa-eye"
                                ></i>

                                Voir le produit

                            </span>

                        </a>


                        <!-- =================================================
                             CONTENU
                        ================================================== -->

                        <div class="product-content">


                            <!-- STOCK -->

                            <?php if (
                                (int)$produit['stock'] > 0
                            ): ?>

                                <span
                                    class="product-stock available"
                                >

                                    <i
                                        class="fa-solid fa-circle-check"
                                    ></i>

                                    Disponible

                                </span>

                            <?php else: ?>

                                <span
                                    class="product-stock unavailable"
                                >

                                    <i
                                        class="fa-solid fa-circle-xmark"
                                    ></i>

                                    Rupture de stock

                                </span>

                            <?php endif; ?>


                            <!-- NOM -->

                            <h3>

                                <?= e(
                                    $produit['nom']
                                ) ?>

                            </h3>


                            <!-- DESCRIPTION -->

                            <p
                                class="product-description"
                            >

                                <?= e(
                                    descriptionCourteProduit(
                                        $produit['description']
                                    )
                                ) ?>

                            </p>


                            <!-- PRIX -->

                            <div
                                class="product-price"
                            >

                                <span>

                                    Prix

                                </span>


                                <strong>

                                    <?= formatPrixProduit(
                                        $produit['prix']
                                    ) ?>

                                </strong>

                            </div>


                            <!-- STOCK -->

                            <div
                                class="product-stock-number"
                            >

                                <?php if (
                                    (int)$produit['stock'] > 0
                                ): ?>

                                    <i
                                        class="fa-solid fa-box"
                                    ></i>

                                    <?= (int)$produit['stock'] ?>

                                    disponible(s)

                                <?php else: ?>

                                    <i
                                        class="fa-solid fa-box-open"
                                    ></i>

                                    Produit indisponible

                                <?php endif; ?>

                            </div>


                            <!-- ACTIONS -->

                            <div
                                class="product-actions"
                            >


                                <a
                                    href="produit-details.php?id=<?= (int)$produit['id'] ?>"
                                    class="product-btn details"
                                >

                                    Voir les détails

                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </a>


                                <?php if (
                                    (int)$produit['stock'] > 0
                                ): ?>

                                    <a
                                        href="contact.php?produit_id=<?= (int)$produit['id'] ?>"
                                        class="product-btn contact"
                                    >

                                        <i
                                            class="fa-solid fa-cart-shopping"
                                        ></i>

                                        Demander

                                    </a>

                                <?php endif; ?>


                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- =================================================
                 AUCUN PRODUIT
            ================================================== -->

            <div class="no-products">


                <div
                    class="no-products-icon"
                >

                    <i
                        class="fa-solid fa-box-open"
                    ></i>

                </div>


                <h2>

                    Aucun produit disponible

                </h2>


                <p>

                    Les produits ajoutés depuis l'espace
                    administrateur apparaîtront automatiquement
                    ici.

                </p>


                <a
                    href="../index.php"
                    class="btn orange"
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

<section class="products-cta">

    <div
        class="container products-cta-inner"
    >


        <div>


            <p class="products-eyebrow">

                BESOIN D'UN ÉQUIPEMENT ?

            </p>


            <h2>

                Vous recherchez un matériel informatique ?

            </h2>


            <p>

                Contactez SOFTEXPRESS pour obtenir des
                informations sur nos produits et leur
                disponibilité.

            </p>


        </div>


        <div
            class="products-cta-actions"
        >


            <a
                href="contact.php"
                class="product-cta-btn orange"
            >

                Nous contacter

            </a>


            <a
                href="formations.php"
                class="product-cta-btn white"
            >

                Voir nos formations

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


        <!-- COLONNE 1 -->

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


            <?php if (!$connecte): ?>

                <a
                    href="../auth/connexion.php"
                >

                    Connexion

                </a>

            <?php endif; ?>


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
     JAVASCRIPT GLOBAL
========================================================= -->

<script
    src="../assets/js/main.js"
></script>


</body>

</html>