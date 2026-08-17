<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - DEMANDE DE MAINTENANCE
|--------------------------------------------------------------------------
| Page permettant à un utilisateur de demander un service de maintenance.
|
| Table :
| demande_maintenance
|
| id
| nom
| prenom
| telephone
| email
| service_id
| appareil
| description_probleme
| statut
| date_demande
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
| VARIABLES UTILISATEUR
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
| COMPATIBILITÉ AVEC ANCIENNE STRUCTURE DE SESSION
|--------------------------------------------------------------------------
|
| Si certaines anciennes pages utilisent encore $_SESSION['user'],
| on récupère les informations sans casser le reste du projet.
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
            (string)($user['prenom'] ?? '')
        );

        $userNom = trim(
            (string)($user['nom'] ?? '')
        );

        $userEmail = trim(
            (string)($user['email'] ?? '')
        );

        $userRole = trim(
            (string)($user['role'] ?? '')
        );

        /*
         * Si l'ancien système contient bien un utilisateur,
         * on considère celui-ci comme connecté.
         */

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
| COMPATIBILITÉ VARIABLES DIRECTES
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    $ancienNom = trim(
        (string)($_SESSION['nom'] ?? '')
    );

    $ancienPrenom = trim(
        (string)($_SESSION['prenom'] ?? '')
    );

    $ancienEmail = trim(
        (string)($_SESSION['email'] ?? '')
    );

    $ancienRole = trim(
        (string)($_SESSION['role'] ?? '')
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


/*
|--------------------------------------------------------------------------
| SI AUCUNE INITIALE
|--------------------------------------------------------------------------
*/

if ($initiales === '') {

    if ($userEmail !== '') {

        $initiales = mb_substr(
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

$initiales = mb_strtoupper(
    $initiales
);


/*
|--------------------------------------------------------------------------
| VARIABLES DE TRAITEMENT
|--------------------------------------------------------------------------
*/

$service = null;

$erreurs = [];

$success = false;


/*
|--------------------------------------------------------------------------
| SERVICE SÉLECTIONNÉ
|--------------------------------------------------------------------------
*/

$serviceId = 0;


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DEPUIS GET
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['service_id'])
    &&
    is_numeric($_GET['service_id'])
) {

    $serviceId = (int)$_GET['service_id'];
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DEPUIS POST
|--------------------------------------------------------------------------
*/

if (
    isset($_POST['service_id'])
    &&
    is_numeric($_POST['service_id'])
) {

    $serviceId = (int)$_POST['service_id'];
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU SERVICE
|--------------------------------------------------------------------------
*/

if ($serviceId > 0) {


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
                    prix
                FROM services_maintenance
                WHERE id = ?
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $serviceId
            ]);

            $service = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        } catch (Throwable $e) {

            $service = null;
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
                    prix
                FROM services_maintenance
                WHERE id = ?
                LIMIT 1
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    'i',
                    $serviceId
                );

                $stmt->execute();

                $result = $stmt->get_result();

                if ($result) {

                    $service = $result->fetch_assoc();
                }

                $stmt->close();
            }

        } catch (Throwable $e) {

            $service = null;
        }
    }
}


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | RÉCUPÉRATION DES DONNÉES
    |--------------------------------------------------------------------------
    */

    $nom = trim(
        (string)($_POST['nom'] ?? '')
    );

    $prenom = trim(
        (string)($_POST['prenom'] ?? '')
    );

    $telephone = trim(
        (string)($_POST['telephone'] ?? '')
    );

    $email = trim(
        (string)($_POST['email'] ?? '')
    );

    $appareil = trim(
        (string)($_POST['appareil'] ?? '')
    );

    $description = trim(
        (string)($_POST['description_probleme'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION DU SERVICE
    |--------------------------------------------------------------------------
    */

    if ($serviceId <= 0) {

        $erreurs[] =
            'Le service de maintenance sélectionné est invalide.';
    }


    /*
    |--------------------------------------------------------------------------
    | NOM
    |--------------------------------------------------------------------------
    */

    if ($nom === '') {

        $erreurs[] =
            'Veuillez renseigner votre nom.';
    }


    /*
    |--------------------------------------------------------------------------
    | PRÉNOM
    |--------------------------------------------------------------------------
    */

    if ($prenom === '') {

        $erreurs[] =
            'Veuillez renseigner votre prénom.';
    }


    /*
    |--------------------------------------------------------------------------
    | TÉLÉPHONE
    |--------------------------------------------------------------------------
    */

    if ($telephone === '') {

        $erreurs[] =
            'Veuillez renseigner votre numéro de téléphone.';
    }


    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $erreurs[] =
            'Veuillez renseigner votre adresse email.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erreurs[] =
            'Veuillez renseigner une adresse email valide.';
    }


    /*
    |--------------------------------------------------------------------------
    | APPAREIL
    |--------------------------------------------------------------------------
    */

    if ($appareil === '') {

        $erreurs[] =
            'Veuillez indiquer l’appareil concerné.';
    }


    /*
    |--------------------------------------------------------------------------
    | DESCRIPTION
    |--------------------------------------------------------------------------
    */

    if ($description === '') {

        $erreurs[] =
            'Veuillez décrire le problème rencontré.';
    }


    /*
    |--------------------------------------------------------------------------
    | ENREGISTREMENT
    |--------------------------------------------------------------------------
    */

    if (empty($erreurs)) {


        /*
        |--------------------------------------------------------------------------
        | SERVICE EXISTANT ?
        |--------------------------------------------------------------------------
        */

        if (!$service) {

            $erreurs[] =
                'Le service de maintenance sélectionné n’existe pas.';

        } else {


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
                        INSERT INTO demande_maintenance
                        (
                            nom,
                            prenom,
                            telephone,
                            email,
                            service_id,
                            appareil,
                            description_probleme,
                            statut,
                            date_demande
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'En attente',
                            NOW()
                        )
                    ";

                    $stmt = $pdo->prepare(
                        $sql
                    );

                    $stmt->execute([
                        $nom,
                        $prenom,
                        $telephone,
                        $email,
                        $serviceId,
                        $appareil,
                        $description
                    ]);

                    $success = true;

                } catch (Throwable $e) {

                    $erreurs[] =
                        'Impossible d’enregistrer votre demande pour le moment.';
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
                        INSERT INTO demande_maintenance
                        (
                            nom,
                            prenom,
                            telephone,
                            email,
                            service_id,
                            appareil,
                            description_probleme,
                            statut,
                            date_demande
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'En attente',
                            NOW()
                        )
                    ";

                    $stmt = $conn->prepare(
                        $sql
                    );

                    if (!$stmt) {

                        throw new Exception(
                            'Erreur de préparation SQL.'
                        );
                    }

                    $stmt->bind_param(
                        'ssssiss',
                        $nom,
                        $prenom,
                        $telephone,
                        $email,
                        $serviceId,
                        $appareil,
                        $description
                    );

                    if ($stmt->execute()) {

                        $success = true;

                    } else {

                        $erreurs[] =
                            'Impossible d’enregistrer votre demande.';
                    }

                    $stmt->close();

                } catch (Throwable $e) {

                    $erreurs[] =
                        'Impossible d’enregistrer votre demande pour le moment.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | AUCUNE CONNEXION
            |--------------------------------------------------------------------------
            */

            else {

                $erreurs[] =
                    'La connexion à la base de données est indisponible.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| VALEURS DU FORMULAIRE
|--------------------------------------------------------------------------
*/

$nomValue = e(
    $_POST['nom'] ?? ''
);

$prenomValue = e(
    $_POST['prenom'] ?? ''
);

$telephoneValue = e(
    $_POST['telephone'] ?? ''
);

$emailValue = e(
    $_POST['email'] ?? ''
);

$appareilValue = e(
    $_POST['appareil'] ?? ''
);

$descriptionValue = e(
    $_POST['description_probleme'] ?? ''
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
        content="Demande de maintenance informatique SOFTEXPRESS."
    >

    <title>
        Demande de maintenance | SOFTEXPRESS
    </title>


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- =====================================================
         CSS DE LA PAGE
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/demande-maintenance.css"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         CSS LOCAL DU PROFIL
         Ne modifie pas style.css
         Ne modifie pas demande-maintenance.css
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
                        id="requestUserProfileButton"
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
                        id="requestUserDropdown"
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
                                            ucfirst($userRole)
                                        ) ?>

                                    </small>

                                <?php endif; ?>


                            </div>

                        </div>


                        <div class="user-dropdown-divider"></div>


                        <!-- ADMINISTRATION -->

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


                        <!-- DÉCONNEXION -->

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

<section class="request-banner">

    <div class="container request-banner-content">

        <p class="request-eyebrow">
            SOFTEXPRESS • ASSISTANCE INFORMATIQUE
        </p>

        <h1>

            Demande de

            <span>
                maintenance
            </span>

        </h1>

        <p>
            Décrivez-nous votre problème et notre équipe
            pourra vous accompagner.
        </p>


        <div class="request-breadcrumb">

            <a href="../index.php">
                Accueil
            </a>

            <span>›</span>

            <a href="maintenance.php">
                Maintenance
            </a>

            <span>›</span>

            <strong>
                Demande
            </strong>

        </div>

    </div>

</section>


<!-- =========================================================
     CONTENU
========================================================= -->

<main class="request-page">

    <div class="container">


        <?php if ($success): ?>


            <!-- =================================================
                 SUCCÈS
            ================================================== -->

            <section class="request-success">

                <div class="success-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <p class="success-label">
                    DEMANDE ENREGISTRÉE
                </p>


                <h2>
                    Votre demande a bien été envoyée !
                </h2>


                <p>
                    Merci pour votre confiance.
                    Notre équipe SOFTEXPRESS prendra
                    connaissance de votre demande de
                    maintenance.
                </p>


                <?php if ($service): ?>

                    <div class="success-service">

                        <span>
                            Service demandé
                        </span>

                        <strong>
                            <?= e(
                                (string)$service['nom']
                            ) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <div class="success-actions">

                    <a
                        href="maintenance.php"
                        class="request-btn primary"
                    >

                        <i class="fa-solid fa-screwdriver-wrench"></i>

                        Retour à la maintenance

                    </a>


                    <a
                        href="../index.php"
                        class="request-btn secondary"
                    >

                        Retour à l'accueil

                    </a>

                </div>

            </section>


        <?php else: ?>


            <!-- =================================================
                 FORMULAIRE
            ================================================== -->

            <section class="request-section">


                <!-- COLONNE GAUCHE -->

                <div class="request-info">


                    <p class="request-label">
                        BESOIN D'ASSISTANCE ?
                    </p>


                    <h2>
                        Parlons de votre problème.
                    </h2>


                    <p>
                        Remplissez le formulaire avec les
                        informations concernant votre
                        équipement et le problème rencontré.
                    </p>


                    <?php if ($service): ?>


                        <div class="selected-service">

                            <div class="selected-service-icon">

                                <i class="fa-solid fa-screwdriver-wrench"></i>

                            </div>


                            <div>

                                <span>
                                    SERVICE SÉLECTIONNÉ
                                </span>

                                <strong>
                                    <?= e(
                                        (string)$service['nom']
                                    ) ?>
                                </strong>

                                <p>
                                    <?= e(
                                        (string)$service['description']
                                    ) ?>
                                </p>

                            </div>

                        </div>


                    <?php else: ?>


                        <div class="no-service">

                            <i class="fa-solid fa-triangle-exclamation"></i>

                            <div>

                                <strong>
                                    Aucun service sélectionné
                                </strong>

                                <p>
                                    Retournez à la page maintenance
                                    pour choisir un service.
                                </p>

                            </div>

                        </div>


                    <?php endif; ?>


                    <div class="request-help">

                        <div>

                            <i class="fa-solid fa-phone"></i>

                        </div>

                        <div>

                            <strong>
                                Une question ?
                            </strong>

                            <span>
                                Notre équipe est disponible
                                pour vous accompagner.
                            </span>

                        </div>

                    </div>


                </div>


                <!-- =================================================
                     COLONNE DROITE
                ================================================== -->

                <div class="request-form-box">


                    <?php if (!empty($erreurs)): ?>


                        <div class="request-errors">

                            <strong>
                                Veuillez corriger les éléments suivants :
                            </strong>

                            <ul>

                                <?php foreach ($erreurs as $erreur): ?>

                                    <li>
                                        <?= e($erreur) ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>


                    <?php endif; ?>


                    <form
                        method="POST"
                        action=""
                    >


                        <input
                            type="hidden"
                            name="service_id"
                            value="<?= (int)$serviceId ?>"
                        >


                        <!-- NOM / PRÉNOM -->

                        <div class="form-row">


                            <div class="form-group">

                                <label for="nom">

                                    Nom

                                    <span>*</span>

                                </label>


                                <div class="input-wrap">

                                    <i class="fa-regular fa-user"></i>

                                    <input
                                        type="text"
                                        id="nom"
                                        name="nom"
                                        value="<?= $nomValue ?>"
                                        placeholder="Votre nom"
                                        autocomplete="family-name"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="form-group">

                                <label for="prenom">

                                    Prénom

                                    <span>*</span>

                                </label>


                                <div class="input-wrap">

                                    <i class="fa-regular fa-user"></i>

                                    <input
                                        type="text"
                                        id="prenom"
                                        name="prenom"
                                        value="<?= $prenomValue ?>"
                                        placeholder="Votre prénom"
                                        autocomplete="given-name"
                                        required
                                    >

                                </div>

                            </div>


                        </div>


                        <!-- TÉLÉPHONE / EMAIL -->

                        <div class="form-row">


                            <div class="form-group">

                                <label for="telephone">

                                    Téléphone

                                    <span>*</span>

                                </label>


                                <div class="input-wrap">

                                    <i class="fa-solid fa-phone"></i>

                                    <input
                                        type="tel"
                                        id="telephone"
                                        name="telephone"
                                        value="<?= $telephoneValue ?>"
                                        placeholder="+237 6XX XXX XXX"
                                        autocomplete="tel"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="form-group">

                                <label for="email">

                                    Email

                                    <span>*</span>

                                </label>


                                <div class="input-wrap">

                                    <i class="fa-regular fa-envelope"></i>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?= $emailValue ?>"
                                        placeholder="votre@email.com"
                                        autocomplete="email"
                                        required
                                    >

                                </div>

                            </div>


                        </div>


                        <!-- APPAREIL -->

                        <div class="form-group">

                            <label for="appareil">

                                Appareil concerné

                                <span>*</span>

                            </label>


                            <div class="input-wrap">

                                <i class="fa-solid fa-laptop"></i>

                                <input
                                    type="text"
                                    id="appareil"
                                    name="appareil"
                                    value="<?= $appareilValue ?>"
                                    placeholder="Ex : HP ProBook 450 G5"
                                    required
                                >

                            </div>

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <label for="description_probleme">

                                Description du problème

                                <span>*</span>

                            </label>


                            <div class="textarea-wrap">

                                <i class="fa-regular fa-message"></i>

                                <textarea
                                    id="description_probleme"
                                    name="description_probleme"
                                    rows="6"
                                    placeholder="Décrivez précisément le problème rencontré..."
                                    required
                                ><?= $descriptionValue ?></textarea>

                            </div>


                            <small>

                                Plus votre description est précise,
                                plus notre équipe pourra comprendre
                                rapidement le problème.

                            </small>

                        </div>


                        <!-- BOUTONS -->

                        <div class="form-actions">


                            <a
                                href="maintenance.php"
                                class="request-btn secondary"
                            >

                                Annuler

                            </a>


                            <button
                                type="submit"
                                class="request-btn primary"
                                <?= !$service ? 'disabled' : '' ?>
                            >

                                <i class="fa-solid fa-paper-plane"></i>

                                Envoyer ma demande

                            </button>


                        </div>


                    </form>

                </div>

            </section>


        <?php endif; ?>


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
     JAVASCRIPT PROFIL
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const button =
            document.getElementById(
                'requestUserProfileButton'
            );

        const dropdown =
            document.getElementById(
                'requestUserDropdown'
            );


        /*
        |--------------------------------------------------------------------------
        | Si l'utilisateur n'est pas connecté,
        | il n'y a rien à gérer.
        |--------------------------------------------------------------------------
        */

        if (!button || !dropdown) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | OUVERTURE / FERMETURE
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