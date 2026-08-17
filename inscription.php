<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

$nom = '';
$prenom = '';
$email = '';
$telephone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $nom === '' ||
        $prenom === '' ||
        $email === '' ||
        $mot_de_passe === '' ||
        $confirmation === ''
    ) {

        $error = 'Veuillez remplir tous les champs obligatoires.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Veuillez entrer une adresse email valide.';

    } elseif (strlen($mot_de_passe) < 6) {

        $error =
            'Le mot de passe doit contenir au moins 6 caractères.';

    } elseif ($mot_de_passe !== $confirmation) {

        $error =
            'Les deux mots de passe ne correspondent pas.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION EMAIL
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM utilisateurs
            WHERE email = ?
            LIMIT 1
        ");

        if (!$check) {

            $error =
                'Une erreur est survenue. Veuillez réessayer.';

        } else {

            $check->bind_param('s', $email);

            $check->execute();

            $result = $check->get_result();

            $existe = $result->fetch_assoc();

            $check->close();


            if ($existe) {

                $error =
                    'Cette adresse email est déjà utilisée.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | HASH DU MOT DE PASSE
                |--------------------------------------------------------------------------
                */

                $hash = password_hash(
                    $mot_de_passe,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | CRÉATION DU COMPTE
                |--------------------------------------------------------------------------
                |
                | Tous les nouveaux comptes sont automatiquement
                | créés avec le rôle "user".
                |
                */

                $stmt = $conn->prepare("
                    INSERT INTO utilisateurs
                    (
                        nom,
                        prenom,
                        email,
                        mot_de_passe,
                        role
                    )
                    VALUES (?, ?, ?, ?, 'user')
                ");

                if (!$stmt) {

                    $error =
                        'Impossible de créer le compte.';

                } else {

                    $stmt->bind_param(
                        'ssss',
                        $nom,
                        $prenom,
                        $email,
                        $hash
                    );


                    if ($stmt->execute()) {

                        $stmt->close();

                        /*
                        |--------------------------------------------------------------------------
                        | CONNEXION AUTOMATIQUE
                        |--------------------------------------------------------------------------
                        */

                        $nouvel_id =
                            (int)$conn->insert_id;

                        session_regenerate_id(true);

                        $_SESSION['user_id'] =
                            $nouvel_id;

                        $_SESSION['user_nom'] =
                            $nom;

                        $_SESSION['user_prenom'] =
                            $prenom;

                        $_SESSION['user_email'] =
                            $email;

                        $_SESSION['user_role'] =
                            'user';


                        header(
                            'Location: ../index.php'
                        );

                        exit;

                    } else {

                        $error =
                            'Une erreur est survenue lors de la création du compte.';

                        $stmt->close();
                    }
                }
            }
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
        content="Créer un compte SOFTEXPRESS."
    >

    <title>
        Inscription | SOFTEXPRESS
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/connexion.css"
    >

</head>


<body class="auth-page">


<div class="auth-wrapper">


    <div
        class="auth-card"
        style="max-width: 500px;"
    >


        <!-- LOGO -->

        <a
            href="../index.php"
            class="auth-logo"
        >

            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

        </a>


        <!-- TITRE -->

        <div class="auth-header">

            <p>
                ESPACE CLIENT
            </p>

            <h1>
                Créer un compte
            </h1>

            <span>
                Inscrivez-vous pour accéder aux services SOFTEXPRESS.
            </span>

        </div>


        <!-- ERREUR -->

        <?php if ($error !== ''): ?>

            <div class="auth-alert">

                <span>!</span>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- FORMULAIRE -->

        <form
            method="POST"
            action="inscription.php"
            class="auth-form"
        >


            <!-- NOM -->

            <div class="auth-group">

                <label for="nom">
                    Nom *
                </label>

                <input
                    type="text"
                    id="nom"
                    name="nom"
                    value="<?= htmlspecialchars(
                        $nom,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Votre nom"
                    autocomplete="family-name"
                    required
                >

            </div>


            <!-- PRÉNOM -->

            <div class="auth-group">

                <label for="prenom">
                    Prénom *
                </label>

                <input
                    type="text"
                    id="prenom"
                    name="prenom"
                    value="<?= htmlspecialchars(
                        $prenom,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Votre prénom"
                    autocomplete="given-name"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="auth-group">

                <label for="email">
                    Adresse email *
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="exemple@email.com"
                    autocomplete="email"
                    required
                >

            </div>


            <!-- TÉLÉPHONE -->

            <div class="auth-group">

                <label for="telephone">
                    Téléphone
                </label>

                <input
                    type="tel"
                    id="telephone"
                    name="telephone"
                    value="<?= htmlspecialchars(
                        $telephone,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="+237 6XX XXX XXX"
                    autocomplete="tel"
                >

            </div>


            <!-- MOT DE PASSE -->

            <div class="auth-group">

                <label for="mot_de_passe">
                    Mot de passe *
                </label>

                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    placeholder="Minimum 6 caractères"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >

            </div>


            <!-- CONFIRMATION -->

            <div class="auth-group">

                <label for="confirmation">
                    Confirmer le mot de passe *
                </label>

                <input
                    type="password"
                    id="confirmation"
                    name="confirmation"
                    placeholder="Répétez votre mot de passe"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >

            </div>


            <!-- BOUTON -->

            <button
                type="submit"
                class="auth-submit"
            >

                Créer mon compte

                <span>→</span>

            </button>

        </form>


        <!-- CONNEXION -->

        <div class="auth-register">

            <span>
                Vous avez déjà un compte ?
            </span>

            <a href="connexion.php">
                Se connecter
            </a>

        </div>


        <!-- RETOUR -->

        <a
            href="../index.php"
            class="auth-back"
        >

            ← Retour au site

        </a>

    </div>

</div>


<script src="../assets/js/main.js"></script>

</body>

</html>