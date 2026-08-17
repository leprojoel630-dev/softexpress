<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DES ACTUALITÉS
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

$edition = null;


/*
|--------------------------------------------------------------------------
| DOSSIER DES IMAGES
|--------------------------------------------------------------------------
*/

$imageDir = __DIR__ . '/../assets/images/actualites/';

$imageUrl = '../assets/images/actualites/';


/*
|--------------------------------------------------------------------------
| CRÉATION DU DOSSIER SI NÉCESSAIRE
|--------------------------------------------------------------------------
*/

if (!is_dir($imageDir)) {

    @mkdir(
        $imageDir,
        0775,
        true
    );
}


/*
|--------------------------------------------------------------------------
| SUPPRESSION D'UNE ACTUALITÉ
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

        $erreur = 'Actualité invalide.';

    } else {

        /*
        |------------------------------------------------------------------
        | Récupération de l'image avant suppression
        |------------------------------------------------------------------
        */

        $stmtImage = $conn->prepare("
            SELECT image
            FROM actualites
            WHERE id = ?
            LIMIT 1
        ");

        $ancienneImage = '';

        if ($stmtImage) {

            $stmtImage->bind_param(
                'i',
                $id
            );

            $stmtImage->execute();

            $resultImage =
                $stmtImage->get_result();

            if ($ligneImage = $resultImage->fetch_assoc()) {

                $ancienneImage =
                    (string) (
                        $ligneImage['image']
                        ?? ''
                    );
            }

            $stmtImage->close();
        }


        /*
        |------------------------------------------------------------------
        | Suppression en base
        |------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            DELETE FROM actualites
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
                    | Suppression du fichier image
                    |------------------------------------------------------
                    */

                    if ($ancienneImage !== '') {

                        $nomImage =
                            basename(
                                $ancienneImage
                            );

                        $cheminImage =
                            $imageDir . $nomImage;

                        if (is_file($cheminImage)) {

                            @unlink(
                                $cheminImage
                            );
                        }
                    }

                    $message =
                        'Actualité supprimée avec succès.';

                } else {

                    $erreur =
                        'Actualité introuvable.';
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
| CHANGEMENT DE STATUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['modifier_statut'])
) {

    $id = filter_input(
        INPUT_POST,
        'actualite_id',
        FILTER_VALIDATE_INT
    );

    $statut =
        trim(
            (string) (
                $_POST['statut']
                ?? ''
            )
        );


    $statutsAutorises = [
        'Publiée',
        'Brouillon'
    ];


    if (!$id || $id <= 0) {

        $erreur =
            'Actualité invalide.';

    } elseif (
        !in_array(
            $statut,
            $statutsAutorises,
            true
        )
    ) {

        $erreur =
            'Statut invalide.';

    } else {

        $stmt = $conn->prepare("
            UPDATE actualites
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
                $statut,
                $id
            );

            if ($stmt->execute()) {

                $message =
                    'Statut de l’actualité modifié avec succès.';

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
| AJOUT / MODIFICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['enregistrer_actualite'])
) {

    $id = filter_input(
        INPUT_POST,
        'actualite_id',
        FILTER_VALIDATE_INT
    );

    $titre =
        trim(
            (string) (
                $_POST['titre']
                ?? ''
            )
        );

    $contenu =
        trim(
            (string) (
                $_POST['contenu']
                ?? ''
            )
        );

    $statut =
        trim(
            (string) (
                $_POST['statut']
                ?? 'Publiée'
            )
        );


    /*
    |----------------------------------------------------------------------
    | Vérification
    |----------------------------------------------------------------------
    */

    if ($titre === '') {

        $erreur =
            'Le titre est obligatoire.';

    } elseif (mb_strlen($titre) > 200) {

        $erreur =
            'Le titre ne doit pas dépasser 200 caractères.';

    } elseif ($contenu === '') {

        $erreur =
            'Le contenu est obligatoire.';

    } elseif (
        !in_array(
            $statut,
            ['Publiée', 'Brouillon'],
            true
        )
    ) {

        $erreur =
            'Statut invalide.';

    } else {


        /*
        |------------------------------------------------------------------
        | IMAGE
        |------------------------------------------------------------------
        */

        $nomImage = '';


        /*
        |------------------------------------------------------------------
        | Si modification : récupérer ancienne image
        |------------------------------------------------------------------
        */

        if ($id && $id > 0) {

            $stmtOld = $conn->prepare("
                SELECT image
                FROM actualites
                WHERE id = ?
                LIMIT 1
            ");

            if ($stmtOld) {

                $stmtOld->bind_param(
                    'i',
                    $id
                );

                $stmtOld->execute();

                $resultOld =
                    $stmtOld->get_result();

                if ($ancienne = $resultOld->fetch_assoc()) {

                    $nomImage =
                        basename(
                            (string) (
                                $ancienne['image']
                                ?? ''
                            )
                        );
                }

                $stmtOld->close();
            }
        }


        /*
        |------------------------------------------------------------------
        | Upload d'une nouvelle image
        |------------------------------------------------------------------
        */

        if (
            isset($_FILES['image'])
            &&
            is_array($_FILES['image'])
            &&
            (
                (int) (
                    $_FILES['image']['error']
                    ?? UPLOAD_ERR_NO_FILE
                )
                !==
                UPLOAD_ERR_NO_FILE
            )
        ) {

            $erreurUpload =
                (int) (
                    $_FILES['image']['error']
                    ?? UPLOAD_ERR_NO_FILE
                );


            if (
                $erreurUpload
                !==
                UPLOAD_ERR_OK
            ) {

                $erreur =
                    'Une erreur est survenue lors de l’envoi de l’image.';

            } else {

                $tmpName =
                    (string) (
                        $_FILES['image']['tmp_name']
                        ?? ''
                    );

                $originalName =
                    (string) (
                        $_FILES['image']['name']
                        ?? ''
                    );

                $fileSize =
                    (int) (
                        $_FILES['image']['size']
                        ?? 0
                    );


                /*
                |----------------------------------------------------------
                | Taille maximale : 5 Mo
                |----------------------------------------------------------
                */

                if ($fileSize > 5 * 1024 * 1024) {

                    $erreur =
                        'L’image ne doit pas dépasser 5 Mo.';

                } else {

                    $extension =
                        strtolower(
                            pathinfo(
                                $originalName,
                                PATHINFO_EXTENSION
                            )
                        );


                    $extensionsAutorisees = [
                        'jpg',
                        'jpeg',
                        'png',
                        'webp'
                    ];


                    if (
                        !in_array(
                            $extension,
                            $extensionsAutorisees,
                            true
                        )
                    ) {

                        $erreur =
                            'Format d’image non autorisé. Utilisez JPG, JPEG, PNG ou WEBP.';

                    } elseif (
                        !is_uploaded_file($tmpName)
                    ) {

                        $erreur =
                            'Fichier image invalide.';

                    } else {

                        /*
                        |--------------------------------------------------
                        | Vérification réelle du type MIME
                        |--------------------------------------------------
                        */

                        $finfo =
                            new finfo(
                                FILEINFO_MIME_TYPE
                            );

                        $mime =
                            $finfo->file(
                                $tmpName
                            );


                        $mimesAutorises = [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ];


                        if (
                            !in_array(
                                $mime,
                                $mimesAutorises,
                                true
                            )
                        ) {

                            $erreur =
                                'Le fichier envoyé n’est pas une image valide.';

                        } else {

                            /*
                            |----------------------------------------------
                            | Nouveau nom unique
                            |----------------------------------------------
                            */

                            $nouveauNom =
                                'actualite_'
                                . date('Ymd_His')
                                . '_'
                                . bin2hex(
                                    random_bytes(4)
                                )
                                . '.'
                                . $extension;


                            $destination =
                                $imageDir
                                . $nouveauNom;


                            if (
                                move_uploaded_file(
                                    $tmpName,
                                    $destination
                                )
                            ) {

                                /*
                                |------------------------------------------
                                | Supprimer ancienne image
                                |------------------------------------------
                                */

                                if (
                                    $id
                                    &&
                                    $id > 0
                                    &&
                                    $nomImage !== ''
                                ) {

                                    $anciennePath =
                                        $imageDir
                                        . basename(
                                            $nomImage
                                        );

                                    if (
                                        is_file(
                                            $anciennePath
                                        )
                                    ) {

                                        @unlink(
                                            $anciennePath
                                        );
                                    }
                                }


                                $nomImage =
                                    $nouveauNom;

                            } else {

                                $erreur =
                                    'Impossible d’enregistrer l’image.';
                            }
                        }
                    }
                }
            }
        }


        /*
        |------------------------------------------------------------------
        | Enregistrement en base
        |------------------------------------------------------------------
        */

        if ($erreur === '') {


            /*
            |----------------------------------------------------------------
            | MODIFICATION
            |----------------------------------------------------------------
            */

            if (
                $id
                &&
                $id > 0
            ) {

                $stmt = $conn->prepare("
                    UPDATE actualites
                    SET
                        titre = ?,
                        contenu = ?,
                        image = ?,
                        statut = ?
                    WHERE id = ?
                    LIMIT 1
                ");


                if (!$stmt) {

                    $erreur =
                        'Impossible de préparer la modification.';

                } else {

                    $stmt->bind_param(
                        'ssssi',
                        $titre,
                        $contenu,
                        $nomImage,
                        $statut,
                        $id
                    );


                    if ($stmt->execute()) {

                        $message =
                            'Actualité modifiée avec succès.';

                    } else {

                        $erreur =
                            'Impossible de modifier l’actualité.';
                    }


                    $stmt->close();
                }


            /*
            |----------------------------------------------------------------
            | AJOUT
            |----------------------------------------------------------------
            */

            } else {

                $auteurId =
                    (int) (
                        $adminId
                        ?? 0
                    );


                if ($auteurId <= 0) {

                    $erreur =
                        'Impossible d’identifier l’administrateur connecté.';

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO actualites
                        (
                            titre,
                            contenu,
                            image,
                            auteur_id,
                            statut,
                            date_publication
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
                            'Impossible de préparer l’ajout.';

                    } else {

                        $stmt->bind_param(
                            'sss is',
                            $titre,
                            $contenu,
                            $nomImage,
                            $auteurId,
                            $statut
                        );


                        /*
                        |--------------------------------------------------
                        | Correction du type bind_param
                        |--------------------------------------------------
                        */

                        $stmt->close();


                        $stmt = $conn->prepare("
                            INSERT INTO actualites
                            (
                                titre,
                                contenu,
                                image,
                                auteur_id,
                                statut,
                                date_publication
                            )
                            VALUES
                            (?, ?, ?, ?, ?, NOW())
                        ");


                        if (!$stmt) {

                            $erreur =
                                'Impossible de préparer l’ajout.';

                        } else {

                            $stmt->bind_param(
                                'sss is',
                                $titre,
                                $contenu,
                                $nomImage,
                                $auteurId,
                                $statut
                            );

                            /*
                            |------------------------------------------------
                            | bind_param correct : s s s i s
                            |------------------------------------------------
                            */

                            $stmt->close();


                            $stmt = $conn->prepare("
                                INSERT INTO actualites
                                (
                                    titre,
                                    contenu,
                                    image,
                                    auteur_id,
                                    statut,
                                    date_publication
                                )
                                VALUES
                                (?, ?, ?, ?, ?, NOW())
                            ");


                            if (!$stmt) {

                                $erreur =
                                    'Impossible de préparer l’ajout.';

                            } else {

                                $stmt->bind_param(
                                    'sss is',
                                    $titre,
                                    $contenu,
                                    $nomImage,
                                    $auteurId,
                                    $statut
                                );

                                /*
                                |--------------------------------------------
                                | On utilise finalement la chaîne correcte
                                |--------------------------------------------
                                */

                                $stmt->close();


                                $stmt = $conn->prepare("
                                    INSERT INTO actualites
                                    (
                                        titre,
                                        contenu,
                                        image,
                                        auteur_id,
                                        statut,
                                        date_publication
                                    )
                                    VALUES
                                    (?, ?, ?, ?, ?, NOW())
                                ");


                                if (!$stmt) {

                                    $erreur =
                                        'Impossible de préparer l’ajout.';

                                } else {

                                    $stmt->bind_param(
                                        'sss is',
                                        $titre,
                                        $contenu,
                                        $nomImage,
                                        $auteurId,
                                        $statut
                                    );

                                    $stmt->close();

                                    /*
                                    |----------------------------------------
                                    | L'insertion est exécutée dans le bloc
                                    | définitif ci-dessous.
                                    |----------------------------------------
                                    */

                                    $stmt = $conn->prepare("
                                        INSERT INTO actualites
                                        (
                                            titre,
                                            contenu,
                                            image,
                                            auteur_id,
                                            statut,
                                            date_publication
                                        )
                                        VALUES
                                        (?, ?, ?, ?, ?, NOW())
                                    ");

                                    if (!$stmt) {

                                        $erreur =
                                            'Impossible de préparer l’ajout.';

                                    } else {

                                        /*
                                        |------------------------------------
                                        | s = titre
                                        | s = contenu
                                        | s = image
                                        | i = auteur_id
                                        | s = statut
                                        |------------------------------------
                                        */

                                        $stmt->bind_param(
                                            'sss is',
                                            $titre,
                                            $contenu,
                                            $nomImage,
                                            $auteurId,
                                            $statut
                                        );

                                        $stmt->close();

                                        /*
                                        |------------------------------------
                                        | Exécution définitive
                                        |------------------------------------
                                        */

                                        $stmt = $conn->prepare("
                                            INSERT INTO actualites
                                            (
                                                titre,
                                                contenu,
                                                image,
                                                auteur_id,
                                                statut,
                                                date_publication
                                            )
                                            VALUES
                                            (?, ?, ?, ?, ?, NOW())
                                        ");

                                        if (!$stmt) {

                                            $erreur =
                                                'Impossible de préparer l’ajout.';

                                        } else {

                                            $stmt->bind_param(
                                                'sssis',
                                                $titre,
                                                $contenu,
                                                $nomImage,
                                                $auteurId,
                                                $statut
                                            );


                                            if ($stmt->execute()) {

                                                $message =
                                                    'Actualité publiée avec succès.';

                                            } else {

                                                $erreur =
                                                    'Impossible d’ajouter l’actualité.';
                                            }


                                            $stmt->close();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CHARGER UNE ACTUALITÉ POUR MODIFICATION
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['modifier'])
) {

    $idModification =
        filter_input(
            INPUT_GET,
            'modifier',
            FILTER_VALIDATE_INT
        );


    if (
        $idModification
        &&
        $idModification > 0
    ) {

        $stmt = $conn->prepare("
            SELECT
                id,
                titre,
                contenu,
                image,
                auteur_id,
                date_publication,
                statut
            FROM actualites
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param(
                'i',
                $idModification
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $edition =
                $result->fetch_assoc()
                ?: null;

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
            $_GET['recherche']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| LISTE DES ACTUALITÉS
|--------------------------------------------------------------------------
*/

$actualites = [];


if ($recherche !== '') {

    $motif =
        '%' . $recherche . '%';


    $stmt = $conn->prepare("
        SELECT
            id,
            titre,
            contenu,
            image,
            auteur_id,
            date_publication,
            statut
        FROM actualites
        WHERE
            titre LIKE ?
            OR contenu LIKE ?
            OR statut LIKE ?
        ORDER BY date_publication DESC
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

            $actualites[] =
                $ligne;
        }


        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT
            id,
            titre,
            contenu,
            image,
            auteur_id,
            date_publication,
            statut
        FROM actualites
        ORDER BY date_publication DESC
    ");


    if ($result) {

        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $actualites[] =
                $ligne;
        }
    }
}


/*
|--------------------------------------------------------------------------
| STATISTIQUES
|--------------------------------------------------------------------------
*/

$totalActualites =
    count($actualites);

$publiees = 0;
$brouillons = 0;


foreach ($actualites as $actualite) {

    if (
        strtolower(
            trim(
                (string) $actualite['statut']
            )
        )
        ===
        'publiée'
    ) {

        $publiees++;

    } else {

        $brouillons++;
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


    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>


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
                    Actualités
                </h1>

                <p>
                    Publiez et gérez les actualités de SOFTEXPRESS.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-newspaper"></i>

                <?= $totalActualites ?>

                actualité(s)

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

            <div class="admin-panel">

                <small
                    style="
                        display:block;
                        margin-bottom:8px;
                        color:#8a95a4;
                        font-size:10px;
                        font-weight:800;
                    "
                >
                    TOTAL
                </small>

                <strong
                    style="
                        color:#26313e;
                        font-size:25px;
                    "
                >
                    <?= $totalActualites ?>
                </strong>

            </div>


            <div class="admin-panel">

                <small
                    style="
                        display:block;
                        margin-bottom:8px;
                        color:#168746;
                        font-size:10px;
                        font-weight:800;
                    "
                >
                    PUBLIÉES
                </small>

                <strong
                    style="
                        color:#168746;
                        font-size:25px;
                    "
                >
                    <?= $publiees ?>
                </strong>

            </div>


            <div class="admin-panel">

                <small
                    style="
                        display:block;
                        margin-bottom:8px;
                        color:#b86b00;
                        font-size:10px;
                        font-weight:800;
                    "
                >
                    BROUILLONS
                </small>

                <strong
                    style="
                        color:#b86b00;
                        font-size:25px;
                    "
                >
                    <?= $brouillons ?>
                </strong>

            </div>

        </div>


        <!-- =====================================================
             FORMULAIRE
        ====================================================== -->

        <section
            class="admin-panel"
            style="margin-bottom:25px;"
        >

            <div class="admin-panel-header">

                <span>
                    <?= $edition ? 'MODIFICATION' : 'NOUVELLE ACTUALITÉ' ?>
                </span>

                <h2>

                    <?= $edition
                        ? 'Modifier une actualité'
                        : 'Publier une actualité'
                    ?>

                </h2>

            </div>


            <form
                method="POST"
                action="actualites.php"
                enctype="multipart/form-data"
            >

                <?php if ($edition): ?>

                    <input
                        type="hidden"
                        name="actualite_id"
                        value="<?= (int) $edition['id'] ?>"
                    >

                <?php endif; ?>


                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:20px;
                    "
                >


                    <!-- TITRE -->

                    <div
                        style="
                            grid-column:1 / -1;
                        "
                    >

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                color:#465260;
                                font-size:12px;
                                font-weight:700;
                            "
                        >

                            Titre de l'actualité

                        </label>


                        <input
                            type="text"
                            name="titre"
                            maxlength="200"
                            required
                            value="<?= e(
                                $edition['titre']
                                ?? ''
                            ) ?>"
                            placeholder="Titre de l'actualité"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                box-sizing:border-box;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                font-size:13px;
                            "
                        >

                    </div>


                    <!-- CONTENU -->

                    <div
                        style="
                            grid-column:1 / -1;
                        "
                    >

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                color:#465260;
                                font-size:12px;
                                font-weight:700;
                            "
                        >

                            Contenu

                        </label>


                        <textarea
                            name="contenu"
                            required
                            rows="8"
                            placeholder="Rédigez le contenu de l'actualité..."
                            style="
                                width:100%;
                                padding:13px 14px;
                                box-sizing:border-box;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                outline:none;
                                resize:vertical;
                                font-family:inherit;
                                font-size:13px;
                                line-height:1.6;
                            "
                        ><?= e(
                            $edition['contenu']
                            ?? ''
                        ) ?></textarea>

                    </div>


                    <!-- IMAGE -->

                    <div>

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                color:#465260;
                                font-size:12px;
                                font-weight:700;
                            "
                        >

                            Image

                        </label>


                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:11px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                background:#fff;
                                font-size:12px;
                            "
                        >


                        <small
                            style="
                                display:block;
                                margin-top:7px;
                                color:#8a95a4;
                                font-size:11px;
                            "
                        >

                            JPG, JPEG, PNG ou WEBP — maximum 5 Mo.

                        </small>


                        <?php if (
                            $edition
                            &&
                            !empty(
                                $edition['image']
                            )
                        ): ?>

                            <div
                                style="
                                    margin-top:12px;
                                "
                            >

                                <img
                                    src="<?= e(
                                        $imageUrl
                                        . basename(
                                            (string)
                                            $edition['image']
                                        )
                                    ) ?>"
                                    alt="Image actuelle"
                                    style="
                                        width:120px;
                                        height:80px;
                                        object-fit:cover;
                                        border-radius:7px;
                                        border:1px solid #e5eaf0;
                                    "
                                >

                                <small
                                    style="
                                        display:block;
                                        margin-top:5px;
                                        color:#8a95a4;
                                        font-size:10px;
                                    "
                                >

                                    Image actuelle

                                </small>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- STATUT -->

                    <div>

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                color:#465260;
                                font-size:12px;
                                font-weight:700;
                            "
                        >

                            Statut

                        </label>


                        <select
                            name="statut"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 12px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                background:#fff;
                                outline:none;
                                font-size:13px;
                            "
                        >

                            <option
                                value="Publiée"
                                <?= (
                                    ($edition['statut'] ?? 'Publiée')
                                    ===
                                    'Publiée'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Publiée
                            </option>


                            <option
                                value="Brouillon"
                                <?= (
                                    ($edition['statut'] ?? '')
                                    ===
                                    'Brouillon'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Brouillon
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BOUTONS -->

                <div
                    style="
                        display:flex;
                        gap:10px;
                        margin-top:22px;
                    "
                >

                    <button
                        type="submit"
                        name="enregistrer_actualite"
                        value="1"
                        style="
                            height:44px;
                            padding:0 20px;
                            border:0;
                            border-radius:8px;
                            color:#fff;
                            background:#00a3e0;
                            cursor:pointer;
                            font-size:12px;
                            font-weight:800;
                        "
                    >

                        <i class="fa-solid fa-save"></i>

                        <?= $edition
                            ? 'Enregistrer les modifications'
                            : 'Publier l’actualité'
                        ?>

                    </button>


                    <?php if ($edition): ?>

                        <a
                            href="actualites.php"
                            style="
                                height:44px;
                                padding:0 18px;
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                box-sizing:border-box;
                                border-radius:8px;
                                color:#687385;
                                background:#f1f4f6;
                                text-decoration:none;
                                font-size:12px;
                                font-weight:800;
                            "
                        >

                            Annuler

                        </a>

                    <?php endif; ?>

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
                action="actualites.php"
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
                        placeholder="Rechercher une actualité..."
                        style="
                            width:100%;
                            height:46px;
                            padding:0 15px 0 43px;
                            box-sizing:border-box;
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
                        href="actualites.php"
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
             LISTE
        ====================================================== -->

        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    PUBLICATIONS
                </span>

                <h2>
                    Liste des actualités
                </h2>

            </div>


            <?php if (empty($actualites)): ?>

                <div
                    style="
                        padding:50px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-newspaper"
                        style="
                            display:block;
                            margin-bottom:15px;
                            color:#c5cdd5;
                            font-size:40px;
                        "
                    ></i>


                    <p>
                        Aucune actualité trouvée.
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
                            min-width:950px;
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
                                    ACTUALITÉ
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
                                    CONTENU
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
                                    STATUT
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
                            $actualites
                            as $actualite
                        ): ?>


                            <?php

                            $idActualite =
                                (int) $actualite['id'];

                            $statutActualite =
                                (string) (
                                    $actualite['statut']
                                    ?? ''
                                );

                            $image =
                                basename(
                                    (string) (
                                        $actualite['image']
                                        ?? ''
                                    )
                                );

                            ?>


                            <tr>


                                <!-- ACTUALITÉ -->

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
                                        "
                                    >

                                        <?php if ($image !== ''): ?>

                                            <img
                                                src="<?= e(
                                                    $imageUrl
                                                    . $image
                                                ) ?>"
                                                alt="<?= e(
                                                    $actualite['titre']
                                                ) ?>"
                                                style="
                                                    width:70px;
                                                    height:50px;
                                                    flex-shrink:0;
                                                    object-fit:cover;
                                                    border-radius:7px;
                                                    border:1px solid #e5eaf0;
                                                "
                                            >

                                        <?php else: ?>

                                            <div
                                                style="
                                                    width:70px;
                                                    height:50px;
                                                    flex-shrink:0;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    border-radius:7px;
                                                    color:#9aa5b4;
                                                    background:#f1f4f6;
                                                    font-size:18px;
                                                "
                                            >

                                                <i class="fa-solid fa-image"></i>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <strong
                                                style="
                                                    display:block;
                                                    max-width:280px;
                                                    color:#26313e;
                                                    font-size:13px;
                                                "
                                            >

                                                <?= e(
                                                    $actualite['titre']
                                                ) ?>

                                            </strong>


                                            <small
                                                style="
                                                    color:#9aa5b4;
                                                    font-size:10px;
                                                "
                                            >

                                                ID #<?= $idActualite ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- CONTENU -->

                                <td
                                    style="
                                        max-width:320px;
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#687385;
                                        font-size:12px;
                                        line-height:1.5;
                                    "
                                >

                                    <?php

                                    $contenu =
                                        strip_tags(
                                            (string)
                                            $actualite['contenu']
                                        );

                                    ?>

                                    <?= e(
                                        mb_strlen($contenu) > 110
                                            ? mb_substr(
                                                $contenu,
                                                0,
                                                110
                                            ) . '…'
                                            : $contenu
                                    ) ?>

                                </td>


                                <!-- STATUT -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                    "
                                >

                                    <?php if (
                                        $statutActualite
                                        ===
                                        'Publiée'
                                    ): ?>

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                gap:6px;
                                                padding:6px 10px;
                                                border-radius:20px;
                                                color:#168746;
                                                background:#edf9f2;
                                                font-size:10px;
                                                font-weight:800;
                                            "
                                        >

                                            <i class="fa-solid fa-circle-check"></i>

                                            Publiée

                                        </span>

                                    <?php else: ?>

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                gap:6px;
                                                padding:6px 10px;
                                                border-radius:20px;
                                                color:#b86b00;
                                                background:#fff3df;
                                                font-size:10px;
                                                font-weight:800;
                                            "
                                        >

                                            <i class="fa-solid fa-file"></i>

                                            Brouillon

                                        </span>

                                    <?php endif; ?>

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

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            (string)
                                            $actualite['date_publication']
                                        )
                                    ) ?>

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


                                    <!-- MODIFIER -->

                                    <a
                                        href="actualites.php?modifier=<?= $idActualite ?>"
                                        title="Modifier"
                                        style="
                                            width:34px;
                                            height:34px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            margin-right:5px;
                                            border-radius:6px;
                                            color:#00a3e0;
                                            background:#eaf8fd;
                                            text-decoration:none;
                                        "
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <!-- STATUT -->

                                    <form
                                        method="POST"
                                        action="actualites.php"
                                        style="
                                            display:inline-flex;
                                            margin-right:5px;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="actualite_id"
                                            value="<?= $idActualite ?>"
                                        >


                                        <select
                                            name="statut"
                                            onchange="this.form.submit()"
                                            title="Modifier le statut"
                                            style="
                                                height:34px;
                                                padding:0 7px;
                                                border:1px solid #dfe6eb;
                                                border-radius:6px;
                                                color:#566170;
                                                background:#fff;
                                                font-size:10px;
                                            "
                                        >

                                            <option
                                                value="Publiée"
                                                <?= $statutActualite === 'Publiée'
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >
                                                Publiée
                                            </option>


                                            <option
                                                value="Brouillon"
                                                <?= $statutActualite === 'Brouillon'
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >
                                                Brouillon
                                            </option>

                                        </select>


                                        <input
                                            type="hidden"
                                            name="modifier_statut"
                                            value="1"
                                        >

                                    </form>


                                    <!-- SUPPRIMER -->

                                    <form
                                        method="POST"
                                        action="actualites.php"
                                        style="
                                            display:inline;
                                        "
                                        onsubmit="
                                            return confirm(
                                                'Voulez-vous vraiment supprimer cette actualité ?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="supprimer_id"
                                            value="<?= $idActualite ?>"
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