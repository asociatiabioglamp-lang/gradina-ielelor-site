/*
 * Scanare cod QR cu camera telefonului (Zona echipei → Recepție). Nu trimite nimic către un serviciu extern: imaginea de la cameră rămâne în telefon.
 * Folosește cititorul din browser (BarcodeDetector), unde există (Chrome pe Android), iar în rest biblioteca jsQR (Apache-2.0, assets/js/vendor/jsQR.js),
 * încărcată numai când se pornește camera. Camera cere HTTPS și permisiunea utilizatorului.
 *
 *   [data-gi-scan]            → containerul; atribute: data-gi-scan-field (câmpul în care se pune codul), data-gi-jsqr (adresa bibliotecii)
 *   [data-gi-scan-open/stop]  → butoanele de pornire / oprire
 *   [data-gi-scan-msg]        → mesajele către utilizator
 */
(function () {
  'use strict';
  var root = document.querySelector('[data-gi-scan]');
  if (!root) return;
  var openBtn = root.querySelector('[data-gi-scan-open]');
  var stopBtn = root.querySelector('[data-gi-scan-stop]');
  var video = root.querySelector('video');
  var msg = root.querySelector('[data-gi-scan-msg]');
  var field = document.querySelector(root.getAttribute('data-gi-scan-field'));
  var form = field ? field.form : null;
  var jsqrUrl = root.getAttribute('data-gi-jsqr');
  var stream = null, timer = null, detector = null, canvas = null, ctx = null, busy = false, errors = 0, starting = false, generation = 0;
  var SHAPE = /^(GI1\.[A-Z0-9-]+\.[A-Z0-9]{2}|[A-Z0-9]{4}-?[A-Z0-9]{4})$/;

  function say(text, bad) {
    msg.textContent = text || '';
    msg.className = 'gi-zona-mic' + (bad ? ' gi-scan-err' : '');
  }

  function stop() {
    generation++; // anulează o pornire care încă așteaptă permisiunea camerei
    starting = false;
    if (timer) { clearTimeout(timer); timer = null; }
    if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
    busy = false;
    video.hidden = true; stopBtn.hidden = true; openBtn.hidden = false; openBtn.disabled = false;
  }

  function found(text) {
    if (!stream) return; // citire întoarsă după ce s-a oprit camera
    var clean = String(text || '').replace(/\s+/g, '').toUpperCase();
    if (!SHAPE.test(clean)) {
      // un alt cod QR (afiș, wifi...): nu e al rezervării; continuă să caute
      say('Acesta nu e un cod de rezervare. Îndreaptă camera spre codul turistului.', true);
      schedule();
      return;
    }
    stop();
    field.value = text;
    say('Cod citit. Se caută rezervarea...');
    if (form) form.submit();
  }

  function loadJsqr(done) {
    if (window.jsQR) return done(true);
    var s = document.createElement('script');
    s.src = jsqrUrl;
    s.onload = function () { done(!!window.jsQR); };
    s.onerror = function () { done(false); };
    document.head.appendChild(s);
  }

  function schedule() { timer = setTimeout(tick, 160); }

  function useJsqr() {
    detector = null;
    loadJsqr(function (ok) {
      if (!ok) { stop(); say('Nu s-a putut încărca cititorul de coduri. Scrie codul de mai jos.', true); return; }
      canvas = document.createElement('canvas');
      ctx = canvas.getContext('2d', { willReadFrequently: true });
      schedule();
    });
  }

  function tick() {
    if (!stream) return;
    if (video.readyState >= 2 && !busy) {
      if (detector) {
        busy = true;
        detector.detect(video).then(function (codes) {
          busy = false;
          if (codes && codes.length) { found(codes[0].rawValue); } else { schedule(); }
        }, function () {
          busy = false; errors++;
          if (errors >= 3) { useJsqr(); } else { schedule(); }
        });
        return;
      }
      var w = video.videoWidth, h = video.videoHeight;
      if (w && h && canvas) {
        var scale = Math.min(1, 640 / w);
        canvas.width = Math.round(w * scale); canvas.height = Math.round(h * scale);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var data = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var result = window.jsQR(data.data, data.width, data.height, { inversionAttempts: 'dontInvert' });
        if (result && result.data) { found(result.data); return; }
      }
    }
    schedule();
  }

  function start() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      say('Camera nu se poate porni în acest browser (sau pagina nu e pe https). Scrie codul de mai jos.', true);
      return;
    }
    if (starting || stream) return; // al doilea apăsat, cât camera se pornește sau merge deja
    starting = true;
    openBtn.disabled = true;
    var mine = ++generation;
    say('Se pornește camera...');
    errors = 0;
    navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then(function (s) {
      if (mine !== generation) { // între timp s-a apăsat „Oprește” sau s-a părăsit pagina
        s.getTracks().forEach(function (t) { t.stop(); });
        return Promise.reject(null);
      }
      starting = false;
      stream = s;
      video.srcObject = s;
      video.setAttribute('playsinline', 'true');
      video.hidden = false; stopBtn.hidden = false; openBtn.hidden = true;
      return video.play();
    }).then(function () {
      if (mine !== generation) { return; }
      say('Îndreaptă camera spre codul QR.');
      if (window.BarcodeDetector) {
        try { detector = new window.BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { detector = null; }
      }
      if (detector) { schedule(); } else { useJsqr(); }
    }).catch(function () {
      if (mine !== generation) { return; } // anulată de utilizator sau pagină părăsită: fără mesaj de eroare
      stop();
      say('Nu am acces la cameră. Permite camera pentru acest site sau scrie codul de mai jos.', true);
    });
  }

  openBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', function () { stop(); say(''); });
  window.addEventListener('pagehide', stop);
  document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); });
}());
