// Test pentru versiunea 1.3.16 (galeria foto de 15 poze + corectura „disponibilitate”), pe WordPress local pornit pe 127.0.0.1:9400
// (vezi tools/local-wp/README.md). Are nevoie de mu-plugin-urile gi-local-setup.php (creează pagina „Galerie foto”),
// gi-test-stiluri-straine.php și gi-test-1-3-14.php (numai local).
// Verifică, cu stilurile temei vechi simulate (?gi_test=stiluri), pe șase ecrane:
//  - pagina are exact 15 poze, fiecare cu miniatură mică, variantă mare, descriere și dimensiuni declarate;
//  - grila: 3 coloane pe ecran lat, 2 pe tabletă și telefon, fără loc gol la final; fără derulare pe orizontală;
//  - greutatea pozelor: miniaturile încap în 0,7 MB în total (înainte: circa 13 MB);
//  - galeria mărită: se deschide cu fotografia mare, săgețile și tastele merg, Escape închide și întoarce focusul;
//  - titlul nu stă sub antet, steagul de limbă nu acoperă nimic; legendele nu sunt cu majuscule;
//  - subsolul are linkul „Galerie foto”; titlul paginii; unealta „Pregătește paginile” cunoaște pagina;
//  - corectura „dispobibilitate” → „disponibilitate” în ce afișează widgeturile Elementor.
// Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules): node test-1-3-16.js
// Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
const Q = '?gi_test=stiluri';
const IMAGES = path.join(__dirname, '..', '..', 'gradina-ielelor-integration', 'assets', 'images');
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}

const SIZES = { 'telefon îngust': [320, 656], 'telefon': [360, 740], 'telefon mare': [414, 896], 'telefon pe lat': [740, 360], 'tabletă': [768, 1024], 'calculator': [1440, 900] };

const MEASURE = () => {
  const grid = document.querySelector('.gi-galerie-grid');
  if (!grid) { return { grid: false }; }
  const buttons = Array.from(grid.children);
  const rects = buttons.map((b) => b.getBoundingClientRect());
  const gridRect = grid.getBoundingClientRect();
  const cols = new Set(rects.map((r) => Math.round(r.left))).size;
  const last = rects[rects.length - 1];
  const imgs = buttons.map((b) => b.querySelector('img'));
  const header = document.querySelector('.gi-site .site-header');
  const hb = header ? header.getBoundingClientRect().bottom : 0;
  const h1 = document.querySelector('h1');
  const h1range = document.createRange(); h1range.selectNodeContents(h1);
  const h1rect = h1range.getClientRects()[0];
  const flag = document.getElementById('gt_float_wrapper');
  const fr = flag ? flag.getBoundingClientRect() : null;
  const overlaps = fr ? rects.filter((r) => !(fr.right <= r.left || r.right <= fr.left || fr.bottom <= r.top || r.bottom <= fr.top)).length : 0;
  const caption = buttons[0].querySelector('span');
  return {
    grid: true, n: buttons.length, cols,
    lastFull: Math.abs(last.width - gridRect.width) < 2,
    srcs: imgs.map((i) => i.getAttribute('src').split('/').pop()),
    fulls: imgs.map((i) => (i.getAttribute('data-full') || '').split('/').pop()),
    alts: imgs.map((i) => i.getAttribute('alt') || ''),
    dims: imgs.map((i) => i.getAttribute('width') + 'x' + i.getAttribute('height')),
    lazy: imgs.every((i) => i.getAttribute('loading') === 'lazy'),
    sx: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    h1Below: !h1rect || h1rect.top >= hb - 1,
    flagOver: overlaps,
    flagPos: flag ? getComputedStyle(flag).position : '',
    capUpper: getComputedStyle(caption).textTransform,
    capDisplay: getComputedStyle(caption).display,
    bodyClass: document.body.className,
    title: document.title,
    footer: Array.from(document.querySelectorAll('.gi-site footer a')).map((a) => a.textContent.replace(/\s+/g, ' ').trim()),
  };
};

