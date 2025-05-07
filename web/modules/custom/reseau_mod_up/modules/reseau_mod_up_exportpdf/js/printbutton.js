(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.liens = {
    attach: function (context, settings) {

      $(once('liens-sharing', '.print', context)).each(function () {
        var url = drupalSettings.node.front + "entity_pdf/node/" + drupalSettings.node.id + "/export_pdf";
        $(this).attr("onclick", "window.open(\"" + url + "\");");
        // console.log(this);
      });

    }
  };

})(jQuery, Drupal, once);
