<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - PROTECTION ADMINISTRATEUR
|--------------------------------------------------------------------------
|
| Ce fichier protège toutes les pages du dossier /admin/.
| Il utilise directement la session créée par connexion.php.
|
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION DE CONNEXION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ../auth/connexion.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| VÉRIFICATION DU RÔLE ADMIN
|--------------------------------------------------------------------------
*/

$role = strtolower(
    trim(
        (string)($_SESSION['user_role'] ?? '')
    )
);

if ($role !== 'admin') {

    header('Location: ../index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| INFORMATIONS DE L'ADMINISTRATEUR
|--------------------------------------------------------------------------
*/

$adminId = (int)($_SESSION['user_id'] ?? 0);

$adminNom = trim(
    (string)($_SESSION['user_nom'] ?? '')
);

$adminPrenom = trim(
    (string)($_SESSION['user_prenom'] ?? '')
);

$adminEmail = trim(
    (string)($_SESSION['user_email'] ?? '')
);


/*
|--------------------------------------------------------------------------
| NOM COMPLET
|--------------------------------------------------------------------------
*/

$adminNomComplet = trim(
    $adminPrenom . ' ' . $adminNom
);

if ($adminNomComplet === '') {
    $adminNomComplet = 'Administrateur';
}


/*
|--------------------------------------------------------------------------
| INITIALES
|--------------------------------------------------------------------------
*/

$adminInitiales = '';

if ($adminPrenom !== '') {

    $adminInitiales .= mb_substr(
        $adminPrenom,
        0,
        1
    );
}

if ($adminNom !== '') {

    $adminInitiales .= mb_substr(
        $adminNom,
        0,
        1
    );
}

if ($adminInitiales === '') {
    $adminInitiales = 'A';
}

$adminInitiales = mb_strtoupper(
    $adminInitiales
);