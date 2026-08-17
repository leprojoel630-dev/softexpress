document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       MENU MOBILE
    ========================================================= */

    const navToggle = document.querySelector(".nav-toggle");
    const mainNav = document.querySelector(".main-nav");

    if (navToggle && mainNav) {

        navToggle.addEventListener("click", function (event) {

            event.stopPropagation();

            const isOpen = mainNav.classList.toggle("open");

            navToggle.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );
        });

        mainNav.querySelectorAll("a").forEach(function (link) {

            link.addEventListener("click", function () {

                mainNav.classList.remove("open");

                navToggle.setAttribute(
                    "aria-expanded",
                    "false"
                );

            });

        });
    }


    /* =========================================================
       SLIDERS
    ========================================================= */

    document.querySelectorAll(".arrow").forEach(function (button) {

        button.addEventListener("click", function () {

            const targetId = button.dataset.target;

            if (!targetId) {
                return;
            }

            const slider = document.getElementById(targetId);

            if (!slider) {
                return;
            }

            const direction =
                button.classList.contains("next") ? 1 : -1;

            const distance =
                Math.max(slider.clientWidth * 0.82, 260);

            slider.scrollBy({
                left: distance * direction,
                behavior: "smooth"
            });

        });

    });


    /* =========================================================
       MENU UTILISATEUR
       VERSION UNIQUE ET STABLE
    ========================================================= */

    const userMenus = document.querySelectorAll(".user-menu");

    userMenus.forEach(function (userMenu) {

        const userButton =
            userMenu.querySelector(".user-profile");

        const dropdown =
            userMenu.querySelector(".user-dropdown");


        /*
         * Si cette page ne contient pas le système utilisateur,
         * on ne fait rien.
         */
        if (!userButton || !dropdown) {
            return;
        }


        /* -----------------------------------------------------
           ACCESSIBILITE
        ----------------------------------------------------- */

        userButton.setAttribute("aria-haspopup", "true");

        userButton.setAttribute("aria-expanded", "false");


        /* -----------------------------------------------------
           OUVERTURE / FERMETURE
        ----------------------------------------------------- */

        userButton.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            const opened =
                userMenu.classList.toggle("active");

            userButton.setAttribute(
                "aria-expanded",
                opened ? "true" : "false"
            );

        });


        /* -----------------------------------------------------
           EMPECHER LE CLIC DANS LE MENU DE LE FERMER
        ----------------------------------------------------- */

        dropdown.addEventListener("click", function (event) {

            event.stopPropagation();

        });

    });


    /* =========================================================
       FERMER TOUS LES MENUS UTILISATEUR
       CLIC EXTERIEUR
    ========================================================= */

    document.addEventListener("click", function () {

        document.querySelectorAll(".user-menu.active")
            .forEach(function (userMenu) {

                userMenu.classList.remove("active");

                const button =
                    userMenu.querySelector(".user-profile");

                if (button) {

                    button.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            });

    });


    /* =========================================================
       TOUCHE ESCAPE
    ========================================================= */

    document.addEventListener("keydown", function (event) {

        if (event.key !== "Escape") {
            return;
        }

        document.querySelectorAll(".user-menu.active")
            .forEach(function (userMenu) {

                userMenu.classList.remove("active");

                const button =
                    userMenu.querySelector(".user-profile");

                if (button) {

                    button.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            });

    });

});