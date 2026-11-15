/*
 * Harta Grădinii (pagina publică): apeși pe un punct de pe plan și vezi numele locului și butonul spre pagina lui.
 * Fără JavaScript, lista de sub plan duce la aceleași pagini.
 */
(function () {
  'use strict';
  var root = document.querySelector('[data-gi-harta]');
  if (!root) return;
  var card = root.querySelector('[data-gi-harta-fisa]');
  if (!card) return;
  var nameEl = card.querySelector('[data-gi-harta-nume]');
  var typeEl = card.querySelector('[data-gi-harta-tip]');
  var linkEl = card.querySelector('[data-gi-harta-link]');
  var pins = Array.prototype.slice.call(root.querySelectorAll('.gi-pin-buton'));

  function select(btn) {
    pins.forEach(function (b) { b.parentNode.classList.toggle('gi-pin-ales', b === btn); b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
    nameEl.textContent = btn.getAttribute('data-nume') || '';
    typeEl.textContent = btn.getAttribute('data-tip') || '';
    linkEl.setAttribute('href', btn.getAttribute('data-url') || '#');
    card.hidden = false;
  }
  pins.forEach(function (btn) { btn.addEventListener('click', function () { select(btn); }); });
  // dacă omul vine dintr-un cod („Ești aici”), locul lui e deja ales
  var here = root.querySelector('.gi-pin-curent .gi-pin-buton');
  if (here) select(here);
}());
