/*
 * Pagina produsului (formularul de rezervare BRW): dacă clientul apasă „Adaugă în coș” fără să aleagă un câmp obligatoriu
 * (numărul de adulți la Cort și Rulotă) sau fără să bifeze acordul cu termenii, formularul nu face nimic și nu spune nimic.
 * Aici apar mesaje vizibile.
 *
 * Regulă de siguranță: scriptul NU oprește trimiterea formularului și nu schimbă nimic din BRW. Doar arată mesaje, și numai
 * pentru câmpuri marcate de BRW ca obligatorii (clasa „ovabrw-input-required”), vizibile și necompletate.
 */
(function () {
  'use strict';

  var pickup = document.querySelector('input[name="ovabrw_pickup_date"]');
  var form = pickup && pickup.closest ? pickup.closest('form') : null;
  if (!form) {
    return;
  }

  function visible(el) {
    return !el.disabled && el.offsetParent !== null;
  }

  // Lista de probleme: { el: câmpul, text: mesajul, anchor: locul după care apare mesajul }
  function problems() {
    var found = [];
    var selects = form.querySelectorAll('select.ovabrw-input-required');
    for (var i = 0; i < selects.length; i++) {
      var s = selects[i];
      if (visible(s) && s.value === '') {
        var first = s.options && s.options[0] ? s.options[0].text : '';
        found.push({
          el: s,
          text: /adul/i.test(first) ? 'Alege numărul de adulți, ca să putem calcula rezervarea.' : 'Alege o opțiune ca să poți continua.',
          anchor: s.closest ? (s.closest('.ovabrw-select') || s) : s
        });
      }
    }
    var boxes = form.querySelectorAll('input[type="checkbox"].ovabrw-input-required');
    for (var j = 0; j < boxes.length; j++) {
      var c = boxes[j];
      if (visible(c) && !c.checked) {
        found.push({
          el: c,
          text: 'Bifează că ești de acord cu termenii și regulamentul, ca să poți continua.',
          anchor: c.closest ? (c.closest('label') || c.parentNode || c) : c
        });
      }
    }
    return found;
  }

  function clearMessages() {
    var old = form.querySelectorAll('.gi-camp-msg');
    for (var i = 0; i < old.length; i++) {
      old[i].parentNode.removeChild(old[i]);
    }
  }

  function show(list) {
    clearMessages();
    var firstBox = null;
    for (var i = 0; i < list.length; i++) {
      var box = document.createElement('div');
      box.className = 'gi-camp-msg';
      box.setAttribute('role', 'alert');
      box.textContent = list[i].text;
      box.style.cssText = 'margin:8px 0 0;padding:10px 14px;border-radius:8px;background:#fdecea;color:#8a1c14;font-weight:600;font-size:15px;line-height:1.4;';
      if (list[i].anchor && list[i].anchor.parentNode) {
        list[i].anchor.parentNode.insertBefore(box, list[i].anchor.nextSibling);
        firstBox = firstBox || box;
      }
    }
    if (firstBox && typeof firstBox.scrollIntoView === 'function') {
      try { firstBox.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (e) { firstBox.scrollIntoView(); }
    }
  }

  form.addEventListener('click', function (event) {
    var target = event.target;
    var button = target && target.closest ? target.closest('button[type="submit"], input[type="submit"]') : null;
    if (!button) {
      return;
    }
    var list = problems();
    if (list.length) {
      show(list);
    } else {
      clearMessages();
    }
  }, true);

  form.addEventListener('change', function (event) {
    var t = event.target;
    if (t && (t.tagName === 'SELECT' || (t.tagName === 'INPUT' && t.type === 'checkbox'))) {
      clearMessages();
    }
  });
})();
