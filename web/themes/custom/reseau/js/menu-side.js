(function ($, Drupal) {
  Drupal.behaviors.mySidebarMenu = {
    attach: function (context, settings) {
      /** Gestion ordre élément firstsidebar **/
      $(once('some-arbitrary-but-unique-key7', '.logo_menus', context)).each(function () {
        window.addEventListener("load", function () {
          const container = document.querySelector(".principale");
          const one = document.querySelector(".logo_menus");
          const three = document.querySelector(".blocs_comp");

          // 👉 FORCER l'affichage du sous-menu actif (desktop ou mobile)
          function openActiveSubmenu() {
            $(one)
              .find("a.is-active")
              .each(function () {
                const $li = $(this).closest("li");
                const $sub = $li.find("ul.collapse");
                if ($sub.length) {
                  $sub.removeClass("collapse").addClass("show");
                }
              });
          }

          function toggleClickListeners(enable) {
            const navHeaders = $(".logo_menus nav > h2");
            const formHeaders = $("#block-formulaireexposerecherche-formationspage-1 > h2");
            if (enable) {
              // Ajouter l'événement de clic
              navHeaders.off("click").on("click", function () {
                const ulSibling = $(this).siblings("ul");
                ulSibling.toggleClass("show");
                $(this).toggleClass("show", ulSibling.hasClass("show"));
              });

              formHeaders.off("click").on("click", function () {
                const formSibling = $(this).siblings("form");
                formSibling.toggleClass("d-none");
                $(this).toggleClass("hide", formSibling.hasClass("d-none"));
              });
            } else {
              // Supprimer l'événement de clic
              navHeaders.off("click");
              formHeaders.off("click");
            }
          }

          // Fonction pour regrouper One et Three en mode desktop
          function adjustLayout() {
            const isDesktop = window.innerWidth >= 768;
            if (isDesktop) {
              // Mode desktop : On s'assure que <aside> est créé
              if (!container.querySelector(".aside")) {
                const aside = document.createElement("aside");
                aside.className =
                  "aside layout-sidebar-first col-12 col-md-4 col-lg-3 mb-5 mb-md-0";

                if (one) aside.appendChild(one);
                if (three) aside.appendChild(three);

                container.insertBefore(
                  aside,
                  container.querySelector(".contenuprincipal")
                );
              }

              if (!$(".logo_menus nav > h2").siblings("ul").hasClass("show")) {
                $(".logo_menus nav > h2").siblings("ul").addClass("show");
              }

              $("#block-formulaireexposerecherche-formationspage-1 > h2")
                .siblings("form")
                .removeClass("d-none");
              $("#block-formulaireexposerecherche-formationspage-1 > h2").addClass("hide");

              toggleClickListeners(false); // Désactiver les clics en desktop

              /** Gestion position sticky **/
              var sidebarcontentheight = 0;
              $(".aside")
                .children()
                .each(function () {
                  sidebarcontentheight =
                    sidebarcontentheight + $(this).outerHeight(true);
                });

              var contenuPrincipalHeight = $(".contenuprincipal").outerHeight(true);

              if (
                contenuPrincipalHeight >= sidebarcontentheight &&
                sidebarcontentheight < $(window).height()
              ) {
                $(".aside > aside").css("position", "sticky");
                $(".aside > aside").css("top", "100px");
                $(".aside > aside").css("height", "auto");
              }
              /** Fin Gestion position sticky **/
            } else {
              // Mode mobile : restaurer la structure
              const aside = container.querySelector(".aside");
              if (aside) {
                if (one)
                  container.insertBefore(
                    one,
                    container.querySelector(".contenuprincipal")
                  );
                if (three)
                  container.insertBefore(
                    three,
                    container.querySelector(".contenuprincipal")
                  );
                aside.remove();
              }

              if ($(".logo_menus nav > h2").siblings("ul").hasClass("show")) {
                $(".logo_menus nav > h2").siblings("ul").removeClass("show");
              }

              $("#block-formulaireexposerecherche-formationspage-1 > h2")
                .siblings("form")
                .addClass("d-none");
              $("#block-formulaireexposerecherche-formationspage-1 > h2").addClass("hide");
              $("aside")
                .css("height", "auto")
                .css("position", "initial")
                .css("top", "auto");

              toggleClickListeners(true); // Activer les clics en mobile
            }

            // Animation d'affichage
            $(".logo_menus")
              .css("display", "block")
              .css("animation-duration", "0.75s")
              .css("animation-name", "animate-fade");
          }

          // 💥 IMPORTANT : appel immédiat
          openActiveSubmenu();
          // Ajuste la disposition au chargement
          adjustLayout();

          // Réajuste la disposition lors du redimensionnement
          window.addEventListener("resize", adjustLayout);
        });
      });
    },
  };
})(jQuery, Drupal);
