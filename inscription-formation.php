<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - INSCRIPTION À UNE FORMATION
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

$connecte = isset($_SESSION['user_id'])
    && $_SESSION['user_id'] !== '';

$userId = (int)($_SESSION['user_id'] ?? 0);

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
| SI PAS CONNECTÉ
|--------------------------------------------------------------------------
*/

if (!$connecte || $userId <= 0) {

    $formationIdConnexion = 0;

    if (
        isset($_GET['id'])
        && is_numeric($_GET['id'])
    ) {
        $formationIdConnexion = (int)$_GET['id'];
    }

    if (
        $formationIdConnexion <= 0
        &&
        isset($_GET['formation_id'])
        &&
        is_numeric($_GET['formation_id'])
    ) {
        $formationIdConnexion = (int)$_GET['formation_id'];
    }

    $urlConnexion = '../auth/connexion.php';

    if ($formationIdConnexion > 0) {

        $urlConnexion .=
            '?redirect=' .
            urlencode(
                'inscriptions-formations.php?id=' .
                $formationIdConnexion
            );
    }

    header(
        'Location: ' . $urlConnexion
    );

    exit;
}


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

    $initiales = mb_substr(
        $nomUtilisateur,
        0,
        1
    );
}

$initiales = mb_strtoupper(
    $initiales
);


/*
|--------------------------------------------------------------------------
| ID FORMATION
|--------------------------------------------------------------------------
*/

$formationId = 0;


/*
| GET
*/

if (
    isset($_GET['id'])
    &&
    is_numeric($_GET['id'])
) {

    $formationId = (int)$_GET['id'];
}


/*
| GET alternative
*/

if (
    $formationId <= 0
    &&
    isset($_GET['formation_id'])
    &&
    is_numeric($_GET['formation_id'])
) {

    $formationId = (int)$_GET['formation_id'];
}


/*
| POST
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['formation_id'])
    &&
    is_numeric($_POST['formation_id'])
) {

    $formationId = (int)$_POST['formation_id'];
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION ID FORMATION
|--------------------------------------------------------------------------
*/

