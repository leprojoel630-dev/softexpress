<?php
$base_url = '/SOFTEXPRESS';
?>

<footer class="site-footer">

    <div class="footer-container">

        <div class="footer-column footer-brand">

            <img
                src="<?= $base_url ?>/assets/images/logo.png"
                alt="SOFTEXPRESS"
                class="footer-logo"
            >

            <p>
                Votre partenaire en formation, équipements informatiques
                et maintenance informatique.
            </p>

        </div>

        <div class="footer-column">

            <h3>Navigation</h3>

            <a href="<?= $base_url ?>/index.php">Accueil</a>
            <a href="<?= $base_url ?>/pages/apropos.php">À propos</a>
            <a href="<?= $base_url ?>/pages/formations.php">Formations</a>
            <a href="<?= $base_url ?>/pages/produits.php">Produits</a>

        </div>

        <div class="footer-column">

            <h3>Nos services</h3>

            <a href="<?= $base_url ?>/pages/formations.php">
                Formations
            </a>

            <a href="<?= $base_url ?>/pages/produits.php">
                Équipements informatiques
            </a>

            <a href="<?= $base_url ?>/pages/maintenance.php">
                Maintenance informatique
            </a>

        </div>

        <div class="footer-column">

            <h3>Contact</h3>

            <p>Téléphone : +237 XX XX XX XX</p>
            <p>Email : contact@softexpress.cm</p>
            <p>Cameroun</p>

        </div>

    </div>

    <div class="footer-bottom">

        <p>
            © <?= date('Y') ?> SOFTEXPRESS. Tous droits réservés.
        </p>

    </div>

</footer>

</body>
</html>