```php
<?php

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DES MESSAGES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| VÉRIFICATION DE LA CONNEXION PDO
|--------------------------------------------------------------------------
|
| Le fichier database.php doit créer une connexion PDO.
| Le code accepte $pdo ou $conn, à condition que ce soit un objet PDO.
|
*/

$pdo = $pdo ?? null;

if (!($pdo instanceof PDO) && isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
}

if (!($pdo instanceof PDO)) {

    die(
        '<div style="
            font-family:Arial,sans-serif;
            padding:30px;
            color:#c62828;
            background:#fff0f0;
            border:1px solid #ffd0d0;
            margin:30px;
            border-radius:10px;
        ">
            <strong>Erreur de connexion à la base de données.</strong><br><br>
            La variable PDO $pdo n\'existe pas dans config/database.php.
        </div>'
    );
}


/*
|--------------------------------------------------------------------------
| MODE D'ERREUR PDO
|--------------------------------------------------------------------------
|
| Les erreurs SQL seront maintenant disponibles dans $erreur.
|
*/

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| ÉCHAPPEMENT HTML
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
| MESSAGES SYSTÈME
|--------------------------------------------------------------------------
*/

$message = '';
$erreur = '';


/*
|--------------------------------------------------------------------------
| ACTION : MARQUER COMME LU
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['marquer_lu'])
) {

    $id = filter_input(
        INPUT_POST,
        'message_id',
        FILTER_VALIDATE_INT
    );

    if (!$id || $id <= 0) {

        $erreur = 'Message invalide.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE messages
                SET statut = 'Lu'
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {

                $message = 'Message marqué comme lu.';

            } else {

                $message = 'Le message est déjà marqué comme lu.';
            }

        } catch (PDOException $e) {

            $erreur =
                'Erreur SQL lors de la modification : '
                . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| ACTION : MARQUER COMME NON LU
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['marquer_non_lu'])
) {

    $id = filter_input(
        INPUT_POST,
        'message_id',
        FILTER_VALIDATE_INT
    );

    if (!$id || $id <= 0) {

        $erreur = 'Message invalide.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE messages
                SET statut = 'Non lu'
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = 'Message marqué comme non lu.';

        } catch (PDOException $e) {

            $erreur =
                'Erreur SQL lors de la modification : '
                . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| ACTION : SUPPRESSION
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

        $erreur = 'Message invalide.';

    } else {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM messages
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {

                $message = 'Message supprimé avec succès.';

            } else {

                $erreur = 'Message introuvable.';
            }

        } catch (PDOException $e) {

            $erreur =
                'Erreur SQL lors de la suppression : '
                . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| RECHERCHE
|--------------------------------------------------------------------------
*/

$recherche = trim(
    (string) ($_GET['recherche'] ?? '')
);


/*
|--------------------------------------------------------------------------
| FILTRE STATUT
|--------------------------------------------------------------------------
*/

$statutFiltre = trim(
    (string) ($_GET['statut'] ?? '')
);


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES MESSAGES
|--------------------------------------------------------------------------
*/

$messages = [];


try {

    $sql = "
        SELECT
            id,
            nom,
            prenom,
            email,
            telephone,
            sujet,
            contenu,
            statut,
            date_envoi
        FROM messages
        WHERE 1 = 1
    ";

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | RECHERCHE
    |--------------------------------------------------------------------------
    */

    if ($recherche !== '') {

        $sql .= "
            AND (
                nom LIKE :recherche_nom
                OR prenom LIKE :recherche_prenom
                OR email LIKE :recherche_email
                OR sujet LIKE :recherche_sujet
                OR contenu LIKE :recherche_contenu
            )
        ";

        $motif = '%' . $recherche . '%';

        $params[':recherche_nom'] = $motif;
        $params[':recherche_prenom'] = $motif;
        $params[':recherche_email'] = $motif;
        $params[':recherche_sujet'] = $motif;
        $params[':recherche_contenu'] = $motif;
    }


    /*
    |--------------------------------------------------------------------------
    | FILTRE STATUT
    |--------------------------------------------------------------------------
    */

    if (
        $statutFiltre === 'Lu'
        || $statutFiltre === 'Non lu'
    ) {

        $sql .= "
            AND statut = :statut
        ";

        $params[':statut'] = $statutFiltre;
    }


    /*
    |--------------------------------------------------------------------------
    | TRI
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY date_envoi DESC
    ";


    /*
    |--------------------------------------------------------------------------
    | EXÉCUTION
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $messages = $stmt->fetchAll();


} catch (PDOException $e) {

    $erreur =
        'Erreur SQL lors de la récupération des messages : '
        . $e->getMessage();

    $messages = [];
}


/*
|--------------------------------------------------------------------------
| STATISTIQUES
|--------------------------------------------------------------------------
*/

$totalMessages = count($messages);

$totalNonLus = 0;
$totalLus = 0;


foreach ($messages as $msg) {

    $statut = strtolower(
        trim(
            (string) ($msg['statut'] ?? '')
        )
    );

    if ($statut === 'non lu') {

        $totalNonLus++;

    } else {

        $totalLus++;
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
                    COMMUNICATION
                </span>

                <h1>
                    Messages
                </h1>

                <p>
                    Consultez et gérez les messages envoyés par les visiteurs.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-envelope"></i>

                <?= $totalMessages ?>

                message(s)

            </div>

        </div>


        <!-- =====================================================
             STATISTIQUES
        ====================================================== -->

        <div
            style="
                display:grid;
                grid-template-columns:repeat(3,minmax(0,1fr));
                gap:18px;
                margin-bottom:25px;
            "
        >


            <!-- TOTAL -->

            <div
                class="admin-panel"
                style="
                    padding:20px;
                    display:flex;
                    align-items:center;
                    gap:15px;
                "
            >

                <div
                    style="
                        width:48px;
                        height:48px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:12px;
                        color:#00a3e0;
                        background:#eaf8fd;
                        font-size:19px;
                    "
                >

                    <i class="fa-solid fa-envelope"></i>

                </div>


                <div>

                    <small
                        style="
                            display:block;
                            color:#8a95a4;
                            font-size:11px;
                            font-weight:700;
                            margin-bottom:4px;
                        "
                    >
                        TOTAL
                    </small>

                    <strong
                        style="
                            color:#26313e;
                            font-size:22px;
                        "
                    >
                        <?= $totalMessages ?>
                    </strong>

                </div>

            </div>


            <!-- NON LUS -->

            <div
                class="admin-panel"
                style="
                    padding:20px;
                    display:flex;
                    align-items:center;
                    gap:15px;
                "
            >

                <div
                    style="
                        width:48px;
                        height:48px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:12px;
                        color:#d88900;
                        background:#fff3df;
                        font-size:19px;
                    "
                >

                    <i class="fa-solid fa-envelope-open-text"></i>

                </div>


                <div>

                    <small
                        style="
                            display:block;
                            color:#8a95a4;
                            font-size:11px;
                            font-weight:700;
                            margin-bottom:4px;
                        "
                    >
                        NON LUS
                    </small>

                    <strong
                        style="
                            color:#26313e;
                            font-size:22px;
                        "
                    >
                        <?= $totalNonLus ?>
                    </strong>

                </div>

            </div>


            <!-- LUS -->

            <div
                class="admin-panel"
                style="
                    padding:20px;
                    display:flex;
                    align-items:center;
                    gap:15px;
                "
            >

                <div
                    style="
                        width:48px;
                        height:48px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:12px;
                        color:#168746;
                        background:#edf9f2;
                        font-size:19px;
                    "
                >

                    <i class="fa-solid fa-envelope-circle-check"></i>

                </div>


                <div>

                    <small
                        style="
                            display:block;
                            color:#8a95a4;
                            font-size:11px;
                            font-weight:700;
                            margin-bottom:4px;
                        "
                    >
                        LUS
                    </small>

                    <strong
                        style="
                            color:#26313e;
                            font-size:22px;
                        "
                    >
                        <?= $totalLus ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- =====================================================
             MESSAGES SYSTÈME
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
                    line-height:1.6;
                "
            >

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($erreur) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             RECHERCHE / FILTRE
        ====================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <form
                method="GET"
                action="messages.php"
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
                        placeholder="Rechercher par nom, email ou sujet..."
                        style="
                            width:100%;
                            height:46px;
                            padding:0 15px 0 43px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            outline:none;
                            font-size:13px;
                            box-sizing:border-box;
                        "
                    >

                </div>


                <select
                    name="statut"
                    style="
                        height:46px;
                        padding:0 35px 0 13px;
                        border:1px solid #dfe6eb;
                        border-radius:8px;
                        color:#566170;
                        background:#fff;
                        font-size:13px;
                        cursor:pointer;
                    "
                >

                    <option value="">
                        Tous les messages
                    </option>

                    <option
                        value="Non lu"
                        <?= $statutFiltre === 'Non lu'
                            ? 'selected'
                            : '' ?>
                    >
                        Non lus
                    </option>

                    <option
                        value="Lu"
                        <?= $statutFiltre === 'Lu'
                            ? 'selected'
                            : '' ?>
                    >
                        Lus
                    </option>

                </select>


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


                <?php if (
                    $recherche !== ''
                    || $statutFiltre !== ''
                ): ?>

                    <a
                        href="messages.php"
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
             LISTE DES MESSAGES
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    BOÎTE DE RÉCEPTION
                </span>

                <h2>
                    Liste des messages
                </h2>

            </div>


            <?php if (empty($messages)): ?>

                <div
                    style="
                        padding:55px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-envelope-open"
                        style="
                            display:block;
                            font-size:40px;
                            margin-bottom:15px;
                            color:#c5cdd5;
                        "
                    ></i>

                    <h3
                        style="
                            margin:0 0 8px;
                            color:#596574;
                        "
                    >
                        Aucun message
                    </h3>

                    <p>
                        Aucun message ne correspond à votre recherche.
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
                                    EXPÉDITEUR
                                </th>


                                <th style="
                                    padding:14px;
                                    text-align:left;
                                    color:#7b8796;
                                    background:#f7f9fb;
                                    border-bottom:1px solid #e5eaf0;
                                    font-size:11px;
                                ">
                                    SUJET
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
                                    ACTIONS
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($messages as $msg): ?>

                            <?php

                            $idMessage =
                                (int) $msg['id'];

                            $statut =
                                trim(
                                    (string) (
                                        $msg['statut'] ?? ''
                                    )
                                );

                            $estNonLu =
                                strtolower($statut) === 'non lu';


                            $prenom =
                                trim(
                                    (string) (
                                        $msg['prenom'] ?? ''
                                    )
                                );

                            $nom =
                                trim(
                                    (string) (
                                        $msg['nom'] ?? ''
                                    )
                                );


                            $nomComplet =
                                trim(
                                    $prenom . ' ' . $nom
                                );


                            if ($nomComplet === '') {

                                $nomComplet = 'Visiteur';
                            }


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

                                $initiales = 'U';
                            }


                            $initiales =
                                mb_strtoupper(
                                    $initiales
                                );


                            $dateMessage = '';

                            if (!empty($msg['date_envoi'])) {

                                $timestamp =
                                    strtotime(
                                        (string)
                                        $msg['date_envoi']
                                    );

                                if ($timestamp !== false) {

                                    $dateMessage =
                                        date(
                                            'd/m/Y H:i',
                                            $timestamp
                                        );
                                }
                            }

                            ?>


                            <tr
                                style="
                                    <?= $estNonLu
                                        ? 'background:#fcfdff;'
                                        : '' ?>
                                "
                            >


                                <!-- ID -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#8a95a4;
                                    font-size:12px;
                                ">

                                    #<?= $idMessage ?>

                                </td>


                                <!-- EXPÉDITEUR -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                ">

                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        gap:11px;
                                    ">

                                        <span style="
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
                                        ">

                                            <?= e($initiales) ?>

                                        </span>


                                        <div>

                                            <strong style="
                                                display:block;
                                                color:#26313e;
                                                font-size:13px;
                                                margin-bottom:3px;
                                            ">

                                                <?= e($nomComplet) ?>

                                            </strong>


                                            <span style="
                                                color:#8a95a4;
                                                font-size:11px;
                                            ">

                                                <?= e(
                                                    $msg['email'] ?? ''
                                                ) ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- SUJET -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    max-width:250px;
                                ">

                                    <strong style="
                                        display:block;
                                        color:#3a4654;
                                        font-size:13px;
                                        margin-bottom:4px;
                                    ">

                                        <?= e(
                                            $msg['sujet'] ?? ''
                                        ) ?>

                                    </strong>


                                    <span style="
                                        display:block;
                                        color:#8a95a4;
                                        font-size:11px;
                                        max-width:250px;
                                        overflow:hidden;
                                        white-space:nowrap;
                                        text-overflow:ellipsis;
                                    ">

                                        <?= e(
                                            $msg['contenu'] ?? ''
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUT -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                ">

                                    <?php if ($estNonLu): ?>

                                        <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:6px;
                                            padding:6px 10px;
                                            border-radius:20px;
                                            color:#b86b00;
                                            background:#fff3df;
                                            font-size:10px;
                                            font-weight:800;
                                        ">

                                            <i class="fa-solid fa-envelope"></i>

                                            Non lu

                                        </span>

                                    <?php else: ?>

                                        <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:6px;
                                            padding:6px 10px;
                                            border-radius:20px;
                                            color:#168746;
                                            background:#edf9f2;
                                            font-size:10px;
                                            font-weight:800;
                                        ">

                                            <i class="fa-solid fa-envelope-open"></i>

                                            Lu

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- DATE -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    color:#687385;
                                    font-size:12px;
                                    white-space:nowrap;
                                ">

                                    <?= e($dateMessage) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td style="
                                    padding:15px 14px;
                                    border-bottom:1px solid #edf0f3;
                                    text-align:right;
                                    white-space:nowrap;
                                ">


                                    <!-- VOIR -->

                                    <button
                                        type="button"
                                        title="Voir le message"
                                        onclick="ouvrirMessage(<?= $idMessage ?>)"
                                        style="
                                            width:34px;
                                            height:34px;
                                            border:0;
                                            border-radius:6px;
                                            color:#00a3e0;
                                            background:#eaf8fd;
                                            cursor:pointer;
                                            margin-right:5px;
                                        "
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </button>


                                    <?php if ($estNonLu): ?>

                                        <!-- MARQUER LU -->

                                        <form
                                            method="POST"
                                            action="messages.php"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= $idMessage ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="marquer_lu"
                                                value="1"
                                                title="Marquer comme lu"
                                                style="
                                                    width:34px;
                                                    height:34px;
                                                    border:0;
                                                    border-radius:6px;
                                                    color:#168746;
                                                    background:#edf9f2;
                                                    cursor:pointer;
                                                    margin-right:5px;
                                                "
                                            >

                                                <i class="fa-solid fa-check"></i>

                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <!-- MARQUER NON LU -->

                                        <form
                                            method="POST"
                                            action="messages.php"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="message_id"
                                                value="<?= $idMessage ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="marquer_non_lu"
                                                value="1"
                                                title="Marquer comme non lu"
                                                style="
                                                    width:34px;
                                                    height:34px;
                                                    border:0;
                                                    border-radius:6px;
                                                    color:#b86b00;
                                                    background:#fff3df;
                                                    cursor:pointer;
                                                    margin-right:5px;
                                                "
                                            >

                                                <i class="fa-solid fa-envelope"></i>

                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- SUPPRIMER -->

                                    <form
                                        method="POST"
                                        action="messages.php"
                                        style="display:inline;"
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer ce message ?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="supprimer_id"
                                            value="<?= $idMessage ?>"
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


<!-- =========================================================
     FENÊTRES DE LECTURE DES MESSAGES
========================================================= -->

<?php foreach ($messages as $msg): ?>

    <div
        id="message-<?= (int) $msg['id'] ?>"
        class="message-modal"
        style="
            display:none;
            position:fixed;
            inset:0;
            z-index:9999;
            align-items:center;
            justify-content:center;
            padding:20px;
            background:rgba(0,0,0,.55);
        "
        onclick="fermerMessage(event, <?= (int) $msg['id'] ?>)"
    >

        <div
            style="
                width:100%;
                max-width:650px;
                max-height:90vh;
                overflow:auto;
                background:#fff;
                border-radius:14px;
                box-shadow:0 20px 60px rgba(0,0,0,.25);
            "
            onclick="event.stopPropagation()"
        >


            <!-- EN-TÊTE -->

            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:15px;
                    padding:20px 22px;
                    border-bottom:1px solid #edf0f3;
                "
            >

                <div>

                    <span
                        style="
                            display:block;
                            color:#00a3e0;
                            font-size:10px;
                            font-weight:800;
                            margin-bottom:5px;
                        "
                    >
                        MESSAGE #<?= (int) $msg['id'] ?>
                    </span>

                    <h2
                        style="
                            margin:0;
                            color:#26313e;
                            font-size:20px;
                        "
                    >

                        <?= e(
                            $msg['sujet'] ?? ''
                        ) ?>

                    </h2>

                </div>


                <button
                    type="button"
                    onclick="fermerMessageDirect(
                        <?= (int) $msg['id'] ?>
                    )"
                    style="
                        width:36px;
                        height:36px;
                        border:0;
                        border-radius:50%;
                        color:#687385;
                        background:#f1f4f6;
                        cursor:pointer;
                    "
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <!-- EXPÉDITEUR -->

            <div
                style="
                    padding:20px 22px;
                    background:#f8fafc;
                    border-bottom:1px solid #edf0f3;
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:12px;
                    "
                >

                    <div
                        style="
                            width:44px;
                            height:44px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:50%;
                            color:#fff;
                            background:linear-gradient(
                                135deg,
                                #f99d1c,
                                #00a3e0
                            );
                            font-size:12px;
                            font-weight:800;
                        "
                    >

                        <?php

                        $p = trim(
                            (string)
                            ($msg['prenom'] ?? '')
                        );

                        $n = trim(
                            (string)
                            ($msg['nom'] ?? '')
                        );

                        $ini = '';

                        if ($p !== '') {
                            $ini .= mb_substr($p, 0, 1);
                        }

                        if ($n !== '') {
                            $ini .= mb_substr($n, 0, 1);
                        }

                        if ($ini === '') {
                            $ini = 'U';
                        }

                        echo e(
                            mb_strtoupper($ini)
                        );

                        ?>

                    </div>


                    <div>

                        <strong
                            style="
                                display:block;
                                color:#26313e;
                                font-size:14px;
                            "
                        >

                            <?= e(
                                trim(
                                    (string)
                                    ($msg['prenom'] ?? '')
                                    . ' '
                                    . ($msg['nom'] ?? '')
                                )
                            ) ?>

                        </strong>


                        <span
                            style="
                                display:block;
                                margin-top:3px;
                                color:#7b8796;
                                font-size:12px;
                            "
                        >

                            <?= e(
                                $msg['email'] ?? ''
                            ) ?>

                        </span>


                        <?php if (
                            !empty($msg['telephone'])
                        ): ?>

                            <span
                                style="
                                    display:block;
                                    margin-top:3px;
                                    color:#7b8796;
                                    font-size:12px;
                                "
                            >

                                <i class="fa-solid fa-phone"></i>

                                <?= e(
                                    $msg['telephone']
                                ) ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- CONTENU -->

            <div
                style="
                    padding:25px 22px;
                "
            >

                <p
                    style="
                        margin:0;
                        color:#4d5866;
                        font-size:14px;
                        line-height:1.8;
                        white-space:pre-wrap;
                    "
                >

                    <?= e(
                        $msg['contenu'] ?? ''
                    ) ?>

                </p>

            </div>


            <!-- PIED -->

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    padding:15px 22px;
                    border-top:1px solid #edf0f3;
                    color:#8a95a4;
                    font-size:11px;
                "
            >

                <span>

                    <?php

                    $timestampModal = strtotime(
                        (string) (
                            $msg['date_envoi'] ?? ''
                        )
                    );

                    if ($timestampModal !== false) {

                        echo e(
                            date(
                                'd/m/Y à H:i',
                                $timestampModal
                            )
                        );

                    } else {

                        echo 'Date inconnue';
                    }

                    ?>

                </span>


                <span>

                    Statut :

                    <strong>
                        <?= e(
                            $msg['statut'] ?? ''
                        ) ?>
                    </strong>

                </span>

            </div>


        </div>

    </div>

<?php endforeach; ?>


<script>

/*
|--------------------------------------------------------------------------
| OUVRIR UN MESSAGE
|--------------------------------------------------------------------------
*/

function ouvrirMessage(id)
{
    const modal = document.getElementById(
        'message-' + id
    );

    if (!modal) {
        return;
    }

    modal.style.display = 'flex';

    document.body.style.overflow = 'hidden';
}


/*
|--------------------------------------------------------------------------
| FERMER UN MESSAGE
|--------------------------------------------------------------------------
*/

function fermerMessage(event, id)
{
    if (
        event.target.id ===
        'message-' + id
    ) {

        fermerMessageDirect(id);
    }
}


/*
|--------------------------------------------------------------------------
| FERMETURE DIRECTE
|--------------------------------------------------------------------------
*/

function fermerMessageDirect(id)
{
    const modal = document.getElementById(
        'message-' + id
    );

    if (!modal) {
        return;
    }

    modal.style.display = 'none';

    document.body.style.overflow = '';
}


/*
|--------------------------------------------------------------------------
| TOUCHE ESC
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function(event)
    {
        if (event.key === 'Escape') {

            document
                .querySelectorAll('.message-modal')
                .forEach(
                    function(modal)
                    {
                        modal.style.display = 'none';
                    }
                );

            document.body.style.overflow = '';
        }
    }
);

</script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
```