if ($formationId <= 0) {

    die(
        '<div style="
            font-family:Arial,sans-serif;
            max-width:700px;
            margin:80px auto;
            padding:30px;
            text-align:center;
            border:1px solid #eee;
            border-radius:15px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
        ">
            <h2 style="color:#d9534f;">
                Formation introuvable
            </h2>

            <p>
                L’identifiant de la formation n’a pas été transmis.
            </p>

            <a
                href="formations.php"
                style="
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 20px;
                    background:#F99D1C;
                    color:white;
                    text-decoration:none;
                    border-radius:8px;
                "
            >
                Retour aux formations
            </a>
        </div>'
    );
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$formation = null;

$telephone = '';

$success = '';

$error = '';


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DE LA FORMATION
|--------------------------------------------------------------------------
*/

if (
    isset($conn)
    &&
    $conn instanceof mysqli
) {

    try {

        $stmt = $conn->prepare("
            SELECT
                id,
                titre,
                description,
                prix,
                image
            FROM formations
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            throw new Exception(
                'Impossible de préparer la requête.'
            );
        }

        $stmt->bind_param(
            'i',
            $formationId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result) {

            $formation =
                $result->fetch_assoc();
        }

        $stmt->close();

    } catch (Throwable $e) {

        $formation = null;
    }
}


/*
|--------------------------------------------------------------------------
| FORMATION INTROUVABLE
|--------------------------------------------------------------------------
*/

if (!$formation) {

    die(
        '<div style="
            font-family:Arial,sans-serif;
            max-width:700px;
            margin:80px auto;
            padding:30px;
            text-align:center;
            border:1px solid #eee;
            border-radius:15px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
        ">
            <h2 style="color:#d9534f;">
                Formation introuvable
            </h2>

            <p>
                La formation demandée n’existe pas dans la base de données.
            </p>

            <a
                href="formations.php"
                style="
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 20px;
                    background:#F99D1C;
                    color:white;
                    text-decoration:none;
                    border-radius:8px;
                "
            >
                Retour aux formations
            </a>
        </div>'
    );
}


/*
|--------------------------------------------------------------------------
| DONNÉES FORMATION
|--------------------------------------------------------------------------
*/

$formationTitre = trim(
    (string)($formation['titre'] ?? '')
);

$formationDescription = trim(
    (string)($formation['description'] ?? '')
);

$formationPrix = (float)(
    $formation['prix'] ?? 0
);

$formationImage = basename(
    trim(
        (string)($formation['image'] ?? '')
    )
);


/*
|--------------------------------------------------------------------------
| IMAGE
|--------------------------------------------------------------------------
*/

$imageFormation =
    '../assets/images/formations/' .
    $formationImage;

$dossierImages =
    __DIR__ .
    '/../assets/images/formations/';


if (
    $formationImage === ''
    ||
    !is_file(
        $dossierImages .
        $formationImage
    )
) {

    $imageFormation =
        '../assets/images/formations/default.jpg';
}


/*
|--------------------------------------------------------------------------
| PRIX
|--------------------------------------------------------------------------
*/

$prixAffiche =
    number_format(
        $formationPrix,
        0,
        ',',
        ' '
    ) .
    ' FCFA';


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $telephone = trim(
        (string)(
            $_POST['telephone'] ?? ''
        )
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION TÉLÉPHONE
    |--------------------------------------------------------------------------
    */

    if ($telephone === '') {

        $error =
            'Veuillez renseigner votre numéro de téléphone.';
    }


    /*
    |--------------------------------------------------------------------------
    | VÉRIFICATION CONNEXION MYSQL
    |--------------------------------------------------------------------------
    */

    elseif (
        !isset($conn)
        ||
        !($conn instanceof mysqli)
    ) {

        $error =
            'La connexion à la base de données est indisponible.';
    }


    /*
    |--------------------------------------------------------------------------
    | ENREGISTREMENT
    |--------------------------------------------------------------------------
    */

    else {

        try {

            /*
            |------------------------------------------------------------------
            | TABLE :
            |
            | inscriptions
            |
            | id
            | nom
            | prenom
            | telephone
            | email
            | formation_id
            | date_inscription
            |------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO inscriptions
                (
                    nom,
                    prenom,
                    telephone,
                    email,
                    formation_id,
                    date_inscription
                )
                VALUES
                (?, ?, ?, ?, ?, NOW())
            ");


            if (!$stmt) {

                throw new Exception(
                    'Erreur lors de la préparation de la requête.'
                );
            }


            /*
            |------------------------------------------------------------------
            | PARAMÈTRES
            |------------------------------------------------------------------
            */

            $stmt->bind_param(
                'ssssi',
                $userNom,
                $userPrenom,
                $telephone,
                $userEmail,
                $formationId
            );


            /*
            |------------------------------------------------------------------
            | EXÉCUTION
            |------------------------------------------------------------------
            */

            if (!$stmt->execute()) {

                throw new Exception(
                    $stmt->error
                );
            }


            /*
            |------------------------------------------------------------------
            | FERMETURE
            |------------------------------------------------------------------
            */

            $stmt->close();


            /*
            |------------------------------------------------------------------
            | SUCCÈS
            |------------------------------------------------------------------
            */

            $success =
                'Votre demande d’inscription a été envoyée avec succès. '
                .
                'SOFTEXPRESS vous contactera prochainement.';


            $telephone = '';

        } catch (Throwable $e) {

            /*
            |------------------------------------------------------------------
            | ERREUR
            |------------------------------------------------------------------
            */

            $error =
                'Impossible d’enregistrer votre inscription. '
                .
                'Veuillez réessayer dans quelques instants.';
        }
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
        content="Inscription à la formation <?= e($formationTitre) ?> - SOFTEXPRESS."
    >

    <title>
        Inscription - <?= e($formationTitre) ?> | SOFTEXPRESS
    </title>


    <!-- CSS PRINCIPAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        .formation-inscription-page {
            min-height:100vh;
            background:#f7fafc;
            padding-bottom:80px;
        }


        .formation-inscription-banner {
            padding:55px 0;
            background:
                linear-gradient(
                    135deg,
                    #00A3E0,
                    #007cae
                );
            color:#ffffff;
        }


        .formation-inscription-banner .eyebrow {
            margin:0 0 10px;
            font-size:12px;
            font-weight:800;
            letter-spacing:2px;
            text-transform:uppercase;
        }


        .formation-inscription-banner h1 {
            margin:0;
            max-width:800px;
            font-size:clamp(30px,5vw,48px);
            line-height:1.15;
        }


        .formation-inscription-content {
            padding:55px 0;
        }


        .formation-inscription-grid {
            display:grid;
            grid-template-columns:
                minmax(280px,420px)
                minmax(0,1fr);
            gap:35px;
            align-items:start;
        }


        .formation-summary {
            background:#ffffff;
            border-radius:18px;
            overflow:hidden;
            border:1px solid #e7edf1;
            box-shadow:
                0 12px 35px
                rgba(0,0,0,.06);
        }


        .formation-summary-image {
            width:100%;
            height:250px;
            background:#f3f6f8;
            overflow:hidden;
        }


        .formation-summary-image img {
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
        }


        .formation-summary-content {
            padding:25px;
        }


        .formation-summary-content h2 {
            margin:0 0 15px;
            color:#111827;
            font-size:25px;
        }


        .formation-summary-content p {
            color:#657080;
            line-height:1.8;
            font-size:14px;
        }


        .formation-summary-price {
            margin-top:20px;
            padding:15px;
            border-radius:10px;
            background:#fff8ee;
            color:#F99D1C;
            font-size:23px;
            font-weight:800;
        }


        .formation-inscription-card {
            background:#ffffff;
            padding:35px;
            border-radius:18px;
            border:1px solid #e7edf1;
            box-shadow:
                0 12px 35px
                rgba(0,0,0,.06);
        }


        .formation-inscription-card .card-label {
            color:#00A3E0;
            font-size:12px;
            font-weight:800;
            letter-spacing:1.5px;
        }


        .formation-inscription-card h2 {
            margin:8px 0 25px;
            color:#111827;
            font-size:30px;
        }


        .formation-alert {
            display:flex;
            align-items:flex-start;
            gap:10px;
            padding:14px 16px;
            margin-bottom:20px;
            border-radius:9px;
            font-size:14px;
            line-height:1.5;
        }


        .formation-alert.success {
            color:#187344;
            background:#edf9f2;
            border:1px solid #ccebd8;
        }


        .formation-alert.error {
            color:#a52b2b;
            background:#fff0f0;
            border:1px solid #f3cccc;
        }


        .formation-form-group {
            margin-bottom:20px;
        }


        .formation-form-group label {
            display:block;
            margin-bottom:8px;
            color:#29313d;
            font-size:14px;
            font-weight:700;
        }


        .formation-form-group input {
            width:100%;
            box-sizing:border-box;
            padding:13px 15px;
            border:1px solid #dce3e8;
            border-radius:9px;
            outline:none;
            background:#ffffff;
            color:#1f2937;
            font-family:inherit;
            font-size:14px;
            transition:.2s ease;
        }


        .formation-form-group input:focus {
            border-color:#00A3E0;
            box-shadow:
                0 0 0 3px
                rgba(0,163,224,.10);
        }


        .formation-submit {
            width:100%;
            min-height:52px;
            border:none;
            border-radius:9px;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            color:#ffffff;
            background:#F99D1C;
            font-family:inherit;
            font-size:15px;
            font-weight:800;
            transition:.25s ease;
        }


        .formation-submit:hover {
            background:#e58b0b;
            transform:translateY(-2px);
        }


        .formation-note {
            margin:15px 0 0;
            text-align:center;
            color:#8a94a6;
            font-size:12px;
        }


        .user-menu {
            position:relative;
            display:flex;
            align-items:center;
        }


        .user-profile {
            border:none;
            background:transparent;
            padding:4px;
            display:flex;
            align-items:center;
            cursor:pointer;
        }


        .user-avatar {
            width:40px;
            height:40px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#ffffff;
            background:
                linear-gradient(
                    135deg,
                    #F99D1C,
                    #00A3E0
                );
            font-size:13px;
            font-weight:800;
            text-transform:uppercase;
            box-shadow:
                0 4px 12px
                rgba(0,163,224,.20);
        }


        .user-dropdown {
            position:absolute;
            top:calc(100% + 10px);
            right:0;
            width:270px;
            padding:10px;
            background:#ffffff;
            border:1px solid #e8edf1;
            border-radius:14px;
            box-shadow:
                0 15px 40px
                rgba(0,0,0,.12);
            z-index:99999;
            opacity:0;
            visibility:hidden;
            transform:translateY(-8px);
            transition:.2s ease;
        }


        .user-dropdown.show {
            opacity:1;
            visibility:visible;
            transform:translateY(0);
        }


        .user-dropdown-header {
            display:flex;
            align-items:center;
            gap:12px;
            padding:10px;
        }


        .user-dropdown-info {
            min-width:0;
            display:flex;
            flex-direction:column;
            gap:3px;
        }


        .user-dropdown-info strong {
            color:#111827;
            font-size:14px;
        }


        .user-dropdown-info span {
            color:#687385;
            font-size:12px;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }


        .user-dropdown-divider {
            height:1px;
            margin:6px 4px;
            background:#edf0f3;
        }


        .user-dropdown-item {
            display:flex;
            align-items:center;
            gap:10px;
            padding:11px 12px;
            border-radius:9px;
            color:#394150;
            text-decoration:none;
            font-size:13px;
            font-weight:600;
        }


        .user-dropdown-item:hover {
            background:#f3f9fc;
            color:#00A3E0;
        }


        .user-dropdown-item i {
            width:18px;
            text-align:center;
            color:#00A3E0;
        }


        .profile-admin i {
            color:#F99D1C;
        }


        .profile-logout {
            color:#d13b3b;
        }


        .profile-logout i {
            color:#d13b3b;
        }


        @media (max-width:850px) {

            .formation-inscription-grid {
                grid-template-columns:1fr;
            }
        }


        @media (max-width:600px) {

            .formation-inscription-banner {
                padding:40px 0;
            }


            .formation-inscription-content {
                padding:35px 0;
            }


            .formation-inscription-card {
                padding:22px;
            }


            .formation-summary-content {
                padding:20px;
            }


            .user-dropdown {
                right:-5px;
                width:260px;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

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


        <nav class="main-nav">

            <a href="../index.php">
                Accueil
            </a>

            <a href="apropos.php">
                À propos
            </a>

            <a
                href="formations.php"
                class="active"
            >
                Formations
            </a>

            <a href="produits.php">
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


        <div class="auth">

            <?php if ($connecte): ?>

                <div class="user-menu">

                    <button
                        type="button"
                        class="user-profile"
                        id="formationUserProfileButton"
                        aria-label="Ouvrir mon profil"
                        aria-expanded="false"
                    >

                        <span class="user-avatar">
                            <?= e($initiales) ?>
                        </span>

                    </button>


                    <div
                        class="user-dropdown"
                        id="formationUserDropdown"
                    >

                        <div class="user-dropdown-header">

                            <span class="user-avatar">
                                <?= e($initiales) ?>
                            </span>


                            <div class="user-dropdown-info">

                                <strong>
                                    <?= e($nomUtilisateur) ?>
                                </strong>

                                <span>
                                    <?= e($userEmail) ?>
                                </span>

                            </div>

                        </div>


                        <div class="user-dropdown-divider"></div>


                        <?php if (
                            strtolower($userRole) === 'admin'
                        ): ?>

                            <a
                                href="../admin/index.php"
                                class="user-dropdown-item profile-admin"
                            >

                                <i class="fa-solid fa-gear"></i>

                                Administration

                            </a>

                        <?php endif; ?>


                        <a
                            href="../auth/deconnexion.php"
                            class="user-dropdown-item profile-logout"
                        >

                            <i class="fa-solid fa-right-from-bracket"></i>

                            Déconnexion

                        </a>

                    </div>

                </div>

            <?php else: ?>

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

<section class="formation-inscription-banner">

    <div class="container">

        <p class="eyebrow">
            SOFTEXPRESS — FORMATION
        </p>

        <h1>
            Inscription à la formation
        </h1>

    </div>

</section>


<!-- =========================================================
     CONTENU
========================================================= -->

<main class="formation-inscription-page">

    <section class="formation-inscription-content">

        <div class="container">

            <div class="formation-inscription-grid">


                <!-- FORMATION -->

                <div class="formation-summary">

                    <div class="formation-summary-image">

                        <img
                            src="<?= e($imageFormation) ?>"
                            alt="<?= e($formationTitre) ?>"
                        >

                    </div>


                    <div class="formation-summary-content">

                        <h2>
                            <?= e($formationTitre) ?>
                        </h2>


                        <?php if (
                            $formationDescription !== ''
                        ): ?>

                            <p>

                                <?= nl2br(
                                    e(
                                        $formationDescription
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <div class="formation-summary-price">

                            <?= e($prixAffiche) ?>

                        </div>

                    </div>

                </div>


                <!-- FORMULAIRE -->

                <div class="formation-inscription-card">

                    <span class="card-label">
                        VOTRE DEMANDE
                    </span>


                    <h2>
                        Finaliser votre inscription
                    </h2>


                    <?php if (
                        $success !== ''
                    ): ?>

                        <div
                            class="formation-alert success"
                        >

                            <i
                                class="fa-solid fa-circle-check"
                            ></i>

                            <span>
                                <?= e($success) ?>
                            </span>

                        </div>


                        <div style="
                            margin-top:25px;
                            padding:20px;
                            background:#f7fafc;
                            border-radius:12px;
                            text-align:center;
                        ">

                            <a
                                href="formations.php"
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:8px;
                                    padding:13px 20px;
                                    background:#00A3E0;
                                    color:#fff;
                                    text-decoration:none;
                                    border-radius:8px;
                                    font-weight:700;
                                "
                            >

                                <i
                                    class="fa-solid fa-arrow-left"
                                ></i>

                                Retour aux formations

                            </a>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $error !== ''
                    ): ?>

                        <div
                            class="formation-alert error"
                        >

                            <i
                                class="fa-solid fa-circle-exclamation"
                            ></i>

                            <span>
                                <?= e($error) ?>
                            </span>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $success === ''
                    ): ?>

                        <!--
                        IMPORTANT :
                        action vide = envoi vers CETTE MÊME PAGE.
                        Cela évite le problème 404.
                        -->

                        <form
                            method="POST"
                            action=""
                        >


                            <!-- ID FORMATION -->

                            <input
                                type="hidden"
                                name="formation_id"
                                value="<?= (int)$formationId ?>"
                            >


                            <!-- NOM -->

                            <div class="formation-form-group">

                                <label>
                                    Nom
                                </label>

                                <input
                                    type="text"
                                    value="<?= e($userNom) ?>"
                                    readonly
                                >

                            </div>


                            <!-- PRÉNOM -->

                            <div class="formation-form-group">

                                <label>
                                    Prénom
                                </label>

                                <input
                                    type="text"
                                    value="<?= e($userPrenom) ?>"
                                    readonly
                                >

                            </div>


                            <!-- EMAIL -->

                            <div class="formation-form-group">

                                <label>
                                    Adresse email
                                </label>

                                <input
                                    type="email"
                                    value="<?= e($userEmail) ?>"
                                    readonly
                                >

                            </div>


                            <!-- TÉLÉPHONE -->

                            <div class="formation-form-group">

                                <label for="telephone">
                                    Téléphone *
                                </label>

                                <input
                                    type="tel"
                                    id="telephone"
                                    name="telephone"
                                    value="<?= e($telephone) ?>"
                                    placeholder="+237 6XX XX XX XX"
                                    required
                                >

                            </div>


                            <!-- BOUTON -->

                            <button
                                type="submit"
                                class="formation-submit"
                            >

                                Envoyer ma demande

                                <i
                                    class="fa-solid fa-paper-plane"
                                ></i>

                            </button>


                            <p class="formation-note">

                                <i
                                    class="fa-solid fa-lock"
                                ></i>

                                Vos informations restent
                                confidentielles.

                            </p>

                        </form>

                    <?php endif; ?>

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


<!-- JAVASCRIPT PRINCIPAL -->

<script src="../assets/js/main.js"></script>


<!-- PROFIL -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const button =
            document.getElementById(
                'formationUserProfileButton'
            );

        const dropdown =
            document.getElementById(
                'formationUserDropdown'
            );


        if (
            !button ||
            !dropdown
        ) {
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