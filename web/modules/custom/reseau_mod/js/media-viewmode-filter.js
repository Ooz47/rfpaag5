(function ($, Drupal) {
  Drupal.behaviors.mediaViewModeFilter = {
    attach(context) {
      // 1) Première passe sur TOUTES les figures, après un léger délai
      once("media-init", ".ck-editor__editable", context).forEach(() => {
        setTimeout(() => {
          document
            .querySelectorAll("figure.drupal-media.ck-widget")
            .forEach((fig) => applyFilter(fig));
        }, 500);
      });

      // 2) À chaque clic sur un media widget sélectionné, on met à jour aussi
      once("media-click-listener", "body", context).forEach(() => {
        document.addEventListener("click", (e) => {
          const fig = e.target.closest("figure.drupal-media.ck-widget");
          if (fig && fig.classList.contains("ck-widget_selected")) {
            setTimeout(() => applyFilter(fig), 50);
          }
        });
      });

      // Types / modes autorisés
      const allowedModesByType = {
        image: [
          "Par défaut",
          "colorbox",
          "colorbox petit",
          "colorbox pleine largeur",
        ],
        galerie: ["Par défaut", "Mur d'images"],
        document: ["Par défaut"],
        audio: ["Par défaut"],
        video: ["Par défaut"],
        remote_video: ["Par défaut"],
      };

      function getMediaType(fig) {
        if (
          fig.querySelector(".blazy--field-media-slideshow") ||
          fig.querySelector(".slick-wrapper")
        ) {
          return "galerie";
        }
        if (fig.querySelector(".file")) return "document";
        if (fig.querySelector("audio")) return "audio";
        if (fig.querySelector(".remotevideo")) return "remote_video";
        if (fig.querySelector("video")) return "video";
        return "image";
      }

      function applyFilter(fig) {
        const type = getMediaType(fig);
        const allowed = allowedModesByType[type];

        // —> 1) si type non-image, on FORCE toujours "default"
        let current = fig.getAttribute("data-view-mode");
        if (["document", "audio", "video", "remote_video"].includes(type)) {
          fig.setAttribute("data-view-mode", "default");
          current = "default";
        }
        // si jamais CKEditor injecte "null", on retombe sur default aussi
        if (!allowed.includes(labelize(current))) {
          fig.setAttribute("data-view-mode", "default");
          current = "default";
        }

        // —> 2) masque / réaffiche les items de la dropdown
        const panel = document.querySelector(".ck-dropdown__panel .ck-list");
        if (panel) {
          panel.querySelectorAll(".ck-list__item").forEach((li) => {
            const label = li.textContent.trim();
            li.classList.toggle("ck-hidden", !allowed.includes(label));
          });
        }
        const toggle = document.querySelector(".ck-dropdown__button");
        if (toggle) {
          toggle.disabled = allowed.length === 1;
        }

        // —> 3) synchronise CKEditor5 model + <textarea>
        const editable = document.querySelector(".ck-editor__editable");
        const editor = editable && editable.ckeditorInstance;
        if (editor) {
          editor.model.change((writer) => {
            const sel = editor.model.document.selection.getSelectedElement();
            if (sel && sel.is("element", "drupalMedia")) {
              writer.setAttribute("viewMode", current, sel);
            }
          });
          editor.updateSourceElement();
        }

        // —> 4) et on remet à jour la vue CKEditor
        if (editor) {
          editor.editing.view.change((viewWriter) => {
            const sel = editor.model.document.selection.getSelectedElement();
            const viewEl = sel && editor.editing.mapper.toViewElement(sel);
            if (viewEl) {
              viewWriter.setAttribute(
                "data-view-mode",
                fig.getAttribute("data-view-mode"),
                viewEl
              );
            }
          });
        }

        // utilitaire pour passer de la machine-name au label en fallback
        function labelize(machine) {
          // Si c’est falsy ou pas une string, on considère que c’est "default"
          if (machine !== "default" && typeof machine !== "string") {
            return "Par défaut";
          }
          return machine === "default"
            ? "Par défaut"
            : machine.replace(/_/g, " ");
        }
      }
    },
  };
})(jQuery, Drupal);
