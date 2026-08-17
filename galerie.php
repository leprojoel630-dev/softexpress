<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DE LA GALERIE
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
| DOSSIER DES IMAGES
|--------------------------------------------------------------------------
*/

$uploadDir = __DIR__ . '/../assets/images/galerie/';
$uploadUrl = '../assets/images/galerie/';


if (!is_dir($uploadDir)) {

    @mkdir(
        $uploadDir,
        0775,
        true
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
| SUPPRESSION
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

        $erreur = 'Élément de galerie invalide.';

    } else {

        /*
        |------------------------------------------------------------------
        | Récupération de l'image avant suppression
        |------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT image
            FROM galerie
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

            $stmt->execute();

            $result =
                $stmt->get_result();

            $galerie =
                $result->fetch_assoc();

            $stmt->close();


            if (!$galerie) {

                $erreur =
                    'Élément de galerie introuvable.';

            } else {

                /*
                |----------------------------------------------------------
                | Suppression dans la base
                |----------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    DELETE FROM galerie
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

                        if (
                            $stmt->affected_rows > 0
                        ) {

                            /*
                            |------------------------------------------------
                            | Suppression du fichier image
                            |------------------------------------------------
                            */

                            $nomImage =
                                basename(
                                    trim(
                                        (string)(
                                            $galerie['image']
                                            ?? ''
                                        )
                                    )
                                );


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


                            $message =
                                'Élément supprimé avec succès.';

                        } else {

                            $erreur =
                                'Élément introuvable.';
                        }

                    } else {

                        $erreur =
                            'Une erreur est survenue lors de la suppression.';
                    }


                    $stmt->close();
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| AJOUT D'UNE IMAGE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['ajouter_galerie'])
) {

    $titre =
        trim(
            (string)(
                $_POST['titre'] ?? ''
            )
        );


    $description =
        trim(
            (string)(
                $_POST['description'] ?? ''
            )
        );


    if ($titre === '') {

        $erreur =
            'Veuillez saisir un titre.';

    } elseif (
        !isset($_FILES['image'])
        ||
        $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $erreur =
            'Veuillez sélectionner une image.';

    } else {

        $fichier =
            $_FILES['image'];


        /*
        |------------------------------------------------------------------
        | Vérification taille
        |------------------------------------------------------------------
        */

        $tailleMax =
            5 * 1024 * 1024;


        if (
            (int)$fichier['size']
            >
            $tailleMax
        ) {

            $erreur =
                'L’image est trop volumineuse. Maximum : 5 Mo.';

        } else {

            /*
            |----------------------------------------------------------------
            | Vérification du type
            |----------------------------------------------------------------
            */

            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            $mime =
                $finfo
                    ? finfo_file(
                        $finfo,
                        $fichier['tmp_name']
                    )
                    : '';


            if ($finfo) {
                finfo_close($finfo);
            }


            $typesAutorises = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif'
            ];


            if (
                !isset(
                    $typesAutorises[$mime]
                )
            ) {

                $erreur =
                    'Format d’image non autorisé. Utilisez JPG, PNG, WEBP ou GIF.';

            } else {

                /*
                |--------------------------------------------------------------
                | Nom sécurisé du fichier
                |--------------------------------------------------------------
                */

                $extension =
                    $typesAutorises[$mime];


                $nomImage =
                    'galerie_'
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


                /*
                |--------------------------------------------------------------
                | Déplacement du fichier
                |--------------------------------------------------------------
                */

                if (
                    !move_uploaded_file(
                        $fichier['tmp_name'],
                        $destination
                    )
                ) {

                    $erreur =
                        'Impossible d’enregistrer l’image.';

                } else {

                    /*
                    |----------------------------------------------------------
                    | Enregistrement en base
                    |----------------------------------------------------------
                    */

                    $stmt = $conn->prepare("
                        INSERT INTO galerie
                        (
                            titre,
                            description,
                            image
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )
                    ");


                    if (!$stmt) {

                        @unlink(
                            $destination
                        );

                        $erreur =
                            'Impossible de préparer l’ajout.';

                    } else {

                        $stmt->bind_param(
                            'sss',
                            $titre,
                            $description,
                            $nomImage
                        );


                        if ($stmt->execute()) {

                            $message =
                                'Image ajoutée à la galerie avec succès.';

                        } else {

                            @unlink(
                                $destination
                            );

                            $erreur =
                                'Impossible d’enregistrer l’image dans la base de données.';
                        }


                        $stmt->close();
                    }
                }
            }
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
        (string)(
            $_GET['recherche'] ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DE LA GALERIE
|--------------------------------------------------------------------------
*/

$galeries = [];


if ($recherche !== '') {

    $motif =
        '%' . $recherche . '%';


    $stmt = $conn->prepare("
        SELECT
            id,
            titre,
            description,
            image,
            date_ajout
        FROM galerie
        WHERE
            titre LIKE ?
            OR description LIKE ?
        ORDER BY date_ajout DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            'ss',
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

            $galeries[] =
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
                image,
                date_ajout
            FROM galerie
            ORDER BY date_ajout DESC
        ");


    if ($result) {

        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $galeries[] =
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
                    GESTION DES MÉDIAS
                </span>

                <h1>
                    Galerie
                </h1>

                <p>
                    Gérez les images et les contenus visuels de SOFTEXPRESS.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-images"></i>

                <?= count($galeries) ?>

                image(s)

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
             AJOUT
        ====================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <div class="admin-panel-header">

                <span>
                    NOUVELLE IMAGE
                </span>

                <h2>
                    Ajouter à la galerie
                </h2>

            </div>


            <form
                method="POST"
                action="galerie.php"
                enctype="multipart/form-data"
            >

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            minmax(220px, 1fr)
                            minmax(220px, 1fr);
                        gap:18px;
                    "
                >

                    <div>

                        <label
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#566170;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            TITRE
                        </label>

                        <input
                            type="text"
                            name="titre"
                            maxlength="150"
                            required
                            placeholder="Titre de l'image"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                font-size:13px;
                            "
                        >

                    </div>


                    <div>

                        <label
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#566170;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            IMAGE
                        </label>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                            required
                            style="
                                width:100%;
                                height:46px;
                                padding:9px 10px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                background:#fff;
                                font-size:12px;
                            "
                        >

                    </div>


                    <div
                        style="
                            grid-column:1 / -1;
                        "
                    >

                        <label
                            style="
                                display:block;
                                margin-bottom:7px;
                                color:#566170;
                                font-size:12px;
                                font-weight:700;
                            "
                        >
                            DESCRIPTION
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Description de l'image (facultatif)"
                            style="
                                width:100%;
                                padding:12px 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                resize:vertical;
                                outline:none;
                                font-family:inherit;
                                font-size:13px;
                            "
                        ></textarea>

                    </div>

                </div>


                <div
                    style="
                        margin-top:18px;
                        display:flex;
                        justify-content:flex-end;
                    "
                >

                    <button
                        type="submit"
                        name="ajouter_galerie"
                        value="1"
                        style="
                            min-height:44px;
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

                        <i class="fa-solid fa-plus"></i>

                        Ajouter l'image

                    </button>

                </div>

            </form>

        </section>


        <!-- =====================================================
             RECHERCHE
        ====================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <form
                method="GET"
                action="galerie.php"
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
                        placeholder="Rechercher une image..."
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
                        href="galerie.php"
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
             GALERIE
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    MÉDIATHÈQUE
                </span>

                <h2>
                    Images de la galerie
                </h2>

            </div>


            <?php if (empty($galeries)): ?>

                <div
                    style="
                        padding:55px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-images"
                        style="
                            font-size:42px;
                            margin-bottom:15px;
                            color:#c5cdd5;
                        "
                    ></i>

                    <h3
                        style="
                            margin:0 0 8px;
                            color:#566170;
                        "
                    >
                        Aucune image
                    </h3>

                    <p>
                        Ajoutez votre première image à la galerie.
                    </p>

                </div>

            <?php else: ?>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fill,
                                minmax(220px, 1fr)
                            );
                        gap:20px;
                        padding:20px 0;
                    "
                >


                    <?php foreach ($galeries as $galerie): ?>


                        <?php

                        $idGalerie =
                            (int)$galerie['id'];

                        $nomImage =
                            basename(
                                trim(
                                    (string)(
                                        $galerie['image']
                                        ?? ''
                                    )
                                )
                            );

                        $imageUrl =
                            $uploadUrl
                            . $nomImage;

                        ?>


                        <article
                            style="
                                overflow:hidden;
                                border:1px solid #e4e9ee;
                                border-radius:12px;
                                background:#fff;
                            "
                        >


                            <!-- IMAGE -->

                            <div
                                style="
                                    width:100%;
                                    height:190px;
                                    overflow:hidden;
                                    background:#f4f6f8;
                                "
                            >

                                <?php if (
                                    $nomImage !== ''
                                    &&
                                    is_file(
                                        $uploadDir
                                        . $nomImage
                                    )
                                ): ?>

                                    <img
                                        src="<?= e($imageUrl) ?>"
                                        alt="<?= e($galerie['titre']) ?>"
                                        style="
                                            width:100%;
                                            height:100%;
                                            display:block;
                                            object-fit:cover;
                                        "
                                    >

                                <?php else: ?>

                                    <div
                                        style="
                                            width:100%;
                                            height:100%;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            color:#b7c0ca;
                                            font-size:38px;
                                        "
                                    >

                                        <i class="fa-solid fa-image"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- INFORMATIONS -->

                            <div
                                style="
                                    padding:16px;
                                "
                            >

                                <h3
                                    style="
                                        margin:0 0 7px;
                                        color:#26313e;
                                        font-size:15px;
                                    "
                                >

                                    <?= e(
                                        $galerie['titre']
                                    ) ?>

                                </h3>


                                <p
                                    style="
                                        margin:0 0 12px;
                                        color:#7b8796;
                                        font-size:12px;
                                        line-height:1.6;
                                        min-height:38px;
                                    "
                                >

                                    <?= e(
                                        $galerie['description']
                                        ?? ''
                                    ) ?>

                                </p>


                                <small
                                    style="
                                        display:block;
                                        margin-bottom:13px;
                                        color:#9aa5b4;
                                        font-size:10px;
                                    "
                                >

                                    <i class="fa-regular fa-calendar"></i>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $galerie['date_ajout']
                                        )
                                    ) ?>

                                </small>


                                <!-- SUPPRESSION -->

                                <form
                                    method="POST"
                                    action="galerie.php"
                                    onsubmit="
                                        return confirm(
                                            'Voulez-vous vraiment supprimer cette image ?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="supprimer_id"
                                        value="<?= $idGalerie ?>"
                                    >


                                    <button
                                        type="submit"
                                        style="
                                            width:100%;
                                            height:36px;
                                            border:0;
                                            border-radius:7px;
                                            color:#d13b3b;
                                            background:#fff0f0;
                                            cursor:pointer;
                                            font-size:11px;
                                            font-weight:700;
                                        "
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                        Supprimer

                                    </button>

                                </form>

                            </div>

                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>