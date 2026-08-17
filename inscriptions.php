<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DES INSCRIPTIONS
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
| SUPPRESSION D'UNE INSCRIPTION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['supprimer_id'])
) {

    $id = filter_input(
        INPUT_POST,
        'supprimer_id',
        FILTER_VALIDATE_INT
    );

    if (!$id || $id <= 0) {

        $erreur = 'Inscription invalide.';

    } else {

        $stmt = $conn->prepare("
            DELETE FROM inscriptions
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
                        'Inscription supprimée avec succès.';

                } else {

                    $erreur =
                        'Inscription introuvable.';
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
| RÉCUPÉRATION DES INSCRIPTIONS
|--------------------------------------------------------------------------
|
| La table inscriptions contient formation_id.
| On utilise donc une jointure avec formations
| pour afficher directement le titre de la formation.
|--------------------------------------------------------------------------
*/

$inscriptions = [];


if ($recherche !== '') {

    $motif = '%' . $recherche . '%';

    $stmt = $conn->prepare("
        SELECT
            i.id,
            i.nom,
            i.prenom,
            i.telephone,
            i.email,
            i.formation_id,
            i.date_inscription,
            f.titre AS formation_titre
        FROM inscriptions i
        LEFT JOIN formations f
            ON f.id = i.formation_id
        WHERE
            i.nom LIKE ?
            OR i.prenom LIKE ?
            OR i.telephone LIKE ?
            OR i.email LIKE ?
            OR f.titre LIKE ?
        ORDER BY i.date_inscription DESC, i.id DESC
    ");

    if ($stmt) {

        $stmt->bind_param(
            'sssss',
            $motif,
            $motif,
            $motif,
            $motif,
            $motif
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while (
            $ligne = $result->fetch_assoc()
        ) {

            $inscriptions[] = $ligne;
        }

        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT
            i.id,
            i.nom,
            i.prenom,
            i.telephone,
            i.email,
            i.formation_id,
            i.date_inscription,
            f.titre AS formation_titre
        FROM inscriptions i
        LEFT JOIN formations f
            ON f.id = i.formation_id
        ORDER BY i.date_inscription DESC, i.id DESC
    ");

    if ($result) {

        while (
            $ligne = $result->fetch_assoc()
        ) {

            $inscriptions[] = $ligne;
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
                    GESTION DES INSCRIPTIONS
                </span>

                <h1>
                    Inscriptions
                </h1>

                <p>
                    Consultez et gérez les inscriptions aux formations.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-file-signature"></i>

                <?= count($inscriptions) ?>

                inscription(s)

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
                action="inscriptions.php"
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
                        placeholder="Rechercher par nom, email, téléphone ou formation..."
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
                        href="inscriptions.php"
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
             TABLEAU
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    INSCRIPTIONS
                </span>

                <h2>
                    Liste des inscrits
                </h2>

            </div>


            <?php if (empty($inscriptions)): ?>


                <div
                    style="
                        padding:45px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-file-signature"
                        style="
                            font-size:38px;
                            margin-bottom:15px;
                            color:#c5cdd5;
                        "
                    ></i>


                    <p>
                        Aucune inscription trouvée.
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
                            min-width:950px;
                        "
                    >


                        <thead>

                            <tr>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:left;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    ID
                                </th>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:left;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    PARTICIPANT
                                </th>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:left;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    CONTACT
                                </th>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:left;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    FORMATION
                                </th>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:left;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    DATE
                                </th>


                                <th
                                    style="
                                        padding:14px;
                                        text-align:right;
                                        color:#7b8796;
                                        background:#f7f9fb;
                                        border-bottom:1px solid #e5eaf0;
                                        font-size:11px;
                                    "
                                >
                                    ACTION
                                </th>


                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $inscriptions
                            as $inscription
                        ): ?>


                            <?php

                            $idInscription =
                                (int) $inscription['id'];

                            $prenom =
                                trim(
                                    (string) (
                                        $inscription['prenom']
                                        ?? ''
                                    )
                                );

                            $nom =
                                trim(
                                    (string) (
                                        $inscription['nom']
                                        ?? ''
                                    )
                                );

                            $nomComplet =
                                trim(
                                    $prenom . ' ' . $nom
                                );


                            $initiales = '';

                            if ($prenom !== '') {

                                $initiales .=
                                    mb_substr(
                                        $prenom,
                                        0,
                                        1
                                    );
                            }

                            if ($nom !== '') {

                                $initiales .=
                                    mb_substr(
                                        $nom,
                                        0,
                                        1
                                    );
                            }

                            if ($initiales === '') {

                                $initiales = 'I';
                            }

                            $initiales =
                                mb_strtoupper(
                                    $initiales
                                );


                            $formationTitre =
                                trim(
                                    (string) (
                                        $inscription[
                                            'formation_titre'
                                        ] ?? ''
                                    )
                                );


                            if ($formationTitre === '') {

                                $formationTitre =
                                    'Formation supprimée';
                            }


                            $dateInscription = '';

                            if (
                                !empty(
                                    $inscription[
                                        'date_inscription'
                                    ]
                                )
                            ) {

                                $timestamp =
                                    strtotime(
                                        $inscription[
                                            'date_inscription'
                                        ]
                                    );

                                if ($timestamp !== false) {

                                    $dateInscription =
                                        date(
                                            'd/m/Y à H:i',
                                            $timestamp
                                        );
                                }
                            }

                            ?>


                            <tr>


                                <!-- ID -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#8a95a4;
                                        font-size:12px;
                                    "
                                >

                                    #<?= $idInscription ?>

                                </td>


                                <!-- PARTICIPANT -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                    "
                                >

                                    <div
                                        style="
                                            display:flex;
                                            align-items:center;
                                            gap:11px;
                                        "
                                    >

                                        <span
                                            style="
                                                width:38px;
                                                height:38px;
                                                display:flex;
                                                align-items:center;
                                                justify-content:center;
                                                flex-shrink:0;
                                                border-radius:50%;
                                                color:#fff;
                                                background:linear-gradient(
                                                    135deg,
                                                    #f99d1c,
                                                    #00a3e0
                                                );
                                                font-size:11px;
                                                font-weight:800;
                                            "
                                        >

                                            <?= e($initiales) ?>

                                        </span>


                                        <strong
                                            style="
                                                color:#26313e;
                                                font-size:13px;
                                            "
                                        >

                                            <?= e(
                                                $nomComplet !== ''
                                                    ? $nomComplet
                                                    : 'Participant'
                                            ) ?>

                                        </strong>

                                    </div>

                                </td>


                                <!-- CONTACT -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                    "
                                >

                                    <div
                                        style="
                                            display:flex;
                                            flex-direction:column;
                                            gap:4px;
                                        "
                                    >

                                        <span
                                            style="
                                                color:#566170;
                                                font-size:12px;
                                            "
                                        >

                                            <i
                                                class="fa-solid fa-envelope"
                                                style="
                                                    width:16px;
                                                    color:#00a3e0;
                                                "
                                            ></i>

                                            <?= e(
                                                $inscription['email']
                                            ) ?>

                                        </span>


                                        <span
                                            style="
                                                color:#7b8796;
                                                font-size:11px;
                                            "
                                        >

                                            <i
                                                class="fa-solid fa-phone"
                                                style="
                                                    width:16px;
                                                    color:#f99d1c;
                                                "
                                            ></i>

                                            <?= e(
                                                $inscription['telephone']
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- FORMATION -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                    "
                                >

                                    <span
                                        style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:7px;
                                            padding:7px 10px;
                                            border-radius:8px;
                                            color:#087eaa;
                                            background:#eaf8fd;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >

                                        <i class="fa-solid fa-graduation-cap"></i>

                                        <?= e(
                                            $formationTitre
                                        ) ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#687385;
                                        font-size:12px;
                                        white-space:nowrap;
                                    "
                                >

                                    <i
                                        class="fa-regular fa-calendar"
                                        style="
                                            margin-right:5px;
                                            color:#00a3e0;
                                        "
                                    ></i>

                                    <?= e(
                                        $dateInscription
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        text-align:right;
                                    "
                                >

                                    <form
                                        method="POST"
                                        action="inscriptions.php"
                                        style="display:inline;"
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer cette inscription ?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="supprimer_id"
                                            value="<?= $idInscription ?>"
                                        >


                                        <button
                                            type="submit"
                                            title="Supprimer l'inscription"
                                            style="
                                                width:36px;
                                                height:36px;
                                                border:0;
                                                border-radius:7px;
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