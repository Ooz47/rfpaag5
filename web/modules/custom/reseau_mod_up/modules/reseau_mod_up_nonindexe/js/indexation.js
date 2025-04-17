(function ($, Drupal, window, document) {

  'use strict';

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.indexation = {
    attach: function (context, settings) {
      // console.log("hello");

      var inputsSitemap = $('input[name="simple_sitemap[default][index]"]');
      var inputMenu = $('input[name="menu[enabled]"]');
      var detailsMenu = $('details[id="edit-menu"]');

      $(once('form-item-field-contenu-indexation', '.field--name-field-contenu-indexation', context)).each(function () {

        var inputName = $(this).find("input").first().attr("name");
        var value = $('input[name="' + inputName + '"]:checked').val();
        console.log(value);

        // Au chargement de l'entité, on vérifie si un choix est sélectionné
        if (value === undefined) {
          // Si undefined, on coche le deuxième : "oui"
          $('input[name="' + inputName + '"]')[1].checked = true;
        }

        if (value == false) {
          // Si "non" est sélectionné, on cache l'option du menu
          detailsMenu.hide(1);
        }

        $('input[name="' + inputName + '"]').change(function () {
          var value = $('input[name="' + inputName + '"]:checked').val();

          // Indexation autorisée
          if (value == 1) {
            inputsSitemap[1].checked = true;
            detailsMenu.show(100);
          } else {
            // Indexation non autorisée
            inputMenu[0].checked = false;
            detailsMenu.hide(100);
            inputsSitemap[0].checked = true;
          }
        });

      });
    }
  };

})(jQuery, Drupal, this, this.document);
