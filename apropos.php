<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>À propos | SOFTEXPRESS</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        /* =====================================================
           PAGE À PROPOS
        ===================================================== */

        .about-hero {
            padding: 90px 0;
            background:
                linear-gradient(
                    135deg,
                    rgba(0, 163, 224, 0.96),
                    rgba(249, 157, 28, 0.94)
                );
            position: relative;
            overflow: hidden;
            color: #fff;
        }

        .about-hero::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border: 70px solid rgba(255,255,255,0.08);
            border-radius: 50%;
            right: -160px;
            top: -180px;
        }

        .about-hero::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border: 45px solid rgba(255,255,255,0.07);
            border-radius: 50%;
            left: -140px;
            bottom: -180px;
        }

        .about-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .about-hero h1 {
            font-size: clamp(38px, 6vw, 64px);
            line-height: 1.05;
            margin: 12px 0 20px;
            font-weight: 800;
        }

        .about-hero h1 span {
            color: #fff;
        }

        .about-hero p {
            max-width: 700px;
            font-size: 17px;
            line-height: 1.8;
        }


        /* =====================================================
           PRÉSENTATION
        ===================================================== */

        .about-section {
            padding: 90px 0;
            background: #fff;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            max-width: 560px;
            display: block;
            border-radius: 22px;
            object-fit: cover;
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }

        .about-content h2 {
            font-size: 38px;
            line-height: 1.2;
            margin: 10px 0 20px;
            color: #111;
        }

        .about-content h2 span {
            color: #00A3E0;
        }

        .about-content p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 16px;
        }


        /* =====================================================
           CARTES
        ===================================================== */

        .about-values {
            padding: 90px 0;
            background: #f7fafc;
        }

        .about-heading {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 50px;
        }

        .about-heading h2 {
            font-size: 38px;
            margin: 10px 0 15px;
            color: #111;
        }

        .about-heading p {
            color: #666;
            line-height: 1.7;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .value-card {
            background: #fff;
            border-radius: 18px;
            padding: 32px 26px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .value-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.10);
        }

        .value-icon {
            width: 58px;
            height: 58px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(
                135deg,
                #00A3E0,
                #F99D1C
            );
            color: #fff;
            font-size: 25px;
            margin-bottom: 20px;
        }

        .value-card h3 {
            margin: 0 0 10px;
            color: #111;
            font-size: 20px;
        }

        .value-card p {
            margin: 0;
            color: #666;
            line-height: 1.7;
        }


        /* =====================================================
           SERVICES
        ===================================================== */

        .about-services {
            padding: 90px 0;
            background: #fff;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-top: 45px;
        }

        .service-box {
            padding: 30px;
            border-radius: 18px;
            border: 1px solid #e8edf1;
            background: #fff;
        }

        .service-box h3 {
            color: #111;
            margin: 15px 0 10px;
        }

        .service-box p {
            color: #666;
            line-height: 1.7;
            margin: 0;
        }


        /* =====================================================
           CTA
        ===================================================== */

        .about-cta {
            padding: 70px 0;
            background: linear-gradient(
                135deg,
                #00A3E0,
                #0088bc
            );
            color: #fff;
        }

        .about-cta-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
        }

        .about-cta h2 {
            margin: 8px 0 10px;
            font-size: 34px;
        }

        .about-cta p {
            margin: 0;
            opacity: .9;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .values-grid,
            .services-grid {
                grid-template-columns: 1fr 1fr;
            }

            .about-cta-inner {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 600px) {

            .about-hero {
                padding: 65px 0;
            }

            .about-section,
            .about-values,
            .about-services {
                padding: 65px 0;
            }

            .values-grid,
            .services-grid {
                grid-template-columns: 1fr;
            }

            .about-content h2,
            .about-heading h2 {
                font-size: 30px;
            }

        }

    </style>

</head>

<body>

<main>


<!-- =========================================================
     HERO
========================================================= -->

<section class="about-hero">

    <div class="container about-hero-content">

        <p class="eyebrow white">
            QUI SOMMES-NOUS ?
        </p>

        <h1>
            À PROPOS DE <span>SOFTEXPRESS</span>
        </h1>

        <p>
            Une entreprise tournée vers la technologie,
            la formation professionnelle, les équipements
            informatiques et les services de maintenance.
        </p>

    </div>

</section>


<!-- =========================================================
     PRÉSENTATION
========================================================= -->

<section class="about-section">

    <div class="container about-grid">

        <div class="about-image">

            <img
                src="../assets/images/backgrounds/technology.jpg"
                alt="SOFTEXPRESS"
                onerror="this.style.display='none';"
            >

        </div>


        <div class="about-content">

            <p class="eyebrow">
                NOTRE ENTREPRISE
            </p>

            <h2>
                La technologie au service de
                <span>votre réussite</span>
            </h2>

            <p>
                SOFTEXPRESS accompagne les particuliers,
                les professionnels et les organisations
                dans leurs besoins liés aux technologies
                de l'information et de la communication.
            </p>

            <p>
                Notre activité repose sur trois domaines
                essentiels : la formation professionnelle,
                la commercialisation d'équipements
                informatiques et la maintenance.
            </p>

            <p>
                Notre objectif est de proposer des solutions
                accessibles, pratiques et adaptées aux
                besoins de chaque client.
            </p>

        </div>

    </div>

</section>


<!-- =========================================================
     NOS VALEURS
========================================================= -->

<section class="about-values">

    <div class="container">

        <div class="about-heading">

            <p class="eyebrow">
                NOS VALEURS
            </p>

            <h2>
                Ce qui nous définit
            </h2>

            <p>
                Des principes simples qui orientent notre
                manière de travailler et d'accompagner
                nos clients.
            </p>

        </div>


        <div class="values-grid">

            <article class="value-card">

                <div class="value-icon">
                    ✓
                </div>

                <h3>
                    Qualité
                </h3>

                <p>
                    Nous cherchons à fournir des services
                    et des solutions répondant aux besoins
                    réels de nos clients.
                </p>

            </article>


            <article class="value-card">

                <div class="value-icon">
                    ⚡
                </div>

                <h3>
                    Réactivité
                </h3>

                <p>
                    Nous accordons une importance particulière
                    à la rapidité de prise en charge et au
                    suivi des demandes.
                </p>

            </article>


            <article class="value-card">

                <div class="value-icon">
                    ★
                </div>

                <h3>
                    Professionnalisme
                </h3>

                <p>
                    Notre priorité est de construire une
                    relation sérieuse et durable avec nos
                    clients et partenaires.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================================
     NOS DOMAINES
========================================================= -->

<section class="about-services">

    <div class="container">

        <div class="about-heading">

            <p class="eyebrow">
                NOS DOMAINES D'ACTIVITÉ
            </p>

            <h2>
                Des solutions pour vos besoins
            </h2>

        </div>


        <div class="services-grid">

            <article class="service-box">

                <div class="value-icon">
                    🎓
                </div>

                <h3>
                    Formation
                </h3>

                <p>
                    Des formations professionnelles en
                    marketing, bureautique et informatique
                    pour développer vos compétences.
                </p>

            </article>


            <article class="service-box">

                <div class="value-icon">
                    💻
                </div>

                <h3>
                    Équipements informatiques
                </h3>

                <p>
                    Ordinateurs portables, modems Wi-Fi
                    et différents équipements informatiques
                    adaptés à vos besoins.
                </p>

            </article>


            <article class="service-box">

                <div class="value-icon">
                    🔧
                </div>

                <h3>
                    Maintenance
                </h3>

                <p>
                    Diagnostic, réparation et maintenance
                    des équipements informatiques pour
                    assurer leur bon fonctionnement.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="about-cta">

    <div class="container about-cta-inner">

        <div>

            <p class="eyebrow white">
                BESOIN DE NOS SERVICES ?
            </p>

            <h2>
                Parlons de votre projet
            </h2>

            <p>
                Notre équipe est disponible pour répondre
                à vos questions et vous accompagner.
            </p>

        </div>

        <a
            href="contact.php"
            class="btn orange big"
        >
            Nous contacter
        </a>

    </div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="container footer-grid">

        <div>

            <img
                src="../assets/images/logo.png"
                alt="SOFTEXPRESS"
            >

            <p>
                Formation, équipements informatiques
                et maintenance.
            </p>

        </div>


        <div>

            <h3>
                Navigation
            </h3>

            <a href="../index.php">
                Accueil
            </a>

            <a href="apropos.php">
                À propos
            </a>

            <a href="formations.php">
                Formations
            </a>

            <a href="produits.php">
                Produits
            </a>

        </div>


        <div>

            <h3>
                Services
            </h3>

            <a href="maintenance.php">
                Maintenance
            </a>

            <a href="actualites.php">
                Actualités
            </a>

            <a href="contact.php">
                Contact
            </a>

            <a href="../auth/connexion.php">
                Connexion
            </a>

        </div>

    </div>


    <div class="bottom">

        <div class="container">

            © <?= date('Y') ?> SOFTEXPRESS —
            Tous droits réservés.

        </div>

    </div>

</footer>


<script src="../assets/js/main.js"></script>

</body>

</html>