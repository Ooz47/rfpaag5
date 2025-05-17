(function ($, Drupal) {
  Drupal.behaviors.mediaViewModeFilter = {
    attach(context) {
      // Modes autorisés par type
      const allowedByType = {
        image:        ['Par défaut','colorbox','colorbox petit','colorbox pleine largeur'],
        galerie:      ['Par défaut',"Mur d'images"],
        document:     [],  // on ne garde aucun item (donc on cache)
        audio:        [],
        video:        [],
        remote_video: [],
      };

      // Détecte le type de média d'une figure
      function getMediaType(fig) {
        if ( fig.querySelector('.blazy--field-media-slideshow')
          || fig.querySelector('.slick-wrapper') ) {
          return 'galerie';
        }
        if ( fig.querySelector('.file') )       return 'document';
        if ( fig.querySelector('audio') )       return 'audio';
        if ( fig.querySelector('video') )       return 'video';
        if ( fig.querySelector('.remotevideo') )return 'remote_video';
        return 'image';
      }

      // Applique le filtre sur la balloon CKEditor
      function filterViewMode(fig) {
        const type = getMediaType(fig);
        const allowed = allowedByType[type];
        const balloon = document.querySelector('.ck-balloon-panel');
        if (!balloon) return;

        // Trouve le dropdown button
        const dropdownBtn = balloon.querySelector('.ck-dropdown button.ck-dropdown__button');
        if (!dropdownBtn) return;

        // Cache ou affiche le bouton selon le type
        if (type === 'image' || type === 'galerie') {
          dropdownBtn.style.display = '';
            if (type === 'galerie') {
            const label = dropdownBtn.querySelector('.ck-button__label');
            if (label && label.textContent === 'colorbox') {
              label.textContent = 'Par défaut';
            }
          }
        } else {
          dropdownBtn.style.display = 'none';
          return;
        }

        // Observer pour détecter l'ouverture du panel
        const observer = new MutationObserver((mutations) => {
          const panel = balloon.querySelector('.ck-dropdown__panel');
          if (!panel) return;

          const listItems = panel.querySelectorAll('.ck-list__item');
          if (listItems.length > 0) {
            // Filtre les options selon le type
            listItems.forEach(li => {
              const label = li.textContent.trim();
              li.style.display = allowed.includes(label) ? '' : 'none';
            });
          }
        });

        // Observe les changements dans tout le balloon
        observer.observe(balloon, {
          childList: true,
          subtree: true
        });
      }

      // Écoute les clics sur les médias
      once('media-click-listener', 'body', context).forEach(() => {
        document.addEventListener('click', e => {
          const fig = e.target.closest('figure.drupal-media.ck-widget');
          if (fig && fig.classList.contains('ck-widget_selected')) {
            filterViewMode(fig);
          }
        });
      });
    }
  };
})(jQuery, Drupal);
