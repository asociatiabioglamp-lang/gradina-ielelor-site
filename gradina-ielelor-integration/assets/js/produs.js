/*
 * Pagina produsului (formularul de rezervare BRW): dacă clientul apasă „Adaugă în coș” fără să aleagă un câmp obligatoriu
 * (numărul de adulți la Cort și Rulotă) sau fără să bifeze acordul cu termenii, formularul nu face nimic și nu spune nimic.
 * Aici apar mesaje vizibile.
 *
 * Regulă de siguranță: scriptul NU oprește trimiterea formularului și nu schimbă nimic din BRW. Doar arată mesaje, și numai
 * pentru câmpuri marcate de BRW ca obligatorii (clasa „ovabrw-input-required”), vizibile și necompletate.
 *
 * Ascultătorii sunt pe întreaga pagină (nu pe formular), ca să meargă și dacă BRW reconstruiește formularul sau sunt mai multe formulare.
 */
(function () {
  'use strict';

  function bookingForm(node) {
    var form = node && node.closest ? node.closest('form') : null;
    return form && form.querySelector('input[name="ovabrw_pickup_date"]') ? form : null;
  }

  function visible(el) {
    return !el.disabled && el.offsetParent !== null;
  }

  // Lista de probleme: { text: mesajul, anchor: locul după care apare mesajul }
  function problems(form) {
    var found = [];
    var selects = form.querySelectorAll('select.ovabrw-input-required');
    for (var i = 0; i < selects.length; i++) {
      var s = selects[i];
      if (visible(s) && s.value === '') {
        var first = s.options && s.options[0] ? s.options[0].text : '';
        found.push({
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
          text: 'Bifează că ești de acord cu termenii și regulamentul, ca să poți continua.',
          anchor: c.closest ? (c.closest('label') || c.parentNode || c) : c
        });
      }
    }
    return found;
  }

  function clearMessages(form) {
    var old = form.querySelectorAll('.gi-camp-msg');
    for (var i = 0; i < old.length; i++) {
      old[i].parentNode.removeChild(old[i]);
    }
  }

  function show(form, list) {
    clearMessages(form);
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

  document.addEventListener('click', function (event) {
    var target = event.target;
    var button = target && target.closest ? target.closest('button[type="submit"], input[type="submit"]') : null;
    var form = button ? bookingForm(button) : null;
    if (!form) {
      return;
    }
    var list = problems(form);
    if (list.length) {
      show(form, list);
    } else {
      clearMessages(form);
    }
  }, true);

  document.addEventListener('change', function (event) {
    var t = event.target;
    var form = t ? bookingForm(t) : null;
    if (form && (t.tagName === 'SELECT' || (t.tagName === 'INPUT' && t.type === 'checkbox'))) {
      clearMessages(form);
    }
  });
})();
