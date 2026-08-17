<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

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

$message = '';
$erreur = '';

$titre = '';
$contenu = '';
$statut = 'Publiée';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim((string)($_POST['titre'] ?? ''));
    $contenu = trim((string)($_POST['contenu'] ?? ''));
    $statut = trim((string)($_POST['statut'] ?? 'Publiée'));

    $statutsAutorises = [
        'Publiée',
        'Brouillon'
    ];

    if ($titre === '') {

        $erreur = 'Veuillez saisir le titre de l’actualité.';

    } elseif ($contenu === '') {

        $erreur = 'Veuillez saisir le contenu de l’actualité.';

    } elseif (!in_array($statut, $statutsAutorises, true)) {

        $erreur = 'Statut invalide.';

    } elseif (
        !isset($_FILES['image'])
        || $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $erreur = 'Veuillez sélectionner une image.';

    } else {

        $dossier =
            __DIR__ . '/../assets/images/actualites/';

        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }

        $extension = strtolower(
            pathinfo(
                basename((string)$_FILES['image']['name']),
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
                'Format d’image non autorisé.';

        } elseif (
            !getimagesize(
                $_FILES['image']['tmp_name']
            )
        ) {

            $erreur =
                'Le fichier sélectionné n’est pas une image valide.';

        } else {

            $nomImage =
                'actualite_'
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

                $auteurId = (int)$adminId;

                $stmt = $conn->prepare("
                    INSERT INTO actualites
                    (
                        titre,
                        contenu,
                        image,
                        auteur_id,
                        statut
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

                    $stmt->bind_param(
                        'sss is',
                        $titre,
                        $contenu,
                        $nomImage,
                        $auteurId,
                        $statut
                    );

                    /*
                    | Correction :
                    | la chaîne correcte est ss sis ? 
                    */

                    $stmt->close();

                    /*
                    | On recrée proprement la requête ci-dessous.
                    */

                    $stmt = $conn->prepare("
                        INSERT INTO actualites
                        (
                            titre,
                            contenu,
                            image,
                            auteur_id,
                            statut
                        )
                        VALUES
                        (?, ?, ?, ?, ?)
                    ");

                    if (!$stmt) {

                        @unlink($destination);

                        $erreur =
                            'Impossible de préparer l’enregistrement.';

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
                        | Cette partie sera remplacée par la version propre
                        | ci-dessous.
                        */

                        $stmt = $conn->prepare("
                            INSERT INTO actualites
                            (titre, contenu, image, auteur_id, statut)
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        if (!$stmt) {

                            @unlink($destination);

                            $erreur =
                                'Impossible de préparer l’enregistrement.';

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
                                    'L’actualité a été publiée avec succès.';

                                $titre = '';
                                $contenu = '';
                                $statut = 'Publiée';

                            } else {

                                @unlink($destination);

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

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-content">

        <div class="admin-content-header">

            <div>

                <span class="admin-eyebrow">
                    ACTUALITÉS
                </span>

                <h1>
                    Publier une actualité
                </h1>

                <p>
                    Ajoutez une nouvelle actualité sur SOFTEXPRESS.
                </p>

            </div>

            <div class="admin-date">

                <i class="fa-solid fa-newspaper"></i>

                Nouvelle actualité

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
                    NOUVELLE ACTUALITÉ
                </span>

                <h2>
                    Informations
                </h2>

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
                style="padding:25px;"
            >


                <div style="margin-bottom:20px;">

                    <label style="
                        display:block;
                        margin-bottom:8px;
                        font-size:12px;
                        font-weight:700;
                        color:#566170;
                    ">
                        Titre
                    </label>

                    <input
                        type="text"
                        name="titre"
                        value="<?= e($titre) ?>"
                        maxlength="200"
                        required
                        placeholder="Titre de l’actualité"
                        style="
                            width:100%;
                            height:46px;
                            padding:0 14px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            font-size:13px;
                        "
                    >

                </div>


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
                            Statut
                        </label>

                        <select
                            name="statut"
                            style="
                                width:100%;
                                height:46px;
                                padding:0 14px;
                                border:1px solid #dfe6eb;
                                border-radius:8px;
                                font-size:13px;
                                background:#fff;
                            "
                        >

                            <option
                                value="Publiée"
                                <?= $statut === 'Publiée' ? 'selected' : '' ?>
                            >
                                Publiée
                            </option>

                            <option
                                value="Brouillon"
                                <?= $statut === 'Brouillon' ? 'selected' : '' ?>
                            >
                                Brouillon
                            </option>

                        </select>

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
                        Contenu
                    </label>

                    <textarea
                        name="contenu"
                        rows="10"
                        required
                        placeholder="Écrivez le contenu de l’actualité..."
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            font-size:13px;
                            resize:vertical;
                        "
                    ><?= e($contenu) ?></textarea>

                </div>


                <div style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:25px;
                ">

                    <a
                        href="actualites.php"
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

                        <i class="fa-solid fa-paper-plane"></i>

                        Publier l’actualité

                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>