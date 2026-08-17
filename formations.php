<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - ADMINISTRATION DES FORMATIONS
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
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$erreur = '';

$uploadDir = __DIR__ . '/../assets/images/formations/';
$uploadUrl = '../assets/images/formations/';


/*
|--------------------------------------------------------------------------
| CRÉATION DU DOSSIER IMAGES SI NÉCESSAIRE
|--------------------------------------------------------------------------
*/

if (!is_dir($uploadDir)) {

    @mkdir(
        $uploadDir,
        0755,
        true
    );
}


/*
|--------------------------------------------------------------------------
| SUPPRESSION D'UNE FORMATION
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

        $erreur = 'Formation invalide.';

    } else {

        /*
        |------------------------------------------------------------------
        | RÉCUPÉRATION DE L'IMAGE AVANT SUPPRESSION
        |------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT image
            FROM formations
            WHERE id = ?
            LIMIT 1
        ");


        $ancienneImage = '';


        if ($stmt) {

            $stmt->bind_param(
                'i',
                $id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                $ancienneImage =
                    trim(
                        (string) (
                            $row['image'] ?? ''
                        )
                    );
            }

            $stmt->close();
        }


        /*
        |------------------------------------------------------------------
        | SUPPRESSION DE LA FORMATION
        |------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            DELETE FROM formations
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

                    /*
                    |------------------------------------------------------
                    | SUPPRESSION DE L'IMAGE ASSOCIÉE
                    |------------------------------------------------------
                    */

                    if ($ancienneImage !== '') {

                        $ancienneImage =
                            basename(
                                $ancienneImage
                            );

                        $fichierImage =
                            $uploadDir . $ancienneImage;


                        if (
                            is_file(
                                $fichierImage
                            )
                        ) {

                            @unlink(
                                $fichierImage
                            );
                        }
                    }


                    $message =
                        'Formation supprimée avec succès.';

                } else {

                    $erreur =
                        'Formation introuvable.';
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
| AJOUT D'UNE FORMATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['ajouter_formation'])
) {

    $titre =
        trim(
            (string) (
                $_POST['titre'] ?? ''
            )
        );


    $description =
        trim(
            (string) (
                $_POST['description'] ?? ''
            )
        );


    $duree =
        trim(
            (string) (
                $_POST['duree'] ?? ''
            )
        );


    $prix =
        trim(
            (string) (
                $_POST['prix'] ?? ''
            )
        );


    /*
    |----------------------------------------------------------------------
    | VALIDATION
    |----------------------------------------------------------------------
    */

    if ($titre === '') {

        $erreur =
            'Veuillez saisir le titre de la formation.';

    } elseif ($description === '') {

        $erreur =
            'Veuillez saisir la description de la formation.';

    } elseif ($duree === '') {

        $erreur =
            'Veuillez saisir la durée de la formation.';

    } elseif (
        $prix === ''
        ||
        !is_numeric($prix)
        ||
        (float) $prix < 0
    ) {

        $erreur =
            'Veuillez saisir un prix valide.';

    }


    /*
    |----------------------------------------------------------------------
    | IMAGE
    |----------------------------------------------------------------------
    */

    $nomImage = '';


    if ($erreur === '') {

        if (
            isset($_FILES['image'])
            &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['image']['error']
                !==
                UPLOAD_ERR_OK
            ) {

                $erreur =
                    'Une erreur est survenue lors de l’envoi de l’image.';

            } else {

                $tmpName =
                    $_FILES['image']['tmp_name'];

                $taille =
                    (int) $_FILES['image']['size'];


                /*
                |----------------------------------------------------------
                | LIMITE 5 MB
                |----------------------------------------------------------
                */

                if ($taille > 5 * 1024 * 1024) {

                    $erreur =
                        'L’image ne doit pas dépasser 5 Mo.';

                } else {

                    $finfo =
                        new finfo(
                            FILEINFO_MIME_TYPE
                        );


                    $mime =
                        $finfo->file(
                            $tmpName
                        );


                    $extensionsAutorisees = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif'
                    ];


                    if (
                        !isset(
                            $extensionsAutorisees[$mime]
                        )
                    ) {

                        $erreur =
                            'Format d’image non autorisé. Utilisez JPG, PNG, WEBP ou GIF.';

                    } else {

                        $extension =
                            $extensionsAutorisees[$mime];


                        $nomImage =
                            'formation_'
                            . date('Ymd_His')
                            . '_'
                            . bin2hex(
                                random_bytes(4)
                            )
                            . '.'
                            . $extension;


                        $destination =
                            $uploadDir
                            . $nomImage;


                        if (
                            !move_uploaded_file(
                                $tmpName,
                                $destination
                            )
                        ) {

                            $erreur =
                                'Impossible d’enregistrer l’image.';
                        }
                    }
                }
            }
        }
    }


    /*
    |----------------------------------------------------------------------
    | INSERTION
    |----------------------------------------------------------------------
    */

    if ($erreur === '') {

        $prixDecimal =
            (float) $prix;


        $stmt = $conn->prepare("
            INSERT INTO formations
            (
                titre,
                description,
                duree,
                prix,
                image,
                date_creation
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ");


        if (!$stmt) {

            $erreur =
                'Impossible de préparer l’ajout de la formation.';

        } else {

            $stmt->bind_param(
                'sssds',
                $titre,
                $description,
                $duree,
                $prixDecimal,
                $nomImage
            );


            if ($stmt->execute()) {

                $message =
                    'Formation ajoutée avec succès.';

            } else {

                /*
                |----------------------------------------------------------
                | SI L'INSERTION ÉCHOUE, ON SUPPRIME L'IMAGE
                |----------------------------------------------------------
                */

                if (
                    $nomImage !== ''
                    &&
                    is_file(
                        $uploadDir . $nomImage
                    )
                ) {

                    @unlink(
                        $uploadDir . $nomImage
                    );
                }


                $erreur =
                    'Impossible d’ajouter la formation.';
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

$recherche =
    trim(
        (string) (
            $_GET['recherche'] ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES FORMATIONS
|--------------------------------------------------------------------------
*/

$formations = [];


if ($recherche !== '') {

    $motif =
        '%' . $recherche . '%';


    $stmt = $conn->prepare("
        SELECT
            id,
            titre,
            description,
            duree,
            prix,
            image,
            date_creation
        FROM formations
        WHERE
            titre LIKE ?
            OR description LIKE ?
            OR duree LIKE ?
        ORDER BY id DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            'sss',
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

            $formations[] =
                $ligne;
        }


        $stmt->close();
    }

} else {

    $result =
        $conn->query("
            SELECT
                id,
                titre,
                description,
                duree,
                prix,
                image,
                date_creation
            FROM formations
            ORDER BY id DESC
        ");


    if ($result) {

        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $formations[] =
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


        <!-- =========================================================
             EN-TÊTE
        ========================================================== -->

        <div class="admin-content-header">

            <div>

                <span class="admin-eyebrow">
                    GESTION DES FORMATIONS
                </span>

                <h1>
                    Formations
                </h1>

                <p>
                    Ajoutez, consultez et gérez les formations proposées par SOFTEXPRESS.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-graduation-cap"></i>

                <?= count($formations) ?>

                formation(s)

            </div>

        </div>


        <!-- =========================================================
             MESSAGES
        ========================================================== -->

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


        <!-- =========================================================
             AJOUT D'UNE FORMATION
        ========================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <div class="admin-panel-header">

                <span>
                    NOUVELLE FORMATION
                </span>

                <h2>
                    Ajouter une formation
                </h2>

            </div>


            <form
                method="POST"
                action="formations.php"
                enctype="multipart/form-data"
            >


                <div
                    style="
                        display:grid;
                        grid-template-columns:repeat(2,minmax(0,1fr));
                        gap:18px;
                    "
                >


                    <!-- TITRE -->

                    <div>

                        <label
                            for="titre"
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#4d5968;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            Titre de la formation *
                        </label>


                        <input
                            type="text"
                            id="titre"
                            name="titre"
                            maxlength="150"
                            required
                            placeholder="Ex : Marketing digital"
                            style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                color:#26313e;
                                background:#fff;
                                font-size:13px;
                            "
                        >

                    </div>


                    <!-- DURÉE -->

                    <div>

                        <label
                            for="duree"
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#4d5968;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            Durée *
                        </label>


                        <input
                            type="text"
                            id="duree"
                            name="duree"
                            maxlength="100"
                            required
                            placeholder="Ex : 3 mois"
                            style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                color:#26313e;
                                background:#fff;
                                font-size:13px;
                            "
                        >

                    </div>


                    <!-- PRIX -->

                    <div>

                        <label
                            for="prix"
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#4d5968;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            Prix (FCFA) *
                        </label>


                        <input
                            type="number"
                            id="prix"
                            name="prix"
                            min="0"
                            step="0.01"
                            required
                            placeholder="Ex : 75000"
                            style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                color:#26313e;
                                background:#fff;
                                font-size:13px;
                            "
                        >

                    </div>


                    <!-- IMAGE -->

                    <div>

                        <label
                            for="image"
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#4d5968;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            Image
                        </label>


                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            style="
                                width:100%;
                                height:44px;
                                padding:9px 10px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                color:#566170;
                                background:#fff;
                                font-size:12px;
                            "
                        >


                        <small
                            style="
                                display:block;
                                margin-top:6px;
                                color:#9aa5b4;
                                font-size:10px;
                            "
                        >
                            JPG, PNG, WEBP ou GIF — 5 Mo maximum.
                        </small>

                    </div>


                    <!-- DESCRIPTION -->

                    <div
                        style="
                            grid-column:1 / -1;
                        "
                    >

                        <label
                            for="description"
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#4d5968;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            Description *
                        </label>


                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            required
                            placeholder="Décrivez la formation..."
                            style="
                                width:100%;
                                padding:13px;
                                resize:vertical;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                color:#26313e;
                                background:#fff;
                                font-family:Arial,Helvetica,sans-serif;
                                font-size:13px;
                                line-height:1.5;
                            "
                        ></textarea>

                    </div>


                </div>


                <!-- BOUTON -->

                <div
                    style="
                        margin-top:20px;
                        display:flex;
                        justify-content:flex-end;
                    "
                >

                    <button
                        type="submit"
                        name="ajouter_formation"
                        value="1"
                        style="
                            min-height:44px;
                            padding:0 22px;
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            gap:8px;
                            border:0;
                            border-radius:8px;
                            color:#fff;
                            background:linear-gradient(
                                135deg,
                                #00a3e0,
                                #008bc2
                            );
                            cursor:pointer;
                            font-size:13px;
                            font-weight:700;
                            box-shadow:0 5px 14px rgba(0,163,224,.18);
                        "
                    >

                        <i class="fa-solid fa-plus"></i>

                        Ajouter la formation

                    </button>

                </div>


            </form>

        </section>


        <!-- =========================================================
             RECHERCHE
        ========================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <form
                method="GET"
                action="formations.php"
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
                        placeholder="Rechercher une formation..."
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
                        href="formations.php"
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


        <!-- =========================================================
             LISTE DES FORMATIONS
        ========================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    CATALOGUE
                </span>

                <h2>
                    Liste des formations
                </h2>

            </div>


            <?php if (empty($formations)): ?>


                <div
                    style="
                        padding:45px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-graduation-cap"
                        style="
                            display:block;
                            margin-bottom:15px;
                            color:#c5cdd5;
                            font-size:38px;
                        "
                    ></i>


                    <h3
                        style="
                            margin:0 0 8px;
                            color:#566170;
                            font-size:15px;
                        "
                    >
                        Aucune formation trouvée
                    </h3>


                    <p
                        style="
                            margin:0;
                            font-size:12px;
                        "
                    >
                        Ajoutez votre première formation à l'aide du formulaire ci-dessus.
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
                            min-width:900px;
                            border-collapse:collapse;
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
                                    DURÉE
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
                                    PRIX
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
                                    ACTIONS
                                </th>


                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $formations
                            as $formation
                        ): ?>


                            <?php

                            $idFormation =
                                (int) $formation['id'];


                            $titreFormation =
                                trim(
                                    (string) (
                                        $formation['titre']
                                        ?? ''
                                    )
                                );


                            $descriptionFormation =
                                trim(
                                    (string) (
                                        $formation['description']
                                        ?? ''
                                    )
                                );


                            $dureeFormation =
                                trim(
                                    (string) (
                                        $formation['duree']
                                        ?? ''
                                    )
                                );


                            $prixFormation =
                                (float) (
                                    $formation['prix']
                                    ?? 0
                                );


                            $imageFormation =
                                basename(
                                    trim(
                                        (string) (
                                            $formation['image']
                                            ?? ''
                                        )
                                    )
                                );


                            $dateFormation =
                                (string) (
                                    $formation['date_creation']
                                    ?? ''
                                );

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

                                    #<?= $idFormation ?>

                                </td>


                                <!-- FORMATION -->

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
                                            gap:12px;
                                            max-width:350px;
                                        "
                                    >


                                        <?php if (
                                            $imageFormation !== ''
                                            &&
                                            is_file(
                                                $uploadDir
                                                . $imageFormation
                                            )
                                        ): ?>


                                            <img
                                                src="<?= e(
                                                    $uploadUrl
                                                    . $imageFormation
                                                ) ?>"
                                                alt="<?= e(
                                                    $titreFormation
                                                ) ?>"
                                                style="
                                                    width:58px;
                                                    height:45px;
                                                    flex-shrink:0;
                                                    object-fit:cover;
                                                    border-radius:7px;
                                                    border:1px solid #e5eaf0;
                                                "
                                            >


                                        <?php else: ?>


                                            <div
                                                style="
                                                    width:58px;
                                                    height:45px;
                                                    flex-shrink:0;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    border-radius:7px;
                                                    color:#00a3e0;
                                                    background:#eaf8fd;
                                                    font-size:18px;
                                                "
                                            >

                                                <i class="fa-solid fa-graduation-cap"></i>

                                            </div>


                                        <?php endif; ?>


                                        <div
                                            style="
                                                min-width:0;
                                            "
                                        >


                                            <strong
                                                style="
                                                    display:block;
                                                    margin-bottom:4px;
                                                    color:#26313e;
                                                    font-size:13px;
                                                "
                                            >

                                                <?= e(
                                                    $titreFormation
                                                ) ?>

                                            </strong>


                                            <span
                                                style="
                                                    display:block;
                                                    overflow:hidden;
                                                    color:#8a95a4;
                                                    font-size:11px;
                                                    line-height:1.4;
                                                    text-overflow:ellipsis;
                                                    white-space:nowrap;
                                                "
                                            >

                                                <?= e(
                                                    $descriptionFormation
                                                ) ?>

                                            </span>


                                        </div>


                                    </div>

                                </td>


                                <!-- DURÉE -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#687385;
                                        font-size:12px;
                                    "
                                >

                                    <i
                                        class="fa-regular fa-clock"
                                        style="color:#00a3e0;"
                                    ></i>

                                    <?= e(
                                        $dureeFormation
                                    ) ?>

                                </td>


                                <!-- PRIX -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#f08b00;
                                        font-size:12px;
                                        font-weight:800;
                                        white-space:nowrap;
                                    "
                                >

                                    <?= number_format(
                                        $prixFormation,
                                        0,
                                        ',',
                                        ' '
                                    ) ?>

                                    FCFA

                                </td>


                                <!-- DATE -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#8a95a4;
                                        font-size:11px;
                                        white-space:nowrap;
                                    "
                                >

                                    <?php

                                    $timestamp =
                                        strtotime(
                                            $dateFormation
                                        );

                                    if (
                                        $timestamp !== false
                                    ) {

                                        echo date(
                                            'd/m/Y',
                                            $timestamp
                                        );

                                    } else {

                                        echo '-';
                                    }

                                    ?>

                                </td>


                                <!-- ACTIONS -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        text-align:right;
                                        white-space:nowrap;
                                    "
                                >


                                    <!-- VOIR -->

                                    <a
                                        href="../pages/formation-details.php?id=<?= $idFormation ?>"
                                        target="_blank"
                                        title="Voir la formation"
                                        style="
                                            width:34px;
                                            height:34px;
                                            margin-right:5px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            border-radius:6px;
                                            color:#00a3e0;
                                            background:#eaf8fd;
                                            text-decoration:none;
                                        "
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </a>


                                    <!-- SUPPRIMER -->

                                    <form
                                        method="POST"
                                        action="formations.php"
                                        style="
                                            display:inline;
                                        "
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer cette formation ?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="supprimer_id"
                                            value="<?= $idFormation ?>"
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