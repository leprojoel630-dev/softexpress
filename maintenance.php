<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DE LA MAINTENANCE
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
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/

$message = '';
$erreur = '';


/*
|--------------------------------------------------------------------------
| MODIFICATION DU STATUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['modifier_statut'])
) {

    $id = filter_input(
        INPUT_POST,
        'demande_id',
        FILTER_VALIDATE_INT
    );

    $nouveauStatut = trim(
        (string) (
            $_POST['statut'] ?? ''
        )
    );


    $statutsAutorises = [
        'En attente',
        'En cours',
        'Terminée',
        'Annulée'
    ];


    if (!$id || $id <= 0) {

        $erreur = 'Demande invalide.';

    } elseif (
        !in_array(
            $nouveauStatut,
            $statutsAutorises,
            true
        )
    ) {

        $erreur = 'Statut sélectionné invalide.';

    } else {

        $stmt = $conn->prepare("
            UPDATE demande_maintenance
            SET statut = ?
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $erreur =
                'Impossible de préparer la modification.';

        } else {

            $stmt->bind_param(
                'si',
                $nouveauStatut,
                $id
            );

            if ($stmt->execute()) {

                $message =
                    'Statut de la demande modifié avec succès.';

            } else {

                $erreur =
                    'Impossible de modifier le statut.';
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUPPRESSION D'UNE DEMANDE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['supprimer_id'])
) {

    $id = filter_input(
        INPUT_POST,
        'supprimer_id',
        FILTER_VALIDATE_INT
    );


    if (!$id || $id <= 0) {

        $erreur = 'Demande invalide.';

    } else {

        $stmt = $conn->prepare("
            DELETE FROM demande_maintenance
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $erreur =
                'Impossible de préparer la suppression.';

        } else {

            $stmt->bind_param(
                'i',
                $id
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $message =
                        'Demande de maintenance supprimée avec succès.';

                } else {

                    $erreur =
                        'Demande introuvable.';
                }

            } else {

                $erreur =
                    'Une erreur est survenue lors de la suppression.';
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| RECHERCHE
|--------------------------------------------------------------------------
*/

