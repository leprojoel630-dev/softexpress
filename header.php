<?php
declare(strict_types=1);
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
        name="robots"
        content="noindex,nofollow"
    >

    <title>
        Administration | SOFTEXPRESS
    </title>


    <!-- CSS DU SITE -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS ADMINISTRATEUR -->
    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="admin-page">


<header class="admin-header">

    <div class="admin-header-inner">


        <!-- LOGO -->

        <a
            href="../index.php"
            class="admin-brand"
        >

            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

        </a>


        <!-- TITRE -->

        <div class="admin-header-title">

            <span>
                ESPACE ADMINISTRATEUR
            </span>

            <strong>
                Administration SOFTEXPRESS
            </strong>

        </div>


        <!-- PROFIL -->

        <div class="admin-profile">


            <div class="admin-avatar">

                <?= e($adminInitiales) ?>

            </div>


            <div class="admin-profile-info">

                <strong>
                    <?= e($adminNomComplet) ?>
                </strong>

                <span>
                    Administrateur
                </span>

            </div>


            <a
                href="../auth/deconnexion.php"
                class="admin-logout"
                title="Déconnexion"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

            </a>


        </div>

    </div>

</header>