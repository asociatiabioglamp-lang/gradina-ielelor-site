// Test pentru versiunile 1.5.0 și 1.5.1 („Zona echipei”: intrare direct de pe site, conturi de vânzări fără administrare WordPress, vouchere,
// copaci și locuri, echipă), pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md).
// NUMAI LOCAL: se autentifică cu admin/password, creează și șterge conturi, vouchere și locuri de probă.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-5-0.php și gi-test-1-3-14.php), WooCommerce activ
// și biblioteca de test jsqr (numai pentru test, nu face parte din plugin):  npm install jsqr  și  NODE_PATH să includă folderul ei node_modules.
// Variabile opționale: GI_ONLY_TAIL=1 (numai conturile de client și variantele de text din Regulament), GI_SKIP_HEAVY=1 (sare peste cele 66 de încărcări de pagini pentru ecrane și subsol, când stilurile nu s-au schimbat), GI_PHP_LOG (calea spre wp-content/gi-php-errors.log, ca să se verifice că nu apar erori PHP), GI_SHOTS (folder pentru capturi).
// Verifică:
//  - căsuța „Administrare” din subsol (pe cinci pagini, șase ecrane, cu stilurile străine): se vede, nu e acoperită, se poate atinge, duce la Zona echipei;
//  - Zona echipei fără cont: formular de intrare, nu se arată nimic, nu apare în Google (antet + meta), fără păstrare în memorii intermediare;
//  - autentificare greșită: mesaj în pagină, același mesaj pentru cont inexistent, fără blocare în plugin (limitarea rămâne la firewall, docs/securitate.md);
//  - administrator: vouchere (valoare, 18 luni, o folosire, cod unic, QR care se citește, printare), copaci și locuri (poză de pe telefon, text curățat,
//    ciornă, QR), echipă (cont nou fără email, parola arătată o singură dată), limite;
//  - colegul de la vânzări: vede numai voucherele (ale lui), în limitele setate; nu intră în administrare (nici profil, nici ecrane, nici admin-post),
//    nu poate scrie prin REST, nu vede copaci/echipă, nu poate printa voucherele altora; parola nouă îi închide sesiunile; „Scoate accesul” funcționează;
//  - ecranele Zonei pe șase mărimi, cu stilurile străine: fără derulare pe orizontală, titlul nu e sub antet, câmpuri și butoane ușor de atins, contur la tastatură.
// Rulează: NODE_PATH="$(npm root -g):<folder>/node_modules" node tools/local-wp/test-1-5-0.js     Iese cu cod 1 dacă o verificare pică.
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
const SHOTS = process.env.GI_SHOTS || '';
const PHP_LOG = process.env.GI_PHP_LOG || '';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const STAMP = String(Date.now()).slice(-6);
const ZONA = BASE + '/zona-echipei/';
const SIZES = { 'telefon îngust': [320, 656], 'telefon': [360, 740], 'telefon mare': [414, 896], 'telefon pe lat': [740, 360], 'tabletă': [820, 1180], 'calculator': [1440, 900] };
const logLines = () => { try { return PHP_LOG ? fs.readFileSync(PHP_LOG, 'utf8').split('\n').filter(Boolean).length : 0; } catch (e) { return 0; } };

// Primul cod QR din elementul dat: SVG → imagine → pixeli → jsQR. Întoarce textul din cod sau null.
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

// Măsurători pe o pagină din Zona echipei (rulează în browser).
const MEASURE = () => {
  const vw = document.documentElement.clientWidth;
  const hdr = document.querySelector('.gi-site .site-header');
  const hb = hdr ? hdr.getBoundingClientRect().bottom : 0;
  const h1 = document.querySelector('h1');
  const rg = document.createRange(); rg.selectNodeContents(h1);
  const r1 = rg.getClientRects()[0];
  const wide = new Set();
  document.querySelectorAll('.gi-zona *').forEach((el) => {
    const r = el.getBoundingClientRect();
    if (r.width && (r.right > vw + 1 || r.left < -1)) { wide.add(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0]); }
  });
  const small = [];
  document.querySelectorAll('.gi-zona :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea, button, a.button)').forEach((el) => {
    const r = el.getBoundingClientRect();
    if (r.width && r.height < 44) { small.push((el.id || el.name || el.textContent.trim().slice(0, 18)) + ' ' + Math.round(r.height)); }
  });
  document.querySelectorAll('.gi-zona :is(input[type=checkbox], input[type=radio])').forEach((el) => {
    const r = (el.closest('label') || el).getBoundingClientRect();
    if (r.width && r.height < 40) { small.push('bifă ' + Math.round(r.height)); }
  });
  const cs = (sel) => { const el = document.querySelector(sel); return el ? getComputedStyle(el) : null; };
  const p = cs('.gi-zona p'); const h2 = cs('.gi-zona h2'); const bp = cs('.gi-zona .button-primary'); const bo = cs('.gi-zona .button-outline');
  return {
    sx: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    h1Below: !r1 || r1.top >= hb - 1,
    wide: Array.from(wide), small,
    pColor: p && p.color, h2Color: h2 && h2.color,
    primary: bp && [bp.backgroundColor, bp.color], outline: bo && [bo.color],
    mobileBar: (() => { const b = document.querySelector('.gi-mobile-booking'); return b ? getComputedStyle(b).display : 'none'; })(),
    flagOk: (() => { const f = document.querySelector('#gt_float_wrapper'); if (!f) { return true; } const r = f.getBoundingClientRect(); return getComputedStyle(f).position === 'absolute' && r.left >= 0 && r.right <= vw + 1; })(),
  };
};

async function runScreens(browser, newCtx, state) {
  // ------------------------------------------------------------------ 6. Ecranele Zonei pe șase mărimi, cu stilurile străine
  const screens = [['intrare', ZONA, false], ['prima pagină', ZONA, true], ['vouchere', ZONA + 'vouchere/', true], ['copaci', ZONA + 'copaci/', true], ['loc nou', ZONA + 'copaci/nou/', true], ['echipă', ZONA + 'echipa/', true]];
  for (const [size, [w, h]] of Object.entries(SIZES)) {
    const phone = Math.min(w, h) <= 430;
    const problems = []; let primary = null;
    for (const [name, url, logged] of screens) {
      const ctx = await browser.newContext(Object.assign({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone }, logged ? { storageState: state } : {}));
      const page = await ctx.newPage(); page.setDefaultTimeout(90000);
      await page.goto(url + '?gi_test=stiluri', { waitUntil: 'load' });
      if (logged && /vouchere|copaci\/$/.test(url)) { await page.waitForSelector('.gi-qr-mini svg').catch(() => {}); }
      await page.waitForTimeout(250);
      const m = await page.evaluate(MEASURE);
      if (m.sx > 0) { problems.push(name + ': derulare pe orizontală ' + m.sx); }
      if (!m.h1Below) { problems.push(name + ': titlul sub antet'); }
      if (m.wide.length) { problems.push(name + ': iese din ecran: ' + m.wide.slice(0, 3).join(',')); }
      if (m.small.length) { problems.push(name + ': prea mic de atins: ' + m.small.slice(0, 3).join(',')); }
      if (m.mobileBar !== 'none') { problems.push(name + ': bara „Rezervă” acoperă formularele'); }
      if (!m.flagOk) { problems.push(name + ': steagul de limbă'); }
      if (m.pColor && m.pColor !== 'rgb(51, 69, 79)') { problems.push(name + ': culoarea textului (' + m.pColor + ')'); }
      if (m.h2Color && m.h2Color !== 'rgb(33, 80, 107)') { problems.push(name + ': culoarea titlurilor (' + m.h2Color + ')'); }
      if (m.primary && (m.primary[0] !== 'rgb(29, 99, 128)' || m.primary[1] !== 'rgb(255, 255, 255)')) { problems.push(name + ': butonul principal (' + m.primary.join(' / ') + ')'); }
      if (m.outline && m.outline[0] !== 'rgb(29, 99, 128)') { problems.push(name + ': butonul secundar (' + m.outline[0] + ')'); }
      if (SHOTS && (w === 390 || w === 414 || w === 1440)) { await page.screenshot({ path: path.join(SHOTS, 'ecran-' + name.replace(/\s+/g, '-') + '-' + w + '.png'), fullPage: true }); }
      await ctx.close();
    }
    ok(problems.length === 0, `Zona, ${size}: ${screens.length} ecrane cu stiluri străine: fără derulare orizontală, titlu sub antet, elemente ieșite sau prea mici, culori păstrate (${problems.join('; ') || 'în regulă'})`);
  }
  // contur la tastatură (stilurile străine îl opresc la linkuri): câmp, buton, link
  {
    const ctx = await newCtx(); const page = await ctx.newPage(); page.setDefaultTimeout(90000);
    await page.goto(ZONA + '?gi_test=stiluri', { waitUntil: 'load' });
    const seen = {};
    for (let i = 0; i < 60 && Object.keys(seen).length < 3; i++) {
      await page.keyboard.press('Tab');
      const info = await page.evaluate(() => { const a = document.activeElement; const cs = getComputedStyle(a); return { id: a.id, tag: a.tagName, cls: String(a.className), outline: cs.outlineStyle, width: parseFloat(cs.outlineWidth) }; });
      if (info.id === 'gi-zona-user' || info.id === 'gi-zona-pass') { seen.field = info; }
      if (info.tag === 'BUTTON' && /button-primary/.test(info.cls)) { seen.button = info; }
      if (info.tag === 'A' && /gi-text-link|gi-admin-box/.test(info.cls)) { seen.link = info; }
    }
    await page.evaluate(() => { const b = document.querySelector('.gi-admin-box'); if (b) { b.focus(); } }); // după tastatură, focus() cere conturul de tastatură
    seen.box = await page.evaluate(() => { const a = document.activeElement; const cs = getComputedStyle(a); return { id: a.id, tag: a.tagName, cls: String(a.className), outline: cs.outlineStyle, width: parseFloat(cs.outlineWidth) }; });
    ok(/gi-admin-box/.test(seen.box.cls) && seen.box.outline !== 'none' && seen.box.width >= 2, `acces cu tastatura: căsuța „Administrare” din subsol are contur vizibil (${seen.box.outline} ${seen.box.width})`);
    ok(['field', 'button', 'link'].every((k) => seen[k] && seen[k].outline !== 'none' && seen[k].width >= 2), `acces cu tastatura: câmpul, butonul și linkul au contur vizibil (${JSON.stringify(Object.fromEntries(Object.entries(seen).map(([k, v]) => [k, v.outline + ' ' + v.width])))})`);
    await ctx.close();
  }

}

