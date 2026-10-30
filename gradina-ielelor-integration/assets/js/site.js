(function () {
  'use strict';

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
      callback();
    }
  }

  ready(function () {
    initMenu();
    initEventFilters();
    initGalleries();
    initBrwPreselection();
  });

  function initMenu() {
    var toggle = document.querySelector('[data-menu-toggle]');
    var menu = document.getElementById('gi-mobile-navigation');
    if (!toggle || !menu) return;

    var openIcon = toggle.querySelector('[data-menu-open]');
    var closeIcon = toggle.querySelector('[data-menu-close]');

    function setOpen(open) {
      menu.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Închide meniul' : 'Deschide meniul');
      if (openIcon) openIcon.hidden = open;
      if (closeIcon) closeIcon.hidden = !open;
    }

    toggle.addEventListener('click', function () {
      setOpen(menu.hidden);
    });

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () { setOpen(false); });
    });
  }

  function initEventFilters() {
    var filters = document.querySelectorAll('[data-event-filter]');
    var events = document.querySelectorAll('[data-event-category]');
    if (!filters.length || !events.length) return;

    filters.forEach(function (button) {
      button.addEventListener('click', function () {
        var selected = button.getAttribute('data-event-filter');
        filters.forEach(function (item) {
          var active = item === button;
          item.classList.toggle('active', active);
          item.setAttribute('aria-pressed', String(active));
        });
        events.forEach(function (event) {
          event.hidden = selected !== 'toate' && event.getAttribute('data-event-category') !== selected;
        });
      });
    });
  }

  function initGalleries() {
    var lightbox = document.getElementById('gi-lightbox');
    if (!lightbox) return;

    var image = lightbox.querySelector('[data-lightbox-image]');
    var caption = lightbox.querySelector('[data-lightbox-caption]');
    var count = lightbox.querySelector('[data-lightbox-count]');
    var closeButton = lightbox.querySelector('[data-lightbox-close]');
    var previousButton = lightbox.querySelector('[data-lightbox-previous]');
    var nextButton = lightbox.querySelector('[data-lightbox-next]');
    var images = [];
    var activeIndex = 0;
    var trigger = null;

    function render() {
      var current = images[activeIndex];
      if (!current) return;
      image.src = current.src;
      image.alt = current.alt;
      caption.textContent = current.getAttribute('data-caption') || current.alt;
      count.textContent = (activeIndex + 1) + ' / ' + images.length;
    }

    function open(button) {
      var gallery = button.closest('.home-photo-gallery');
      if (!gallery) return;
      images = Array.from(gallery.querySelectorAll('img'));
      activeIndex = Number(button.getAttribute('data-gallery-index')) || 0;
      trigger = button;
      render();
      lightbox.hidden = false;
      document.body.classList.add('gi-lightbox-open');
      closeButton.focus();
    }

    function close() {
      if (lightbox.hidden) return;
      lightbox.hidden = true;
      document.body.classList.remove('gi-lightbox-open');
      image.src = '';
      if (trigger) trigger.focus();
    }

    function move(direction) {
      if (!images.length) return;
      activeIndex = (activeIndex + direction + images.length) % images.length;
      render();
    }

    document.querySelectorAll('.gi-gallery-trigger').forEach(function (button) {
      button.addEventListener('click', function () { open(button); });
    });
    closeButton.addEventListener('click', close);
    previousButton.addEventListener('click', function () { move(-1); });
    nextButton.addEventListener('click', function () { move(1); });
    lightbox.addEventListener('mousedown', function (event) {
      if (event.target === lightbox) close();
    });

    document.addEventListener('keydown', function (event) {
      if (lightbox.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        move(-1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        move(1);
      } else if (event.key === 'Tab') {
        var controls = [closeButton, previousButton, nextButton];
        var first = controls[0];
        var last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }

  // Îmbunătățire progresivă: folosește numai o categorie existentă în selectorul real BRW.
  // Nu interceptează submit și nu modifică disponibilitatea, prețul sau fluxul WooCommerce.
  function initBrwPreselection() {
    document.querySelectorAll('[data-gi-preselect-category]').forEach(function (region) {
      var category = region.getAttribute('data-gi-preselect-category');
      var select = region.querySelector('select[name="cat"]');
      if (!category || !select) return;

      var exists = Array.from(select.options).some(function (option) {
        return option.value === category;
      });
      if (!exists) return;

      select.value = category;
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }
}());
