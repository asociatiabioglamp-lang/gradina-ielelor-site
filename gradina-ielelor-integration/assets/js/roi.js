/* Regulamentul: doar de consultat (fără copiere) și bifa de derulare la finalizarea rezervării. */
(function () {
  'use strict';

  // 1. Doar consultare: în zonele marcate cu [data-gi-protejat] nu se copiază, nu se taie, nu se trage și nu se deschide meniul clic-dreapta.
  function inProtected(node) {
    if (node && node.nodeType === 3) { node = node.parentNode; }
    return !!(node && node.closest && node.closest('[data-gi-protejat]'));
  }
  function selectionInProtected() {
    var selection = window.getSelection && window.getSelection();
    return !!(selection && selection.rangeCount && (inProtected(selection.anchorNode) || inProtected(selection.focusNode)));
  }
  ['copy', 'cut'].forEach(function (name) {
    document.addEventListener(name, function (event) {
      if (inProtected(event.target) || selectionInProtected()) { event.preventDefault(); }
    }, true);
  });
  ['dragstart', 'selectstart', 'contextmenu'].forEach(function (name) {
    document.addEventListener(name, function (event) {
      if (inProtected(event.target)) { event.preventDefault(); }
    }, true);
  });

  // 2. Bifa e activă numai după ce regulamentul a fost derulat până la capăt (sau când încape întreg pe ecran, vizibil).
  var STORE = 'gi_roi_derulat';
  function remembered() { try { return window.sessionStorage.getItem(STORE) === '1'; } catch (e) { return false; } }
  function remember() { try { window.sessionStorage.setItem(STORE, '1'); } catch (e) { /* fără memorie în sesiune */ } }

  function setup(root) {
    var box = root.querySelector('[data-gi-roi-box]');
    var checkbox = root.querySelector('input[name="gi_roi"]');
    var seen = root.querySelector('input[name="gi_roi_seen"]');
    var hint = root.querySelector('[data-gi-roi-hint]');
    if (!checkbox || !seen || !box || root.getAttribute('data-gi-ready') === '1') { return; }
    root.setAttribute('data-gi-ready', '1');

    function unlock(announce) {
      seen.value = '1';
      checkbox.removeAttribute('aria-disabled');
      root.classList.add('gi-roi-citit');
      remember();
      if (hint && announce) { hint.textContent = 'Ai ajuns la sfârșitul regulamentului. Poți bifa.'; }
    }
    // După reîmprospătarea plății, ce a fost citit rămâne citit (serverul păstrează starea; aici, o plasă de siguranță).
    if (seen.value === '1' || remembered()) { unlock(false); return; }

    checkbox.setAttribute('aria-disabled', 'true');
    checkbox.addEventListener('click', function (event) {
      if (checkbox.getAttribute('aria-disabled') === 'true') {
        event.preventDefault();
        if (hint) { hint.textContent = 'Mai ai de citit: derulează regulamentul până jos (cu tastatura: apasă pe casetă, apoi tasta End).'; }
      }
    });

    function check() {
      if (box.clientHeight === 0) { return; } // caseta nu se vede încă (ascunsă de temă sau de un pas al plății)
      var fits = box.scrollHeight <= box.clientHeight + 2;
      var atEnd = box.scrollHeight - box.scrollTop - box.clientHeight < 12;
      if (fits || atEnd) { unlock(true); box.removeEventListener('scroll', check); window.removeEventListener('resize', check); }
    }
    box.addEventListener('scroll', check, { passive: true });
    window.addEventListener('resize', check);
    if (window.ResizeObserver) { new window.ResizeObserver(check).observe(box); }
    check();
  }

  function init() {
    var roots = document.querySelectorAll('[data-gi-roi]');
    for (var i = 0; i < roots.length; i++) { setup(roots[i]); }
  }

  init();
  // Pagina de plată se reîmprospătează singură (WooCommerce); caseta se reface de fiecare dată.
  if (window.jQuery) { window.jQuery(document.body).on('updated_checkout', init); }
})();
