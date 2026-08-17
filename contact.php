<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - CONTACT
|--------------------------------------------------------------------------
|
| Les messages envoyés depuis contact.php sont enregistrés dans :
| messages
|
| Ils apparaîtront ensuite dans :
| admin/messages.php
|
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
| FONCTION D'ÉCHAPPEMENT
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION DE LA CONNEXION MYSQLI
|--------------------------------------------------------------------------
*/

if (
    !isset($conn)
    || !($conn instanceof mysqli)
) {
    die(
        'Erreur de connexion à la base de données.'
    );
}


/*
|--------------------------------------------------------------------------
| INFORMATIONS UTILISATEUR
|--------------------------------------------------------------------------
*/

$connecte = isset($_SESSION['user_id']);

$userPrenom = trim(
    (string) ($_SESSION['user_prenom'] ?? '')
);

$userNom = trim(
    (string) ($_SESSION['user_nom'] ?? '')
);

$userEmail = trim(
    (string) ($_SESSION['user_email'] ?? '')
);

$userRole = trim(
    (string) ($_SESSION['user_role'] ?? '')
);


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ ANCIENNE STRUCTURE SESSION
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    if (
        isset($_SESSION['user'])
        && is_array($_SESSION['user'])
    ) {

        $user = $_SESSION['user'];

        $userPrenom = trim(
            (string) ($user['prenom'] ?? '')
        );

        $userNom = trim(
            (string) ($user['nom'] ?? '')
        );

        $userEmail = trim(
            (string) ($user['email'] ?? '')
        );

        $userRole = trim(
            (string) ($user['role'] ?? '')
        );

        if (
            $userPrenom !== ''
            || $userNom !== ''
            || $userEmail !== ''
        ) {

            $connecte = true;
        }
    }
}


/*
|--------------------------------------------------------------------------
| COMPATIBILITÉ ANCIENNES VARIABLES SESSION
|--------------------------------------------------------------------------
*/

