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
        // console.log(firstpan);
      });

    }
  };

})(jQuery, Drupal, this, this.document);