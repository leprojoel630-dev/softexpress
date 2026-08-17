<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if ($email === '' || $mot_de_passe === '') {

        $error = 'Veuillez remplir tous les champs.';

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                nom,
                prenom,
                email,
                mot_de_passe,
                role
            FROM utilisateurs
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = 'Erreur de connexion à la base de données.';

        } else {

            $stmt->bind_param('s', $email);

            $stmt->execute();

            $result = $stmt->get_result();

            $utilisateur = $result->fetch_assoc();

            $stmt->close();


            if (!$utilisateur) {

                $error = 'Email ou mot de passe incorrect.';

            } else {

                $motDePasseStocke =
                    trim((string)$utilisateur['mot_de_passe']);


                /*
                |--------------------------------------------------------------------------
                | Vérification
                |--------------------------------------------------------------------------
                */

                $motDePasseValide = false;


                /*
                | Ancien mot de passe en clair
                */

                if (
                    hash_equals(
                        $motDePasseStocke,
                        $mot_de_passe
                    )
                ) {

                    $motDePasseValide = true;

                }


                /*
                | Mot de passe hashé
                */

                elseif (
                    password_verify(
                        $mot_de_passe,
                        $motDePasseStocke
                    )
                ) {

                    $motDePasseValide = true;
                }


                if (!$motDePasseValide) {

                    $error =
                        'Email ou mot de passe incorrect.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Connexion réussie
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    $_SESSION['user_id'] =
                        (int)$utilisateur['id'];

                    $_SESSION['user_nom'] =
                        $utilisateur['nom'];

                    $_SESSION['user_prenom'] =
                        $utilisateur['prenom'];

                    $_SESSION['user_email'] =
                        $utilisateur['email'];

                    $_SESSION['user_role'] =
                        $utilisateur['role'];


                    /*
                    |--------------------------------------------------------------------------
                    | Sécurisation du mot de passe
                    |--------------------------------------------------------------------------
                    */

                    if (
                        password_get_info(
                            $motDePasseStocke
                        )['algo'] === 0
                    ) {

                        $nouveauHash =
                            password_hash(
                                $mot_de_passe,
                                PASSWORD_DEFAULT
                            );

                        $update = $conn->prepare("
                            UPDATE utilisateurs
                            SET mot_de_passe = ?
                            WHERE id = ?
                        ");

                        if ($update) {

                            $userId =
                                (int)$utilisateur['id'];

                            $update->bind_param(
                                'si',
                                $nouveauHash,
                                $userId
                            );

                            $update->execute();

                            $update->close();
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECTION
                    |--------------------------------------------------------------------------
                    */

                    header(
                        'Location: ../index.php'
                    );

                    exit;
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

    <title>
        Connexion | SOFTEXPRESS
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

    <div class="auth-card">


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
                Connexion
            </h1>

            <span>
                Connectez-vous à votre compte SOFTEXPRESS.
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
            action="connexion.php"
            class="auth-form"
        >

            <div class="auth-group">

                <label for="email">
                    Adresse email
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


            <div class="auth-group">

                <label for="mot_de_passe">
                    Mot de passe
                </label>

                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    placeholder="Votre mot de passe"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-submit"
            >

                Se connecter

                <span>→</span>

            </button>

        </form>


        <!-- INSCRIPTION -->

        <div class="auth-register">

            <span>
                Vous n'avez pas encore de compte ?
            </span>

            <a href="inscription.php">
                Créer un compte
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