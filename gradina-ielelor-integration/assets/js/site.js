(function () {
  'use strict';

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
      callback();
    }
  }

  // La plecarea de pe pagina principală (pageswap); sosirea (pagereveal) e tratată în head, de booking_arrival_script().
  initPhotoTransitions();

  ready(function () {
    initMenu();
    initEventFilters();
    initGalleries();
    initBrwLabels();
    initBrwPreselection();
    initHomeBookingCta();
  });

  /**
   * Pagina principală, pe telefon: butonul fix „Rezervă cazare” apare doar când nici butonul din hero,
   * nici modulul de rezervări, nici footer-ul nu sunt pe ecran, ca să nu acopere formularul.
   * (Paginile dedicate au aceeași logică în pages.js.) Fără IntersectionObserver butonul rămâne mereu vizibil.
   */
  function initHomeBookingCta() {
    var shell = document.querySelector('.home-page');
    var cta = shell && shell.querySelector('.mobile-booking-cta');
    if (!cta || !('IntersectionObserver' in window)) return;

    var watched = Array.prototype.slice.call(shell.querySelectorAll('.home-hero-actions .button, #rezervare, .site-footer'));
    if (!watched.length) return;

    var onScreen = [];
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var index = onScreen.indexOf(entry.target);
        if (entry.isIntersecting && index === -1) onScreen.push(entry.target);
        if (!entry.isIntersecting && index !== -1) onScreen.splice(index, 1);
      });
      cta.classList.toggle('is-visible', onScreen.length === 0);
    });

    cta.classList.add('is-managed');
    watched.forEach(function (element) { observer.observe(element); });
  }

  /**
   * Fotografia unui card zboară spre pagina dedicată (și înapoi) numai dacă acel card e pe ecran.
   * Un card din afara ecranului își pierde numele de tranziție, iar pagina face doar o trecere lină.
   * Aici: pageswap, la plecarea de pe pagina principală. Sosirea (pagereveal) are aceeași regulă în scriptul din head,
   * pentru că acest fișier se încarcă la final, după pagereveal.
   */
  function initPhotoTransitions() {
    function onScreen(element) {
      var rect = element.getBoundingClientRect();
      return rect.bottom > 0 && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;
    }
    function dropOffscreenCards(event) {
      if (!event.viewTransition) return;
      document.querySelectorAll('.gi-offer-card img').forEach(function (image) {
        image.style.viewTransitionName = onScreen(image) ? '' : 'none';
      });
    }
    window.addEventListener('pageswap', dropOffscreenCards);
  }

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

    // Esc închide meniul deschis și duce focusul înapoi pe buton.
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || menu.hidden) return;
      var focusInside = menu.contains(document.activeElement) || document.activeElement === toggle;
      setOpen(false);
      if (focusInside) toggle.focus();
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
    var shell = document.querySelector('.site-shell');
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
      // Restul paginii devine inert cât timp galeria e deschisă (și pentru cititoarele de ecran).
      if (shell) shell.inert = true;
      closeButton.focus();
    }

    function close() {
      if (lightbox.hidden) return;
      lightbox.hidden = true;
      document.body.classList.remove('gi-lightbox-open');
      if (shell) shell.inert = false;
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
      preselectCategory(region, region.getAttribute('data-gi-preselect-category'));
    });

    // Paginile dedicate trimit înapoi la modulul de rezervări cu #rezervare-<tip>.
    // Pagina poate fi deja preîncărcată în fundal, așa că verificăm și la activare și la schimbarea fragmentului.
    applyBookingHash();
    window.addEventListener('hashchange', applyBookingHash);
    document.addEventListener('prerenderingchange', applyBookingHash);
  }

  function applyBookingHash() {
    var match = /^#rezervare-([a-z-]+)$/.exec(window.location.hash);
    var section = document.getElementById('rezervare');
    if (!match || !section) return;

    section.querySelectorAll('[data-gi-categories]').forEach(function (region) {
      var categories = {};
      try {
        categories = JSON.parse(region.getAttribute('data-gi-categories')) || {};
      } catch (error) {
        categories = {};
      }
      preselectCategory(region, categories[match[1]]);
    });
    section.scrollIntoView({ block: 'start' });

    // Preselecția se face o singură dată: la reîncărcare sau la „Înapoi” nu mai suprascrie alegerea vizitatorului.
    // Cât timp pagina e doar preîncărcată, adresa rămâne neschimbată; se curăță la activare (prerenderingchange).
    if (!document.prerendering && window.history && history.replaceState) {
      history.replaceState(history.state, '', window.location.pathname + window.location.search + '#rezervare');
    }
  }

  /**
   * Câmpurile formularului BRW primesc un nume accesibil din eticheta lor vizibilă, dacă pluginul nu l-a legat
   * (ex. <label> fără „for”). Nu schimbă valori, validări sau trimiterea formularului.
   */
  function initBrwLabels() {
    var counter = 0;
    document.querySelectorAll('.gi-real-booking').forEach(function (region) {
      region.querySelectorAll('select, input:not([type="hidden"]):not([type="submit"]), textarea').forEach(function (field) {
        if ((field.labels && field.labels.length) || field.getAttribute('aria-label') || field.getAttribute('aria-labelledby')) return;
        var wrapper = field.closest('.s_field, .ovabrw-search-field, .label-field') || field.parentElement;
        var label = wrapper && wrapper.querySelector('label, .label');
        if (!label || !label.textContent.trim()) return;
        counter += 1;
        if (label.tagName === 'LABEL' && !label.htmlFor) {
          if (!field.id) field.id = 'gi-brw-field-' + counter;
          label.htmlFor = field.id;
        } else {
          if (!label.id) label.id = 'gi-brw-label-' + counter;
          field.setAttribute('aria-labelledby', label.id);
        }
      });
    });
  }

  function preselectCategory(region, category) {
    var select = region.querySelector('select[name="cat"]');
    if (!category || !select) return;

    var exists = Array.from(select.options).some(function (option) {
      return option.value === category;
    });
    if (!exists) return;

    select.value = category;
    select.dispatchEvent(new Event('change', { bubbles: true }));
  }
}());