if (!$connecte) {

    $ancienNom = trim(
        (string) ($_SESSION['nom'] ?? '')
    );

    $ancienPrenom = trim(
        (string) ($_SESSION['prenom'] ?? '')
    );

    $ancienEmail = trim(
        (string) ($_SESSION['email'] ?? '')
    );

    $ancienRole = trim(
        (string) ($_SESSION['role'] ?? '')
    );

    if (
        $ancienNom !== ''
        || $ancienPrenom !== ''
        || $ancienEmail !== ''
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

$initiales = mb_strtoupper(
    $initiales
);


/*
|--------------------------------------------------------------------------
| VARIABLES DU FORMULAIRE
|--------------------------------------------------------------------------
*/

$success = '';
$error = '';

$nom = '';
$prenom = '';
$email = '';
$telephone = '';

$sujet = trim(
    (string) ($_GET['sujet'] ?? '')
);

$contenu = '';


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim(
        (string) ($_POST['nom'] ?? '')
    );

    $prenom = trim(
        (string) ($_POST['prenom'] ?? '')
    );

    $email = trim(
        (string) ($_POST['email'] ?? '')
    );

    $telephone = trim(
        (string) ($_POST['telephone'] ?? '')
    );

    $sujet = trim(
        (string) ($_POST['sujet'] ?? '')
    );

    $contenu = trim(
        (string) ($_POST['contenu'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $nom === ''
        || $prenom === ''
        || $email === ''
        || $sujet === ''
        || $contenu === ''
    ) {

        $error =
            'Veuillez remplir tous les champs obligatoires.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Veuillez entrer une adresse email valide.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | INSERTION DANS LA TABLE messages
            |--------------------------------------------------------------------------
            |
            | IMPORTANT :
            | On utilise messages et NON contacts.
            |
            */

            $stmt = $conn->prepare("
                INSERT INTO messages
                (
                    nom,
                    prenom,
                    email,
                    telephone,
                    sujet,
                    contenu,
                    statut,
                    date_envoi
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Non lu',
                    NOW()
                )
            ");


            if (!$stmt) {

                throw new Exception(
                    'Impossible de préparer la requête.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | PARAMÈTRES
            |--------------------------------------------------------------------------
            */

            $stmt->bind_param(
                'ssssss',
                $nom,
                $prenom,
                $email,
                $telephone,
                $sujet,
                $contenu
            );


            /*
            |--------------------------------------------------------------------------
            | EXÉCUTION
            |--------------------------------------------------------------------------
            */

            if (!$stmt->execute()) {

                throw new Exception(
                    'Impossible d’enregistrer le message.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | FERMETURE
            |--------------------------------------------------------------------------
            */

            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | MESSAGE DE SUCCÈS
            |--------------------------------------------------------------------------
            */

            $success =
                'Votre message a été envoyé avec succès. '
                . 'Notre équipe vous répondra dans les meilleurs délais.';


            /*
            |--------------------------------------------------------------------------
            | RÉINITIALISATION DU FORMULAIRE
            |--------------------------------------------------------------------------
            */

            $nom = '';
            $prenom = '';
            $email = '';
            $telephone = '';
            $sujet = '';
            $contenu = '';


        } catch (Throwable $e) {

            $error =
                'Une erreur est survenue lors de l’envoi du message.';
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
        content="Contactez SOFTEXPRESS pour toute demande d'information."
    >

    <title>
        Contact | SOFTEXPRESS
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/contact.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         STYLE LOCAL DU PROFIL
         Même fonctionnement que demande-maintenance.php
    ====================================================== -->

    <style>

        .contact-page .user-menu {

            position: relative;

            display: flex;

            align-items: center;
        }


        .contact-page .user-profile {

            border: none;

            background: transparent;

            padding: 4px;

            display: flex;

            align-items: center;

            gap: 8px;

            cursor: pointer;

            font-family: inherit;
        }


        .contact-page .user-avatar {

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


        .contact-page .user-dropdown {

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


        .contact-page .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .contact-page .user-dropdown-header {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px;
        }


        .contact-page .user-avatar-large {

            width: 48px;

            height: 48px;
        }


        .contact-page .user-info-text {

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 4px;
        }


        .contact-page .user-info-text strong {

            color: #111827;

            font-size: 14px;

            font-weight: 800;
        }


        .contact-page .user-info-text span {

            color: #687385;

            font-size: 12px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .contact-page .user-info-text small {

            color: #00A3E0;

            font-size: 11px;

            font-weight: 700;
        }


        .contact-page .user-dropdown-divider {

            height: 1px;

            background: #edf0f3;

            margin: 6px 4px;
        }


        .contact-page .user-dropdown-item {

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


        .contact-page .user-dropdown-item i {

            width: 18px;

            text-align: center;

            color: #00A3E0;
        }


        .contact-page .user-dropdown-item:hover {

            background: #f3f9fc;

            color: #00A3E0;
        }


        .contact-page .profile-admin i {

            color: #F99D1C;
        }


        .contact-page .profile-logout {

            color: #d13b3b;
        }


        .contact-page .profile-logout i {

            color: #d13b3b;
        }


        .contact-page .profile-logout:hover {

            background: #fff1f1;

            color: #c62828;
        }


        @media (max-width: 600px) {

            .contact-page .user-dropdown {

                position: fixed;

                top: 75px;

                right: 15px;

                width: calc(100vw - 30px);

                max-width: 280px;
            }
        }

    </style>

</head>


<body class="contact-page">


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

            <a href="maintenance.php">
                Maintenance
            </a>

            <a href="actualites.php">
                Actualités
            </a>

            <a
                href="contact.php"
                class="active"
            >
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
                        id="contactUserProfileButton"
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
                        id="contactUserDropdown"
                    >


                        <!-- INFORMATIONS UTILISATEUR -->

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

<section class="contact-banner">

    <div class="container">

        <p class="contact-eyebrow">
            SOFTEXPRESS
        </p>

        <h1>
            Contactez-<span>nous</span>
        </h1>

        <p>
            Une question, une demande d'information ou un besoin
            particulier ? Notre équipe est à votre écoute.
        </p>

        <div class="contact-breadcrumb">

            <a href="../index.php">
                Accueil
            </a>

            <span>›</span>

            <strong>
                Contact
            </strong>

        </div>

    </div>

</section>


<!-- =========================================================
     CONTACT
========================================================= -->

<main>

<section class="contact-section">

    <div class="container">


        <div class="contact-heading">

            <p>
                PARLONS DE VOTRE PROJET
            </p>

            <h2>
                Comment pouvons-nous vous aider ?
            </h2>

            <span>
                Envoyez-nous votre message et notre équipe
                vous répondra dans les meilleurs délais.
            </span>

        </div>


        <div class="contact-layout">


            <!-- INFORMATIONS -->

            <div class="contact-info">


                <div class="contact-info-card">

                    <div class="contact-icon">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>

                    <div>

                        <h3>
                            Adresse
                        </h3>

                        <p>
                            SOFTEXPRESS<br>
                            Cameroun
                        </p>

                    </div>

                </div>


                <div class="contact-info-card">

                    <div class="contact-icon">

                        <i class="fa-solid fa-phone"></i>

                    </div>

                    <div>

                        <h3>
                            Téléphone
                        </h3>

                        <p>
                            +237 650 29 65 18<br>
                            +237 699 39 21 40
                        </p>

                    </div>

                </div>


                <div class="contact-info-card">

                    <div class="contact-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </div>

                    <div>

                        <h3>
                            Email
                        </h3>

                        <p>
                            contact@softexpress.com
                        </p>

                    </div>

                </div>


                <div class="contact-info-card">

                    <div class="contact-icon">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                    <div>

                        <h3>
                            Disponibilité
                        </h3>

                        <p>
                            Lundi – Vendredi<br>
                            08h00 – 17h00
                        </p>

                    </div>

                </div>


            </div>


            <!-- FORMULAIRE -->

            <div class="contact-form-card">


                <div class="contact-form-header">

                    <span>
                        ENVOYER UN MESSAGE
                    </span>

                    <h2>
                        Écrivez-nous
                    </h2>

                </div>


                <?php if ($success !== ''): ?>

                    <div class="contact-alert success">

                        <i class="fa-solid fa-circle-check"></i>

                        <span>
                            <?= e($success) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if ($error !== ''): ?>

                    <div class="contact-alert error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <span>
                            <?= e($error) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    action="contact.php"
                    class="contact-form"
                >


                    <div class="form-row">

                        <div class="form-group">

                            <label for="nom">
                                Nom *
                            </label>

                            <input
                                type="text"
                                id="nom"
                                name="nom"
                                value="<?= e($nom) ?>"
                                placeholder="Votre nom"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="prenom">
                                Prénom *
                            </label>

                            <input
                                type="text"
                                id="prenom"
                                name="prenom"
                                value="<?= e($prenom) ?>"
                                placeholder="Votre prénom"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="email">
                                Email *
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= e($email) ?>"
                                placeholder="exemple@email.com"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="telephone">
                                Téléphone
                            </label>

                            <input
                                type="tel"
                                id="telephone"
                                name="telephone"
                                value="<?= e($telephone) ?>"
                                placeholder="+237 6XX XX XX XX"
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="sujet">
                            Sujet *
                        </label>

                        <select
                            id="sujet"
                            name="sujet"
                            required
                        >

                            <option value="">
                                Sélectionnez un sujet
                            </option>

                            <option
                                value="Demande d'information"
                                <?= $sujet === 'Demande d\'information'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Demande d'information
                            </option>

                            <option
                                value="Formation"
                                <?= $sujet === 'Formation'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Formation
                            </option>

                            <option
                                value="Produit"
                                <?= $sujet === 'Produit'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Produit
                            </option>

                            <option
                                value="Maintenance"
                                <?= $sujet === 'Maintenance'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Maintenance informatique
                            </option>

                            <option
                                value="Demande de devis"
                                <?= $sujet === 'Demande de devis'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Demande de devis
                            </option>

                            <option
                                value="Autre"
                                <?= $sujet === 'Autre'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Autre
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="contenu">
                            Votre message *
                        </label>

                        <textarea
                            id="contenu"
                            name="contenu"
                            rows="7"
                            placeholder="Écrivez votre message..."
                            required
                        ><?= e($contenu) ?></textarea>

                    </div>


                    <button
                        type="submit"
                        class="contact-submit"
                    >

                        Envoyer le message

                        <i class="fa-solid fa-paper-plane"></i>

                    </button>


                    <p class="form-note">

                        <i class="fa-solid fa-lock"></i>

                        Vos informations restent confidentielles.

                    </p>

                </form>

            </div>

        </div>

    </div>

</section>


<!-- CTA -->

<section class="contact-cta">

    <div class="container contact-cta-inner">

        <div>

            <span>
                SOFTEXPRESS
            </span>

            <h2>
                Besoin d'une solution informatique ?
            </h2>

            <p>
                Formation, équipements ou maintenance :
                nous sommes là pour vous accompagner.
            </p>

        </div>


        <a
            href="formations.php"
            class="contact-cta-btn"
        >

            Découvrir nos formations

            <i class="fa-solid fa-arrow-right"></i>

        </a>

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
                'contactUserProfileButton'
            );

        const dropdown =
            document.getElementById(
                'contactUserDropdown'
            );


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
                    !dropdown.contains(event.target)
                    &&
                    !button.contains(event.target)
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

                if (event.key === 'Escape') {

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