(function ($, Drupal, window, document) {

  'use strict';

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.webformcustom = {
    attach: function (context, settings) {
      // Sélection de l'élément "formations"
      var inputFormation = $('select[id="edit-formations"]');

      // Utilisation de once pour éviter les doublons
      $(once('inputFormation', inputFormation, context)).each(function () {
        // Ajout d'un événement sur le changement de "secteurs"
        $(once('secteursChange', 'select[id="edit-secteurs"]', context)).change(function () {
          // Destruction de l'instance chosen sur "formations"
          inputFormation.chosen("destroy");
        });
      });
    }
  };

})(jQuery, Drupal, this, this.document);