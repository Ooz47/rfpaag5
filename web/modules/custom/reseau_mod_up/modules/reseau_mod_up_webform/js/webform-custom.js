(function ($, Drupal, window, document) {

  'use strict';

  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.liens = {
    attach: function (context, settings) {
      $(once('liens-sharing', '.print', context)).each(function () {
        var url = drupalSettings.node.front + "entity_pdf/node/" + drupalSettings.node.id + "/export_pdf";
        $(this).attr("onclick", "window.open(\"" + url + "\");");
        // console.log(this);
      });
    }
  };

})(jQuery, Drupal, this, this.document);