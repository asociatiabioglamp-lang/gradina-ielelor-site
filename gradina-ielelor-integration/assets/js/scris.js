/*
 * Zona echipei: butoane peste caseta de text (Titlu, Subtitlu, Listă, Îngroșat, Link) care scriu semnele simple din GI_Text (#, ##, -, **, [text](adresa)).
 */
(function () {
  'use strict';
  var area = document.querySelector('textarea[data-gi-scris]');
  if (!area) return;
  var bar = document.createElement('div');
  bar.className = 'gi-scris-bara';
  bar.setAttribute('role', 'toolbar');
  bar.setAttribute('aria-label', 'Formatare text');
  area.parentNode.insertBefore(bar, area);

  function lineStart() {
    var v = area.value, s = area.selectionStart;
    return v.lastIndexOf('\n', s - 1) + 1;
  }
  function prefixLine(prefix) {
    var v = area.value, ls = lineStart();
    area.value = v.slice(0, ls) + prefix + v.slice(ls);
    var pos = area.selectionStart + prefix.length;
    area.focus(); area.setSelectionRange(pos, pos);
  }
  function wrap(left, right, placeholder) {
    var v = area.value, s = area.selectionStart, e = area.selectionEnd;
    var mid = s === e ? placeholder : v.slice(s, e);
    area.value = v.slice(0, s) + left + mid + right + v.slice(e);
    area.focus(); area.setSelectionRange(s + left.length, s + left.length + mid.length);
  }
  var buttons = [
    ['Titlu', function () { prefixLine('# '); }],
    ['Subtitlu', function () { prefixLine('## '); }],
    ['Listă', function () { prefixLine('- '); }],
    ['Îngroșat', function () { wrap('**', '**', 'text'); }],
    ['Link', function () {
      var url = window.prompt('Adresa linkului (ex. https://exemplu.ro sau tel:0721333164):', 'https://');
      if (url) wrap('[', '](' + url + ')', 'text link');
    }]
  ];
  buttons.forEach(function (b) {
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'button button-outline'; btn.textContent = b[0];
    btn.addEventListener('click', b[1]);
    bar.appendChild(btn);
  });
}());
