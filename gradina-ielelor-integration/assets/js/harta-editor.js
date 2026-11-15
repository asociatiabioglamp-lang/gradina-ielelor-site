/*
 * Zona echipei → locul → „Unde e locul pe hartă?”: apeși pe plan și apare un punct; poziția se păstrează în procente (stânga, sus).
 */
(function () {
  'use strict';
  var box = document.querySelector('[data-gi-harta-editor]');
  if (!box) return;
  var frame = box.querySelector('.gi-harta-cadru');
  var img = frame.querySelector('img');
  var pin = frame.querySelector('.gi-pin-ales');
  var fx = box.querySelector('input[name="poz_x"]');
  var fy = box.querySelector('input[name="poz_y"]');
  var state = box.querySelector('[data-gi-harta-stare]');
  var clear = box.querySelector('[data-gi-harta-scoate]');

  function set(x, y) {
    fx.value = x; fy.value = y;
    pin.hidden = false;
    pin.style.left = x + '%'; pin.style.top = y + '%';
    state.textContent = 'Marcat pe hartă.';
  }
  img.addEventListener('click', function (e) {
    var r = img.getBoundingClientRect();
    if (!r.width || !r.height) return;
    var x = Math.max(0, Math.min(100, (e.clientX - r.left) / r.width * 100));
    var y = Math.max(0, Math.min(100, (e.clientY - r.top) / r.height * 100));
    set(Math.round(x * 10) / 10, Math.round(y * 10) / 10);
  });
  clear.addEventListener('click', function () {
    fx.value = ''; fy.value = '';
    pin.hidden = true;
    state.textContent = 'Încă nu e marcat pe hartă.';
  });
}());
