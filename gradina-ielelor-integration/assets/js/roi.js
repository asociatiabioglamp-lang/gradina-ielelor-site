/* Regulamentul: doar de consultat (fără copiere) și bifa de citire la finalizarea rezervării. */
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

  // 2. Bifa e activă numai după ce regulamentul a fost derulat până la capăt (sau când încape întreg pe ecran).
  function setup(root) {
    var box = root.querySelector('[data-gi-roi-box]');
    var checkbox = root.querySelector('input[name="gi_roi"]');
    var seen = root.querySelector('input[name="gi_roi_seen"]');
    var hint = root.querySelector('[data-gi-roi-hint]');
    if (!checkbox || !seen || root.getAttribute('data-gi-ready') === '1') { return; }
    root.setAttribute('data-gi-ready', '1');

    function unlock() {
      seen.value = '1';
      checkbox.disabled = false;
      root.classList.add('gi-roi-citit');
      if (hint) { hint.hidden = true; }
    }
    if (seen.value === '1' || !box) { unlock(); return; }

    checkbox.disabled = true;
    function atEnd() {
      return box.scrollHeight <= box.clientHeight + 2 || box.scrollHeight - box.scrollTop - box.clientHeight < 12;
    }
    function check() { if (atEnd()) { unlock(); box.removeEventListener('scroll', check); } }
    box.addEventListener('scroll', check, { passive: true });
    check();
  }

  function init() {
    var roots = document.querySelectorAll('[data-gi-roi]');
    for (var i = 0; i < roots.length; i++) { setup(roots[i]); }
  }

  init();
  // Pagina de plată se reîmprospătează singură (WooCommerce); caseta se refac de fiecare dată.
  if (window.jQuery) { window.jQuery(document.body).on('updated_checkout', init); }
})();
