/*
 * Pagini dedicate (Glamping etc.): butonul fix de rezervare de pe mobil apare doar când
 * niciun alt buton de rezervare ([data-gi-book]) și nici footer-ul nu sunt pe ecran.
 * Fără IntersectionObserver sau pe alte pagini nu face nimic, iar butonul rămâne vizibil ca până acum.
 */
(function () {
  'use strict';

  function init() {
    var shell = document.querySelector('.gi-dedicated');
    var cta = shell && shell.querySelector('.mobile-booking-cta');
    if (!cta || !('IntersectionObserver' in window)) return;

    var watched = Array.prototype.slice.call(shell.querySelectorAll('[data-gi-book], .site-footer'));
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  /*
   * Tranziția foto: la plecarea spre o pagină nouă (logo, „Acasă”, „Rezervă”), pagina principală se deschide sus,
   * cu cardul ofertei în afara ecranului, așa că fotografia mare nu mai „zboară” spre el; rămâne trecerea lină.
   * La „Înapoi” (traverse) păstrăm zborul: pagina principală revine unde era cardul, iar site.js verifică la sosire.
   */
  /*
   * Regulamentul deschis din pagina de plată se află într-o filă nouă. „Înapoi la finalizarea rezervării” închide fila
   * (revii în fila de plată, cu formularul completat). Dacă fila nu se poate închide, linkul duce la pagina de plată.
   */
  document.querySelectorAll('[data-gi-return-checkout]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      if (window.history.length > 1 && !window.opener) {
        // Pagina s-a deschis în aceeași filă: „Înapoi” readuce plata exact cum era (formular, regulament citit).
        var came = document.referrer ? new URL(document.referrer, window.location.href) : null;
        if (came && came.origin === window.location.origin && came.pathname === new URL(button.href, window.location.href).pathname) {
          event.preventDefault();
          window.history.back();
        }
        return;
      }
      event.preventDefault();
      window.close();
      window.setTimeout(function () { window.location.href = button.getAttribute('href'); }, 300);
    });
  });

  window.addEventListener('pageswap', function (event) {
    if (!event.viewTransition || (event.activation && event.activation.navigationType === 'traverse')) return;
    document.querySelectorAll('.gi-page-hero-media img').forEach(function (hero) { hero.style.viewTransitionName = 'none'; });
  });
}());
