// Test pentru versiunea 1.4.0 (zona privată „Grădina”: copaci și locuri cu cod QR, pagină de printat, vouchere; galeria se aplică singură),
// pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md). NUMAI LOCAL: se autentifică cu admin/password, creează
// și șterge locuri, vouchere și conturi de probă.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins, WooCommerce activ, „Produs de probă” (id 30) și biblioteca de test jsqr
// (numai pentru test, nu face parte din plugin):  npm install jsqr  și  NODE_PATH să includă folderul node_modules respectiv.
// Verifică:
//  - galeria: pagina „Galerie foto” primește singură șablonul, o singură dată; o alegere făcută de mână nu se atinge; „Revino” nu o reaplică;
//  - locurile: pagina publică (titlu, tip, rezumat, poză, text, „Mai multe de văzut”, SEO), pe șase ecrane, cu stilurile străine;
//  - codul QR din administrare se citește (decodat cu jsQR) și duce la pagina locului; la fel în listă, în ecranul de printat și în pagina de printat;
//  - pagina de printat: acces numai cu drept + cod de siguranță, mărimile, fără meniurile administrării;
//  - voucherele: generate corect (valoare, o folosire, 18 luni, cod unic), erori la date greșite, cod QR care se citește, aplicare în coș;
//  - accesul: editorul are locurile, nu are voucherele; vizitatorul nu are nimic; vizitatorul care scanează un voucher nu pățește nimic.
// Rulează: NODE_PATH="$(npm root -g):<folder>/node_modules" node tools/local-wp/test-1-4-0.js     Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(BASE)) {
  console.error('Refuz să rulez pe ' + BASE + ': testul e numai pentru WordPress-ul local (GI_BASE=http://127.0.0.1:9400).');
  process.exit(2);
}
let JSQR;
try { JSQR = fs.readFileSync(require.resolve('jsqr/dist/jsQR.js'), 'utf8'); } catch (e) { console.error('Lipsește biblioteca de test jsqr (npm install jsqr).'); process.exit(2); }
const IMG = path.join(__dirname, '..', '..', 'gradina-ielelor-integration', 'assets', 'images');
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const STAMP = String(Date.now()).slice(-6);
const UTM = '?utm_source=qr&utm_medium=print&utm_campaign=gradina';

// Citește primul cod QR din elementul dat: SVG → imagine → pixeli → jsQR. Întoarce textul din cod sau null.
async function decode(page, selector, index) {
  return page.evaluate(async ({ selector, index, lib }) => {
    if (!window.jsQR) { (0, eval)(lib); }
    const el = document.querySelectorAll(selector)[index || 0];
    const svg = el && el.querySelector('svg');
    if (!svg) { return null; }
    const xml = new XMLSerializer().serializeToString(svg).replace('<svg ', '<svg width="480" height="480" ');
    const url = URL.createObjectURL(new Blob([xml], { type: 'image/svg+xml' }));
    const img = new Image();
    await new Promise((res, rej) => { img.onload = res; img.onerror = rej; img.src = url; });
    const c = document.createElement('canvas'); c.width = 480; c.height = 480;
    const g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, 480, 480); g.drawImage(img, 0, 0, 480, 480);
    const d = g.getImageData(0, 0, 480, 480);
    const r = window.jsQR(d.data, 480, 480);
    return r ? r.data : null;
  }, { selector, index, lib: JSQR });
}

