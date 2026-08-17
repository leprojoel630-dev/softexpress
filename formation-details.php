<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - DÉTAILS D'UNE FORMATION
|--------------------------------------------------------------------------
| Même système que formations.php :
| - Session
| - Profil utilisateur
| - Même navbar
| - Même CSS global
| - Même cercle utilisateur
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
| FONCTION ÉCHAPPEMENT
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
| RÉCUPÉRATION DE LA FORMATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header('Location: formations.php');
    exit;
}

$id = (int) $_GET['id'];

$formation = null;


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
                titre,
                description,
                duree,
                prix,
                image,
                date_creation
            FROM formations
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$id]);

        $formation = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

    } catch (Throwable $e) {

        $formation = null;
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
                titre,
                description,
                duree,
                prix,
                image,
                date_creation
            FROM formations
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                'i',
                $id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if ($result) {

                $formation =
                    $result->fetch_assoc();

                $result->free();
            }

            $stmt->close();
        }

    } catch (Throwable $e) {

        $formation = null;
    }
}


/*
|--------------------------------------------------------------------------
| FORMATION INTROUVABLE
|--------------------------------------------------------------------------
*/

if (
    !$formation ||
    !is_array($formation)
) {
    header('Location: formations.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DONNÉES FORMATION
|--------------------------------------------------------------------------
*/

$titre = e(
    $formation['titre'] ?? 'Formation'
);

$description =
    (string)(
        $formation['description'] ?? ''
    );

$duree = e(
    $formation['duree'] ?? ''
);

$prix =
    $formation['prix'] ?? '';


/*
|--------------------------------------------------------------------------
| IMAGE
|--------------------------------------------------------------------------
*/

$imageNom =
    basename(
        trim(
            (string)(
                $formation['image'] ?? ''
            )
        )
    );

$imageDossier =
    __DIR__ .
    '/../assets/images/formations/';


if (
    $imageNom !== '' &&
    is_file(
        $imageDossier . $imageNom
    )
) {

    $imagePath =
        '../assets/images/formations/' .
        $imageNom;

} else {

    $imagePath =
        '../assets/images/formations/informatique.jpg';
}


/*
|--------------------------------------------------------------------------
| PROFIL UTILISATEUR
|--------------------------------------------------------------------------
*/

$connecte =
    isset($_SESSION['user_id']);


$nomProfil = '';


if ($connecte) {

    $prenom =
        trim(
            (string)(
                $_SESSION['user_prenom'] ?? ''
            )
        );

    $nom =
        trim(
            (string)(
                $_SESSION['user_nom'] ?? ''
            )
        );

    $nomProfil =
        trim(
            $prenom . ' ' . $nom
        );
}


$emailProfil =
    $connecte
        ? (string)(
            $_SESSION['user_email'] ?? ''
        )
        : '';


$roleProfil =
    $connecte
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

    if (
        !empty(
            $_SESSION['user_prenom']
        )
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


    if (
        !empty(
            $_SESSION['user_nom']
        )
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


    if ($initiales === '') {

        $initiales = 'U';
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
        content="Découvrez la formation <?= $titre ?> proposée par SOFTEXPRESS."
    >

    <title>
        <?= $titre ?> | SOFTEXPRESS
    </title>


    <!-- CSS GLOBAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- ICÔNES -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
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

            <a
                href="../index.php"
            >
                Accueil
            </a>


            <a
                href="apropos.php"
            >
                À propos
            </a>


            <a
                href="formations.php"
                class="active"
            >
                Formations
            </a>


            <a
                href="produits.php"
            >
                Produits
            </a>


            <a
                href="maintenance.php"
            >
                Maintenance
            </a>


            <a
                href="actualites.php"
            >
                Actualités
            </a>


            <a
                href="contact.php"
            >
                Contact
            </a>

        </nav>


        <!-- =====================================================
             ESPACE UTILISATEUR
        ====================================================== -->

        <div class="auth">


            <?php if ($connecte): ?>


                <!-- PROFIL CONNECTÉ -->

                <div class="user-menu">


                    <!-- CERCLE UTILISATEUR -->

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


                    <!-- MENU DÉROULANT -->

                    <div class="user-dropdown">


                        <!-- INFORMATIONS -->

                        <div class="user-dropdown-header">

                            <span class="user-avatar">

                                <?= e($initiales) ?>

                            </span>


                            <div>

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


                        <!-- ADMINISTRATION -->

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


                        <!-- DÉCONNEXION -->

                        <a
                            href="../auth/deconnexion.php"
                            class="profile-logout"
                        >

                            ↪ Déconnexion

                        </a>

                    </div>

                </div>


            <?php else: ?>


                <!-- VISITEUR -->

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
     CONTENU FORMATION
========================================================= -->

<main class="sx-page">


    <!-- =========================================================
         FIL D'ARIANE
    ========================================================= -->

    <div class="sx-container">

        <div class="sx-breadcrumb">

            <a href="../index.php">
                Accueil
            </a>

            <i class="fa-solid fa-chevron-right"></i>

            <a href="formations.php">
                Formations
            </a>

            <i class="fa-solid fa-chevron-right"></i>

            <span>
                <?= $titre ?>
            </span>

        </div>

    </div>



    <!-- =========================================================
         HERO FORMATION
    ========================================================= -->

    <section class="sx-formation-hero">

        <div class="sx-container">

            <div class="sx-formation-grid">


                <!-- IMAGE -->

                <div class="sx-formation-image-box">

                    <div class="sx-image-decoration"></div>

                    <img
                        src="<?= e($imagePath) ?>"
                        alt="<?= $titre ?>"
                        class="sx-formation-image"
                    >

                </div>



                <!-- INFORMATIONS -->

                <div class="sx-formation-content">


                    <div class="sx-small-title">

                        FORMATION SOFTEXPRESS

                    </div>


                    <h1>

                        <?= $titre ?>

                    </h1>


                    <div class="sx-title-line"></div>


                    <p class="sx-intro">

                        Développez vos compétences et maîtrisez
                        les connaissances nécessaires pour évoluer
                        efficacement dans le monde professionnel.

                    </p>



                    <!-- INFORMATIONS -->

                    <div class="sx-info-row">


                        <?php if ($duree !== ''): ?>

                            <div class="sx-info-card">


                                <div class="sx-info-icon">

                                    <i class="fa-regular fa-clock"></i>

                                </div>


                                <div>

                                    <span>
                                        Durée
                                    </span>

                                    <strong>

                                        <?= $duree ?>

                                    </strong>

                                </div>


                            </div>

                        <?php endif; ?>



                        <?php if ($prix !== ''): ?>

                            <div class="sx-info-card">


                                <div
                                    class="sx-info-icon sx-price-icon"
                                >

                                    <i
                                        class="fa-solid fa-money-bill-wave"
                                    ></i>

                                </div>


                                <div>

                                    <span>
                                        Tarif
                                    </span>

                                    <strong>

                                        <?= number_format(
                                            (float)$prix,
                                            0,
                                            ',',
                                            ' '
                                        ) ?>

                                        FCFA

                                    </strong>

                                </div>


                            </div>

                        <?php endif; ?>


                    </div>



                    <!-- ACTIONS -->

                    <div class="sx-actions">


                        <a
                            href="inscription-formation.php?formation_id=<?= $id ?>"
                            class="sx-btn sx-btn-primary"
                        >

                            <i
                                class="fa-solid fa-user-plus"
                            ></i>

                            S'inscrire à cette formation

                        </a>


                        <a
                            href="formations.php"
                            class="sx-btn sx-btn-outline"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Retour aux formations

                        </a>


                    </div>


                </div>

            </div>

        </div>

    </section>



    <!-- =========================================================
         DESCRIPTION
    ========================================================= -->

    <section class="sx-description-section">

        <div class="sx-container">

            <div class="sx-description-grid">


                <!-- CONTENU -->

                <div class="sx-description">


                    <span class="sx-section-label">

                        À PROPOS DE LA FORMATION

                    </span>


                    <h2>

                        Présentation

                    </h2>


                    <div class="sx-heading-line"></div>


                    <div class="sx-description-text">

                        <?php

                        echo nl2br(
                            e($description)
                        );

                        ?>

                    </div>


                </div>



                <!-- CARTE LATÉRALE -->

                <aside class="sx-side-card">


                    <div class="sx-side-icon">

                        <i
                            class="fa-solid fa-graduation-cap"
                        ></i>

                    </div>


                    <h3>

                        Prêt à commencer ?

                    </h3>


                    <p>

                        Inscrivez-vous dès maintenant et
                        développez vos compétences avec
                        SOFTEXPRESS.

                    </p>


                    <a
                        href="inscription-formation.php?formation_id=<?= $id ?>"
                        class="sx-side-btn"
                    >

                        S'inscrire

                        <i
                            class="fa-solid fa-arrow-right"
                        ></i>

                    </a>


                </aside>


            </div>

        </div>

    </section>



    <!-- =========================================================
         AVANTAGES
    ========================================================= -->

    <section class="sx-benefits-section">

        <div class="sx-container">


            <div class="sx-section-header">


                <span>

                    SOFTEXPRESS

                </span>


                <h2>

                    Pourquoi choisir cette formation ?

                </h2>


                <p>

                    Une approche pensée pour vous aider à
                    acquérir des compétences utiles et
                    directement applicables.

                </p>


            </div>



            <div class="sx-benefits-grid">


                <!-- AVANTAGE 1 -->

                <div class="sx-benefit">


                    <div class="sx-benefit-icon">

                        <i
                            class="fa-solid fa-laptop"
                        ></i>

                    </div>


                    <h3>

                        Formation pratique

                    </h3>


                    <p>

                        Apprenez à travers des exemples et
                        des situations proches du monde
                        professionnel.

                    </p>


                </div>



                <!-- AVANTAGE 2 -->

                <div class="sx-benefit">


                    <div class="sx-benefit-icon">

                        <i
                            class="fa-solid fa-person-chalkboard"
                        ></i>

                    </div>


                    <h3>

                        Accompagnement

                    </h3>


                    <p>

                        Bénéficiez d'un accompagnement
                        adapté à votre progression.

                    </p>


                </div>



                <!-- AVANTAGE 3 -->

                <div class="sx-benefit">


                    <div class="sx-benefit-icon">

                        <i
                            class="fa-solid fa-certificate"
                        ></i>

                    </div>


                    <h3>

                        Compétences professionnelles

                    </h3>


                    <p>

                        Développez des compétences utiles
                        pour vos études ou votre activité.

                    </p>


                </div>


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
     JAVASCRIPT
========================================================= -->

<script
    src="../assets/js/main.js"
></script>


</body>

</html>