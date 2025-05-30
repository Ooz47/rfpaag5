/**
 * @file
 * Defines Javascript behaviors for the Content Lock button.
 */

(function ($, Drupal, once) {
  /**
   * Behaviors for tabs in the node edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches automatic submission behavior for content lock buttons.
   */
  Drupal.behaviors.contentLockButton = {
    attach: function attach(context, drupalSettings) {
      if (!drupalSettings.content_lock) {
        return;
      }

      $.each(drupalSettings.content_lock, function (formId, settings) {
        once('content-lock', `form.${formId}`, context).forEach(
          function (elem) {
            new Drupal.content_lock(elem, settings);
          },
        );
      });
    },
  };

  Drupal.content_lock = function (form, settings) {
    const that = this;

    if (settings.hasOwnProperty('lockUrl')) {
      const ajaxCall = Drupal.ajax({
        url: settings.lockUrl,
        element: form,
      });

      ajaxCall.commands.insert = function (...args) {
        if (args[1].selector === '') {
          args[1].selector = `#${form.id}`;
        }
        Drupal.AjaxCommands.prototype.insert.apply(this, args);
      };

      ajaxCall.commands.lockForm = function (ajax, response, status) {
        if (response.lockable && response.lock !== true) {
          that.lock();
        }
      };

      ajaxCall.execute();

      this.lock = function () {
        const $form = $(form);
        $form.prop('disabled', true).addClass('is-disabled');
        $form.find(':input').prop('disabled', true).addClass('is-disabled');
        $form
          .find('.content-lock-actions :input')
          .prop('disabled', true)
          .addClass('is-disabled')
          .attr(
            'title',
            Drupal.t('Action not available because content is locked.'),
          );

        if (Drupal.CKEditor5Instances instanceof Map) {
          Drupal.CKEditor5Instances.forEach(function (instance) {
            instance.enableReadOnlyMode('content_lock');
          });
        }
      };
    }

    if (settings.hasOwnProperty('unlockUrl')) {
      let onBeforeUnLoadEvent = false;

      const unloadHandler = function () {
        if (!onBeforeUnLoadEvent) {
          onBeforeUnLoadEvent = true;
          if (typeof navigator.sendBeacon === 'function') {
            navigator.sendBeacon(settings.unlockUrl);
          }
        }
      };

      window.onunload = unloadHandler;
      window.onbeforeunload = unloadHandler;
    }
  };
})(jQuery, Drupal, once);
