<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - AJOUTER UNE FORMATION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| FONCTION D'ÉCHAPPEMENT
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$erreur = '';

$titre = '';
$description = '';
$duree = '';
$prix = '';


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim(
        (string)($_POST['titre'] ?? '')
    );

    $description = trim(
        (string)($_POST['description'] ?? '')
    );

    $duree = trim(
        (string)($_POST['duree'] ?? '')
    );

    $prix = trim(
        (string)($_POST['prix'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($titre === '') {

        $erreur = 'Veuillez saisir le titre de la formation.';

    } elseif ($description === '') {

        $erreur = 'Veuillez saisir la description de la formation.';

    } elseif ($duree === '') {

        $erreur = 'Veuillez saisir la durée de la formation.';

    } elseif ($prix === '' || !is_numeric($prix)) {

        $erreur = 'Veuillez saisir un prix valide.';

    } elseif ((float)$prix < 0) {

        $erreur = 'Le prix ne peut pas être négatif.';

    } elseif (
        !isset($_FILES['image'])
        || $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $erreur = 'Veuillez sélectionner une image.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | UPLOAD IMAGE
        |--------------------------------------------------------------------------
        */

        $dossier = __DIR__ . '/../assets/images/formations/';


        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }


        $nomOriginal = basename(
            (string)$_FILES['image']['name']
        );

        $extension = strtolower(
            pathinfo(
                $nomOriginal,
                PATHINFO_EXTENSION
            )
        );


        $extensionsAutorisees = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (!in_array(
            $extension,
            $extensionsAutorisees,
            true
        )) {

            $erreur =
                'Format d’image non autorisé. Utilisez JPG, JPEG, PNG ou WEBP.';

        } elseif (
            !getimagesize(
                $_FILES['image']['tmp_name']
            )
        ) {

            $erreur =
                'Le fichier sélectionné n’est pas une image valide.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | NOM UNIQUE
            |--------------------------------------------------------------------------
            */

            $nomImage =
                'formation_'
                . date('Ymd_His')
                . '_'
                . bin2hex(random_bytes(4))
                . '.'
                . $extension;


            $destination =
                $dossier . $nomImage;


            if (!move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $destination
            )) {

                $erreur =
                    'Impossible d’enregistrer l’image.';

            } else {


                /*
                |--------------------------------------------------------------------------
                | INSERTION
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO formations
                    (
                        titre,
                        description,
                        duree,
                        prix,
                        image
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


                if (!$stmt) {

                    @unlink($destination);

                    $erreur =
                        'Impossible de préparer l’enregistrement.';

                } else {

                    $prixDecimal = (float)$prix;

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
                            'La formation a été ajoutée avec succès.';

                        $titre = '';
                        $description = '';
                        $duree = '';
                        $prix = '';

                    } else {

                        @unlink($destination);

                        $erreur =
                            'Impossible d’ajouter la formation.';
                    }


                    $stmt->close();
                }
            }
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


    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>


    <main class="admin-content">


        <div class="admin-content-header">

            <div>

                <span class="admin-eyebrow">
                    FORMATIONS
                </span>

                <h1>
                    Ajouter une formation
                </h1>

                <p>
                    Créez une nouvelle formation professionnelle.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-graduation-cap"></i>

                Nouvelle formation

            </div>

        </div>


        <?php if ($message !== ''): ?>

            <div style="
                margin-bottom:20px;
                padding:14px 16px;
                border-radius:9px;
                background:#edf9f2;
                border:1px solid #ccefdc;
                color:#168746;
                font-size:13px;
                font-weight:700;
            ">

                <i class="fa-solid fa-circle-check"></i>

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <?php if ($erreur !== ''): ?>

            <div style="
                margin-bottom:20px;
                padding:14px 16px;
                border-radius:9px;
                background:#fff0f0;
                border:1px solid #ffd5d5;
                color:#c62828;
                font-size:13px;
                font-weight:700;
            ">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($erreur) ?>

            </div>

        <?php endif; ?>


        <section class="admin-panel">


            <div class="admin-panel-header">

                <span>
                    NOUVELLE FORMATION
                </span>

                <h2>
                    Informations de la formation
                </h2>

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
                style="padding:25px;"
            >


                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:20px;
                ">


                    <div>

                        <label style="
                            display:block;
                            margin-bottom:8px;
                            font-size:12px;
                            font-weight:700;
                            color:#566170;
                        ">
                            Titre de la formation
                        </label>

                        <input
                            type="text"
                            name="titre"
                            value="<?= e($titre) ?>"
                            required
                            maxlength="150"
                            placeholder="Ex : Formation Marketing Digital"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                font-size:13px;
                                outline:none;
                            "
                        >

                    </div>


                    <div>

                        <label style="
                            display:block;
                            margin-bottom:8px;
                            font-size:12px;
                            font-weight:700;
                            color:#566170;
                        ">
                            Durée
                        </label>

                        <input
                            type="text"
                            name="duree"
                            value="<?= e($duree) ?>"
                            required
                            maxlength="100"
                            placeholder="Ex : 2 mois"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                font-size:13px;
                                outline:none;
                            "
                        >

                    </div>


                    <div>

                        <label style="
                            display:block;
                            margin-bottom:8px;
                            font-size:12px;
                            font-weight:700;
                            color:#566170;
                        ">
                            Prix (FCFA)
                        </label>

                        <input
                            type="number"
                            name="prix"
                            value="<?= e($prix) ?>"
                            required
                            min="0"
                            step="0.01"
                            placeholder="Ex : 50000"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                font-size:13px;
                                outline:none;
                            "
                        >

                    </div>


                    <div>

                        <label style="
                            display:block;
                            margin-bottom:8px;
                            font-size:12px;
                            font-weight:700;
                            color:#566170;
                        ">
                            Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                            required
                            style="
                                width:100%;
                                height:46px;
                                padding:10px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                font-size:12px;
                                background:#fff;
                            "
                        >

                    </div>


                </div>


                <div style="margin-top:20px;">

                    <label style="
                        display:block;
                        margin-bottom:8px;
                        font-size:12px;
                        font-weight:700;
                        color:#566170;
                    ">
                        Description
                    </label>

                    <textarea
                        name="description"
                        required
                        rows="7"
                        placeholder="Décrivez la formation..."
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            font-size:13px;
                            resize:vertical;
                            outline:none;
                        "
                    ><?= e($description) ?></textarea>

                </div>


                <div style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:25px;
                ">

                    <a
                        href="formations.php"
                        style="
                            height:44px;
                            padding:0 20px;
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:8px;
                            background:#f1f4f6;
                            color:#687385;
                            text-decoration:none;
                            font-size:13px;
                            font-weight:700;
                        "
                    >
                        Annuler
                    </a>


                    <button
                        type="submit"
                        style="
                            height:44px;
                            padding:0 22px;
                            border:0;
                            border-radius:8px;
                            background:#00a3e0;
                            color:#fff;
                            cursor:pointer;
                            font-size:13px;
                            font-weight:700;
                        "
                    >

                        <i class="fa-solid fa-plus"></i>

                        Ajouter la formation

                    </button>

                </div>


            </form>

        </section>


    </main>

</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>