(async () => {
  const browser = await chromium.launch();
  const actx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const admin = await actx.newPage();
  admin.setDefaultTimeout(90000);
  await admin.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await admin.fill('#user_login', 'admin'); await admin.fill('#user_pass', 'password'); await admin.click('#wp-submit');
  await admin.waitForURL('**/wp-admin/**');
  const nonce = (await (await admin.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  const H = { 'X-WP-Nonce': nonce };
  const rest = async (method, route, data) => {
    const r = await admin.request.fetch(BASE + '/wp-json' + route, { method, headers: H, data });
    let body = null; try { body = await r.json(); } catch (e) { body = null; }
    return { status: r.status(), body };
  };
  const created = { posts: [], coupons: [], users: [], media: [] };

  // ------------------------------------------------------------------ 1. Galeria se aplică singură
  {
    const pg = await rest('GET', '/wp/v2/pages?slug=galerie-foto&context=edit');
    const id = pg.body && pg.body[0] && pg.body[0].id;
    ok(!!id, 'galerie: pagina „Galerie foto” există în mediul local');
    const tpl = async () => (await rest('GET', '/wp/v2/pages/' + id + '?context=edit')).body.template;
    const hit = async (q) => { const r = await admin.request.get(BASE + '/?gi_test=galerie_reset' + (q || '')); return r.json(); };
    const visit = () => admin.request.get(BASE + '/');
    const reset = await hit();
    ok(reset.ok && reset.sablon === 'default', 'galerie: readusă la starea de pe live (șablon implicit, fără marcajul „aplicată”)');
    await visit(); // orice cerere către site pornește aplicarea; chiar și citirea prin REST de mai jos ar porni-o
    ok((await tpl()) === 'gi-galerie.php', 'galerie: la prima rulare, pagina primește singură șablonul galeriei');
    const prev = await admin.request.get(BASE + '/galerie-foto/');
    ok((await prev.text()).split('gi-gallery-trigger').length - 1 === 15, 'galerie: pagina arată cele 15 poze, fără să apese cineva nimic');
    // „Revino” desface, iar aplicarea automată nu se repetă
    await admin.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    const toate = await rest('GET', '/wp/v2/pages?per_page=100&context=edit&status=publish,draft');
    const sabloane = toate.body.map((p) => [p.id, p.template]);
    await admin.locator('input[type="submit"][value^="Revino"]').first().click();
    await admin.waitForLoadState('domcontentloaded');
    ok((await tpl()) === '', 'galerie: „Revino” readuce șablonul vechi');
    await visit(); await visit();
    ok((await tpl()) === '', 'galerie: după „Revino”, șablonul nu se aplică din nou singur');
    for (const [pid, t] of sabloane) { await rest('POST', '/wp/v2/pages/' + pid, { template: t }); }
    // o alegere făcută de mână nu se atinge
    await hit('&sablon=gi-content.php');
    await visit();
    ok((await tpl()) === 'gi-content.php', 'galerie: dacă pagina are deja un șablon ales de mână, nu se schimbă');
    await hit('&sablon=gi-galerie.php'); // readus: pagina de probă locală folosește șablonul galeriei
    await visit();
    ok((await tpl()) === 'gi-galerie.php', 'galerie: mediul local readus la starea inițială');
  }

  // ------------------------------------------------------------------ 2. Locurile (copaci): creare și pagina publică
  const media = await (async () => {
    const buf = fs.readFileSync(path.join(IMG, 'intrare-munte-960.webp'));
    const r = await admin.request.post(BASE + '/wp-json/wp/v2/media', { headers: Object.assign({}, H, { 'Content-Disposition': 'attachment; filename="intrare-test.webp"', 'Content-Type': 'image/webp' }), data: buf });
    const b = await r.json().catch(() => null);
    if (b && b.id) { created.media.push(b.id); }
    return b && b.id ? b.id : 0;
  })();
  const mk = async (title, extra) => {
    const r = await rest('POST', '/wp/v2/puncte', Object.assign({ title, status: 'publish', content: '<p>Text de probă pentru ' + title + '.</p>', excerpt: 'Rezumat ' + title, meta: { _gi_tip: 'copac', _gi_stiintific: 'Quercus test' } }, extra || {}));
    if (r.body && r.body.id) { created.posts.push(r.body.id); }
    return r;
  };
  const TITLE_A = 'Stejarul bătrân ' + STAMP;
  const TITLE_B = 'Teiul de lângă lac ' + STAMP;
  const a = await mk(TITLE_A, media ? { featured_media: media } : {});
  const b2 = await mk(TITLE_B, { meta: { _gi_tip: 'planta', _gi_stiintific: '' } });
  const c3 = await rest('POST', '/wp/v2/puncte', { title: 'Ciornă ' + STAMP, status: 'draft', content: 'ciornă' });
  if (c3.body && c3.body.id) { created.posts.push(c3.body.id); }
  ok(a.status === 201 && b2.status === 201, 'locuri: două locuri create (copac, plantă) cu tip, nume științific, rezumat și poză');
  const A = a.body, B = b2.body;
  ok(/\/in-gradina\/stejarul-batran-/.test(A.link), `locuri: adresa stabilă începe cu /in-gradina/ (${A.link.replace(BASE, '')})`);

  const SIZES = [[320, 656], [360, 740], [390, 844], [768, 1024], [1440, 900]];
  for (const [w, h] of SIZES) {
    const phone = w < 700;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone });
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    const resp = await page.goto(A.link + '?gi_test=stiluri', { waitUntil: 'load' });
    await page.waitForTimeout(400);
    const m = await page.evaluate(() => {
      const hdr = document.querySelector('.gi-site .site-header'); const hb = hdr ? hdr.getBoundingClientRect().bottom : 0;
      const h1 = document.querySelector('h1'); const rg = document.createRange(); rg.selectNodeContents(h1); const r1 = rg.getClientRects()[0];
      const fig = document.querySelector('.gi-punct-foto img');
      return {
        title: document.title, h1: h1.textContent, cls: document.body.className,
        eyebrow: (document.querySelector('.gi-content-hero .eyebrow') || {}).textContent || '',
        sci: (document.querySelector('.gi-punct-stiintific') || {}).textContent || '',
        lead: (document.querySelector('.gi-content-lead') || {}).textContent || '',
        body: (document.querySelector('.gi-punct-body') || {}).textContent || '',
        photo: fig ? { w: fig.naturalWidth, shown: fig.getBoundingClientRect().width } : null,
        others: Array.from(document.querySelectorAll('.gi-punct-lista a')).map((x) => x.textContent.replace(/\s+/g, ' ').trim()),
        sx: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        h1Below: !r1 || r1.top >= hb - 1,
        desc: (document.querySelector('meta[name="description"]') || {}).content || '',
        crumbs: Array.from(document.querySelectorAll('.gi-breadcrumb > *')).map((x) => x.textContent.trim()).join(' '),
      };
    });
    if (w === 1440) {
      ok(resp.status() === 200 && /gi-page-punct/.test(m.cls) && m.title === TITLE_A + ' | ' + m.title.split(' | ')[1], `pagina locului: 200, clasa de pagină, titlul („${m.title}”)`);
      ok(m.h1 === TITLE_A && m.eyebrow === 'Copac' && m.sci === 'Quercus test', 'pagina locului: titlu, tip („Copac”) și nume științific');
      ok(/Rezumat Stejarul/.test(m.lead) && /Text de probă pentru Stejarul/.test(m.body), 'pagina locului: rezumatul sub titlu și textul scris de proprietară');
      ok(m.photo && m.photo.w >= 900, `pagina locului: poza locului se încarcă (${m.photo ? m.photo.w : 0} px)`);
      const mine = m.others.filter((x) => x.includes(STAMP));
      ok(mine.length === 1 && /Teiul de lângă lac/.test(mine[0]) && /Plantă/.test(mine[0]) && !m.others.some((x) => /Ciornă/.test(x)), `pagina locului: „Mai multe de văzut” arată celălalt loc, nu pe cel curent și nu ciorna (${m.others.join(' | ')})`);
      ok(/Acasă\s*\/\s*În Grădină\s*\/\s*Stejarul/.test(m.crumbs) && /Rezumat Stejarul/.test(m.desc), 'pagina locului: firimituri și descriere SEO din rezumat');
    }
    ok(m.sx <= 0 && m.h1Below, `pagina locului — ${w} px, cu stiluri străine: fără derulare pe orizontală, titlul nu e sub antet`);
    await ctx.close();
  }
  {
    const r = await admin.request.get(B.link);
    const t = await r.text();
    ok(r.status() === 200 && /Text de probă pentru Teiul/.test(t) && !/gi-punct-foto/.test(t) && !/gi-punct-stiintific/.test(t), 'pagina locului fără poză și fără nume științific: nu apar casete goale');
    const dp = new URL(c3.body.link);
    const dr = await (await browser.newContext()).request.get(BASE + '/?p=' + c3.body.id + '&post_type=gi_punct', { maxRedirects: 0 });
    ok(dr.status() === 404 && !/Text de ciornă|>ciornă</.test(await dr.text()), `ciorna nu se vede vizitatorilor (${dr.status()})`);
    // adresa veche a unui loc redirecționează și păstrează parametrii de sursă
    const oldSlug = new URL(B.link).pathname;
    await rest('POST', '/wp/v2/puncte/' + b2.body.id, { slug: 'teiul-nou-' + STAMP });
    const rd = await (await browser.newContext()).request.get(BASE + oldSlug + UTM, { maxRedirects: 0 });
    ok(rd.status() === 301 && /teiul-nou-/.test(rd.headers().location || '') && /utm_source=qr/.test(rd.headers().location || ''), `adresa veche a unui loc redirecționează la cea nouă și păstrează sursa (${rd.status()} → ${(rd.headers().location || '').replace(BASE, '')})`);
    const evil = await (await browser.newContext()).request.get(BASE + oldSlug + '?utm_source=a%26p%3D1%23frag&utm_medium=b%0d%0aSet-Cookie:%20z=1&utm_campaign=%2F%2Fevil.example%2F', { maxRedirects: 0 });
    const loc = evil.headers().location || '';
    ok(evil.status() === 301 && loc.startsWith(BASE) && /^\/in-gradina\/[^?#]+\?utm_source=[\w.-]*&utm_medium=[\w.-]*&utm_campaign=[\w.-]*$/.test(loc.replace(BASE, '')), `redirecționarea adresei vechi: doar cei trei parametri utm, cu valori simple; fără parametri în plus, fragment sau antete (${loc.replace(BASE, '')})`);
    await rest('POST', '/wp/v2/puncte/' + b2.body.id, { slug: oldSlug.split('/').filter(Boolean).pop() });
  }

  // ------------------------------------------------------------------ 3. Codul QR în administrare
  const qrOf = (p) => p.link + UTM;
  {
    await admin.goto(BASE + '/wp-admin/post.php?post=' + A.id + '&action=edit', { waitUntil: 'load' });
    await admin.waitForSelector('.gi-qr-box svg');
    const text = await decode(admin, '.gi-qr-box', 0);
    ok(text === qrOf(A), `cod QR (pagina locului în administrare): se citește și duce la pagina locului (${text})`);
    const txt = await admin.textContent('#gi_punct_qr');
    ok(/Printează acest cod/.test(txt) && /Descarcă/.test(txt) && /Nu schimba adresa/.test(txt), 'cod QR: butoanele „Printează”, „Descarcă” și avertismentul despre adresă');
    // descărcarea SVG
    const [dl] = await Promise.all([admin.waitForEvent('download'), admin.click('[data-qr-download]')]);
    const svgText = fs.readFileSync(await dl.path(), 'utf8');
    ok(/^cod-qr-stejarul-batran/.test(dl.suggestedFilename()) && /<svg[^>]+width="800"/.test(svgText), `cod QR: descărcarea dă un fișier SVG (${dl.suggestedFilename()})`);
    // ciorna
    await admin.goto(BASE + '/wp-admin/post.php?post=' + c3.body.id + '&action=edit', { waitUntil: 'load' });
    ok(/după ce <strong>publici|după ce publici/i.test((await admin.textContent('#gi_punct_qr')).replace(/\s+/g, ' ')) && (await admin.locator('#gi_punct_qr .gi-qr-box').count()) === 0, 'cod QR: la o ciornă nu apare cod, ci explicația');
    // salvarea câmpurilor din formular
    await admin.goto(BASE + '/wp-admin/post.php?post=' + B.id + '&action=edit', { waitUntil: 'load' });
    await admin.selectOption('#gi_tip', 'loc'); await admin.fill('#gi_stiintific', 'Tilia test');
    await admin.click('#publish'); await admin.waitForLoadState('load');
    const chk = await rest('GET', '/wp/v2/puncte/' + B.id + '?context=edit');
    ok(chk.body.meta._gi_tip === 'loc' && chk.body.meta._gi_stiintific === 'Tilia test', 'administrare: tipul și numele științific se salvează din formular');
    // lista locurilor
    await admin.goto(BASE + '/wp-admin/edit.php?post_type=gi_punct', { waitUntil: 'load' });
    await admin.waitForSelector('.gi-qr-mini svg');
    const mini = await decode(admin, '.gi-qr-mini', 0);
    ok(/\?utm_source=qr&utm_medium=print&utm_campaign=gradina$/.test(mini || '') && (await admin.locator('.gi-qr-mini svg').count()) >= 2, `lista locurilor: coloana „Cod QR” arată coduri care se citesc (${mini})`);
  }

  // ------------------------------------------------------------------ 4. Ecranul și pagina de printat
  {
    await admin.goto(BASE + '/wp-admin/admin.php?page=gi-qr', { waitUntil: 'load' });
    await admin.waitForSelector('.gi-qr-mini svg');
    const rows = await admin.locator('table tbody tr').count();
    ok(rows >= 2, `ecranul „Coduri QR de printat”: lista cu locurile publicate (${rows} rânduri, ciorna lipsește)`);
    ok(/acesta nu e site-ul real/.test(await admin.textContent('.wrap')) && /127\.0\.0\.1/.test(await admin.textContent('.wrap .notice-warning')), 'ecranul „Coduri QR de printat”: pe un site care nu e cel real apare avertizarea cu adresa la care duc codurile');
    await admin.check('[data-qr-select-all]');
    const checked = await admin.locator('input[name="ids[]"]:checked').count();
    ok(checked === rows, 'ecranul „Coduri QR de printat”: „alege toate” bifează toate rândurile');
    await admin.selectOption('#gi_qr_size', '6');
    const [popup] = await Promise.all([admin.waitForEvent('popup'), admin.click('button:has-text("Printează codurile alese")')]);
    await popup.waitForLoadState('load'); await popup.waitForSelector('.gi-tile .qr svg');
    const info = await popup.evaluate(() => ({
      tiles: document.querySelectorAll('.gi-tile').length, adminBar: !!document.getElementById('wpadminbar') || !!document.getElementById('adminmenu'),
      noindex: !!document.querySelector('meta[name="robots"][content*="noindex"]'), w: getComputedStyle(document.querySelector('.gi-tile .qr')).width,
      titles: Array.from(document.querySelectorAll('.gi-tile strong')).map((x) => x.textContent),
    }));
    ok(info.tiles === rows && !info.adminBar && info.noindex, `pagina de printat: ${info.tiles} coduri, fără meniurile administrării, cu noindex`);
    ok(Math.abs(parseFloat(info.w) - 6 * 37.795) < 3, `pagina de printat: mărimea aleasă (6 cm) se aplică (${info.w})`);
    const t0 = await decode(popup, '.gi-tile .qr', 0);
    const all = [];
    for (let i = 0; i < info.tiles; i++) { all.push(await decode(popup, '.gi-tile .qr', i)); }
    ok(all.every((x) => /\/in-gradina\/.*\?utm_source=qr&utm_medium=print&utm_campaign=gradina$/.test(x || '')) && all.includes(qrOf(A)) && all.includes(qrOf(B)), 'pagina de printat: fiecare cod se citește și duce la pagina locului lui');
    // „dus-întors”: codul citit deschide pagina
    const trip = await popup.request.get(all.find((x) => x === qrOf(A)));
    ok(trip.status() === 200 && /Stejarul bătrân/.test(await trip.text()), 'dus-întors: adresa citită din cod deschide pagina locului');
    const url = popup.url();
    await popup.close();
    // fără cod de siguranță sau fără cont
    const noNonce = await admin.request.get(url.replace(/_wpnonce=[^&]+/, '_wpnonce=gresit'));
    ok(noNonce.status() === 403 || /a expirat|expired|Ești sigur|Are you sure/i.test(await noNonce.text()), `pagina de printat: refuzată cu cod de siguranță greșit (${noNonce.status()})`);
    const anon = await (await browser.newContext()).request.get(url, { maxRedirects: 0 });
    ok([302, 400, 401, 403].includes(anon.status()) && !/gi-tile/.test(await anon.text()), `pagina de printat: refuzată fără cont, nu se afișează nimic (${anon.status()})`);
  }

  // ------------------------------------------------------------------ 5. Vouchere
  const NOTE = 'proba-' + STAMP;
  let codes = [];
  {
    await admin.goto(BASE + '/wp-admin/admin.php?page=gi-vouchere', { waitUntil: 'load' });
    ok(/valabile\s*18\s*luni/.test((await admin.textContent('.wrap')).replace(/\s+/g, ' ')), 'vouchere: ecranul spune că sunt valabile 18 luni');
    // date greșite
    const submit = async (vals) => {
      await admin.goto(BASE + '/wp-admin/admin.php?page=gi-vouchere', { waitUntil: 'load' });
      await admin.check('input[name="tip"][value="' + vals.tip + '"]');
      await admin.fill('#gi_v_valoare', vals.valoare); await admin.fill('#gi_v_numar', vals.numar); await admin.fill('#gi_v_prefix', vals.prefix); await admin.fill('#gi_v_nota', vals.nota || '');
      await admin.evaluate(() => { document.querySelectorAll('form input[required]').forEach((i) => i.removeAttribute('required')); const n = document.querySelector('#gi_v_numar'); n.removeAttribute('min'); n.removeAttribute('max'); });
      await admin.click('button:has-text("Generează codurile")'); await admin.waitForLoadState('load');
      return (await admin.textContent('.wrap')).replace(/\s+/g, ' ');
    };
    let t = await submit({ tip: 'procent', valoare: '150', numar: '2', prefix: 'TEST' });
    ok(/Procentul nu poate fi mai mare de 100/.test(t), 'vouchere: procent peste 100 → mesaj de eroare');
    t = await submit({ tip: 'suma', valoare: '0', numar: '2', prefix: 'TEST' });
    ok(/mai mare ca 0/.test(t), 'vouchere: valoare 0 → mesaj de eroare');
    t = await submit({ tip: 'suma', valoare: '50', numar: '0', prefix: 'TEST' });
    ok(/între 1 și 100/.test(t), 'vouchere: număr de coduri 0 → mesaj de eroare');
    t = await submit({ tip: 'suma', valoare: '50', numar: '2', prefix: '!' });
    ok(/între 2 și 10 litere/.test(t), 'vouchere: început de cod prea scurt → mesaj de eroare');
    const before = await rest('GET', '/wc/v3/coupons?per_page=100&search=' + NOTE);
    ok(before.body.length === 0, 'vouchere: datele greșite nu creează niciun cod');
    // generare corectă
    t = await submit({ tip: 'suma', valoare: '50', numar: '3', prefix: 'test', nota: NOTE });
    const listed = await rest('GET', '/wc/v3/coupons?per_page=100&search=TEST');
    const mine = (listed.body || []).filter((c) => c.description === NOTE);
    mine.forEach((c) => created.coupons.push(c.id));
    codes = mine.map((c) => c.code);
    ok(mine.length === 3 && new Set(codes).size === 3, `vouchere: 3 coduri generate, toate diferite (${codes.join(', ')})`);
    ok(mine.every((c) => /^test-[a-z2-9]{4}-[a-z2-9]{4}$/.test(c.code)), 'vouchere: codurile au forma PREFIX-XXXX-XXXX, fără caractere care se încurcă (0, O, 1, I, L)');
    ok(mine.every((c) => c.discount_type === 'fixed_cart' && parseFloat(c.amount) === 50 && c.usage_limit === 1), 'vouchere: sumă fixă de 50 lei, o singură folosire pe cod');
    const months = mine.map((c) => (new Date(c.date_expires_gmt || c.date_expires).getTime() - Date.now()) / (1000 * 3600 * 24 * 30.44));
    ok(months.every((x) => x > 17.8 && x < 18.2), `vouchere: valabile 18 luni de la generare (${months.map((x) => x.toFixed(1)).join(', ')} luni)`);
    ok(/Am generat 3 cod/.test(t) && codes.every((c) => t.includes(c.toUpperCase())), 'vouchere: după generare, ecranul arată codurile');
    // procent
    t = await submit({ tip: 'procent', valoare: '10', numar: '1', prefix: 'PROC', nota: NOTE + '-p' });
    const lp = await rest('GET', '/wc/v3/coupons?per_page=100&search=PROC');
    const pc = (lp.body || []).filter((c) => c.description === NOTE + '-p');
    pc.forEach((c) => created.coupons.push(c.id));
    ok(pc.length === 1 && pc[0].discount_type === 'percent' && parseFloat(pc[0].amount) === 10, 'vouchere: reducere în procente (10%)');
    // lista și codurile QR
    await admin.goto(BASE + '/wp-admin/admin.php?page=gi-vouchere', { waitUntil: 'load' });
    await admin.waitForSelector('.gi-qr-mini svg');
    const row = admin.locator('tbody tr', { hasText: codes[0].toUpperCase() });
    ok((await row.count()) === 1 && /50 lei/.test(await row.textContent()) && /0 \/ 1/.test(await row.textContent()) && /valabil/.test(await row.textContent()), 'vouchere: lista arată codul (cu litere mari), „50 lei”, „0 / 1”, „valabil”');
    const idx = await admin.evaluate((code) => Array.from(document.querySelectorAll('table.widefat tbody tr')).findIndex((tr) => tr.textContent.includes(code)), codes[0].toUpperCase());
    const qrTxt = await decode(admin, '.gi-qr-mini', idx);
    ok(qrTxt === BASE + '/?gi_cod=' + codes[0].toUpperCase(), `vouchere: codul QR din listă se citește și duce la /?gi_cod=COD (${qrTxt})`);
    // printare
    await admin.locator('tbody tr', { hasText: codes[0].toUpperCase() }).locator('input[type="checkbox"]').check();
    await admin.locator('tbody tr', { hasText: codes[1].toUpperCase() }).locator('input[type="checkbox"]').check();
    const [pop] = await Promise.all([admin.waitForEvent('popup'), admin.click('button:has-text("Printează voucherele alese")')]);
    await pop.waitForLoadState('load'); await pop.waitForSelector('.gi-tile .qr svg');
    const tiles = await pop.evaluate(() => Array.from(document.querySelectorAll('.gi-tile')).map((t) => ({ title: t.querySelector('strong').textContent, note: (t.querySelector('span') || {}).textContent || '' })));
    ok(tiles.length === 2 && tiles.every((x) => /^TEST-/.test(x.title) && /Reducere 50 lei · valabil până la \d\d\.\d\d\.\d{4}/.test(x.note)), `vouchere: pagina de printat are cele 2 vouchere alese, cu valoarea și data (${tiles.map((x) => x.note).join(' | ')})`);
    ok((await decode(pop, '.gi-tile .qr', 0)) === BASE + '/?gi_cod=' + tiles[0].title, 'vouchere: codul QR din pagina de printat se citește');
    await pop.close();
  }

  // ------------------------------------------------------------------ 6. Aplicarea voucherului în coș
  {
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    const code = codes[2].toUpperCase();
    await page.goto(BASE + '/?gi_cod=' + code, { waitUntil: 'load' });
    ok(/#rezervare$/.test(page.url()) || /\/$/.test(page.url().split('#')[0]), `voucher scanat: vizitatorul ajunge la rezervare (${page.url().replace(BASE, '')})`);
    const cookies = await ctx.cookies();
    const ck = cookies.find((c) => c.name === 'gi_cod');
    ok(ck && ck.value === code && ck.httpOnly, 'voucher scanat: codul e reținut într-un cookie (30 de zile, fără acces din JavaScript)');
    await page.goto(BASE + '/?add-to-cart=30', { waitUntil: 'load' });
    await page.goto(BASE + '/cart/', { waitUntil: 'load' });
    const cart = (await page.textContent('body')).replace(/\s+/g, ' ');
    ok(new RegExp(codes[2].replace(/-/g, '[- ]'), 'i').test(cart) && /50[,.]00/.test(cart), 'voucher scanat: în coș, reducerea de 50 lei e aplicată singură');
    ok((await page.locator('.cart-discount').count()) === 1 && (await page.locator('[class*="is-error"], .woocommerce-error').count()) === 0, 'voucher scanat: o singură reducere aplicată, fără mesaje de eroare');
    ok(!(await ctx.cookies()).some((c) => c.name === 'gi_cod'), 'voucher scanat: după încercare, cookie-ul se șterge (nu mai reapare nimic la pagina următoare)');
    await page.reload({ waitUntil: 'load' });
    ok((await page.locator('.cart-discount').count()) === 1 && (await page.locator('[class*="is-error"], .woocommerce-error').count()) === 0, 'voucher scanat: la reîncărcare nu apar erori sau aplicări în plus');
    // cod inventat: fără erori grave
    const ctx2 = await browser.newContext();
    const p2 = await ctx2.newPage();
    p2.setDefaultTimeout(90000);
    await p2.goto(BASE + '/?gi_cod=NU-EXISTA-1234', { waitUntil: 'load' });
    await p2.goto(BASE + '/?add-to-cart=30', { waitUntil: 'load' });
    await p2.goto(BASE + '/cart/', { waitUntil: 'load' });
    const bad = await p2.textContent('body');
    const viaRest = await (await browser.newContext()).request.get(BASE + '/wp-json/wp/v2/puncte?gi_cod=ABCD-EFGH', { maxRedirects: 0 });
    ok(viaRest.status() === 200 && !/gi_cod/.test(viaRest.headers()['set-cookie'] || ''), `?gi_cod= nu face nimic pe cererile REST (${viaRest.status()}, fără cookie)`);
    ok(!/Fatal error|Warning:|Notice:/.test(bad) && (await p2.locator('.cart-discount').count()) === 0, 'voucher inventat: coșul se deschide normal, fără erori PHP și fără reducere');
    ok(!(await ctx2.cookies()).some((c) => c.name === 'gi_cod'), 'voucher inventat: cookie-ul se șterge după încercare');
    // un cupon obișnuit WooCommerce (nu generat din zona Grădina) nu se aplică prin codul QR
    const plain = await rest('POST', '/wc/v3/coupons', { code: 'plain' + STAMP, discount_type: 'percent', amount: '50', description: 'cupon de probă obișnuit' });
    if (plain.body && plain.body.id) { created.coupons.push(plain.body.id); }
    const ctx3 = await browser.newContext();
    const p3 = await ctx3.newPage();
    p3.setDefaultTimeout(90000);
    await p3.goto(BASE + '/?gi_cod=PLAIN' + STAMP, { waitUntil: 'load' });
    await p3.goto(BASE + '/?add-to-cart=30', { waitUntil: 'load' });
    await p3.goto(BASE + '/cart/', { waitUntil: 'load' });
    ok((await p3.locator('.cart-discount').count()) === 0, 'cupon obișnuit prin codul QR: nu se aplică (doar voucherele generate din zona Grădina)');
    await ctx3.close();
    await ctx.close(); await ctx2.close();
  }

  // ------------------------------------------------------------------ 7. Accesul
  {
    const mkUser = async (role) => {
      const r = await rest('POST', '/wp/v2/users', { username: role + 'test' + STAMP, email: role + STAMP + '@example.test', password: 'Proba!' + STAMP + 'xY', roles: [role] });
      if (r.body && r.body.id) { created.users.push(r.body.id); }
      return { name: role + 'test' + STAMP, pass: 'Proba!' + STAMP + 'xY' };
    };
    const login = async (u) => {
      const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
      const p = await ctx.newPage(); p.setDefaultTimeout(90000);
      await p.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
      await p.fill('#user_login', u.name); await p.fill('#user_pass', u.pass); await p.click('#wp-submit');
      await p.waitForLoadState('load');
      return { p, ctx };
    };
    const ed = await mkUser('editor');
    const { p: pe, ctx: ce } = await login(ed);
    let r = await pe.goto(BASE + '/wp-admin/edit.php?post_type=gi_punct', { waitUntil: 'load' });
    ok(r.status() === 200 && (await pe.locator('table.wp-list-table').count()) === 1, 'acces: editorul vede „Copaci și locuri”');
    r = await pe.goto(BASE + '/wp-admin/admin.php?page=gi-qr', { waitUntil: 'load' });
    ok(r.status() === 200 && /Coduri QR de printat/.test(await pe.textContent('.wrap h1')), 'acces: editorul vede „Coduri QR de printat”');
    r = await pe.goto(BASE + '/wp-admin/admin.php?page=gi-vouchere', { waitUntil: 'load' });
    ok(r.status() === 403 || /nu ai voie|not allowed|permission/i.test(await pe.textContent('body')), `acces: editorul NU vede „Vouchere” (${r.status()})`);
    const menu = await (async () => { await pe.goto(BASE + '/wp-admin/', { waitUntil: 'load' }); return pe.evaluate(() => Array.from(document.querySelectorAll('#adminmenu a')).map((a) => a.textContent.replace(/\s+/g, ' ').trim()).join(' | ')); })();
    ok(/Grădina/.test(menu) && !/Vouchere/.test(menu), 'acces: în meniul editorului apare „Grădina”, fără „Vouchere”');
    // cod de siguranță VALID al editorului (din ecranul lui), ca să se vadă că refuzul vine din drepturi, nu din cod greșit
    await pe.goto(BASE + '/wp-admin/admin.php?page=gi-qr', { waitUntil: 'load' });
    const edNonce = await pe.inputValue('input[name="_wpnonce"]');
    const printUrl = (extra) => BASE + '/wp-admin/admin-post.php?action=gi_qr_print&_wpnonce=' + edNonce + extra;
    const couponId = created.coupons[0];
    const pr = await pe.request.get(printUrl('&tip=vouchere&ids[]=' + couponId), { maxRedirects: 0 });
    ok(pr.status() === 403 && !/gi-tile/.test(await pr.text()), `acces: editorul, cu cod de siguranță valid, nu poate printa vouchere (${pr.status()})`);
    const idor = await pe.request.get(printUrl('&tip=puncte&ids[]=' + couponId + '&ids[]=' + c3.body.id + '&ids[]=1&ids[]=30'), { maxRedirects: 0 });
    ok(idor.status() === 200 && (await idor.text()).split('class="gi-tile"').length - 1 === 0, 'acces: cu id-uri de cupon, ciornă, pagină sau produs, pagina de printat nu arată nimic');
    const genNonce = (await (await pe.request.get(BASE + '/?gi_test=nonce&n=gi_voucher_gen')).json()).nonce; // cod de siguranță VALID pentru generare: refuzul trebuie să vină din drepturi
    const gen = await pe.request.post(BASE + '/wp-admin/admin-post.php', { form: { action: 'gi_voucher_gen', _wpnonce: genNonce, tip: 'suma', valoare: '10', numar: '1', prefix: 'EDIT' }, maxRedirects: 0 });
    const afterGen = await rest('GET', '/wc/v3/coupons?per_page=20&search=EDIT');
    ok(gen.status() === 403 && afterGen.body.filter((c) => /^edit-/.test(c.code)).length === 0, `acces: editorul nu poate genera vouchere (${gen.status()})`);
    await ce.close();
    for (const role of ['author', 'contributor']) {
      const u = await mkUser(role);
      const { p: pa, ctx: ca } = await login(u);
      const rr = await pa.goto(BASE + '/wp-admin/admin.php?page=gi-qr', { waitUntil: 'load' });
      ok(rr.status() === 403 || /nu ai voie|not allowed|permission/i.test(await pa.textContent('body')), `acces: ${role === 'author' ? 'autorul' : 'colaboratorul'} nu vede zona Grădina (${rr.status()})`);
      const nonceA = (await (await pa.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
      const cr = await pa.request.post(BASE + '/wp-json/wp/v2/puncte', { headers: { 'X-WP-Nonce': nonceA }, data: { title: 'nepermis ' + role, status: 'publish' } });
      ok([401, 403].includes(cr.status()), `acces: ${role === 'author' ? 'autorul' : 'colaboratorul'} nu poate crea un loc prin REST (${cr.status()})`);
      await ca.close();
    }
    const sub = await mkUser('subscriber');
    const { p: ps, ctx: cs } = await login(sub);
    r = await ps.goto(BASE + '/wp-admin/admin.php?page=gi-qr', { waitUntil: 'load' });
    ok(r.status() === 403 || /nu ai voie|not allowed|permission/i.test(await ps.textContent('body')), `acces: un cont simplu (abonat) nu vede zona Grădina (${r.status()})`);
    await cs.close();
    const post = await (await browser.newContext()).request.post(BASE + '/wp-json/wp/v2/puncte', { data: { title: 'nepermis', status: 'publish' } });
    ok([401, 403].includes(post.status()), `acces: fără cont nu se poate crea un loc (${post.status()})`);
  }

  // ------------------------------------------------------------------ 8. Curățenie
  for (const id of created.coupons) { await rest('DELETE', '/wc/v3/coupons/' + id + '?force=true'); }
  for (const id of created.posts) { await rest('DELETE', '/wp/v2/puncte/' + id + '?force=true'); }
  for (const id of created.media) { await rest('DELETE', '/wp/v2/media/' + id + '?force=true'); }
  for (const id of created.users) { await rest('DELETE', '/wp/v2/users/' + id + '?force=true&reassign=1'); }
  const left = await rest('GET', '/wp/v2/puncte?per_page=100&search=' + STAMP + '&status=any');
  ok(Array.isArray(left.body) && left.body.length === 0, 'curățenie: locurile de probă au fost șterse');

  await browser.close();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})();