(async () => {
  const phpBefore = logLines();
  const browser = await chromium.launch();
  const newCtx = (opts) => browser.newContext(Object.assign({ viewport: { width: 1440, height: 900 } }, opts || {}));
  const api = async (ctx, p) => (await ctx.request.get(BASE + '/?gi_test=' + p)).json();
  const created = { posts: [], coupons: [], users: [], media: [] };

  // ------------------------------------------------------------------ cont de administrare (REST pentru curățenie) și starea de pornire
  if (process.env.GI_ONLY_SCREENS) {
    // Numai ecranele Zonei (după ce s-au reparat stilurile): intră ca administrator și măsoară.
    const c = await newCtx(); const pg = await c.newPage(); pg.setDefaultTimeout(90000);
    await pg.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await pg.fill('#gi-zona-user', 'admin'); await pg.fill('#gi-zona-pass', 'password');
    await Promise.all([pg.waitForNavigation({ waitUntil: 'domcontentloaded' }), pg.click('.gi-zona-login button[type=submit]')]);
    await runScreens(browser, newCtx, await c.storageState());
    await browser.close();
    console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
    process.exit(failures ? 1 : 0);
  }
  const actx = await newCtx();
  const admin = await actx.newPage(); admin.setDefaultTimeout(90000);
  await admin.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await admin.fill('#user_login', 'admin'); await admin.fill('#user_pass', 'password');
  await Promise.all([admin.waitForURL('**/wp-admin/**'), admin.click('#wp-submit')]);
  ok(/\/wp-admin\//.test(admin.url()), 'administrare: autentificarea clasică de la wp-login.php duce în continuare în administrare (fără redirecționare spre Zona echipei)');
  const restNonce = (await (await actx.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  const RH = { 'X-WP-Nonce': restNonce };
  const rest = async (method, route, data) => {
    const r = await actx.request.fetch(BASE + '/wp-json' + route, { method, headers: RH, data });
    let body = null; try { body = await r.json(); } catch (e) { body = null; }
    return { status: r.status(), body };
  };
  await api(actx, 'zona_reset'); await api(actx, 'emailuri');
  const adminInfo = await api(actx, 'utilizator&login=admin');
  ok(adminInfo.caps.gi_zona && adminInfo.caps.gi_vouchere && adminInfo.caps.gi_vouchere_toate && adminInfo.caps.gi_copaci && adminInfo.caps.gi_echipa, 'roluri: administratorul primește drepturile Zonei echipei (zonă, vouchere, toate voucherele, copaci, echipă)');
  const nonceFor = async (ctx, action) => (await api(ctx, 'nonce&n=' + action)).nonce;

  // ------------------------------------------------------------------ 1. Căsuța „Administrare” în subsol
  if (!process.env.GI_SKIP_HEAVY) {
  const PUBLIC = ['/', '/glamping/', '/contact/', '/galerie-foto/', '/despre-noi/'];
  for (const [size, [w, h]] of Object.entries(SIZES)) {
    const phone = Math.min(w, h) <= 430;
    const ctx = await newCtx({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone });
    const page = await ctx.newPage(); page.setDefaultTimeout(90000);
    const problems = [];
    for (const p of PUBLIC) {
      const resp = await page.goto(BASE + p + '?gi_test=stiluri', { waitUntil: 'load' });
      // Pe telefon, butonul fix „Rezervă” de jos se retrage când subsolul ajunge pe ecran (cu o mică întârziere): măsurăm după ce s-a retras.
      await page.evaluate(() => { const b = document.querySelector('.gi-admin-box'); if (b) { b.scrollIntoView({ block: 'center', behavior: 'instant' }); } }); // „instant”: paginile au derulare lină
      await page.waitForTimeout(600);
      const m = await page.evaluate(() => {
        const box = document.querySelector('.gi-admin-box');
        if (!box) { return null; }
        const r = box.getBoundingClientRect();
        const top = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
        const cs = getComputedStyle(box);
        return { href: box.getAttribute('href'), rel: box.getAttribute('rel'), text: box.textContent.replace(/\s+/g, ' ').trim(), h: r.height, w: r.width, left: r.left, right: r.right, vw: document.documentElement.clientWidth, covered: !(top && (top === box || box.contains(top))), color: cs.color, bg: cs.backgroundColor, sx: document.documentElement.scrollWidth - document.documentElement.clientWidth, icon: !!box.querySelector('svg use[href="#gi-icon-lock"]') };
      });
      if (resp.status() !== 200) { problems.push(p + ': ' + resp.status()); continue; }
      if (!m) { problems.push(p + ': lipsește căsuța'); continue; }
      if (!/\/zona-echipei\/$/.test(m.href) || !/nofollow/.test(m.rel || '') || m.text !== 'Administrare' || !m.icon) { problems.push(p + ': link/text/iconiță (' + m.href + ' | ' + m.text + ')'); }
      if (m.h < 44) { problems.push(p + ': prea mică (' + Math.round(m.h) + ' px)'); }
      if (m.left < 0 || m.right > m.vw + 1 || m.sx > 0) { problems.push(p + ': iese din ecran / derulare pe orizontală'); }
      if (m.covered) { problems.push(p + ': acoperită de altceva'); }
    }
    ok(problems.length === 0, `căsuța „Administrare” — ${size}: pe ${PUBLIC.length} pagini se vede, e ușor de atins, nu e acoperită, duce la Zona echipei (${problems.join('; ') || 'în regulă'})`);
    await ctx.close();
  }
  }
  {
    const ctx = await newCtx(); const page = await ctx.newPage(); page.setDefaultTimeout(90000);
    await page.goto(BASE + '/glamping/', { waitUntil: 'load' });
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('.gi-admin-box').click()]);
    ok(/\/zona-echipei\/$/.test(page.url()) && (await page.locator('#gi-zona-user').count()) === 1, 'căsuța „Administrare”: un clic duce la formularul de intrare din Zona echipei');
    const box = await page.evaluate(() => { const b = document.querySelector('.gi-admin-box'); const cs = getComputedStyle(b); return { color: cs.color, bg: cs.backgroundColor, border: cs.borderTopColor }; });
    ok(box.border !== 'rgba(0, 0, 0, 0)' && box.color !== box.bg, `căsuța „Administrare”: se vede și pe pagina Zonei (chenar ${box.border}, text ${box.color})`);
    await ctx.close();
  }

  if (process.env.GI_ONLY_FOOTER) { await browser.close(); console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.'); process.exit(failures ? 1 : 0); }

  // ------------------------------------------------------------------ 2. Zona fără cont
  {
    const ctx = await newCtx();
    const r = await ctx.request.get(ZONA, { maxRedirects: 0 });
    const hd = r.headers();
    const html = await r.text();
    ok(r.status() === 200, 'zona fără cont: pagina se deschide (200)');
    ok(/noindex/.test(hd['x-robots-tag'] || '') && /noindex/.test((html.match(/<meta name='robots'[^>]*>|<meta name="robots"[^>]*>/) || [''])[0]), 'zona fără cont: nu apare în Google (antet X-Robots-Tag și meta robots: noindex)');
    ok(/no-store|no-cache/.test(hd['cache-control'] || ''), `zona fără cont: nu se păstrează în memorii intermediare (Cache-Control: ${hd['cache-control']})`);
    ok(/id="gi-zona-user"/.test(html) && /name="pwd"/.test(html) && /autocomplete="current-password"/.test(html) && /for="gi-zona-user"/.test(html) && /for="gi-zona-pass"/.test(html), 'zona fără cont: formular de intrare cu etichete și completare automată');
    ok(/Zona echipei \| /.test((html.match(/<title>([^<]*)<\/title>/) || [])[1] || ''), 'zona fără cont: titlul paginii e „Zona echipei | …”');
    for (const s of ['vouchere/', 'copaci/', 'copaci/nou/', 'echipa/', 'istoric/', 'tipar/?tip=vouchere&ids[]=1']) {
      const t = await (await ctx.request.get(ZONA + s)).text();
      ok(/id="gi-zona-user"/.test(t) && !/gi-zona-lista|gi-zona-coduri|gi-qr-mini|name="gi_actiune"/.test(t), `zona fără cont: /${s.split('?')[0]} arată numai formularul de intrare`);
    }
    const rr = await ctx.request.get(ZONA + 'oarecare/', { maxRedirects: 0 });
    ok(rr.status() === 302 && /\/zona-echipei\/$/.test(rr.headers().location || ''), `zona: o adresă necunoscută duce la prima pagină a Zonei (${rr.status()})`);
    const nf = await ctx.request.get(ZONA + '123/', { maxRedirects: 0 });
    ok(nf.status() === 404, `zona: o adresă care nu e a Zonei (cu număr) rămâne 404 obișnuit (${nf.status()})`);
    const post = await ctx.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'genereaza', tip: 'suma', valoare: '50', numar: '1', prefix: 'FARACONT', _gi_nonce: 'x' }, maxRedirects: 0 });
    const aft = await api(ctx, 'cupon&cod=FARACONT');
    ok([200, 302].includes(post.status()) && aft.exists === false, 'zona fără cont: trimiterea unui formular de voucher nu face nimic');
    {
      const inj = await ctx.request.get(BASE + '/glamping/?gi_zona=acasa', { maxRedirects: 0 });
      const it = await inj.text();
      ok(inj.status() === 200 && !/gi-zona-user/.test(it) && !/noindex/.test(inj.headers()['x-robots-tag'] || '') && /gi-glamping|Glamping/.test(it), 'zona: ?gi_zona=acasa pus pe altă pagină (Glamping) nu o transformă în Zonă (rămâne pagina obișnuită, fără noindex)');
      const inj404 = await ctx.request.get(BASE + '/nu-exista-' + STAMP + '/?gi_zona=acasa', { maxRedirects: 0 });
      ok(inj404.status() === 404, `zona: ?gi_zona=acasa pus pe o adresă care nu există rămâne 404 (${inj404.status()})`);
    }
    const sm = await (await ctx.request.get(BASE + '/wp-sitemap.xml')).text();
    ok(!/zona-echipei/.test(sm), 'zona: nu apare în harta site-ului');
    await ctx.close();
  }

  // ------------------------------------------------------------------ 2b. Regulament: voucherul nu se folosește parțial (hotărâre 15.11.2026)
  {
    // În baza de date locală rămâne textul vechi de pe live („Îl poți folosi și parțial…”), ca să se vadă că pluginul îl înlocuiește la afișare.
    const v = await newCtx(); const r = await v.request.get(BASE + '/regulament-politici/'); const html = await r.text();
    ok(r.status() === 200 && /Voucherul se folosește o singură dată, în întregime; nu se poate folosi parțial\. Dacă rezervarea e mai mică decât valoarea voucherului, diferența se pierde\./.test(html) && !/Îl poți folosi și parțial/.test(html) && !/suma nefolosită rămâne pe voucher/.test(html), 'regulament: „Voucherul se folosește o singură dată, în întregime; nu se poate folosi parțial. Dacă rezervarea e mai mică decât valoarea voucherului, diferența se pierde.” înlocuiește propoziția veche, iar restul paginii rămâne');
    ok(/Primești un voucher cu toată suma plătită/.test(html) && /31 decembrie/.test(html), 'regulament: celelalte reguli despre voucher (toată suma, 31 decembrie) rămân neatinse');
    await v.close();
  }

  // ------------------------------------------------------------------ 3. Autentificare greșită (fără blocare în plugin: hotărâre din docs/securitate.md)
  {
    const ctx = await newCtx(); const page = await ctx.newPage(); page.setDefaultTimeout(90000);
    await api(ctx, 'esecuri_login'); // pornim de la zero numărătoarea ascultătorului montat după cel din Zonă
    await page.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await page.fill('#gi-zona-user', 'admin'); await page.fill('#gi-zona-pass', 'parola-gresita');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('.gi-zona-login button[type=submit]')]);
    const seenFail = (await api(ctx, 'esecuri_login')).count;
    ok(seenFail === 1, `autentificare greșită: alte pluginuri (limitare, jurnal) văd eșecul, chiar dacă vine din Zonă (${seenFail})`);
    const m1 = await page.locator('.gi-zona-notice').innerText().catch(() => '');
    ok(/\/zona-echipei\/\?eroare=1/.test(page.url()) && /nu sunt corecte/.test(m1), `autentificare greșită: rămâi în Zona echipei, cu mesaj în pagină („${m1.trim()}”)`);
    await page.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await page.fill('#gi-zona-user', 'nu.exista.' + STAMP); await page.fill('#gi-zona-pass', 'oricare');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('.gi-zona-login button[type=submit]')]);
    const m2 = await page.locator('.gi-zona-notice').innerText().catch(() => '');
    ok(m2.trim() === m1.trim(), 'autentificare greșită: același mesaj pentru un cont care nu există (nu se poate afla ce conturi există)');
    // încă 10 încercări greșite, ca de la formularul de pe site: pluginul NU blochează intrarea (limitarea rămâne la firewall, docs/securitate.md)
    for (let i = 0; i < 10; i++) {
      await ctx.request.post(BASE + '/wp-login.php', { form: { log: 'admin', pwd: 'x' + i, 'wp-submit': 'Log In', redirect_to: ZONA }, headers: { Referer: ZONA }, maxRedirects: 0 });
    }
    const wp = await ctx.request.post(BASE + '/wp-login.php', { form: { log: 'admin', pwd: 'gresit', 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(wp.status() === 200 && /nu sunt corecte/.test(await wp.text()) && !/Prea multe încercări/.test(await wp.text()), 'autentificare greșită: la wp-login.php (fără referință din Zonă) rămâne pagina obișnuită, cu mesajul obișnuit');
    await page.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await page.fill('#gi-zona-user', 'admin'); await page.fill('#gi-zona-pass', 'password');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('.gi-zona-login button[type=submit]')]);
    ok(/Bună, /.test(await page.locator('.gi-zona-hello').innerText().catch(() => '')), 'autentificare: după 12 încercări greșite, parola corectă intră (pluginul nu blochează conturile; limitarea e la firewall)');
    await ctx.close();
  }

  // ------------------------------------------------------------------ 4. Administratorul în Zonă
  const adm = await newCtx(); const ap = await adm.newPage(); ap.setDefaultTimeout(90000);
  const submit = async (page, sel) => { await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click(sel)]); };
  const credChecks = async () => {
    // utilizatorul și parola alese de proprietară (1.5.1)
    const L = 'alesul.' + STAMP, PW = 'Aleasa-Cheie-' + STAMP;
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.click('[data-gi-genereaza="#gi-t-parola"]');
    const gen = await ap.inputValue('#gi-t-parola');
    ok(/^[a-km-zA-HJ-NP-Z2-9]{16}$/.test(gen), `echipă: butonul „Generează” pune o parolă aleatoare de 16 caractere (${gen.length} caractere)`);
    await ap.fill('#gi-t-nume', 'Ales ' + STAMP); await ap.fill('#gi-t-email', L + '@example.com'); await ap.fill('#gi-t-login', L); await ap.fill('#gi-t-parola', PW);
    await submit(ap, 'button:has-text("Creează contul")');
    const tx = await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText().catch(() => '');
    created.users.push(L);
    ok(tx.includes('Utilizator: ' + L) && tx.includes('Parola: ' + PW), 'echipă: contul se creează cu utilizatorul și parola alese de proprietară');
    const lg = await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: L, pwd: PW, 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(lg.status() === 302 && (lg.headers().location || '') === ZONA, 'echipă: colegul intră cu utilizatorul și parola alese, și ajunge în Zonă');
    ok((await api(adm, 'cauta_optiuni&q=' + encodeURIComponent(PW))).count === 0 && (await api(adm, 'cauta_istoric&q=' + encodeURIComponent(PW))).count === 0, 'echipă: parola aleasă nu rămâne în baza de date, nici în istoric');
    const tryCreate = async (fields) => {
      await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
      await ap.fill('#gi-t-nume', 'Alt ' + STAMP); await ap.fill('#gi-t-email', fields.email); await ap.fill('#gi-t-login', fields.login); await ap.fill('#gi-t-parola', fields.parola);
      await submit(ap, 'button:has-text("Creează contul")');
      return (await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ');
    };
    ok(/există deja/.test(await tryCreate({ email: 'alt.' + STAMP + '@example.com', login: L, parola: 'Alta-Parola-Lunga-' + STAMP })), 'echipă: un utilizator care există deja e refuzat');
    ok(/cel puțin 12/.test(await tryCreate({ email: 'alt2.' + STAMP + '@example.com', login: 'altul.' + STAMP, parola: 'scurta' })), 'echipă: o parolă prea scurtă e refuzată');
    ok(/la fel cu utilizatorul/.test(await tryCreate({ email: 'alt3.' + STAMP + '@example.com', login: 'identic.' + STAMP, parola: 'identic.' + STAMP })), 'echipă: parola identică cu utilizatorul e refuzată');
    ok(/prea ușor de ghicit/.test(await tryCreate({ email: 'alt5.' + STAMP + '@example.com', login: 'usor1.' + STAMP, parola: 'Gradina-2026-Cheie' })), 'echipă: o parolă cu numele Grădinii e refuzată (prea ușor de ghicit)');
    ok(/prea ușor de ghicit/.test(await tryCreate({ email: 'alt6.' + STAMP + '@example.com', login: 'usor2.' + STAMP, parola: '123456789012' })), 'echipă: o parolă numai din cifre e refuzată');
    ok(/3–60 de caractere/.test(await tryCreate({ email: 'alt4.' + STAMP + '@example.com', login: 'ab', parola: 'Parola-Lunga-' + STAMP })), 'echipă: un utilizator prea scurt e refuzat');
    ok((await api(adm, 'utilizator&login=altul.' + STAMP)).exists === false && (await api(adm, 'utilizator&login=identic.' + STAMP)).exists === false, 'echipă: la o cerere refuzată nu se creează niciun cont');
    // „Parolă nouă” cu parola scrisă de proprietară, inclusiv cu bară inversă și ghilimele
    const PW2 = 'Noua\\Cheie"cu\'semne-' + STAMP;
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.locator('.gi-zona-lista li:has-text("' + L + '") input[name="parola_noua"]').fill(PW2);
    await submit(ap, '.gi-zona-lista li:has-text("' + L + '") button:has-text("Parolă nouă")');
    const tx2 = await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText().catch(() => '');
    ok(tx2.includes('Parola: ' + PW2), 'echipă: „Parolă nouă” cu parola scrisă de proprietară (cu \\ și ghilimele) se arată exact');
    const lgA = await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: L, pwd: PW, 'wp-submit': 'Log In' }, maxRedirects: 0 });
    const lgB = await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: L, pwd: PW2, 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(lgA.status() === 200 && lgB.status() === 302, `echipă: parola veche nu mai merge (${lgA.status()}), cea nouă, cu semne speciale, merge (${lgB.status()})`);
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.locator('.gi-zona-lista li:has-text("' + L + '") input[name="parola_noua"]').fill('scurta');
    await ap.evaluate(() => { const i = document.querySelector('input[name="parola_noua"]'); i.closest('form').noValidate = true; });
    await submit(ap, '.gi-zona-lista li:has-text("' + L + '") button:has-text("Parolă nouă")');
    ok(/cel puțin 12/.test((await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ')) && (await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: L, pwd: PW2, 'wp-submit': 'Log In' }, maxRedirects: 0 })).status() === 302, 'echipă: „Parolă nouă” cu parolă scurtă e refuzată, iar parola de până atunci rămâne');
  };

  const histChecks = async () => {
    // ---- ștergerea voucherelor (1.5.1)
    const del = flashCodes[1], used = flashCodes[0];
    await ap.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    ok((await ap.locator('button:has-text("Șterge voucherele alese")').count()) === 1, 'vouchere: administratorul are butonul „Șterge voucherele alese”');
    await ap.locator('.gi-zona-lista li', { hasText: del }).locator('input[name="ids[]"]').check();
    await submit(ap, 'button:has-text("Șterge voucherele alese")');
    const m1 = (await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ');
    ok(new RegExp('Am șters voucherul ' + del).test(m1) && (await api(adm, 'cupon&cod=' + del)).exists === false, `vouchere: voucherul ales se șterge (${del}) și nu mai poate fi folosit`);
    ok((await ap.locator('.gi-zona-lista li', { hasText: del }).count()) === 0 && (await ap.locator('.gi-zona-lista li', { hasText: used }).count()) === 1, 'vouchere: lista nu-l mai arată; celălalt voucher rămâne');
    const printDeleted = await (await adm.request.get(ZONA + 'tipar/?tip=vouchere&ids[]=' + cp[1].id + '&_gi_nonce=' + (await nonceFor(adm, 'gi_zona_tipar')))).text();
    ok(!/class="gi-tile"/.test(printDeleted), 'vouchere: un voucher șters nu se mai poate printa nici cu linkul făcut de mână');
    await api(adm, 'folosit&cod=' + used);
    await ap.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    await ap.locator('.gi-zona-lista li', { hasText: used }).locator('input[name="ids[]"]').check();
    await submit(ap, 'button:has-text("Șterge voucherele alese")');
    const m2 = (await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ');
    ok(/deja folosit/.test(m2) && (await api(adm, 'cupon&cod=' + used)).exists === true, 'vouchere: un voucher folosit NU se șterge (mesaj clar, rămâne ca dovadă)');
    const plainCode = 'SIMPLU' + STAMP; const plain = await api(adm, 'cupon_simplu&cod=' + plainCode);
    if (plain.id) { created.coupons.push(plain.id); }
    const nSt = await nonceFor(adm, 'gi_zona_sterge');
    await adm.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'sterge', 'ids[]': String(plain.id), _gi_nonce_sterge: nSt }, maxRedirects: 0 });
    ok((await api(adm, 'cupon&cod=' + plainCode)).exists === true && /nu s-a putut șterge|nu s-au putut șterge/.test(await (await adm.request.get(ZONA + 'vouchere/')).text()), 'vouchere: un cupon care nu e din Zonă (făcut altfel) nu se poate șterge de aici');
    await adm.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'sterge', 'ids[]': String(cp[0].id), _gi_nonce_sterge: await nonceFor(adm, 'gi_zona_genereaza') }, maxRedirects: 0 });
    ok(/Pagina a expirat/.test(await (await adm.request.get(ZONA + 'vouchere/')).text()) && (await api(adm, 'cupon&cod=' + used)).exists === true, 'vouchere: ștergerea cu un cod de siguranță de la altă acțiune nu face nimic');

    // ---- istoricul (1.5.1)
    const rows = (await api(adm, 'istoric&n=100')).rows;
    const by = (a) => rows.filter((r) => r.actiune === a);
    ok(by('voucher_creat').some((r) => /TESTZ/.test(r.detalii) && /admin/.test(r.utilizator) && r.detalii.includes(del) && r.detalii.includes(used)), 'istoric: generarea voucherelor e înregistrată: cine, ce, cu toate codurile');
    ok(by('voucher_creat').some((r) => Number(r.user_id) === u1.id && /VANZ/.test(r.detalii)), 'istoric: și generarea făcută de colegul de la vânzări, pe numele lui');
    ok(by('voucher_sters').some((r) => r.obiect === del && /admin/.test(r.utilizator)), 'istoric: ștergerea e înregistrată (cine, ce cod)');
    ok(!by('voucher_sters').some((r) => r.obiect === used || r.obiect === plainCode.toUpperCase()), 'istoric: voucherul folosit și cuponul străin nu s-au șters, deci nu apar ca șterse');
    for (const a of ['cont_creat', 'cont_parola', 'cont_scos', 'cont_reactivat', 'cont_rol', 'cont_limite']) {
      ok(by(a).length >= 1, 'istoric: evenimentul „' + a + '” e înregistrat');
    }
    ok(by('loc_creat').some((r) => /Stejarul zonei/.test(r.detalii)) && by('loc_modificat').length >= 1, 'istoric: crearea și modificarea unui loc sunt înregistrate');
    const secrets = [cred.pass, cred2.pass, 'Cl13nt-' + STAMP + 'x'];
    let leak = 0; for (const sct of secrets) { leak += (await api(adm, 'cauta_istoric&q=' + encodeURIComponent(sct))).count; }
    ok(leak === 0, 'istoric: nicio parolă nu apare în istoric');
    await ap.goto(ZONA, { waitUntil: 'domcontentloaded' });
    ok((await ap.locator('.gi-zona-tile strong', { hasText: 'Istoric' }).count()) === 1, 'istoric: administratorul are dala „Istoric” pe prima pagină');
    await ap.goto(ZONA + 'istoric/', { waitUntil: 'domcontentloaded' });
    const ht = await ap.locator('.gi-zona').innerText();
    const hh = await ap.content();
    ok(/A generat 2 vouchere de 50 lei/.test(ht) && new RegExp('A șters voucherul ' + del).test(ht) && /A creat contul colegului/.test(ht) && /A adăugat locul/.test(ht) && /A dat o parolă nouă lui/.test(ht), 'istoric (pagina): se văd generarea, ștergerea, contul creat, locul adăugat și parola nouă dată, în română');
    ok(hh.includes('gi-zona-coduri-istoric') && hh.split(del).length > 2, 'istoric (pagina): codul voucherului șters rămâne în istoric, în lista codurilor generate');
    await ap.goto(ZONA + 'istoric/?tip=cont_', { waitUntil: 'domcontentloaded' });
    const ct = await ap.locator('.gi-zona-tabel').innerText();
    ok(/A creat contul colegului/.test(ct) && !/vouchere de/.test(ct) && !/A adăugat locul/.test(ct), 'istoric (pagina): filtrul „Conturi” arată numai conturile');
    ok((await ap.locator('.gi-zona-tabel td:first-child').first().innerText()).match(/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/) !== null, 'istoric (pagina): data și ora sunt în formatul zz.ll.aaaa hh:mm');
    ok((await ap.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)) <= 0, 'istoric (pagina): fără derulare pe orizontală');
    // ora afișată e ora site-ului (nu ora GMT, nici ora dublată): cu fusul Europe/Bucharest, un eveniment nou apare la ora de acolo
    await api(adm, 'fus&z=Europe/Bucharest');
    try {
      await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
      await submit(ap, 'button:has-text("Salvează limitele")');
      const lastRow = (await api(adm, 'istoric&n=1')).rows[0];
      const gmtNow = new Date();
      const stored = new Date(lastRow.creat.replace(' ', 'T') + 'Z');
      ok(lastRow.actiune === 'cont_limite' && Math.abs(gmtNow - stored) < 3 * 60 * 1000, `istoric: ora se păstrează în GMT (${lastRow.creat}, acum GMT ${gmtNow.toISOString().slice(0, 16).replace('T', ' ')})`);
      await ap.goto(ZONA + 'istoric/', { waitUntil: 'domcontentloaded' });
      const shown = (await ap.locator('.gi-zona-tabel td:first-child').first().innerText()).trim();
      const parts = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Bucharest', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(gmtNow).reduce((o, x) => { o[x.type] = x.value; return o; }, {});
      const expect = `${parts.day}.${parts.month}.${parts.year} ${parts.hour === '24' ? '00' : parts.hour}:${parts.minute}`;
      const toMin = (s) => { const m = s.match(/^(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2})$/); return m ? Date.UTC(+m[3], +m[2] - 1, +m[1], +m[4], +m[5]) / 60000 : NaN; };
      ok(Math.abs(toMin(shown) - toMin(expect)) <= 3, `istoric: ora afișată e ora site-ului (se vede ${shown}, la București ar fi ${expect})`);
    } finally {
      await api(adm, 'fus&z=');
    }
  };

  const tailChecks = async () => {
  {
    // un cont de client existent (cineva din echipă care a rezervat și el): primește rolul de vânzări și își păstrează parola
    const cl = 'client.test.' + STAMP, cmail = cl + '@example.com', cpass = 'Cl13nt-' + STAMP + 'x';
    const made = await api(adm, 'creeaza_client&login=' + cl + '&email=' + encodeURIComponent(cmail) + '&parola=' + encodeURIComponent(cpass));
    if (made.ok) { created.users.push(cl); }
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.fill('#gi-t-nume', 'Client ' + STAMP); await ap.fill('#gi-t-email', cmail);
    await submit(ap, 'button:has-text("Creează contul")');
    const nt = (await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ');
    const uc = await api(adm, 'utilizator&login=' + cl);
    ok(made.ok && /cont de client/.test(nt) && uc.roles.includes('customer') && uc.roles.includes('gi_vanzari') && uc.caps.gi_vouchere && !uc.caps.edit_posts && !/Parola:/.test(nt), 'echipă: un cont de client existent primește rolul de vânzări (păstrează „client”), fără parolă nouă afișată');
    const lg = await newCtx();
    const rr = await lg.request.post(BASE + '/wp-login.php', { form: { log: cl, pwd: cpass, 'wp-submit': 'Log In', redirect_to: ZONA + '../wp-admin/' }, maxRedirects: 0 });
    ok(rr.status() === 302 && (rr.headers().location || '') === ZONA, `echipă: clientul intră cu parola lui obișnuită și ajunge în Zonă; un redirect_to cu „..” nu-l scoate din Zonă (${(rr.headers().location || '').replace(BASE, '')})`);
    await lg.close();
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.fill('#gi-t-nume', 'Client ' + STAMP); await ap.fill('#gi-t-email', cmail);
    await submit(ap, 'button:has-text("Creează contul")');
    ok(/deja în echipă/.test((await ap.locator('.gi-zona-notice').allInnerTexts()).join(' ')), 'echipă: același email încă o dată, când e deja în echipă → „Acest coleg e deja în echipă.”');
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await submit(ap, '.gi-zona-lista li:has-text("' + cl + '") button:has-text("Scoate accesul")'); // pe un cont existent, lista arată numele lui, nu pe cel scris la „Adaugă”
    const uc2 = await api(adm, 'utilizator&login=' + cl);
    ok(uc2.roles.join() === 'customer', `echipă: „Scoate accesul” scoate numai rolul de vânzări; contul de client rămâne (${JSON.stringify(uc2.roles)})`);
  }
  {
    // propoziția despre folosirea parțială: variante de marcaj (spațiu dur, sedilă, în mijlocul paragrafului, text invalid)
    const NOU = 'Voucherul se folosește o singură dată, în întregime; nu se poate folosi parțial. Dacă rezervarea e mai mică decât valoarea voucherului, diferența se pierde.';
    const f = async (html) => (await api(adm, 'filtru_voucher&html=' + encodeURIComponent(html)));
    const v1 = await f('<li>Îl poți folosi și parțial: suma nefolosită rămâne pe voucher, până la aceeași dată.</li>');
    ok(v1.out === '<li>' + NOU + '</li>', 'regulament (filtru): marcajul de pe site (<li>) se înlocuiește exact, fără resturi');
    const v2 = await f('<li>Îl\u00a0poți folosi\u00a0și parțial: suma nefolosită rămâne pe voucher.</li>');
    ok(v2.out === '<li>' + NOU + '</li>', 'regulament (filtru): cu spații dure în propoziție');
    const v3 = await f('<li>Îl poţi folosi şi parţial: suma nefolosită rămâne pe voucher.</li>');
    ok(v3.out === '<li>' + NOU + '</li>', 'regulament (filtru): cu diacritice cu sedilă (ţ, ş)');
    const v4 = await f('<p>Prima. Îl poți folosi și parțial: suma nefolosită rămâne pe voucher. Altă propoziție rămâne.</p>');
    ok(/Altă propoziție rămâne\./.test(v4.out) && /^<p>Prima\. Voucherul se folosește/.test(v4.out) && !/rămâne pe voucher\./.test(v4.out.replace(NOU, '')), 'regulament (filtru): în mijlocul paragrafului nu se pierde următoarea propoziție');
    const v5 = await f('<p>Alt text cu parțial în el, fără propoziția cerută.</p>');
    ok(v5.out === '<p>Alt text cu parțial în el, fără propoziția cerută.</p>', 'regulament (filtru): alt text cu „parțial” rămâne neschimbat');
    const v6 = await f('<p>parțial \ufffd Îl poți folosi și parțial: x.</p>');
    ok(v6.out.length > 10, 'regulament (filtru): textul cu caractere ciudate nu dispare');
  }
  };
  await ap.goto(ZONA, { waitUntil: 'domcontentloaded' });
  await ap.fill('#gi-zona-user', 'admin'); await ap.fill('#gi-zona-pass', 'password');
  await submit(ap, '.gi-zona-login button[type=submit]');
  ok(/\/zona-echipei\/$/.test(ap.url()), 'administrator: după autentificare ajunge în Zona echipei');
  if (process.env.GI_ONLY_TAIL) {
    // Numai conturile de client și variantele de text din Regulament (pentru reluări rapide).
    ap.on('dialog', (d) => d.accept());
    await tailChecks();
    for (const login of created.users) { await api(actx, 'sterge_utilizator&login=' + login); }
    await browser.close();
    console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
    process.exit(failures ? 1 : 0);
  }
  const tiles = await ap.locator('.gi-zona-tile strong').allInnerTexts();
  ok(tiles.join('|') === 'Vouchere|Copaci și locuri|Echipă|Istoric', `administrator: pagina Zonei arată cele patru dale (${tiles.join(' | ')})`);
  ok((await ap.locator('.gi-zona-foot a', { hasText: 'Administrarea WordPress' }).count()) === 1, 'administrator: are și linkul „Administrarea WordPress”');
  ok((await ap.locator('#wpadminbar').count()) === 1, 'administrator: bara de administrare WordPress rămâne');
  if (SHOTS) { fs.mkdirSync(SHOTS, { recursive: true }); await ap.screenshot({ path: path.join(SHOTS, 'zona-acasa-admin.png'), fullPage: true }); }

  // --- 4a. Vouchere
  await ap.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  await ap.check('input[name=tip][value=suma]');
  await ap.fill('#gi-v-valoare', '50'); await ap.fill('#gi-v-numar', '2'); await ap.fill('#gi-v-prefix', 'TESTZ'); await ap.fill('#gi-v-nota', 'probă zona ' + STAMP);
  await submit(ap, 'button:has-text("Generează codurile")');
  const flashCodes = (await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText()).split('\n').map((x) => x.trim()).filter(Boolean);
  ok(flashCodes.length === 2 && flashCodes.every((c) => /^TESTZ-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(c)) && flashCodes[0] !== flashCodes[1], `vouchere: două coduri unice, cu începutul ales (${flashCodes.join(', ')})`);
  const cp = [];
  for (const c of flashCodes) { const d = await api(adm, 'cupon&cod=' + c); cp.push(d); if (d.id) { created.coupons.push(d.id); } }
  ok(cp.every((d) => d.individual === false), 'vouchere: codurile proprietarei se pot cumula cu alte reduceri (fără „folosire individuală”)');
  const now = Date.now() / 1000; const month = 30.44 * 86400;
  ok(cp.every((d) => d.amount === 50 && d.type === 'fixed_cart' && d.limit === 1 && d.creator === adminInfo.id && /probă zona/.test(d.description)), 'vouchere: 50 lei, sumă fixă, o singură folosire, cine l-a generat e înregistrat, nota se păstrează');
  ok(cp.every((d) => Math.abs((d.expires - now) / month - 18) < 0.2), `vouchere: valabile 18 luni de azi (${((cp[0].expires - now) / month).toFixed(2)} luni)`);
  await ap.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  await ap.waitForSelector('.gi-qr-mini svg');
  const listed = await ap.locator('.gi-zona-lista .gi-zona-cod').allInnerTexts();
  ok(flashCodes.every((c) => listed.includes(c)) && (await ap.locator('.gi-zona-card h2', { hasText: 'Toate voucherele' }).count()) === 1, 'vouchere: lista „Toate voucherele” arată codurile generate');
  const idx = listed.indexOf(flashCodes[0]);
  ok((await decode(ap, '.gi-qr-mini', idx)) === BASE + '/?gi_cod=' + flashCodes[0], 'vouchere: codul QR din listă se citește și duce la adresa cu codul voucherului');
  ok(/Creat la \d{2}\.\d{2}\.\d{4} \d{2}:\d{2} · generat de /.test(await ap.locator('.gi-zona-lista li', { hasText: flashCodes[0] }).innerText()), 'vouchere: administratorul vede când a fost creat fiecare voucher și cine l-a generat');
  // printare
  await ap.locator('.gi-zona-lista li', { hasText: flashCodes[0] }).locator('input[name="ids[]"]').check();
  await ap.locator('.gi-zona-lista li', { hasText: flashCodes[1] }).locator('input[name="ids[]"]').check();
  await ap.selectOption('#gi-v-size', '8');
  const [pop] = await Promise.all([adm.waitForEvent('page'), ap.click('button:has-text("Printează voucherele alese")')]);
  await pop.waitForLoadState('load'); await pop.waitForSelector('.gi-tile .qr svg');
  ok((await pop.locator('.gi-tile').count()) === 2 && !(await pop.locator('#wpadminbar, .site-header').count()), 'vouchere: pagina de printat are cele două coduri și nimic din meniurile site-ului');
  ok((await decode(pop, '.gi-tile .qr', 0)) === BASE + '/?gi_cod=' + (await pop.locator('.gi-tile strong').nth(0).innerText()), 'vouchere: codul QR din pagina de printat se citește și duce la voucherul de sub el');
  ok(/--w:\s*8cm/.test(await pop.locator('style').first().innerText()), 'vouchere: mărimea aleasă (8 cm) ajunge în pagina de printat');
  const popHead = await adm.request.get(pop.url());
  ok(/noindex/.test(popHead.headers()['x-robots-tag'] || '') && /no-store|no-cache/.test(popHead.headers()['cache-control'] || ''), 'vouchere: pagina de printat nu e indexată și nu se păstrează în memorie');
  const popUrl = new URL(pop.url());
  await pop.close();
  const noNonce = await adm.request.get(ZONA + 'tipar/?tip=vouchere&ids[]=' + cp[0].id);
  ok(noNonce.status() === 403, `vouchere: printarea fără cod de siguranță e refuzată (${noNonce.status()})`);
  const badTip = await adm.request.get(ZONA + 'tipar/?tip=altceva&_gi_nonce=' + popUrl.searchParams.get('_gi_nonce'));
  ok(badTip.status() === 403, `vouchere: un tip de printare necunoscut e refuzat (${badTip.status()})`);
  // erori la date greșite (server): nu se creează nimic
  const rawGen = async (ctx, fields) => {
    const n = await nonceFor(ctx, 'gi_zona_genereaza');
    const r = await ctx.request.post(ZONA + 'vouchere/', { form: Object.assign({ gi_actiune: 'genereaza', _gi_nonce: n }, fields), maxRedirects: 0 });
    const t = await (await ctx.request.get(ZONA + 'vouchere/')).text();
    return { status: r.status(), text: t, errors: (t.match(/gi-zona-err[^>]*>[\s\S]*?<\/div>/) || [''])[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() };
  };
  for (const [what, f, re] of [
    ['valoare 0', { tip: 'suma', valoare: '0', numar: '1', prefix: 'EROARE' }, /valoarea reducerii/],
    ['procent peste 100', { tip: 'procent', valoare: '150', numar: '1', prefix: 'EROARE' }, /Procentul nu poate/],
    ['0 coduri', { tip: 'suma', valoare: '10', numar: '0', prefix: 'EROARE' }, /între 1 și 100/],
    ['101 coduri', { tip: 'suma', valoare: '10', numar: '101', prefix: 'EROARE' }, /între 1 și 100/],
    ['început prea scurt', { tip: 'suma', valoare: '10', numar: '1', prefix: 'a' }, /între 2 și 10/],
  ]) {
    const r = await rawGen(adm, f);
    ok(re.test(r.errors) && (await api(adm, 'cupon&cod=EROARE')).exists === false, `vouchere: ${what} → mesaj clar, nu se creează nimic (${r.errors.slice(0, 70)})`);
  }
  {
    const r = await adm.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'genereaza', tip: 'suma', valoare: '10', numar: '1', prefix: 'FARANONCE' }, maxRedirects: 0 });
    const t = await (await adm.request.get(ZONA + 'vouchere/')).text();
    ok(/Pagina a expirat/.test(t) && (await api(adm, 'cupon&cod=FARANONCE')).exists === false, 'vouchere: fără cod de siguranță (pagină expirată) nu se creează nimic');
    const wrong = await nonceFor(adm, 'gi_zona_salveaza');
    await adm.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'genereaza', tip: 'suma', valoare: '10', numar: '1', prefix: 'ALTNONCE', _gi_nonce: wrong }, maxRedirects: 0 });
    const t2 = await (await adm.request.get(ZONA + 'vouchere/')).text();
    ok(/Pagina a expirat/.test(t2) && (await api(adm, 'cupon&cod=ALTNONCE')).exists === false, 'vouchere: codul de siguranță de la altă acțiune (copaci) nu merge la vouchere');
  }

  // --- 4b. Copaci și locuri, cu poză de pe telefon
  await ap.goto(ZONA + 'copaci/', { waitUntil: 'domcontentloaded' });
  await ap.click('a:has-text("Adaugă un loc")');
  await ap.waitForSelector('#gi-p-titlu');
  const T1 = 'Stejarul zonei ' + STAMP;
  await ap.fill('#gi-p-titlu', T1); await ap.selectOption('#gi-p-tip', 'copac'); await ap.fill('#gi-p-st', 'Quercus robur');
  await ap.fill('#gi-p-rez', 'Are peste 200 de ani.');
  await ap.fill('#gi-p-text', 'Prima propoziție.\n\nA doua propoziție.<script>window.__xss=1</script><img src=x onerror="window.__xss=2">');
  await ap.setInputFiles('#gi-p-foto', path.join(IMG, 'intrare-munte-960.webp'));
  await ap.fill('#gi-p-alt', 'Stejarul, văzut de pe cărare');
  ok(await ap.locator('input[name=publica]').isChecked(), 'copaci: un loc nou e „Publică” din start');
  await submit(ap, 'button:has-text("Salvează")');
  const placeId = Number((ap.url().match(/copaci\/(\d+)\//) || [])[1] || 0);
  if (placeId) { created.posts.push(placeId); }
  ok(placeId > 0 && /Salvat\./.test(await ap.locator('.gi-zona-notice').first().innerText()), `copaci: locul se salvează (id ${placeId}) și apare „Salvat.”`);
  const place = (await rest('GET', '/wp/v2/puncte/' + placeId + '?context=edit')).body || {};
  if (place.featured_media) { created.media.push(place.featured_media); }
  ok(place.status === 'publish' && place.meta && place.meta._gi_tip === 'copac' && place.meta._gi_stiintific === 'Quercus robur' && /Are peste 200/.test(place.excerpt.raw), 'copaci: stare, tip, nume științific și rezumat salvate');
  ok(place.featured_media > 0, 'copaci: poza încărcată de pe formular e poza locului');
  ok(!/<script|onerror/i.test(place.content.raw) && /Prima propoziție/.test(place.content.raw), 'copaci: scripturile din text sunt scoase, textul rămâne');
  const media = place.featured_media ? (await rest('GET', '/wp/v2/media/' + place.featured_media)).body : null;
  ok(media && media.alt_text === 'Stejarul, văzut de pe cărare', 'copaci: textul „ce se vede în poză” ajunge ca text alternativ al pozei');
  await ap.waitForSelector('.gi-zona-qr svg');
  ok((await decode(ap, '.gi-zona-qr', 0)) === place.link + '?utm_source=qr&utm_medium=print&utm_campaign=gradina', 'copaci: codul QR din formular se citește și duce la pagina locului, cu sursa „qr / print”');
  ok((await ap.locator('img.gi-zona-foto').count()) === 1, 'copaci: după salvare se vede poza încărcată');
  {
    const v = await newCtx(); const vr = await v.request.get(place.link); const vt = await vr.text();
    ok(vr.status() === 200 && new RegExp(T1).test(vt) && /A doua propoziție/.test(vt) && /gi-punct-foto/.test(vt) && !/<script[^>]*>\s*window\.__xss|onerror=/.test(vt), 'copaci: pagina publică a locului se vede vizitatorilor, cu poză, fără cod străin');
    await v.close();
  }
  const [pop2] = await Promise.all([adm.waitForEvent('page'), ap.click('a:has-text("Printează acest cod")')]);
  await pop2.waitForLoadState('load'); await pop2.waitForSelector('.gi-tile .qr svg');
  ok((await decode(pop2, '.gi-tile .qr', 0)) === place.link + '?utm_source=qr&utm_medium=print&utm_campaign=gradina', 'copaci: pagina de printat are codul locului și se citește');
  await pop2.close();
  // listă + editare + ciornă
  await ap.goto(ZONA + 'copaci/', { waitUntil: 'domcontentloaded' });
  await ap.waitForSelector('.gi-qr-mini svg');
  ok((await ap.locator('.gi-zona-lista li', { hasText: T1 }).count()) === 1, 'copaci: locul apare în listă, cu codul lui');
  await ap.click('.gi-zona-lista a:has-text("' + T1 + '")');
  await ap.waitForSelector('#gi-p-titlu');
  ok((await ap.inputValue('#gi-p-titlu')) === T1 && (await ap.inputValue('#gi-p-st')) === 'Quercus robur' && (await ap.inputValue('#gi-p-alt')) === 'Stejarul, văzut de pe cărare', 'copaci: formularul de modificare arată datele salvate');
  await ap.fill('#gi-p-rez', 'Rezumat nou.'); await ap.uncheck('input[name=publica]');
  await submit(ap, 'button:has-text("Salvează")');
  const draft = (await rest('GET', '/wp/v2/puncte/' + placeId + '?context=edit')).body;
  ok(draft.status === 'draft' && /Rezumat nou/.test(draft.excerpt.raw) && draft.featured_media === place.featured_media, 'copaci: modificarea se salvează, ciorna păstrează poza');
  {
    const v = await newCtx(); const vr = await v.request.get(place.link, { maxRedirects: 0 });
    ok(vr.status() === 404 || vr.status() === 301, `copaci: o ciornă nu se vede vizitatorilor (${vr.status()})`);
    await v.close();
  }
  await ap.goto(ZONA + 'copaci/', { waitUntil: 'domcontentloaded' });
  const dr = ap.locator('.gi-zona-lista li', { hasText: T1 });
  ok(/ciornă/.test(await dr.innerText()) && (await dr.locator('input[name="ids[]"]').count()) === 0, 'copaci: ciorna e marcată în listă și nu se poate printa');
  const nonce2 = await nonceFor(adm, 'gi_zona_tipar');
  const emptySheet = await (await adm.request.get(ZONA + 'tipar/?tip=copaci&ids[]=' + placeId + '&_gi_nonce=' + nonce2)).text();
  ok(/Nu ai ales niciun cod/.test(emptySheet) && !/class="gi-tile"/.test(emptySheet), 'copaci: o ciornă nu iese nici dacă linkul de printare e făcut de mână');
  // poză nepotrivită și poză prea mare
  await ap.goto(ZONA + 'copaci/' + placeId + '/', { waitUntil: 'domcontentloaded' });
  await ap.check('input[name=publica]');
  await ap.setInputFiles('#gi-p-foto', { name: 'nu-e-poza.txt', mimeType: 'text/plain', buffer: Buffer.from('nu sunt o poza') });
  await submit(ap, 'button:has-text("Salvează")');
  const notice = await ap.locator('.gi-zona-notice').allInnerTexts();
  ok(notice.some((x) => /nu a fost acceptată/.test(x)) && (await rest('GET', '/wp/v2/puncte/' + placeId + '?context=edit')).body.status === 'publish', 'copaci: un fișier care nu e poză e refuzat cu mesaj, iar restul se salvează');
  await ap.goto(ZONA + 'copaci/' + placeId + '/', { waitUntil: 'domcontentloaded' });
  await ap.setInputFiles('#gi-p-foto', { name: 'mare.jpg', mimeType: 'image/jpeg', buffer: Buffer.alloc(9 * 1024 * 1024, 1) });
  await submit(ap, 'button:has-text("Salvează")');
  ok((await ap.locator('.gi-zona-notice').allInnerTexts()).some((x) => /8 MB|mare/.test(x)), 'copaci: o poză prea mare e refuzată cu un mesaj clar');
  {
    const r = await adm.request.post(ZONA + 'copaci/nou/', { form: { gi_actiune: 'salveaza', titlu: 'FARA CODUL ' + STAMP, publica: '1' }, maxRedirects: 0 });
    const q = await rest('GET', '/wp/v2/puncte?search=' + encodeURIComponent('FARA CODUL ' + STAMP) + '&status=any&context=edit');
    ok(Array.isArray(q.body) && q.body.length === 0, 'copaci: fără cod de siguranță nu se creează nimic');
  }
  const htmlReq = await adm.request.post(ZONA + 'copaci/nou/', { form: { gi_actiune: 'salveaza', titlu: '   ', _gi_nonce: await nonceFor(adm, 'gi_zona_salveaza') }, maxRedirects: 0 });
  const htmlTxt = await (await adm.request.get(ZONA + 'copaci/nou/')).text();
  ok(/Scrie titlul locului/.test(htmlTxt), 'copaci: titlu gol → mesaj clar');

  {
    const ini = await api(adm, 'ini');
    const toBytes = (v) => { const n = parseFloat(v); const u = String(v).slice(-1).toUpperCase(); return u === 'G' ? n * 1073741824 : u === 'M' ? n * 1048576 : u === 'K' ? n * 1024 : n; };
    const limit = toBytes(ini.post_max_size);
    if (limit > 0 && limit <= 64 * 1048576) {
      const n = await nonceFor(adm, 'gi_zona_salveaza');
      await adm.request.post(ZONA + 'copaci/nou/', { multipart: { gi_actiune: 'salveaza', titlu: 'PREA MARE ' + STAMP, _gi_nonce: n, foto: { name: 'uriasa.jpg', mimeType: 'image/jpeg', buffer: Buffer.alloc(limit + 2 * 1048576, 1) } }, maxRedirects: 0 });
      const tt = await (await adm.request.get(ZONA + 'copaci/')).text();
      ok(/prea mare/.test(tt) && /nu s-a păstrat/.test(tt), `copaci: o cerere mai mare decât limita serverului (${ini.post_max_size}) primește un mesaj clar, nu o pagină goală`);
    } else {
      console.log(`[nota]   copaci: serverul local are post_max_size=${ini.post_max_size}; cererea peste limită se simulează (PHP lasă $_POST gol, CONTENT_LENGTH rămâne mare)`);
    }
    // simulare: exact condițiile pe care le produce PHP la o cerere peste post_max_size
    {
      const n2 = await nonceFor(adm, 'gi_zona_salveaza');
      await adm.request.post(ZONA + 'copaci/nou/?gi_test_oversize=1', { form: { gi_actiune: 'salveaza', titlu: 'SIMULAT ' + STAMP, _gi_nonce: n2 }, maxRedirects: 0 });
      const t2 = await (await adm.request.get(ZONA + 'copaci/')).text();
      const q2 = await rest('GET', '/wp/v2/puncte?search=' + encodeURIComponent('SIMULAT ' + STAMP) + '&status=any&context=edit');
      ok(/Poza e prea mare/.test(t2) && /nu s-a păstrat/.test(t2) && Array.isArray(q2.body) && q2.body.length === 0, 'copaci: o cerere peste limita serverului (simulată) primește un mesaj clar și nu creează nimic');
    }
  }

  // --- 4c. Echipă
  await api(adm, 'emailuri');
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  ok((await ap.inputValue('#gi-l-p')) === '10' && (await ap.inputValue('#gi-l-s')) === '100' && (await ap.inputValue('#gi-l-n')) === '10', 'echipă: limitele de pornire (provizorii) sunt 10% / 100 lei / 10 coduri');
  const COLEG = 'Colega Test ' + STAMP; const EMAIL = 'colega.test.' + STAMP + '@example.com';
  await ap.fill('#gi-t-nume', COLEG); await ap.fill('#gi-t-email', EMAIL);
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.dblclick('button:has-text("Creează contul")')]); // dublu clic: formularul se trimite o singură dată
  const credText = await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText();
  const cred = { login: (credText.match(/Utilizator: (\S+)/) || [])[1], pass: (credText.match(/Parola: (\S+)/) || [])[1] };
  ok(!!cred.login && !!cred.pass && cred.pass.length >= 12 && credText.includes(ZONA), `echipă: contul se creează și se arată o singură dată adresa, utilizatorul și parola (${cred.login})`);
  ok((await api(adm, 'cauta_optiuni&q=' + encodeURIComponent(cred.pass))).count === 0, 'echipă: parola nouă nu rămâne în baza de date (nici temporar, în opțiuni)');
  const u1 = await api(adm, 'utilizator&login=' + cred.login);
  if (u1.id) { created.users.push(cred.login); }
  ok(u1.exists && u1.roles.join() === 'gi_vanzari' && u1.caps.gi_zona && u1.caps.gi_vouchere && !u1.caps.edit_posts && !u1.caps.upload_files && !u1.caps.gi_copaci && !u1.caps.gi_echipa && !u1.caps.gi_vouchere_toate && !u1.caps.manage_woocommerce, 'echipă: contul nou are numai rolul „Vânzări (Grădina)”: Zona + vouchere, nimic din administrare');
  ok((await api(adm, 'emailuri')).count === 0, 'echipă: nu s-a trimis niciun email');
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  ok(!(await ap.content()).includes(cred.pass) && (await ap.locator('.gi-zona-lista li', { hasText: COLEG }).count()) === 1, 'echipă: parola nu se mai arată după ce ai plecat din pagină; colegul e în listă');
  await ap.fill('#gi-t-nume', 'Altă ' + STAMP); await ap.fill('#gi-t-email', EMAIL);
  await submit(ap, 'button:has-text("Creează contul")');
  ok(/deja în echipă/.test(await ap.locator('.gi-zona-notice').first().innerText()), 'echipă: același email a doua oară (colegul e deja în echipă) → mesaj, nu se face alt cont');
  {
    const n = await nonceFor(adm, 'gi_zona_echipa_adauga');
    await adm.request.post(ZONA + 'echipa/', { form: { gi_actiune: 'adauga', nume: 'X', email: 'nu-e-email', _gi_nonce: n }, maxRedirects: 0 });
    ok(/numele și un email valid/.test(await (await adm.request.get(ZONA + 'echipa/')).text()), 'echipă: email greșit → mesaj clar');
  }
  if (SHOTS) {
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' }); await ap.screenshot({ path: path.join(SHOTS, 'zona-echipa-admin.png'), fullPage: true });
    await ap.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' }); await ap.waitForSelector('.gi-qr-mini svg'); await ap.screenshot({ path: path.join(SHOTS, 'zona-vouchere-admin.png'), fullPage: true });
  }

  // ------------------------------------------------------------------ 5. Colegul de la vânzări
  const sal = await newCtx(); const sp = await sal.newPage(); sp.setDefaultTimeout(90000);
  await sp.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await sp.fill('#user_login', cred.login); await sp.fill('#user_pass', cred.pass);
  await submit(sp, '#wp-submit');
  ok(/\/zona-echipei\/$/.test(sp.url()), 'vânzări: autentificarea clasică (wp-login.php) îl duce în Zona echipei, nu în administrare');
  ok((await sp.locator('.gi-zona-tile strong').allInnerTexts()).join('|') === 'Vouchere' && (await sp.locator('.gi-zona-foot a', { hasText: 'Administrarea WordPress' }).count()) === 0 && (await sp.locator('#wpadminbar').count()) === 0, 'vânzări: vede numai „Vouchere”, fără link spre administrare, fără bara WordPress');
  ok(new RegExp('Bună, ' + COLEG).test(await sp.locator('.gi-zona-hello').innerText()), 'vânzări: e salutat cu numele lui');
  if (SHOTS) { await sp.screenshot({ path: path.join(SHOTS, 'zona-acasa-vanzari.png'), fullPage: true }); }
  for (const p of ['/wp-admin/', '/wp-admin/profile.php', '/wp-admin/edit.php', '/wp-admin/edit.php?post_type=gi_punct', '/wp-admin/admin.php?page=gi-vouchere', '/wp-admin/post-new.php', '/wp-admin/users.php', '/wp-admin/upload.php', '/wp-admin/admin-post.php?action=gi_voucher_gen']) {
    await sp.goto(BASE + p, { waitUntil: 'domcontentloaded' });
    ok(sp.url().startsWith(ZONA), `vânzări: ${p} → înapoi în Zona echipei (${sp.url().replace(BASE, '')})`);
  }
  await sp.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
  ok(!(await sp.evaluate(() => document.body.classList.contains('admin-bar'))) && (await sp.locator('#wpadminbar').count()) === 0, 'vânzări: pe paginile site-ului nu apare bara de administrare');
  const sNonce = (await (await sal.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  const SH = { 'X-WP-Nonce': sNonce };
  const sr = async (method, route, data) => (await sal.request.fetch(BASE + '/wp-json' + route, { method, headers: SH, data })).status();
  ok([401, 403].includes(await sr('POST', '/wp/v2/posts', { title: 'x', status: 'publish' })), 'vânzări: nu poate crea articole prin REST');
  ok([401, 403].includes(await sr('POST', '/wp/v2/puncte', { title: 'x', status: 'publish' })), 'vânzări: nu poate crea locuri prin REST');
  ok([401, 403].includes(await sr('POST', '/wp/v2/pages/' + (await rest('GET', '/wp/v2/pages?per_page=1')).body[0].id, { title: 'x' })), 'vânzări: nu poate modifica pagini prin REST');
  ok([401, 403, 404].includes(await sr('GET', '/wp/v2/users')) && [401, 403, 404].includes(await sr('GET', '/wp/v2/users/1')), 'vânzări: nu poate lista conturile prin REST');
  ok([401, 403].includes(await sr('POST', '/wp/v2/media', { title: 'x' })), 'vânzări: nu poate încărca fișiere prin REST');
  ok([401, 403].includes(await sr('POST', '/wc/v3/coupons', { code: 'REST' + STAMP, amount: '99' })), 'vânzări: nu poate crea cupoane prin REST (numai prin Zona, în limite)');
  // copaci și echipă: închise
  await sp.goto(ZONA + 'copaci/', { waitUntil: 'domcontentloaded' });
  ok(/nu are acces la copaci/.test(await sp.locator('.gi-zona').innerText()) && (await sp.locator('form').count()) === 0, 'vânzări: „Copaci și locuri” e închis');
  await sp.goto(ZONA + 'copaci/nou/', { waitUntil: 'domcontentloaded' });
  ok(/nu are acces la copaci/.test(await sp.locator('.gi-zona').innerText()), 'vânzări: nici formularul de loc nou nu se deschide');
  await sp.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  ok(/Numai proprietara/.test(await sp.locator('.gi-zona').innerText()) && (await sp.locator('form').count()) === 0, 'vânzări: „Echipă” e închisă');
  {
    // trimiteri făcute de mână, chiar cu cod de siguranță valid: tot nu merg
    const nPl = await nonceFor(sal, 'gi_zona_salveaza');
    await sal.request.post(ZONA + 'copaci/nou/', { form: { gi_actiune: 'salveaza', titlu: 'Vanzari ' + STAMP, publica: '1', _gi_nonce: nPl }, maxRedirects: 0 });
    const q = await rest('GET', '/wp/v2/puncte?search=' + encodeURIComponent('Vanzari ' + STAMP) + '&status=any&context=edit');
    ok(q.body.length === 0 && /Nu ai voie/.test(await (await sal.request.get(ZONA + 'copaci/')).text()), 'vânzări: un loc trimis de mână (cu cod valid) nu se creează');
    const nTm = await nonceFor(sal, 'gi_zona_echipa_adauga');
    await sal.request.post(ZONA + 'echipa/', { form: { gi_actiune: 'adauga', nume: 'Intrus', email: 'intrus.' + STAMP + '@example.com', _gi_nonce: nTm }, maxRedirects: 0 });
    ok((await api(sal, 'utilizator&login=intrus.' + STAMP)).exists === false && /Nu ai voie/.test(await (await sal.request.get(ZONA)).text()), 'vânzări: un cont nou trimis de mână (cu cod valid) nu se creează');
    const nLm = await nonceFor(sal, 'gi_zona_echipa_limite');
    await sal.request.post(ZONA + 'echipa/', { form: { gi_actiune: 'limite', lim_procent: '100', lim_suma: '99999', lim_numar: '100', _gi_nonce: nLm }, maxRedirects: 0 });
    const lim = await (await adm.request.get(ZONA + 'echipa/')).text();
    ok(/id="gi-l-p"[^>]*value="10"/.test(lim), 'vânzări: limitele nu se pot schimba de colegul de la vânzări (trimitere făcută de mână, cu cod valid)');
    const nPw = await nonceFor(sal, 'gi_zona_echipa_parola');
    await sal.request.post(ZONA + 'echipa/', { form: { gi_actiune: 'parola', utilizator: String(adminInfo.id), _gi_nonce: nPw }, maxRedirects: 0 });
    const c5 = await newCtx(); const r5 = await c5.request.post(BASE + '/wp-login.php', { form: { log: 'admin', pwd: 'password', 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(r5.status() === 302 && !/eroare/.test(r5.headers().location || ''), 'vânzări: parola proprietarei nu se poate schimba de colegul de la vânzări (trimitere făcută de mână, cu cod valid)');
    await c5.close();
  }
  // vouchere în limite
  await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  ok(/cel mult 10%/.test(await sp.locator('.gi-zona-card').first().innerText()) && /100 lei/.test(await sp.locator('.gi-zona-card').first().innerText()) && /cel mult 10 coduri/.test(await sp.locator('.gi-zona-card').first().innerText()), 'vânzări: formularul arată limitele lui (10% / 100 lei / 10 coduri)');
  ok(/Voucherele tale/.test(await sp.locator('.gi-zona-card').nth(1).innerText()), 'vânzări: lista se numește „Voucherele tale”');
  const sGen = (fields) => rawGen(sal, fields);
  for (const [what, f, re] of [
    ['11 coduri', { tip: 'suma', valoare: '10', numar: '11', prefix: 'VANZ' }, /cel mult 10 coduri/],
    ['11%', { tip: 'procent', valoare: '11', numar: '1', prefix: 'VANZ' }, /cel mult 10% reducere/],
    ['101 lei', { tip: 'suma', valoare: '101', numar: '1', prefix: 'VANZ' }, /cel mult 100 lei/],
  ]) {
    const r = await sGen(f);
    ok(re.test(r.errors), `vânzări: ${what} → refuzat (${r.errors.slice(0, 80)})`);
  }
  await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  ok((await sp.locator('.gi-zona-lista .gi-zona-cod').count()) === 0, 'vânzări: după încercările peste limite nu s-a creat niciun voucher');
  await sp.check('input[name=tip][value=procent]');
  await sp.fill('#gi-v-valoare', '10'); await sp.fill('#gi-v-numar', '3'); await sp.fill('#gi-v-prefix', 'VANZ');
  await submit(sp, 'button:has-text("Generează codurile")');
  const sCodes = (await sp.locator('.gi-zona-notice .gi-zona-coduri').innerText()).split('\n').map((x) => x.trim()).filter(Boolean);
  ok(sCodes.length === 3 && sCodes.every((c) => /^VANZ-/.test(c)), `vânzări: 3 coduri de 10% generate în limite (${sCodes.join(', ')})`);
  const sc = []; for (const c of sCodes) { const d = await api(sal, 'cupon&cod=' + c); sc.push(d); if (d.id) { created.coupons.push(d.id); } }
  ok(sc.every((d) => d.type === 'percent' && d.amount === 10 && d.limit === 1 && d.creator === u1.id && Math.abs((d.expires - now) / month - 18) < 0.2), 'vânzări: voucherele lui: 10%, o folosire, 18 luni, înregistrate pe numele lui');
  ok(sc.every((d) => d.individual === true), 'vânzări: codurile lui se aplică singure la o rezervare (nu se adună: zece coduri de 10% nu fac 100%)');
  await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  await sp.waitForSelector('.gi-qr-mini svg');
  const sListed = await sp.locator('.gi-zona-lista .gi-zona-cod').allInnerTexts();
  ok(sListed.length === 3 && sListed.every((c) => sCodes.includes(c)) && !sListed.some((c) => flashCodes.includes(c)), 'vânzări: vede numai voucherele lui, nu și pe ale proprietarei');
  ok(/Creat la /.test(await sp.locator('.gi-zona-lista').innerText()) && !/generat de/i.test(await sp.locator('.gi-zona-lista').innerText()), 'vânzări: vede când a fost creat voucherul, dar nu „generat de” (e doar pentru proprietară)');
  ok((await decode(sp, '.gi-qr-mini', 0)) === BASE + '/?gi_cod=' + sListed[0], 'vânzări: codul QR al voucherului lui se citește');
  const [pop3] = await Promise.all([sal.waitForEvent('page'), (async () => { await sp.locator('.gi-zona-lista input[name="ids[]"]').nth(0).check(); await sp.click('button:has-text("Printează voucherele alese")'); })()]);
  await pop3.waitForLoadState('load'); await pop3.waitForSelector('.gi-tile .qr svg');
  ok((await pop3.locator('.gi-tile').count()) === 1 && (await decode(pop3, '.gi-tile .qr', 0)) === BASE + '/?gi_cod=' + (await pop3.locator('.gi-tile strong').innerText()), 'vânzări: poate printa voucherul lui, cu codul QR care se citește');
  await pop3.close();
  {
    const n = await nonceFor(sal, 'gi_zona_tipar');
    const other = await (await sal.request.get(ZONA + 'tipar/?tip=vouchere&ids[]=' + cp[0].id + '&ids[]=' + cp[1].id + '&_gi_nonce=' + n)).text();
    ok(!/class="gi-tile"/.test(other) && !other.includes(flashCodes[0]) && !other.includes(flashCodes[1]), 'vânzări: voucherele altcuiva nu se pot printa nici cu linkul făcut de mână');
    const pl = await sal.request.get(ZONA + 'tipar/?tip=copaci&ids[]=' + placeId + '&_gi_nonce=' + n);
    ok(pl.status() === 403, `vânzări: printarea codurilor pentru copaci e refuzată (${pl.status()})`);
  }
  if (SHOTS) { await sp.screenshot({ path: path.join(SHOTS, 'zona-vouchere-vanzari.png'), fullPage: true }); }

  // --- colegul de la vânzări: fără ștergere de vouchere și fără istoric; își schimbă singur parola (1.5.1)
  {
    await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    ok((await sp.locator('button:has-text("Șterge voucherele alese")').count()) === 0, 'vânzări: lista lui de vouchere nu are butonul „Șterge”');
    const ownCode = sCodes[0]; const ownInfo = await api(sal, 'cupon&cod=' + ownCode);
    const nDel = await nonceFor(sal, 'gi_zona_sterge');
    await sal.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'sterge', 'ids[]': String(ownInfo.id), _gi_nonce_sterge: nDel }, maxRedirects: 0 });
    ok(/Nu ai voie/.test(await (await sal.request.get(ZONA + 'vouchere/')).text()) && (await api(sal, 'cupon&cod=' + ownCode)).exists === true, 'vânzări: ștergerea trimisă de mână (cu cod valid) e refuzată, voucherul rămâne');
    await sp.goto(ZONA + 'istoric/', { waitUntil: 'domcontentloaded' });
    ok(/nu are acces la istoric/.test(await sp.locator('.gi-zona').innerText()) && (await sp.locator('.gi-zona-tabel').count()) === 0, 'vânzări: istoricul e închis');
    await sp.goto(ZONA, { waitUntil: 'domcontentloaded' });
    ok((await sp.locator('.gi-zona-tile', { hasText: 'Istoric' }).count()) === 0 && (await sp.locator('input[type=password]').count()) === 0, 'vânzări: pe prima pagină nu are dala „Istoric” și nici formular de schimbare a parolei');
    // nu-și poate schimba parola: nici prin REST (users/me), nici prin „Mi-am uitat parola”; proprietara o poate
    const ALTA = 'AltaParola-' + STAMP + 'Zz';
    const rr = await sal.request.fetch(BASE + '/wp-json/wp/v2/users/me', { method: 'POST', headers: SH, data: { password: ALTA } });
    const lgOld = await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: cred.login, pwd: cred.pass, 'wp-submit': 'Log In' }, maxRedirects: 0 });
    const lgNew = await (await newCtx()).request.post(BASE + '/wp-login.php', { form: { log: cred.login, pwd: ALTA, 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(rr.status() === 403 && lgOld.status() === 302 && lgNew.status() === 200, `vânzări: prin REST (users/me) parola NU se schimbă, cu eroare clară (cerere: ${rr.status()}; parola veche merge, cea nouă nu)`);
    await api(adm, 'emailuri'); // pornim numărătoarea de emailuri de la zero
    const lp = await (await newCtx()).request.post(BASE + '/wp-login.php?action=lostpassword', { form: { user_login: cred.login }, maxRedirects: 0 });
    const lpUnknown = await (await newCtx()).request.post(BASE + '/wp-login.php?action=lostpassword', { form: { user_login: 'nu.exista.' + STAMP }, maxRedirects: 0 });
    ok(lp.status() === 302 && (lp.headers().location || '') === (lpUnknown.headers().location || '') && /checkemail=confirm/.test(lp.headers().location || '') && (await api(adm, 'emailuri')).count === 0, 'vânzări: „Mi-am uitat parola” răspunde ca pentru un cont inexistent și nu trimite niciun email (nu se poate afla cine e coleg de la vânzări)');
    const lpAdmin = await (await newCtx()).request.post(BASE + '/wp-login.php?action=lostpassword', { form: { user_login: 'admin' }, maxRedirects: 0 });
    ok(lpAdmin.status() === 302 && /checkemail=confirm/.test(lpAdmin.headers().location || ''), 'proprietara: „Mi-am uitat parola” merge ca până acum (link pe email)');
    ok(/parola o dă proprietara/.test(await (await (await newCtx()).request.get(ZONA)).text()), 'intrare: sub formular scrie că parola colegilor o dă proprietara');
    ok((await api(adm, 'cauta_optiuni&q=' + encodeURIComponent(ALTA))).count === 0, 'vânzări: parola încercată prin REST nu rămâne nicăieri');
  }

  // --- limitele se schimbă de proprietară și se aplică imediat
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  await ap.fill('#gi-l-p', '20'); await ap.fill('#gi-l-s', '150,5'); await ap.fill('#gi-l-n', '12');
  await submit(ap, 'button:has-text("Salvează limitele")');
  ok(/Limitele au fost salvate/.test(await ap.locator('.gi-zona-notice').first().innerText()) && (await ap.inputValue('#gi-l-s')) === '150,5', 'echipă: limitele noi se salvează (20% / 150,5 lei / 12 coduri)');
  const r12 = await sGen({ tip: 'procent', valoare: '15', numar: '12', prefix: 'VANZ' });
  await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  const afterIds = await sp.locator('.gi-zona-lista .gi-zona-cod').allInnerTexts();
  ok(!r12.errors && afterIds.length === 15, `vânzări: cu limitele noi (20%, 12 coduri) 12 coduri de 15% merg (${afterIds.length} în listă)`);
  for (const c of afterIds) { const d = await api(sal, 'cupon&cod=' + c); if (d.id && !created.coupons.includes(d.id)) { created.coupons.push(d.id); } }
  const r13 = await sGen({ tip: 'procent', valoare: '15', numar: '13', prefix: 'VANZ' });
  ok(/cel mult 12 coduri/.test(r13.errors), 'vânzări: 13 coduri → refuzat cu limita nouă');
  await ap.fill('#gi-l-p', '0'); await submit(ap, 'button:has-text("Salvează limitele")');
  const r0 = await sGen({ tip: 'procent', valoare: '5', numar: '1', prefix: 'VANZ' });
  ok(/Nu ai voie să generezi reduceri în procente/.test(r0.errors), 'vânzări: cu limita 0 la procente, tipul „procent” e oprit');
  await api(adm, 'zona_reset');

  // --- parola nouă închide sesiunile; „Scoate accesul” scoate accesul
  ap.on('dialog', (d) => d.accept()); // confirmarea de la „Scoate accesul”
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  const sessionsBefore = (await api(adm, 'utilizator&login=' + cred.login)).sessions;
  await submit(ap, '.gi-zona-lista li:has-text("' + COLEG + '") button:has-text("Parolă nouă")');
  const cred2 = { pass: ((await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText()).match(/Parola: (\S+)/) || [])[1] };
  ok(!!cred2.pass && cred2.pass !== cred.pass, 'echipă: „Parolă nouă” arată o parolă nouă, o singură dată');
  ok((await api(adm, 'cauta_optiuni&q=' + encodeURIComponent(cred2.pass))).count === 0, 'echipă: nici parola schimbată nu rămâne în baza de date');
  ok(sessionsBefore >= 1 && (await api(adm, 'utilizator&login=' + cred.login)).sessions === 0, `echipă: parola nouă închide sesiunile deschise (${sessionsBefore} → 0)`);
  await sp.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
  ok((await sp.locator('#gi-zona-user').count()) === 1 && (await sp.locator('.gi-zona-lista').count()) === 0, 'vânzări: după parola nouă, sesiunea veche cere din nou intrarea');
  {
    const c2 = await newCtx(); const p2 = await c2.newPage(); p2.setDefaultTimeout(90000);
    await p2.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await p2.fill('#gi-zona-user', cred.login); await p2.fill('#gi-zona-pass', cred.pass);
    await submit(p2, '.gi-zona-login button[type=submit]');
    ok(/eroare=1/.test(p2.url()), 'echipă: parola veche nu mai merge');
    await p2.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    await p2.fill('#gi-zona-user', cred.login); await p2.fill('#gi-zona-pass', cred2.pass);
    await submit(p2, '.gi-zona-login button[type=submit]');
    ok(/\/zona-echipei\/vouchere\/$/.test(p2.url()) && (await p2.locator('#gi-v-valoare').count()) === 1, `echipă: parola nouă merge, iar după intrare ajungi unde voiai (vouchere), nu la prima pagină (${p2.url().replace(BASE, '')})`);
    await p2.goto(ZONA, { waitUntil: 'domcontentloaded' });
    // ieșirea din cont
    await Promise.all([p2.waitForNavigation({ waitUntil: 'domcontentloaded' }), p2.locator('.gi-zona-foot a', { hasText: 'Ieși din cont' }).click()]);
    const outTxt = await p2.content();
    ok(/zona-echipei/.test(p2.url()) && /id="gi-zona-user"/.test(outTxt), `ieșire din cont: „Ieși din cont” duce înapoi la formularul de intrare (${p2.url().replace(BASE, '')})`);
    await p2.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    ok((await p2.locator('.gi-zona-lista').count()) === 0, 'ieșire din cont: după ieșire, voucherele nu se mai văd');
    await c2.close();
  }
  // ținte nepotrivite pentru „Parolă nouă” / „Scoate accesul”
  {
    for (const [what, id] of [['propriul cont', adminInfo.id], ['un cont inexistent', 999999]]) {
      const n = await nonceFor(adm, 'gi_zona_echipa_parola');
      await adm.request.post(ZONA + 'echipa/', { form: { gi_actiune: 'parola', utilizator: String(id), _gi_nonce: n }, maxRedirects: 0 });
      const t = await (await adm.request.get(ZONA + 'echipa/')).text();
      ok(/nu se poate modifica de aici/.test(t) && (await api(adm, 'utilizator&login=admin')).exists, `echipă: „Parolă nouă” pe ${what} e refuzată`);
    }
    // parola administratorului n-a fost schimbată (încă intră cu „password”)
    const c3 = await newCtx(); const r = await c3.request.post(BASE + '/wp-login.php', { form: { log: 'admin', pwd: 'password', 'wp-submit': 'Log In' }, maxRedirects: 0 });
    ok(r.status() === 302 && !/eroare/.test(r.headers().location || ''), 'echipă: parola administratorului a rămas neschimbată');
    await c3.close();
  }
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  await submit(ap, '.gi-zona-lista li:has-text("' + COLEG + '") button:has-text("Scoate accesul")');
  const u2 = await api(adm, 'utilizator&login=' + cred.login);
  ok(u2.exists && u2.roles.length === 0 && !u2.caps.gi_zona, `echipă: „Scoate accesul” îi ia rolul (${JSON.stringify(u2.roles)})`);
  ok((await ap.locator('.gi-zona-lista li', { hasText: COLEG }).count()) === 0, 'echipă: contul scos dispare din lista echipei');
  {
    const c4 = await newCtx(); const p4 = await c4.newPage(); p4.setDefaultTimeout(90000);
    await p4.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await p4.fill('#gi-zona-user', cred.login); await p4.fill('#gi-zona-pass', cred2.pass);
    await submit(p4, '.gi-zona-login button[type=submit]');
    const t = await p4.locator('.gi-zona').innerText();
    ok(/nu are acces aici/.test(t) && (await p4.locator('.gi-zona-tile').count()) === 0, 'echipă: un cont fără rol poate intra, dar nu are acces la nimic');
    await p4.goto(ZONA + 'vouchere/', { waitUntil: 'domcontentloaded' });
    ok((await p4.locator('.gi-zona-lista, form[action*="vouchere"]').count()) === 0, 'echipă: contul scos nu vede voucherele');
    await c4.close();
  }
  {
    // același email, a doua oară: contul fără rol se reactivează (nu rămâne orfan, nu trebuie wp-admin)
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await ap.fill('#gi-t-nume', COLEG); await ap.fill('#gi-t-email', EMAIL);
    await submit(ap, 'button:has-text("Creează contul")');
    const t3 = await ap.locator('.gi-zona-notice').allInnerTexts();
    const pass3 = ((await ap.locator('.gi-zona-notice .gi-zona-coduri').innerText().catch(() => '')).match(/Parola: (\S+)/) || [])[1];
    const u3 = await api(adm, 'utilizator&login=' + cred.login);
    ok(t3.some((x) => /reactivat/.test(x)) && u3.roles.join() === 'gi_vanzari' && u3.caps.gi_vouchere && !!pass3 && pass3 !== cred2.pass, 'echipă: același email după „Scoate accesul” → contul se reactivează, cu parolă nouă');
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    ok((await ap.locator('.gi-zona-lista li', { hasText: COLEG }).count()) === 1, 'echipă: colegul reactivat apare din nou în lista echipei');
  }
  await credChecks();
  await tailChecks();
  await histChecks();

  if (!process.env.GI_SKIP_HEAVY) { await runScreens(browser, newCtx, await adm.storageState()); }

  // ------------------------------------------------------------------ curățenie
  for (const id of created.coupons) { await rest('DELETE', '/wc/v3/coupons/' + id + '?force=true'); }
  for (const id of created.posts) { await rest('DELETE', '/wp/v2/puncte/' + id + '?force=true'); }
  for (const id of created.media) { await rest('DELETE', '/wp/v2/media/' + id + '?force=true'); }
  for (const login of created.users) { await api(actx, 'sterge_utilizator&login=' + login); }
  await api(actx, 'zona_reset');
  ok((await api(actx, 'utilizator&login=' + (created.users[0] || 'x'))).exists === false, 'curățenie: contul de probă a fost șters');

  const phpAfter = logLines();
  if (PHP_LOG) { ok(phpAfter === phpBefore, `fără erori PHP noi în jurnal (${phpAfter - phpBefore} linii noi)`); }
  await browser.close();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
