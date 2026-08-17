<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - SIDEBAR ADMINISTRATEUR
|--------------------------------------------------------------------------
| Le sidebar calcule lui-même les statistiques.
| Il fonctionne donc sur toutes les pages de /admin/.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| INITIALISATION DES STATISTIQUES
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
| FONCTION DE COMPTAGE
|--------------------------------------------------------------------------
*/

if (
    isset($conn)
    &&
    $conn instanceof mysqli
) {

    $tables = [
        'utilisateurs' => 'utilisateurs',
        'formations' => 'formations',
        'inscriptions' => 'inscriptions',
        'produits' => 'produits',
        'messages' => 'messages',
        'devis' => 'demande_devis',
        'maintenance' => 'demande_maintenance',
        'actualites' => 'actualites',
        'galerie' => 'galerie'
    ];


    foreach ($tables as $cle => $table) {

        try {

            $result = $conn->query(
                "SELECT COUNT(*) AS total FROM `$table`"
            );

            if ($result) {

                $ligne = $result->fetch_assoc();

                $stats[$cle] =
                    (int)($ligne['total'] ?? 0);
            }

        } catch (Throwable $e) {

            /*
             * Si une table n'existe pas encore,
             * on laisse simplement la valeur à 0.
             */

            $stats[$cle] = 0;
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE ACTIVE
|--------------------------------------------------------------------------
*/

$pageActuelle =
    basename(
        $_SERVER['PHP_SELF'] ?? 'index.php'
    );

?>


<aside class="admin-sidebar">


    <!-- =========================================================
         TITRE DU MENU
    ========================================================== -->

    <div class="admin-sidebar-title">

        <span>
            MENU ADMINISTRATION
        </span>

    </div>


    <!-- =========================================================
         MENU PRINCIPAL
    ========================================================== -->

    <nav class="admin-menu">


        <!-- TABLEAU DE BORD -->

        <a
            href="index.php"
            class="<?= $pageActuelle === 'index.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-chart-line"></i>

            <span>
                Tableau de bord
            </span>

        </a>


        <!-- UTILISATEURS -->

        <a
            href="utilisateurs.php"
            class="<?= $pageActuelle === 'utilisateurs.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-users"></i>

            <span>
                Utilisateurs
            </span>

            <b>
                <?= (int)$stats['utilisateurs'] ?>
            </b>

        </a>


        <!-- FORMATIONS -->

        <a
            href="formations.php"
            class="<?= $pageActuelle === 'formations.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-graduation-cap"></i>

            <span>
                Formations
            </span>

            <b>
                <?= (int)$stats['formations'] ?>
            </b>

        </a>


        <!-- INSCRIPTIONS -->

        <a
            href="inscriptions.php"
            class="<?= $pageActuelle === 'inscriptions.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-file-signature"></i>

            <span>
                Inscriptions
            </span>

            <b>
                <?= (int)$stats['inscriptions'] ?>
            </b>

        </a>


        <!-- PRODUITS -->

        <a
            href="produits.php"
            class="<?= $pageActuelle === 'produits.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-laptop"></i>

            <span>
                Produits
            </span>

            <b>
                <?= (int)$stats['produits'] ?>
            </b>

        </a>


        <!-- DEVIS -->

        <a
            href="devis.php"
            class="<?= $pageActuelle === 'devis.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-file-invoice"></i>

            <span>
                Demandes de devis
            </span>

            <b>
                <?= (int)$stats['devis'] ?>
            </b>

        </a>


        <!-- MAINTENANCE -->

        <a
            href="maintenance.php"
            class="<?= $pageActuelle === 'maintenance.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-screwdriver-wrench"></i>

            <span>
                Maintenance
            </span>

            <b>
                <?= (int)$stats['maintenance'] ?>
            </b>

        </a>


        <!-- MESSAGES -->

        <a
            href="messages.php"
            class="<?= $pageActuelle === 'messages.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-envelope"></i>

            <span>
                Messages
            </span>

            <b>
                <?= (int)$stats['messages'] ?>
            </b>

        </a>


        <!-- ACTUALITÉS -->

        <a
            href="actualites.php"
            class="<?= $pageActuelle === 'actualites.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-newspaper"></i>

            <span>
                Actualités
            </span>

            <b>
                <?= (int)$stats['actualites'] ?>
            </b>

        </a>


        <!-- GALERIE -->

        <a
            href="galerie.php"
            class="<?= $pageActuelle === 'galerie.php' ? 'active' : '' ?>"
        >

            <i class="fa-solid fa-images"></i>

            <span>
                Galerie
            </span>

            <b>
                <?= (int)$stats['galerie'] ?>
            </b>

        </a>


    </nav>


    <!-- =========================================================
         BAS DU MENU
    ========================================================== -->

    <div class="admin-sidebar-bottom">

        <a href="../index.php">

            <i class="fa-solid fa-globe"></i>

            <span>
                Voir le site
            </span>

        </a>

    </div>


</aside>