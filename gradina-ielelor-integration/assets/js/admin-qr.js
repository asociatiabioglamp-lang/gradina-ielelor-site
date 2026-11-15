/*
 * Zona privată „Grădina” (administrare): desenează codurile QR în browser, cu biblioteca din assets/js/vendor/qrcode.js (licență MIT).
 * Nu se trimite nimic către un serviciu extern. Nivelul de corecție „Q” (25%): codul rămâne citibil și dacă se murdărește
 * sau se udă, cât stă în teren.
 *
 * Folosit de: căsuța „Cod QR” din pagina unui loc, lista locurilor, ecranul „Coduri QR de printat”, pagina de printare, vouchere.
 *   [data-qr-url]                   → se umple cu SVG-ul codului pentru adresa din atribut
 *   [data-qr-download]              → descarcă SVG-ul din elementul indicat de data-qr-source, cu numele din data-filename
 *   [data-qr-select-all]            → bifează / debifează toate căsuțele din tabelul în care se află
 *   [data-qr-select-all-list]       → la fel, pentru listele din Zona echipei (căsuțele „ids[]” din același formular)
 *   [data-gi-confirma]              → cere o confirmare înainte de a trimite formularul (ex. „Scoți accesul acestui cont?”)
 *   [data-gi-genereaza="#câmp"]      → pune în câmpul indicat o parolă aleatoare de 16 caractere (butonul „Generează” din Echipă)
 *   form[data-gi-o-data]            → după trimitere, butoanele de trimitere se opresc (un dublu clic nu trimite formularul de două ori)
 */
(function () {
  'use strict';

  function makeSvg(text) {
    var qr = window.qrcode(0, 'Q');
    qr.addData(text);
    qr.make();
    return qr.createSvgTag({ cellSize: 4, margin: 16, scalable: true });
  }

  function draw(root) {
    var nodes = (root || document).querySelectorAll('[data-qr-url]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (el.getAttribute('data-qr-done')) continue;
      // Marcat ca „încercat” înainte: un cod care nu se poate genera nu se reîncearcă la nesfârșit.
      el.setAttribute('data-qr-done', '1');
      try {
        el.innerHTML = makeSvg(el.getAttribute('data-qr-url'));
      } catch (e) {
        el.textContent = 'Codul nu se poate genera.';
      }
    }
  }

  function download(button) {
    var source = document.querySelector(button.getAttribute('data-qr-source') || '');
    var svg = source && source.querySelector('svg');
    if (!svg) return;
    var markup = new XMLSerializer().serializeToString(svg);
    // Mărime explicită, ca fișierul să se deschidă la o dimensiune utilă în orice program.
    markup = markup.replace('<svg ', '<svg width="800" height="800" ');
    var blob = new Blob([markup], { type: 'image/svg+xml' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = button.getAttribute('data-filename') || 'cod-qr.svg';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
  }

  function bindActions() {
    document.addEventListener('click', function (event) {
      var button = event.target && event.target.closest ? event.target.closest('[data-qr-download]') : null;
      if (button) {
        event.preventDefault();
        download(button);
      }
    });

    document.addEventListener('change', function (event) {
      var box = event.target;
      if (!box || !box.hasAttribute) return;
      var scope = null;
      var selector = '';
      if (box.hasAttribute('data-qr-select-all')) {
        scope = box.closest('table');
        selector = 'tbody input[type="checkbox"]';
      } else if (box.hasAttribute('data-qr-select-all-list')) {
        scope = box.closest('form');
        selector = 'input[type="checkbox"][name="ids[]"]';
      }
      if (!scope) return;
      var boxes = scope.querySelectorAll(selector);
      for (var i = 0; i < boxes.length; i++) boxes[i].checked = box.checked;
    });

    // Parolă aleatoare, din caractere ușor de citit și de dictat (fără 0/O, 1/l/I).
    document.addEventListener('click', function (event) {
      var button = event.target && event.target.closest ? event.target.closest('[data-gi-genereaza]') : null;
      if (!button) return;
      var field = document.querySelector(button.getAttribute('data-gi-genereaza'));
      var crypto = window.crypto || window.msCrypto;
      if (!field || !crypto || !crypto.getRandomValues) return;
      var chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      var values = new Uint32Array(16);
      crypto.getRandomValues(values);
      var out = '';
      for (var i = 0; i < values.length; i++) out += chars.charAt(values[i] % chars.length);
      field.value = out;
      field.focus();
    });

    // O singură trimitere: un dublu clic ar crea două conturi, două loturi de vouchere sau ar schimba parola de două ori.
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || !form.hasAttribute || !form.hasAttribute('data-gi-o-data')) return;
      setTimeout(function () {
        var buttons = form.querySelectorAll('button[type="submit"]');
        for (var i = 0; i < buttons.length; i++) buttons[i].disabled = true;
      }, 0);
    });
    window.addEventListener('pageshow', function (event) {
      if (!event.persisted) return;
      var buttons = document.querySelectorAll('form[data-gi-o-data] button[type="submit"]');
      for (var i = 0; i < buttons.length; i++) buttons[i].disabled = false;
    });

    // Acțiuni care cer o confirmare (în Zona echipei): fără confirmare, formularul nu se trimite.
    // Legat înaintea verificării bibliotecii de coduri: confirmarea nu depinde de ea.
    document.addEventListener('click', function (event) {
      var target = event.target && event.target.closest ? event.target.closest('[data-gi-confirma]') : null;
      if (target && !window.confirm(target.getAttribute('data-gi-confirma'))) {
        event.preventDefault();
      }
    });
  }

  function init() {
    bindActions();
    if (typeof window.qrcode !== 'function') return;
    // Adresele sunt ASCII; totuși, codificarea UTF-8 evită orice caracter tăiat dacă apare vreodată unul în afara ASCII.
    if (window.qrcode.stringToBytesFuncs && window.qrcode.stringToBytesFuncs['UTF-8']) {
      window.qrcode.stringToBytes = window.qrcode.stringToBytesFuncs['UTF-8'];
    }
    draw(document);

    // Căsuțele pot apărea după încărcare (ecrane care își mută conținutul): desenăm și ce se adaugă ulterior.
    if (window.MutationObserver && document.body) {
      var pending = null;
      new MutationObserver(function () {
        if (pending) return;
        pending = setTimeout(function () { pending = null; draw(document); }, 120);
      }).observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