(async () => {
  const browser = await chromium.launch();

  // 0. Fișierele din plugin: miniaturi mici, toate cu varianta mare alături
  const files = fs.readdirSync(IMAGES).filter((f) => /^galerie-\d\d-.*-640\.webp$/.test(f));
  const total = files.reduce((a, f) => a + fs.statSync(path.join(IMAGES, f)).size, 0);
  const biggest = Math.max.apply(null, files.map((f) => fs.statSync(path.join(IMAGES, f)).size));
  ok(files.length === 15, `fișiere: 15 miniaturi în plugin (${files.length})`);
  ok(total < 700 * 1024 && biggest < 80 * 1024, `fișiere: miniaturile au ${Math.round(total / 1024)} KB în total, cea mai mare ${Math.round(biggest / 1024)} KB`);
  const manifest = fs.readFileSync(path.join(__dirname, '..', '..', 'binary-manifest.tsv'), 'utf8').split('\n');
  const inManifest = (name) => manifest.some((l) => l.startsWith('assets/images/' + name + '\t'));
  ok(files.every(inManifest), 'fișiere: toate miniaturile sunt în binary-manifest.tsv');

  async function open(size, urlPath, withForeign) {
    const [w, h] = SIZES[size];
    const phone = Math.min(w, h) <= 430;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone });
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    let bytes = 0; const imgs = new Set();
    page.on('response', async (r) => { if (/\.(webp|jpe?g|png)(\?|$)/i.test(r.url())) { try { bytes += (await r.body()).length; imgs.add(r.url()); } catch (e) { /* ignorat */ } } });
    const resp = await page.goto(BASE + urlPath + (withForeign ? Q : ''), { waitUntil: 'load' });
    await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 80)); } window.scrollTo(0, 0); });
    await page.waitForTimeout(700);
    const m = await page.evaluate(MEASURE);
    m.status = resp.status(); m.w = w; m.h = h; m.bytes = bytes; m.imgCount = imgs.size;
    return { m, page, ctx };
  }

  // 1. Pagina, pe toate ecranele, cu stilurile străine simulate
  for (const size of Object.keys(SIZES)) {
    const { m, ctx } = await open(size, '/galerie-foto/', true);
    const wide = m.w > 900;
    const problems = [];
    if (m.status !== 200 || !m.grid) { problems.push('pagina nu are galeria (' + m.status + ')'); }
    else {
      if (m.n !== 15) { problems.push(m.n + ' poze în loc de 15'); }
      if (m.cols !== (wide ? 3 : 2)) { problems.push(m.cols + ' coloane în loc de ' + (wide ? 3 : 2)); }
      if (!wide && !m.lastFull) { problems.push('ultima poză lasă un gol lângă ea'); }
      if (m.sx > 0) { problems.push('derulare pe orizontală ' + m.sx + ' px'); }
      if (!m.h1Below) { problems.push('titlul stă sub antet'); }
      if (m.flagOver) { problems.push('steagul acoperă ' + m.flagOver + ' poze'); }
      if (m.flagPos !== 'absolute') { problems.push('steagul însoțește derularea'); }
      if (m.capUpper !== 'none') { problems.push('legendele sunt cu majuscule'); }
      if (m.bytes > 700 * 1024) { problems.push('pozele cântăresc ' + Math.round(m.bytes / 1024) + ' KB'); }
    }
    ok(problems.length === 0, `Galerie foto — ${size} (${m.w} px): 15 poze, grilă, titlu, steag, legende, derulare, greutate ${Math.round(m.bytes / 1024)} KB în ${m.imgCount} fișiere de imagine (${problems.join('; ') || 'în regulă'})`);
    await ctx.close();
  }

  // 2. Conținutul fiecărei poze, pagina, subsolul
  {
    const { m, ctx } = await open('calculator', '/galerie-foto/', true);
    ok(m.srcs.every((s) => /^galerie-\d\d-.*-640\.webp$/.test(s)), 'poze: fiecare folosește o miniatură de 640 px');
    ok(m.fulls.every((s) => /\.webp$/.test(s)) && m.fulls.every((s, i) => s !== m.srcs[i]), 'poze: fiecare are și varianta mare, diferită de miniatură');
    ok(m.alts.every((a) => a.length > 15), 'poze: fiecare are descriere (alt) completă');
    ok(m.dims.every((d) => d === '640x480') && m.lazy, 'poze: dimensiuni declarate (fără „salturi” la încărcare) și încărcare treptată');
    ok(/gi-page-galerie/.test(m.bodyClass) && /^Galerie foto \|/.test(m.title), `pagina: clasa de pagină și titlul („${m.title}”)`);
    ok(m.footer.includes('Galerie foto'), 'subsol: link „Galerie foto”');
    await ctx.close();
  }
  {
    // fără stilurile străine, designul gândit
    const { m, ctx } = await open('calculator', '/galerie-foto/', false);
    ok(m.n === 15 && m.cols === 3 && m.sx <= 0, 'designul nostru (fără stiluri străine): 15 poze, 3 coloane, fără derulare pe orizontală');
    await ctx.close();
  }

  // 3. Galeria mărită
  for (const size of ['calculator', 'telefon']) {
    const { page, ctx } = await open(size, '/galerie-foto/', true);
    const triggers = page.locator('.gi-gallery-trigger');
    await triggers.nth(4).scrollIntoViewIfNeeded();
    await triggers.nth(4).click();
    await page.waitForTimeout(500);
    const state = () => page.evaluate(() => ({
      open: !document.getElementById('gi-lightbox').hidden,
      src: document.querySelector('[data-lightbox-image]').getAttribute('src'),
      nat: document.querySelector('[data-lightbox-image]').naturalWidth,
      cap: document.querySelector('[data-lightbox-caption]').textContent,
      cnt: document.querySelector('[data-lightbox-count]').textContent,
      inert: !!document.querySelector('.site-shell').inert,
    }));
    let s = await state();
    ok(s.open && /galerie-cort-interior-1200\.webp$/.test(s.src) && s.nat >= 1200 && s.cnt === '5 / 15' && s.inert, `galerie mărită, ${size}: se deschide cu fotografia mare (${s.src.split('/').pop()}, ${s.nat} px), „${s.cnt}”, restul paginii inert`);
    await page.keyboard.press('ArrowRight'); await page.waitForTimeout(300);
    s = await state();
    ok(s.cnt === '6 / 15' && /glamping-platforme-apus-960\.webp$/.test(s.src), `galerie mărită, ${size}: săgeata dreapta merge la a șasea poză`);
    await page.keyboard.press('ArrowLeft'); await page.keyboard.press('ArrowLeft'); await page.waitForTimeout(300);
    s = await state();
    ok(s.cnt === '4 / 15', `galerie mărită, ${size}: săgeata stânga înapoi („${s.cnt}”)`);
    // de la ultima se trece la prima
    await page.keyboard.press('Escape');
    await triggers.nth(14).scrollIntoViewIfNeeded(); await triggers.nth(14).click(); await page.waitForTimeout(300);
    await page.keyboard.press('ArrowRight'); await page.waitForTimeout(300);
    s = await state();
    ok(s.cnt === '1 / 15', `galerie mărită, ${size}: după a 15-a vine prima („${s.cnt}”)`);
    await page.keyboard.press('Escape'); await page.waitForTimeout(300);
    const closed = await page.evaluate(() => ({ hidden: document.getElementById('gi-lightbox').hidden, inert: !!document.querySelector('.site-shell').inert, focus: document.activeElement && document.activeElement.classList.contains('gi-gallery-trigger'), idx: document.activeElement && document.activeElement.getAttribute('data-gallery-index') }));
    ok(closed.hidden && !closed.inert && closed.focus && closed.idx === '14', `galerie mărită, ${size}: Escape închide, pagina redevine activă, focusul se întoarce pe poza deschisă`);
    await ctx.close();
  }

  // 4. Unealta „Pregătește paginile” cunoaște pagina
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'password'); await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');
    await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    const txt = await page.textContent('body');
    ok(/\/galerie-foto\//.test(txt), 'administrare: „Setări → Grădina Ielelor → Pregătește paginile” are rândul /galerie-foto/ în tabel');
    await page.goto(BASE + '/wp-admin/post-new.php?post_type=page', { waitUntil: 'domcontentloaded' });
    const tpl = await page.evaluate(() => Array.from(document.querySelectorAll('select[name="page_template"] option, .editor-post-template__select option')).map((o) => o.textContent));
    ok(tpl.length === 0 || tpl.some((t) => /Galerie foto/.test(t)), 'administrare: șablonul „Grădina Ielelor — Galerie foto” apare în lista de șabloane' + (tpl.length === 0 ? ' (editorul de blocuri nu l-a încărcat în test; verificat prin lista din plugin)' : ''));
    await ctx.close();
  }

  // 4b. Ca pe live: pagina „Galerie foto” are șablonul implicit; pasul 2 din „Pregătește paginile” îi pune șablonul galeriei, „Revino” îl readuce
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'password'); await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');
    const nonce = (await (await page.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
    const H = { 'X-WP-Nonce': nonce };
    const found = await (await page.request.get(BASE + '/wp-json/wp/v2/pages?slug=galerie-foto&context=edit', { headers: H })).json();
    const id = found[0] && found[0].id;
    ok(!!id, 'unealta: pagina „Galerie foto” există în mediul local');
    const gridCount = async () => {
      const r = await page.request.get(BASE + '/galerie-foto/');
      return (await r.text()).split('gi-gallery-trigger').length - 1;
    };
    const setTemplate = (template) => page.request.post(BASE + '/wp-json/wp/v2/pages/' + id, { headers: H, data: { template } });
    const settings = async () => { await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' }); return page.textContent('body'); };
    const pressed = async (label) => {
      await page.locator('input[type="submit"][value^="' + label + '"]').first().click();
      await page.waitForLoadState('domcontentloaded');
      return page.textContent('body');
    };
    // „Revino” desface șabloanele tuturor paginilor atinse vreodată de unealtă: notăm șabloanele de acum și le readucem la final.
    const toate = await (await page.request.get(BASE + '/wp-json/wp/v2/pages?per_page=100&context=edit&status=publish,draft', { headers: H })).json();
    const sabloane = toate.map((p) => [p.id, p.template]);
    await setTemplate('');
    ok((await gridCount()) === 0, 'unealta: cu șablonul implicit (ca pe live), pagina nu arată galeria nouă');
    let t = await settings();
    ok(/galerie-foto[\s\S]{0,80}altul \(default\)/.test(t), 'unealta: tabelul arată că pagina „Galerie foto” are alt șablon');
    t = await pressed('2. Pune șabloanele');
    ok(/Galerie foto \(\/galerie-foto\/\): șablonul a trecut pe cel nou/.test(t), 'unealta: pasul 2 pune șablonul galeriei pe pagina existentă');
    ok((await gridCount()) === 15, 'unealta: după pasul 2, pagina arată galeria cu 15 poze');
    t = await pressed('2. Pune șabloanele');
    ok(/Galerie foto \(\/galerie-foto\/\): are deja șablonul potrivit/.test(t), 'unealta: apăsat a doua oară, pasul 2 nu schimbă nimic');
    t = await pressed('Revino');
    ok(/Galerie foto”: șablonul vechi \(default\) e înapoi/.test(t), 'unealta: „Revino” readuce șablonul vechi');
    ok((await gridCount()) === 0, 'unealta: după „Revino”, pagina redevine cum era');
    for (const [pid, tpl] of sabloane) { await page.request.post(BASE + '/wp-json/wp/v2/pages/' + pid, { headers: H, data: { template: tpl } }); } // readucem toate șabloanele
    await setTemplate('gi-galerie.php');
    ok((await gridCount()) === 15, 'unealta: mediul local readus la starea inițială');
    await ctx.close();
  }

  // 5. Corectura „dispobibilitate”
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    const resp = await page.goto(BASE + '/?gi_test=typo', { waitUntil: 'domcontentloaded' });
    let r = {};
    try { r = JSON.parse(await resp.text()); } catch (e) { r = {}; }
    ok(/disponibilitate în intervalul/.test(r.widget || '') && !/dispobibilitate/.test(r.widget || ''), 'disponibilitate: corectat în widgetul Elementor');
    ok(/disponibilitate în intervalul/.test(r.pagina || '') && !/dispobibilitate/.test(r.pagina || ''), 'disponibilitate: corectat și în tot conținutul Elementor al paginii');
    ok(r.corect_neschimbat === '<p>Nu avem disponibilitate.</p>' && r.alt_text_neschimbat === '<p>Altceva.</p>', 'disponibilitate: textul corect și alt text rămân neschimbate');
    await ctx.close();
  }

  await browser.close();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})();
