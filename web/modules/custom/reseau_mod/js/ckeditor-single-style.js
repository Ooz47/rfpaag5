(function (Drupal) {
  Drupal.behaviors.ckeditor5SingleStyle = {
    attach: function (context, settings) {
      // Liste des classes de style avec leur classe de base correspondante
      const styleConfig = {
        // Styles de bloc
        information: "blocwysiwyg",
        question: "blocwysiwyg",
        interdiction: "blocwysiwyg",
        croche: "blocwysiwyg",
        caution: "blocwysiwyg",
        // Styles de texte
        email: "divwysiwyg",
        telephone: "divwysiwyg",
      };

      const styleClasses = Object.keys(styleConfig);

      once("ckeditor5-single-style", ".ck-editor__editable", context).forEach(
        function (element) {
          //console.log("Élément CKEditor trouvé:", element);

          const editor = element.ckeditorInstance;
          if (editor) {
           // console.log("Instance éditeur trouvée");

            // Fonction pour mettre à jour les boutons de style
            function updateStyleButtons(activeStyle) {
              const styleButtons = document.querySelectorAll(
                ".ck-style-grid__button"
              );
              styleButtons.forEach((button) => {
                const preview = button.querySelector(
                  ".ck-style-grid__button__preview p"
                );
                if (
                  preview &&
                  styleClasses.some((cls) => preview.classList.contains(cls))
                ) {
                  button.classList.toggle(
                    "ck-on",
                    preview.classList.contains(activeStyle)
                  );
                  button.classList.toggle(
                    "ck-off",
                    !preview.classList.contains(activeStyle)
                  );
                }
              });
            }

            editor.model.document.on("change:data", (evt, data) => {
              const selection = editor.model.document.selection;
              const position = selection.getFirstPosition();

              if (position) {
                const block = position.parent;
                const htmlAttrs = block.getAttribute("htmlPAttributes");

                if (htmlAttrs && htmlAttrs.classes) {
                  //console.log("Classes actuelles:", htmlAttrs.classes);

                  // Vérifie si plusieurs styles sont appliqués
                  const appliedStyles = htmlAttrs.classes.filter((cls) =>
                    styleClasses.includes(cls)
                  );

                  if (appliedStyles.length > 1) {
                    //console.log("Styles multiples détectés:", appliedStyles);

                    // Garde uniquement le dernier style et sa classe de base
                    const lastStyle = appliedStyles[appliedStyles.length - 1];
                    const baseClass = styleConfig[lastStyle];
                    const newClasses = htmlAttrs.classes.filter(
                      (cls) => cls === baseClass || cls === lastStyle
                    );

                    //console.log("Nouvelles classes:", newClasses);

                    editor.model.change((writer) => {
                      writer.setAttribute(
                        "htmlPAttributes",
                        {
                          ...htmlAttrs,
                          classes: newClasses,
                        },
                        block
                      );

                      // Mise à jour des boutons après le changement
                      updateStyleButtons(lastStyle);

                      // Mise à jour du texte du bouton déroulant
                      const dropdownButton = document.querySelector(
                        ".ck-style-dropdown .ck-dropdown__button .ck-button__label"
                      );
                      if (dropdownButton) {
                        const styleNames = {
                          information: "Information",
                          question: "Question",
                          interdiction: "Interdiction",
                          croche: "Accroche",
                          caution: "Avertissement",
                          email: "Email",
                          telephone: "Téléphone",
                        };
                        dropdownButton.textContent =
                          styleNames[lastStyle] || lastStyle;
                      }
                    });
                  } else if (appliedStyles.length === 1) {
                    // S'il n'y a qu'un style, mettre à jour les boutons
                    updateStyleButtons(appliedStyles[0]);
                  }
                }
              }
            });
          }
        }
      );
    },
  };
})(Drupal);
