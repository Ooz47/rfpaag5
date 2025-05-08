(function ($, Drupal) {
    'use strict';
  
    Drupal.behaviors.customDropbuttonFix = {
      attach: function (context) {
        once('force-redirect-on-dropbutton', context.querySelectorAll('.dropbutton__item:not(.secondary-action) a'))
          .forEach(function (el) {
            el.addEventListener('mousedown', function (e) {
              if (e.button !== 0) {
                // Ignorer clic du milieu ou droit
                return;
              }
              e.preventDefault(); // Empêche le focus auto et scroll vers le haut
              const href = el.getAttribute('href');
              if (href) {
                window.location.href = href;
              }
            });
          });
      }
    };
  
  })(jQuery, Drupal);
  