$recherche = trim(
    (string) (
        $_GET['recherche'] ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES DEMANDES
|--------------------------------------------------------------------------
*/

$demandes = [];


if ($recherche !== '') {

    $motif = '%' . $recherche . '%';


    $stmt = $conn->prepare("
        SELECT
            d.id,
            d.nom,
            d.prenom,
            d.telephone,
            d.email,
            d.service_id,
            d.appareil,
            d.description_probleme,
            d.statut,
            d.date_demande,
            s.nom AS service_nom,
            s.prix AS service_prix
        FROM demande_maintenance d
        LEFT JOIN services_maintenance s
            ON s.id = d.service_id
        WHERE
            d.nom LIKE ?
            OR d.prenom LIKE ?
            OR d.telephone LIKE ?
            OR d.email LIKE ?
            OR d.appareil LIKE ?
            OR d.description_probleme LIKE ?
            OR d.statut LIKE ?
            OR s.nom LIKE ?
        ORDER BY d.date_demande DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            'ssssssss',
            $motif,
            $motif,
            $motif,
            $motif,
            $motif,
            $motif,
            $motif,
            $motif
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $demandes[] =
                $ligne;
        }


        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT
            d.id,
            d.nom,
            d.prenom,
            d.telephone,
            d.email,
            d.service_id,
            d.appareil,
            d.description_probleme,
            d.statut,
            d.date_demande,
            s.nom AS service_nom,
            s.prix AS service_prix
        FROM demande_maintenance d
        LEFT JOIN services_maintenance s
            ON s.id = d.service_id
        ORDER BY d.date_demande DESC
    ");


    if ($result) {

        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $demandes[] =
                $ligne;
        }
    }
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

    require_once __DIR__ . '/includes/sidebar.php';

    ?>


    <main class="admin-content">


        <!-- =====================================================
             EN-TÊTE
        ====================================================== -->

        <div class="admin-content-header">

            <div>

                <span class="admin-eyebrow">
                    ASSISTANCE INFORMATIQUE
                </span>


                <h1>
                    Maintenance
                </h1>


                <p>
                    Consultez et gérez les demandes de maintenance
                    envoyées par les clients.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-screwdriver-wrench"></i>

                <?= count($demandes) ?>

                demande(s)

            </div>

        </div>


        <!-- =====================================================
             MESSAGES
        ====================================================== -->

        <?php if ($message !== ''): ?>

            <div
                style="
                    margin-bottom:20px;
                    padding:14px 16px;
                    border-radius:9px;
                    background:#edf9f2;
                    border:1px solid #ccefdc;
                    color:#168746;
                    font-size:13px;
                    font-weight:700;
                "
            >

                <i class="fa-solid fa-circle-check"></i>

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <?php if ($erreur !== ''): ?>

            <div
                style="
                    margin-bottom:20px;
                    padding:14px 16px;
                    border-radius:9px;
                    background:#fff0f0;
                    border:1px solid #ffd5d5;
                    color:#c62828;
                    font-size:13px;
                    font-weight:700;
                "
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($erreur) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             RECHERCHE
        ====================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <form
                method="GET"
                action="maintenance.php"
                style="
                    display:flex;
                    gap:12px;
                    align-items:center;
                    flex-wrap:wrap;
                "
            >

                <div
                    style="
                        flex:1;
                        min-width:250px;
                        position:relative;
                    "
                >

                    <i
                        class="fa-solid fa-magnifying-glass"
                        style="
                            position:absolute;
                            left:15px;
                            top:50%;
                            transform:translateY(-50%);
                            color:#9aa5b4;
                        "
                    ></i>


                    <input
                        type="text"
                        name="recherche"
                        value="<?= e($recherche) ?>"
                        placeholder="Rechercher un client, appareil, service..."
                        style="
                            width:100%;
                            height:46px;
                            padding:0 15px 0 43px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            outline:none;
                            font-size:13px;
                        "
                    >

                </div>


                <button
                    type="submit"
                    style="
                        height:46px;
                        padding:0 20px;
                        border:0;
                        border-radius:8px;
                        color:#fff;
                        background:#00a3e0;
                        cursor:pointer;
                        font-size:13px;
                        font-weight:700;
                    "
                >

                    <i class="fa-solid fa-search"></i>

                    Rechercher

                </button>


                <?php if ($recherche !== ''): ?>

                    <a
                        href="maintenance.php"
                        style="
                            height:46px;
                            padding:0 18px;
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:8px;
                            color:#687385;
                            background:#f1f4f6;
                            text-decoration:none;
                            font-size:13px;
                            font-weight:700;
                        "
                    >

                        Réinitialiser

                    </a>

                <?php endif; ?>

            </form>

        </section>


        <!-- =====================================================
             LISTE DES DEMANDES
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    INTERVENTIONS
                </span>


                <h2>
                    Liste des demandes de maintenance
                </h2>

            </div>


            <?php if (empty($demandes)): ?>


                <div
                    style="
                        padding:45px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-screwdriver-wrench"
                        style="
                            font-size:38px;
                            margin-bottom:15px;
                            color:#c5cdd5;
                        "
                    ></i>


                    <p>
                        Aucune demande de maintenance trouvée.
                    </p>

                </div>


            <?php else: ?>


                <div
                    style="
                        width:100%;
                        overflow-x:auto;
                    "
                >


                    <table
                        style="
                            width:100%;
                            border-collapse:collapse;
                            min-width:1250px;
                        "
                    >


                        <thead>

                            <tr>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    ID
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    CLIENT
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    CONTACT
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    SERVICE
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    APPAREIL
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    PROBLÈME
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    STATUT
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    DATE
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:right;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    ACTION
                                </th>


                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($demandes as $demande): ?>


                            <?php

                            $idDemande =
                                (int) $demande['id'];


                            $nomComplet =
                                trim(
                                    (string) $demande['prenom']
                                    . ' '
                                    . (string) $demande['nom']
                                );


                            $statut =
                                trim(
                                    (string) (
                                        $demande['statut']
                                        ?? 'En attente'
                                    )
                                );


                            ?>


                            <tr>


                                <!-- ID -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#8a95a4;
                                    font-size:12px;
                                ">

                                    #<?= $idDemande ?>

                                </td>


                                <!-- CLIENT -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                ">

                                    <strong style="
                                        color:#26313e;
                                        font-size:13px;
                                    ">

                                        <?= e(
                                            $nomComplet !== ''
                                                ? $nomComplet
                                                : 'Client'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CONTACT -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    font-size:12px;
                                ">


                                    <div style="
                                        color:#566170;
                                        margin-bottom:5px;
                                    ">

                                        <i
                                            class="fa-solid fa-phone"
                                            style="color:#00a3e0;"
                                        ></i>

                                        <?= e(
                                            $demande['telephone']
                                        ) ?>

                                    </div>


                                    <div style="
                                        color:#7b8796;
                                    ">

                                        <i
                                            class="fa-solid fa-envelope"
                                            style="color:#f99d1c;"
                                        ></i>

                                        <?= e(
                                            $demande['email']
                                        ) ?>

                                    </div>


                                </td>


                                <!-- SERVICE -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                ">


                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:6px;
                                        padding:6px 10px;
                                        border-radius:20px;
                                        color:#087eaa;
                                        background:#eaf8fd;
                                        font-size:11px;
                                        font-weight:700;
                                    ">


                                        <i class="fa-solid fa-wrench"></i>


                                        <?= e(
                                            $demande['service_nom']
                                                ?? 'Service supprimé'
                                        ) ?>


                                    </span>


                                </td>


                                <!-- APPAREIL -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#566170;
                                    font-size:12px;
                                ">

                                    <i
                                        class="fa-solid fa-laptop"
                                        style="color:#f99d1c;"
                                    ></i>

                                    <?= e(
                                        $demande['appareil']
                                    ) ?>

                                </td>


                                <!-- PROBLÈME -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#687385;
                                    font-size:12px;
                                    max-width:280px;
                                ">

                                    <?= e(
                                        $demande['description_probleme']
                                    ) ?>

                                </td>


                                <!-- STATUT -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                ">


                                    <form
                                        method="POST"
                                        action="maintenance.php"
                                        style="
                                            display:flex;
                                            align-items:center;
                                            gap:5px;
                                        "
                                    >


                                        <input
                                            type="hidden"
                                            name="demande_id"
                                            value="<?= $idDemande ?>"
                                        >


                                        <select
                                            name="statut"
                                            style="
                                                height:34px;
                                                padding:0 8px;
                                                border:1px solid #dfe6eb;
                                                border-radius:6px;
                                                color:#566170;
                                                background:#fff;
                                                font-size:11px;
                                            "
                                        >


                                            <option
                                                value="En attente"
                                                <?= $statut === 'En attente'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                En attente
                                            </option>


                                            <option
                                                value="En cours"
                                                <?= $statut === 'En cours'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                En cours
                                            </option>


                                            <option
                                                value="Terminée"
                                                <?= $statut === 'Terminée'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Terminée
                                            </option>


                                            <option
                                                value="Annulée"
                                                <?= $statut === 'Annulée'
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                Annulée
                                            </option>


                                        </select>


                                        <button
                                            type="submit"
                                            name="modifier_statut"
                                            value="1"
                                            title="Modifier le statut"
                                            style="
                                                width:34px;
                                                height:34px;
                                                border:0;
                                                border-radius:6px;
                                                color:#00a3e0;
                                                background:#eaf8fd;
                                                cursor:pointer;
                                            "
                                        >

                                            <i class="fa-solid fa-check"></i>

                                        </button>


                                    </form>


                                </td>


                                <!-- DATE -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#687385;
                                    font-size:12px;
                                    white-space:nowrap;
                                ">


                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $demande['date_demande']
                                        )
                                    ) ?>


                                </td>


                                <!-- ACTION -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    text-align:right;
                                ">


                                    <form
                                        method="POST"
                                        action="maintenance.php"
                                        style="display:inline;"
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer cette demande de maintenance ?'
                                            );
                                        "
                                    >


                                        <input
                                            type="hidden"
                                            name="supprimer_id"
                                            value="<?= $idDemande ?>"
                                        >


                                        <button
                                            type="submit"
                                            title="Supprimer"
                                            style="
                                                width:34px;
                                                height:34px;
                                                border:0;
                                                border-radius:6px;
                                                color:#d13b3b;
                                                background:#fff0f0;
                                                cursor:pointer;
                                            "
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </button>


                                    </form>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>