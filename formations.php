<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - FORMATIONS
|--------------------------------------------------------------------------
| Page autonome :
| - Session correctement démarrée
| - Connexion à la base existante
| - Profil utilisateur cohérent avec l'accueil
| - CSS correctement chargé
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


function formatPrix($prix): string
{
    return number_format(
        (float)$prix,
        0,
        ',',
        ' '
    ) . ' FCFA';
}


function imageFormation(?string $image): string
{
    $image = basename(
        trim((string)$image)
    );

    $dossier = __DIR__ .
        '/../assets/images/formations/';

    if (
        $image !== '' &&
        is_file($dossier . $image)
    ) {
        return '../assets/images/formations/' .
            $image;
    }

    return '../assets/images/formations/informatique.jpg';
}


function descriptionCourte(
    ?string $texte,
    int $longueur = 120
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
| RÉCUPÉRATION DES FORMATIONS
|--------------------------------------------------------------------------
*/

$formations = [];


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
            ORDER BY id DESC
        ";

        $stmt = $pdo->query($sql);

        if ($stmt !== false) {

            $formations =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }

    } catch (Throwable $e) {

        $formations = [];
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
            ORDER BY id DESC
        ";

        $result = $conn->query($sql);

        if ($result !== false) {

            while (
                $row =
                $result->fetch_assoc()
            ) {

                $formations[] = $row;
            }

            $result->free();
        }

    } catch (Throwable $e) {

        $formations = [];
    }
}


/*
|--------------------------------------------------------------------------
| INFORMATIONS UTILISATEUR
|--------------------------------------------------------------------------
*/

$connecte =
    isset($_SESSION['user_id']);


$nomProfil = '';


if ($connecte) {

    $prenom =
        trim(
            (string)(
                $_SESSION['user_prenom'] ??
                ''
            )
        );

    $nom =
        trim(
            (string)(
                $_SESSION['user_nom'] ??
                ''
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
            $_SESSION['user_email'] ??
            ''
        )
        : '';


$roleProfil =
    $connecte
        ? (string)(
            $_SESSION['user_role'] ??
            'user'
        )
        : 'user';


/*
|--------------------------------------------------------------------------
| INITIALES DU PROFIL
|--------------------------------------------------------------------------
*/

$initiales = 'U';

if ($connecte) {

    $initiales = '';

    if (!empty($_SESSION['user_prenom'])) {

        $initiales .= strtoupper(
            mb_substr(
                (string)$_SESSION['user_prenom'],
                0,
                1
            )
        );
    }

    if (!empty($_SESSION['user_nom'])) {

        $initiales .= strtoupper(
            mb_substr(
                (string)$_SESSION['user_nom'],
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

    <title>Formations | SOFTEXPRESS</title>


    <!-- CSS GLOBAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS FORMATIONS -->

    <link
        rel="stylesheet"
        href="../assets/css/formations.css"
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


                    <!-- CERCLE -->

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


                    <!-- MENU -->

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
     BANNIÈRE
========================================================= -->

<section class="page-banner">

    <div class="page-banner-overlay"></div>


    <div class="container page-banner-content">


        <p class="eyebrow white">

            APPRENDRE • PROGRESSER • RÉUSSIR

        </p>


        <h1>

            NOS <span>FORMATIONS</span>

        </h1>


        <p>

            Développez vos compétences avec les
            formations professionnelles proposées
            par SOFTEXPRESS.

        </p>


        <div class="breadcrumb">

            <a href="../index.php">
                Accueil
            </a>

            <span>›</span>

            <strong>
                Formations
            </strong>

        </div>


    </div>

</section>



<!-- =========================================================
     CONTENU
========================================================= -->

<main>


<section class="formations-section">

    <div class="container">


        <!-- TITRE -->

        <div class="formations-heading">

            <div>

                <p class="eyebrow">
                    NOS PROGRAMMES
                </p>


                <h2>
                    Choisissez votre formation
                </h2>


                <p>

                    Découvrez nos formations et choisissez
                    celle qui correspond à vos objectifs.

                </p>

            </div>

        </div>



        <!-- =================================================
             FORMATIONS DISPONIBLES
        ================================================== -->

        <?php if (!empty($formations)): ?>


            <div class="formations-grid">


                <?php foreach (
                    $formations as $formation
                ): ?>


                    <article
                        class="formation-card"
                    >


                        <!-- IMAGE -->

                        <a
                            href="formation-details.php?id=<?= (int)$formation['id'] ?>"
                            class="formation-image"
                        >

                            <img
                                src="<?= e(
                                    imageFormation(
                                        $formation['image']
                                    )
                                ) ?>"
                                alt="<?= e(
                                    $formation['titre']
                                ) ?>"
                            >


                            <span
                                class="formation-overlay"
                            >

                                Voir les détails

                            </span>

                        </a>



                        <!-- CONTENU -->

                        <div
                            class="formation-content"
                        >


                            <h3>

                                <?= e(
                                    $formation['titre']
                                ) ?>

                            </h3>


                            <p
                                class="formation-description"
                            >

                                <?= e(
                                    descriptionCourte(
                                        $formation['description']
                                    )
                                ) ?>

                            </p>



                            <!-- INFORMATIONS -->

                            <div
                                class="formation-info"
                            >


                                <div>

                                    <span
                                        class="info-label"
                                    >
                                        Durée
                                    </span>


                                    <strong>

                                        <?= e(
                                            $formation['duree']
                                        ) ?>

                                    </strong>

                                </div>



                                <div>

                                    <span
                                        class="info-label"
                                    >
                                        Prix
                                    </span>


                                    <strong
                                        class="formation-price"
                                    >

                                        <?= formatPrix(
                                            $formation['prix']
                                        ) ?>

                                    </strong>

                                </div>


                            </div>



                            <!-- BOUTONS -->

                            <div
                                class="formation-actions"
                            >


                                <a
                                    href="formation-details.php?id=<?= (int)$formation['id'] ?>"
                                    class="formation-btn details-btn"
                                >

                                    Voir les détails

                                </a>


                                <a
                                    href="inscription-formation.php?formation_id=<?= (int)$formation['id'] ?>"
                                    class="formation-btn register-btn"
                                >

                                    S'inscrire

                                </a>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- AUCUNE FORMATION -->

            <div class="no-formations">


                <div
                    class="no-formations-icon"
                >
                    🎓
                </div>


                <h2>
                    Aucune formation disponible
                </h2>


                <p>

                    Les formations ajoutées depuis
                    l'espace administrateur apparaîtront
                    automatiquement sur cette page.

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

<section class="formations-cta">


    <div
        class="container formations-cta-inner"
    >


        <div>

            <p class="eyebrow white">

                BESOIN D'INFORMATIONS ?

            </p>


            <h2>

                Vous ne savez pas quelle
                formation choisir ?

            </h2>


            <p>

                Notre équipe est disponible pour
                vous accompagner dans votre choix.

            </p>

        </div>



        <div
            class="formations-cta-actions"
        >


            <a
                href="contact.php"
                class="btn orange big"
            >

                Nous contacter

            </a>


            <?php if (!$connecte): ?>

                <a
                    href="../auth/connexion.php"
                    class="btn ghost big"
                >

                    Se connecter

                </a>

            <?php endif; ?>


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