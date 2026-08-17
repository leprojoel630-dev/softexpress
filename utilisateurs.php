<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - GESTION DES UTILISATEURS
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
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';
$erreur = '';


/*
|--------------------------------------------------------------------------
| SUPPRESSION D'UN UTILISATEUR
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

        $erreur =
            'Utilisateur invalide.';

    } elseif ($id === $adminId) {

        $erreur =
            'Vous ne pouvez pas supprimer votre propre compte administrateur.';

    } else {

        $stmt = $conn->prepare("
            DELETE FROM utilisateurs
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
                        'Utilisateur supprimé avec succès.';

                } else {

                    $erreur =
                        'Utilisateur introuvable.';
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
| MODIFICATION DU RÔLE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['modifier_role'])
) {

    $id = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );

    $nouveauRole =
        trim(
            (string)(
                $_POST['role'] ?? ''
            )
        );


    $rolesAutorises = [
        'admin',
        'client'
    ];


    if (!$id || $id <= 0) {

        $erreur =
            'Utilisateur invalide.';

    } elseif (
        !in_array(
            $nouveauRole,
            $rolesAutorises,
            true
        )
    ) {

        $erreur =
            'Rôle sélectionné invalide.';

    } elseif ($id === $adminId) {

        $erreur =
            'Vous ne pouvez pas modifier votre propre rôle.';

    } else {

        $stmt = $conn->prepare("
            UPDATE utilisateurs
            SET role = ?
            WHERE id = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $erreur =
                'Impossible de préparer la modification.';

        } else {

            $stmt->bind_param(
                'si',
                $nouveauRole,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    'Rôle de l’utilisateur modifié avec succès.';

            } else {

                $erreur =
                    'Impossible de modifier le rôle.';
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
    (string)(
        $_GET['recherche'] ?? ''
    )
);


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DES UTILISATEURS
|--------------------------------------------------------------------------
*/

$utilisateurs = [];


if ($recherche !== '') {

    $motif =
        '%' . $recherche . '%';


    $stmt = $conn->prepare("
        SELECT
            id,
            nom,
            prenom,
            email,
            role
        FROM utilisateurs
        WHERE
            nom LIKE ?
            OR prenom LIKE ?
            OR email LIKE ?
            OR role LIKE ?
        ORDER BY id DESC
    ");


    if ($stmt) {

        $stmt->bind_param(
            'ssss',
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

            $utilisateurs[] =
                $ligne;
        }

        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT
            id,
            nom,
            prenom,
            email,
            role
        FROM utilisateurs
        ORDER BY id DESC
    ");


    if ($result) {

        while (
            $ligne =
            $result->fetch_assoc()
        ) {

            $utilisateurs[] =
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
                    GESTION DES COMPTES
                </span>

                <h1>
                    Utilisateurs
                </h1>

                <p>
                    Gérez les comptes et les rôles des utilisateurs.
                </p>

            </div>


            <div class="admin-date">

                <i class="fa-solid fa-users"></i>

                <?= count($utilisateurs) ?>

                utilisateur(s)

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
                action="utilisateurs.php"
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
                        placeholder="Rechercher par nom, prénom, email ou rôle..."
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
                        href="utilisateurs.php"
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
                    COMPTES
                </span>

                <h2>
                    Liste des utilisateurs
                </h2>

            </div>


            <?php if (empty($utilisateurs)): ?>

                <div
                    style="
                        padding:45px 20px;
                        text-align:center;
                        color:#7b8796;
                    "
                >

                    <i
                        class="fa-solid fa-users-slash"
                        style="
                            font-size:38px;
                            margin-bottom:15px;
                            color:#c5cdd5;
                        "
                    ></i>

                    <p>
                        Aucun utilisateur trouvé.
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
                            min-width:750px;
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
                                    UTILISATEUR
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
                                    EMAIL
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
                                    RÔLE
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
                            $utilisateurs
                            as $utilisateur
                        ): ?>


                            <?php

                            $idUtilisateur =
                                (int)$utilisateur['id'];

                            $roleUtilisateur =
                                strtolower(
                                    trim(
                                        (string)(
                                            $utilisateur['role']
                                            ?? ''
                                        )
                                    )
                                );

                            $nomComplet =
                                trim(
                                    (string)(
                                        $utilisateur['prenom']
                                        ?? ''
                                    )
                                    . ' '
                                    .
                                    (string)(
                                        $utilisateur['nom']
                                        ?? ''
                                    )
                                );

                            $initiales = '';

                            $prenom =
                                trim(
                                    (string)(
                                        $utilisateur['prenom']
                                        ?? ''
                                    )
                                );

                            $nom =
                                trim(
                                    (string)(
                                        $utilisateur['nom']
                                        ?? ''
                                    )
                                );

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

                                    #<?= $idUtilisateur ?>

                                </td>


                                <!-- NOM -->

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
                                                    : 'Utilisateur'
                                            ) ?>

                                        </strong>

                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        color:#687385;
                                        font-size:13px;
                                    "
                                >

                                    <?= e(
                                        $utilisateur['email']
                                    ) ?>

                                </td>


                                <!-- ROLE -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                    "
                                >


                                    <?php if (
                                        $roleUtilisateur === 'admin'
                                    ): ?>

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

                                            <i class="fa-solid fa-shield-halved"></i>

                                            Administrateur

                                        </span>

                                    <?php else: ?>

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                gap:6px;
                                                padding:6px 10px;
                                                border-radius:20px;
                                                color:#087eaa;
                                                background:#eaf8fd;
                                                font-size:10px;
                                                font-weight:800;
                                            "
                                        >

                                            <i class="fa-solid fa-user"></i>

                                            Client

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- ACTIONS -->

                                <td
                                    style="
                                        padding:15px 14px;
                                        border-bottom:1px solid #edf0f3;
                                        text-align:right;
                                    "
                                >


                                    <?php if (
                                        $idUtilisateur
                                        !==
                                        $adminId
                                    ): ?>


                                        <!-- MODIFIER ROLE -->

                                        <form
                                            method="POST"
                                            action="utilisateurs.php"
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                gap:6px;
                                                margin-right:6px;
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= $idUtilisateur ?>"
                                            >

                                            <select
                                                name="role"
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
                                                    value="client"
                                                    <?= $roleUtilisateur === 'client'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Client
                                                </option>

                                                <option
                                                    value="admin"
                                                    <?= $roleUtilisateur === 'admin'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Admin
                                                </option>

                                            </select>


                                            <button
                                                type="submit"
                                                name="modifier_role"
                                                value="1"
                                                title="Modifier le rôle"
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


                                        <!-- SUPPRIMER -->

                                        <form
                                            method="POST"
                                            action="utilisateurs.php"
                                            style="display:inline;"
                                            onsubmit="
                                                return confirm(
                                                    'Voulez-vous vraiment supprimer cet utilisateur ?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="supprimer_id"
                                                value="<?= $idUtilisateur ?>"
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


                                    <?php else: ?>


                                        <span
                                            style="
                                                color:#9aa5b4;
                                                font-size:11px;
                                                font-weight:600;
                                            "
                                        >

                                            Compte actuel

                                        </span>


                                    <?php endif; ?>


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