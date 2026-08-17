<?php
declare(strict_types=1);

/*
 * Composant utilisateur secondaire.
 * Les pages principales utilisent includes/header.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$siteUrl = '/SOFTEXPRESS';

$isConnected = isset($_SESSION['user_id']);
$prenom = trim((string)($_SESSION['user_prenom'] ?? ''));
$nom = trim((string)($_SESSION['user_nom'] ?? ''));
$email = trim((string)($_SESSION['user_email'] ?? ''));
$role = strtolower(trim((string)($_SESSION['user_role'] ?? 'user')));

$initials = strtoupper(
    mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)
);

if ($initials === '') {
    $initials = 'U';
}
?>

<?php if ($isConnected): ?>

<div class="user-menu">

    <button
        type="button"
        class="user-profile"
        aria-label="Menu utilisateur"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <span class="user-avatar">
            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </button>

    <div class="user-dropdown">

        <div class="user-dropdown-header">

            <span class="user-avatar">
                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <div class="user-info-text">

                <strong>
                    <?= htmlspecialchars(trim($prenom . ' ' . $nom), ENT_QUOTES, 'UTF-8') ?>
                </strong>

                <?php if ($email !== ''): ?>
                    <small>
                        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                    </small>
                <?php endif; ?>

                <small class="user-role">
                    <?= $role === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                </small>

            </div>

        </div>

        <?php if ($role === 'admin'): ?>
            <a
                href="<?= $siteUrl ?>/admin/index.php"
                class="profile-admin"
            >
                ⚙ Administration
            </a>
        <?php endif; ?>

        <a
            href="<?= $siteUrl ?>/auth/deconnexion.php"
            class="profile-logout"
        >
            ↪ Déconnexion
        </a>

    </div>

</div>

<?php else: ?>

<div class="auth">

    <a
        href="<?= $siteUrl ?>/auth/connexion.php"
        class="btn outline"
    >
        Connexion
    </a>

    <a
        href="<?= $siteUrl ?>/auth/inscription.php"
        class="btn orange"
    >
        Inscription
    </a>

</div>

<?php endif; ?>
