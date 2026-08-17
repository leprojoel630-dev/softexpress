<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - TABLEAU DE BORD ADMINISTRATEUR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| ÉCHAPPEMENT
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
| STATISTIQUES
|--------------------------------------------------------------------------
*/

$stats = [
    'utilisateurs' => 0,
    'formations' => 0,
    'inscriptions' => 0,
    'produits' => 0,
    'messages' => 0,
    'devis' => 0,
    'maintenance' => 0,
    'actualites' => 0,
    'galerie' => 0
];


/*
|--------------------------------------------------------------------------
| COMPTER LES ENREGISTREMENTS
|--------------------------------------------------------------------------
*/

function compterTable(
    mysqli $conn,
    string $table
): int {

    $tablesAutorisees = [
        'utilisateurs',
        'formations',
        'inscriptions',
        'produits',
        'messages',
        'demande_devis',
        'demande_maintenance',
        'actualites',
        'galerie'
    ];

    if (
        !in_array(
            $table,
            $tablesAutorisees,
            true
        )
    ) {
        return 0;
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM `$table`"
    );

    if (!$result) {
        return 0;
    }

    $ligne = $result->fetch_assoc();

    return (int)(
        $ligne['total'] ?? 0
    );
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES STATISTIQUES
|--------------------------------------------------------------------------
*/

if (
    isset($conn)
    &&
    $conn instanceof mysqli
) {

    $stats['utilisateurs'] =
        compterTable(
            $conn,
            'utilisateurs'
        );

    $stats['formations'] =
        compterTable(
            $conn,
            'formations'
        );

    $stats['inscriptions'] =
        compterTable(
            $conn,
            'inscriptions'
        );

    $stats['produits'] =
        compterTable(
            $conn,
            'produits'
        );

    $stats['messages'] =
        compterTable(
            $conn,
            'messages'
        );

    $stats['devis'] =
        compterTable(
            $conn,
            'demande_devis'
        );

    $stats['maintenance'] =
        compterTable(
            $conn,
            'demande_maintenance'
        );

    $stats['actualites'] =
        compterTable(
            $conn,
            'actualites'
        );

    $stats['galerie'] =
        compterTable(
            $conn,
            'galerie'
        );
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>


<div class="admin-layout">


    <?php

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once __DIR__ . '/includes/sidebar.php';

    ?>


    <main class="admin-content">


        <!-- =====================================================
             EN-TÊTE
        ====================================================== -->

        <div class="admin-content-header">

            <div>

                <span class="admin-eyebrow">
                    SOFTEXPRESS
                </span>

                <h1>
                    Tableau de bord
                </h1>

                <p>
                    Bienvenue dans votre espace
                    d'administration.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-regular fa-calendar"></i>

                <?= date('d/m/Y') ?>

            </div>

        </div>


        <!-- =====================================================
             STATISTIQUES
        ====================================================== -->

        <section class="admin-stat-grid">


            <a
                href="utilisateurs.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon blue">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div>

                    <span>
                        Utilisateurs
                    </span>

                    <strong>
                        <?= $stats['utilisateurs'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="formations.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon orange">

                    <i class="fa-solid fa-graduation-cap"></i>

                </div>

                <div>

                    <span>
                        Formations
                    </span>

                    <strong>
                        <?= $stats['formations'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="inscriptions.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon green">

                    <i class="fa-solid fa-file-signature"></i>

                </div>

                <div>

                    <span>
                        Inscriptions
                    </span>

                    <strong>
                        <?= $stats['inscriptions'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="produits.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon purple">

                    <i class="fa-solid fa-laptop"></i>

                </div>

                <div>

                    <span>
                        Produits
                    </span>

                    <strong>
                        <?= $stats['produits'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="messages.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon red">

                    <i class="fa-solid fa-envelope"></i>

                </div>

                <div>

                    <span>
                        Messages
                    </span>

                    <strong>
                        <?= $stats['messages'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="devis.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon cyan">

                    <i class="fa-solid fa-file-invoice"></i>

                </div>

                <div>

                    <span>
                        Demandes de devis
                    </span>

                    <strong>
                        <?= $stats['devis'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="maintenance.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon dark">

                    <i class="fa-solid fa-screwdriver-wrench"></i>

                </div>

                <div>

                    <span>
                        Maintenance
                    </span>

                    <strong>
                        <?= $stats['maintenance'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="actualites.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon blue">

                    <i class="fa-solid fa-newspaper"></i>

                </div>

                <div>

                    <span>
                        Actualités
                    </span>

                    <strong>
                        <?= $stats['actualites'] ?>
                    </strong>

                </div>

            </a>


            <a
                href="galerie.php"
                class="admin-stat-card"
            >

                <div class="admin-stat-icon orange">

                    <i class="fa-solid fa-images"></i>

                </div>

                <div>

                    <span>
                        Galerie
                    </span>

                    <strong>
                        <?= $stats['galerie'] ?>
                    </strong>

                </div>

            </a>


        </section>


        <!-- =====================================================
             ACCÈS RAPIDES
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    GESTION RAPIDE
                </span>

                <h2>
                    Accès rapides
                </h2>

            </div>


            <div class="admin-quick-grid">


                <a
                    href="formation-ajouter.php"
                    class="admin-quick-card"
                >

                    <i class="fa-solid fa-plus"></i>

                    <strong>
                        Ajouter une formation
                    </strong>

                    <span>
                        Créer une nouvelle formation
                    </span>

                </a>


                <a
                    href="produit-ajouter.php"
                    class="admin-quick-card"
                >

                    <i class="fa-solid fa-plus"></i>

                    <strong>
                        Ajouter un produit
                    </strong>

                    <span>
                        Ajouter un équipement
                    </span>

                </a>


                <a
                    href="actualite-ajouter.php"
                    class="admin-quick-card"
                >

                    <i class="fa-solid fa-plus"></i>

                    <strong>
                        Publier une actualité
                    </strong>

                    <span>
                        Ajouter une nouvelle actualité
                    </span>

                </a>


                <a
                    href="galerie-ajouter.php"
                    class="admin-quick-card"
                >

                    <i class="fa-solid fa-image"></i>

                    <strong>
                        Ajouter une image
                    </strong>

                    <span>
                        Enrichir la galerie
                    </span>

                </a>


            </div>

        </section>


    </main>

</div>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>