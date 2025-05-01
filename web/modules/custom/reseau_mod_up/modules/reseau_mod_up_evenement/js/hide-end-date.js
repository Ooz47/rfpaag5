(function ($, Drupal) {
  Drupal.behaviors.hideSmartDateEnd = {
    attach: function (context, settings) {
      // Fonction pour (ré)attacher le comportement de masquage.
      const attachEndDateLogic = function () {
        const $wrapper = $('#edit-field-date-evenement-0', context);
        const $endWrapper = $wrapper.find('#edit-field-date-evenement-0-end-value').closest('.form-item.form-datetime-wrapper');
        const $duration = $wrapper.find('.field-duration');
        const $allDay = $wrapper.find('.allday');

        console.log('wrapper', $wrapper.length);
        console.log('endWrapper', $endWrapper.length);
        console.log('duration', $duration.length);
        console.log('allDay', $allDay.length);

        if (!$endWrapper.length || !$duration.length || !$allDay.length) {
          return;
        }

        function toggleEndDate() {
          const isAllDay = $allDay.is(':checked');
          const isNoEndTime = $duration.val() === '0';

          if (isAllDay || !isNoEndTime) {
            $endWrapper.slideDown();
          } else {
            $endWrapper.slideUp();
          }
        }

        $allDay.on('change', toggleEndDate);
        $duration.on('change', toggleEndDate);

        // Initialisation
        toggleEndDate();
      };

      // Delay l'attachement si Smart Date n'a pas encore injecté la case à cocher.
      setTimeout(attachEndDateLogic, 300); // 300ms fonctionne dans la plupart des cas
    }
  };
})(jQuery, Drupal);
