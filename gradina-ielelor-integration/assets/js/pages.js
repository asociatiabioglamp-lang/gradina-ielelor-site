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
  window.addEventListener('pageswap', function (event) {
    if (!event.viewTransition || (event.activation && event.activation.navigationType === 'traverse')) return;
    var hero = document.querySelector('.gi-page-hero-media img');
    if (hero) hero.style.viewTransitionName = 'none';
  });
}());
