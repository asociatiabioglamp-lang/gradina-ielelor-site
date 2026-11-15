/*
 * Fișa de cazare (Zona echipei → Recepție → Fișă de cazare): citirea zonei de jos a actului (mrz.js), semnături desenate cu degetul, o singură trimitere.
 * Nimic din act nu pleacă din telefon în afară de câmpurile pe care recepționerul le trimite cu „Înscrie fișa”; textul brut al zonei MRZ nu se trimite.
 *
 *   form[data-gi-fisa]       → formularul
 *   [data-gi-mrz-read]       → butonul „Citește actul”;  textarea #gi-f-mrz;  ul[data-gi-mrz-checks] → rezultatul verificărilor
 *   canvas[data-gi-pad=sigT|sigR] + input[name=sigT|sigR] (ascuns, primește imaginea PNG); [data-gi-clear=sigT|sigR] → „Șterge”
 */
(function () {
  'use strict';
  var form = document.querySelector('[data-gi-fisa]');
  if (!form) return;
  var field = function (name) { return form.elements['f_' + name]; };
  var checks = form.querySelector('[data-gi-mrz-checks]');
  var mrzBox = form.querySelector('#gi-f-mrz');
  var today = new Date().toISOString().slice(0, 10);

  function fmt(iso) { return iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? iso.slice(8, 10) + '.' + iso.slice(5, 7) + '.' + iso.slice(0, 4) : ''; }
  function addCheck(kind, text) {
    var li = document.createElement('li');
    li.className = kind;
    li.textContent = text;
    checks.appendChild(li);
  }
  function source(name, text) {
    var el = form.querySelector('[data-gi-sursa="' + name + '"]');
    if (el) el.textContent = text || '';
  }
  function put(name, value, from) {
    var el = field(name);
    if (!el) return;
    el.value = value || '';
    source(name, value && from ? 'citit din act' : '');
  }

  /* ---------- Citește actul ---------- */
  var readBtn = form.querySelector('[data-gi-mrz-read]');
  function readMrz() {
    checks.textContent = '';
    if (!window.GIMrz) { addCheck('bad', 'Cititorul de acte nu s-a încărcat. Reîncarcă pagina sau scrie datele de mână.'); return; }
    var r = window.GIMrz.parse(mrzBox.value);
    if (r.error) { addCheck('bad', r.error); return; }
    r.checks.forEach(function (c) { addCheck(c.ok ? 'ok' : 'bad', c.text + (c.ok ? ' se potrivește.' : ' NU se potrivește: actul a fost citit greșit sau e alterat.')); });
    if (r.expISO) {
      if (r.expISO < today) addCheck('bad', 'Actul a expirat la ' + fmt(r.expISO) + '. Cere alt act.');
      else addCheck('ok', 'Actul e valabil până la ' + fmt(r.expISO) + '.');
    } else {
      addCheck('bad', 'Data expirării din act nu e o dată valabilă.');
    }
    put('nume', r.sur, true);
    put('prenume', r.given, true);
    put('dob', r.dobISO, true);
    put('sex', r.sexNorm, true);
    put('cet', r.country, true);
    put('tip', r.docType, true);
    put('doc', r.docNoClean, true);
    put('exp', r.expISO, true);
    put('cnp', r.cnp, true);
    if (r.cnp) addCheck('ok', 'CNP-ul din act e valid și se potrivește cu data nașterii și cu sexul.');
    else if (r.nat === 'ROU') addCheck('info', 'CNP-ul nu apare în datele citite din act. Dacă îl ai pe act, scrie-l mai jos.');
    if (!r.allOk) addCheck('info', 'Verifică actul cu ochiul liber și corectează câmpurile de mai jos înainte de a înscrie fișa.');
    mrzBox.value = ''; // textul brut al zonei de jos nu rămâne în pagină
  }
  if (readBtn) readBtn.addEventListener('click', readMrz);
  if (mrzBox) {
    mrzBox.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); readMrz(); } });
  }
  // Ce scrie de mână recepționerul nu mai e „citit din act”.
  ['nume', 'prenume', 'dob', 'sex', 'cet', 'tip', 'doc', 'exp', 'cnp'].forEach(function (name) {
    var el = field(name);
    if (el) el.addEventListener('input', function () { source(name, ''); });
  });

  /* ---------- Semnături ---------- */
  var pads = {};
  function makePad(canvas) {
    var name = canvas.getAttribute('data-gi-pad');
    var store = form.elements[name];
    var ctx = canvas.getContext('2d');
    var drawing = false, used = false, last = null;
    function setup() {
      var w = Math.round(canvas.getBoundingClientRect().width) || 300;
      if (canvas.width !== w) { canvas.width = w; }
      ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#11293a';
    }
    function pos(e) {
      var r = canvas.getBoundingClientRect();
      var p = e.touches && e.touches[0] ? e.touches[0] : e;
      return { x: p.clientX - r.left, y: p.clientY - r.top };
    }
    function down(e) { setup(); drawing = true; last = pos(e); if (e.pointerId !== undefined) { try { canvas.setPointerCapture(e.pointerId); } catch (x) { /* nu contează */ } } e.preventDefault(); }
    function move(e) {
      if (!drawing) return;
      var p = pos(e);
      ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
      last = p; used = true;
      e.preventDefault();
    }
    function up() { drawing = false; }
    if (window.PointerEvent) {
      canvas.addEventListener('pointerdown', down);
      canvas.addEventListener('pointermove', move);
      canvas.addEventListener('pointerup', up);
      canvas.addEventListener('pointercancel', up);
      canvas.addEventListener('pointerleave', up);
    } else {
      canvas.addEventListener('touchstart', down, { passive: false });
      canvas.addEventListener('touchmove', move, { passive: false });
      canvas.addEventListener('touchend', up);
      canvas.addEventListener('mousedown', down);
      window.addEventListener('mousemove', move);
      window.addEventListener('mouseup', up);
    }
    setup();
    // După o greșeală de completare formularul se întoarce cu semnătura deja făcută: o redesenăm.
    if (store && store.value && /^data:image\/png;base64,/.test(store.value)) {
      var img = new Image();
      img.onload = function () { ctx.drawImage(img, 0, 0, canvas.width, canvas.height); used = true; };
      img.src = store.value;
    }
    pads[name] = {
      used: function () { return used; },
      clear: function () { ctx.clearRect(0, 0, canvas.width, canvas.height); used = false; if (store) store.value = ''; },
      save: function () { if (store) store.value = used ? canvas.toDataURL('image/png') : ''; }
    };
  }
  Array.prototype.forEach.call(form.querySelectorAll('canvas[data-gi-pad]'), makePad);
  Array.prototype.forEach.call(form.querySelectorAll('[data-gi-clear]'), function (btn) {
    btn.addEventListener('click', function () { var p = pads[btn.getAttribute('data-gi-clear')]; if (p) p.clear(); });
  });

  /* ---------- Trimiterea ---------- */
  var submitBtn = form.querySelector('[data-gi-fisa-submit]');
  var warn = document.createElement('p');
  warn.className = 'gi-zona-notice gi-zona-err';
  warn.setAttribute('role', 'alert');
  warn.hidden = true;
  if (submitBtn && submitBtn.parentNode) submitBtn.parentNode.insertBefore(warn, submitBtn);
  form.addEventListener('submit', function (e) {
    var missing = [];
    if (!pads.sigT || !pads.sigT.used()) missing.push('semnătura turistului');
    if (!pads.sigR || !pads.sigR.used()) missing.push('semnătura recepționerului');
    if (missing.length) {
      e.preventDefault();
      warn.textContent = 'Lipsește: ' + missing.join(' și ') + '. Semnați în câmpurile de mai sus.';
      warn.hidden = false;
      return;
    }
    warn.hidden = true;
    pads.sigT.save();
    pads.sigR.save();
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Se înscrie...'; } // o singură trimitere
  });
  window.addEventListener('pageshow', function (event) { if (event.persisted && submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Înscrie fișa'; } });
}());
