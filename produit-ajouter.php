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

$nom = '';
$description = '';
$prix = '';
$stock = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim((string)($_POST['nom'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $prix = trim((string)($_POST['prix'] ?? ''));
    $stock = trim((string)($_POST['stock'] ?? ''));

    if ($nom === '') {

        $erreur = 'Veuillez saisir le nom du produit.';

    } elseif ($description === '') {

        $erreur = 'Veuillez saisir la description du produit.';

    } elseif ($prix === '' || !is_numeric($prix) || (float)$prix < 0) {

        $erreur = 'Veuillez saisir un prix valide.';

    } elseif (
        $stock === ''
        || filter_var($stock, FILTER_VALIDATE_INT) === false
        || (int)$stock < 0
    ) {

        $erreur = 'Veuillez saisir un stock valide.';

    } elseif (
        !isset($_FILES['image'])
        || $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $erreur = 'Veuillez sélectionner une image.';

    } else {

        $dossier = __DIR__ . '/../assets/images/produits/';

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

        if (!in_array($extension, $extensionsAutorisees, true)) {

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

            $nomImage =
                'produit_'
                . date('Ymd_His')
                . '_'
                . bin2hex(random_bytes(4))
                . '.'
                . $extension;

            $destination = $dossier . $nomImage;

            if (!move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $destination
            )) {

                $erreur =
                    'Impossible d’enregistrer l’image.';

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO produits
                    (
                        nom,
                        description,
                        prix,
                        stock,
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
                    $stockEntier = (int)$stock;

                    $stmt->bind_param(
                        's s d i s',
                        $nom,
                        $description,
                        $prixDecimal,
                        $stockEntier,
                        $nomImage
                    );

                    /*
                    | Correction du type de bind_param :
                    | s s d i s
                    */

                    if ($stmt->execute()) {

                        $message =
                            'Le produit a été ajouté avec succès.';

                        $nom = '';
                        $description = '';
                        $prix = '';
                        $stock = '';

                    } else {

                        @unlink($destination);

                        $erreur =
                            'Impossible d’ajouter le produit.';
                    }

                    $stmt->close();
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
                    PRODUITS
                </span>

                <h1>
                    Ajouter un produit
                </h1>

                <p>
                    Ajoutez un nouvel équipement informatique.
                </p>

            </div>

            <div class="admin-date">

                <i class="fa-solid fa-laptop"></i>

                Nouveau produit

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
                    NOUVEAU PRODUIT
                </span>

                <h2>
                    Informations du produit
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
                            Nom du produit
                        </label>

                        <input
                            type="text"
                            name="nom"
                            value="<?= e($nom) ?>"
                            maxlength="150"
                            required
                            placeholder="Ex : Ordinateur portable HP"
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
                            min="0"
                            step="0.01"
                            required
                            placeholder="Ex : 350000"
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


                    <div>

                        <label style="
                            display:block;
                            margin-bottom:8px;
                            font-size:12px;
                            font-weight:700;
                            color:#566170;
                        ">
                            Stock
                        </label>

                        <input
                            type="number"
                            name="stock"
                            value="<?= e($stock) ?>"
                            min="0"
                            step="1"
                            required
                            placeholder="Ex : 10"
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
                        rows="7"
                        required
                        placeholder="Décrivez le produit..."
                        style="
                            width:100%;
                            padding:14px;
                            border:1px solid #dfe6eb;
                            border-radius:8px;
                            font-size:13px;
                            resize:vertical;
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
                        href="produits.php"
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

                        Ajouter le produit

                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>