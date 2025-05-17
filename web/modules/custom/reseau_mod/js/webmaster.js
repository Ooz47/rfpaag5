(function ($, Drupal, window, document) {

  'use strict';

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.admin = {
    attach: function (context, settings) {

      $(once('some-arbitrary-but-unique-keysss', '.toolbar-icon-entity-user-collection', context)).each(function () {
        var firstpan = $(this).siblings(".toolbar-menu").find("li:nth-child(2)");
        firstpan.css('display', 'none');
        var span2 = $(this).siblings(".toolbar-menu").find("li:nth-child(3)");
        span2.css('display', 'none');
        console.log(firstpan);
      });

      $(once('ModificationMenuHelp', '.toolbar-icon-admin-toolbar-tools-help', context)).each(function () {
        var firstpan = $(this).siblings(".toolbar-menu").find("li:nth-child(1)");
        firstpan.css('display', 'none');
        var span2 = $(this).siblings(".toolbar-menu").find("li:nth-child(2)");
        span2.css('display', 'none');
        console.log(firstpan);
      });

      // Cache les liens "Manage permissions" pour tous les vocabulaires de taxonomie
        $(once('ModificationMenuTaxonomyPermissions', '.toolbar-icon-entity-taxonomy-vocabulary-collection', context)).each(function () {
            // Sélectionne tous les liens "Manage permissions" dans le menu de taxonomie
            $(this).siblings(".toolbar-menu")
                .find("a[href*='/permissions']")
                .closest('li')
                .css('display', 'none');
        });

    }
  };

})(jQuery, Drupal, this, this.document);