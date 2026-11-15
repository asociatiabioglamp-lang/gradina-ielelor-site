// Test pentru versiunea 1.6.0 (codul QR al rezervării și pagina Recepție din Zona echipei), pe WordPress local pornit pe 127.0.0.1:9400
// (vezi tools/local-wp/README.md). NUMAI LOCAL: se autentifică cu admin/password, creează și șterge comenzi și conturi de probă.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-6-0.php, gi-test-1-5-0.php, gi-test-1-3-14.php), WooCommerce activ,
// produsul „Rezervare de probă” (îl pune gi-local-setup.php), ffmpeg (pentru camera simulată) și biblioteca de test jsqr (numai pentru test):
//   npm install jsqr  și  NODE_PATH să includă folderul ei node_modules.
// Variabile opționale: GI_PHP_LOG (calea spre wp-content/gi-php-errors.log), GI_SHOTS (folder pentru capturi), GI_TMP (folder pentru fișiere temporare).
// Verifică:
//  - codul rezervării: apare pe pagina de mulțumire numai pentru comenzi finalizate, e stabil, e unic, are forma XXXX-XXXX din alfabetul fără 0/O/1/I/L;
//    codul QR se citește și conține numai GI1.<cod>.<suma de control> (fără nume, telefon, email); pagina /cod-rezervare/<cod>/ există numai pentru coduri reale,
//    nu apare în Google, nu se păstrează în memorii intermediare, nu are date personale; emailul are codul și linkul (nu și emailul către administrator);
//  - Recepție (Zona echipei): căutare după cod scris sau după tot textul din QR; suma de control greșită, cod invalid, cod necunoscut; rezervare plătită /
//    neplătită / anulată; datele din comandă (sosire, plecare, nopți, adulți, copii, telefon); fără derulare pe orizontală pe telefon;
//  - camera: antetul permite camera numai pe pagina Recepție; scanarea cu o cameră simulată care arată codul QR găsește rezervarea;
//  - contul „Recepție”: se creează de proprietară, vede numai Recepția, nu vede vouchere/copaci/echipă/istoric, nu intră în administrare, nu își poate schimba parola,
//    „Mi-am uitat parola” răspunde ca pentru un cont inexistent; căutările apar în Istoric fără date personale.
// Rulează: NODE_PATH="$(npm root -g):<folder>/node_modules" node tools/local-wp/test-1-6-0.js     Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(BASE)) {
  console.error('Refuz să rulez pe ' + BASE + ': testul e numai pentru WordPress-ul local (GI_BASE=http://127.0.0.1:9400).');
  process.exit(2);
}
let JSQR;
try { JSQR = fs.readFileSync(require.resolve('jsqr/dist/jsQR.js'), 'utf8'); } catch (e) { console.error('Lipsește biblioteca de test jsqr (npm install jsqr).'); process.exit(2); }
const SHOTS = process.env.GI_SHOTS || '';
const PHP_LOG = process.env.GI_PHP_LOG || '';
const TMP = process.env.GI_TMP || fs.mkdtempSync(path.join(os.tmpdir(), 'gi160-'));
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const STAMP = String(Date.now()).slice(-6);
const ZONA = BASE + '/zona-echipei/';
const RECEPTIE = ZONA + 'receptie/';
const CODE_RE = /^[ABCDEFGHJKMNPQRSTUVWXYZ2-9]{4}-[ABCDEFGHJKMNPQRSTUVWXYZ2-9]{4}$/;
const logLines = () => { try { return PHP_LOG ? fs.readFileSync(PHP_LOG, 'utf8').split('\n').filter(Boolean).length : 0; } catch (e) { return 0; } };

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

// Același calcul ca în plugin (GI_Rezervari::checksum), scris separat aici ca să se poată compara.
function checksum(code) {
  const AL = '0123456789ABCDEFGHJKMNPQRSTUVWXYZ';
  let s = 0;
  for (const ch of code.replace('-', '')) { s = (s * 31 + AL.indexOf(ch) + 1) % 1089; }
  return AL[Math.floor(s / 33) % 33] + AL[s % 33];
}

(async () => {
  const phpBefore = logLines();
  const browser = await chromium.launch();
  const newCtx = (opts) => browser.newContext(Object.assign({ viewport: { width: 1440, height: 900 } }, opts || {}));
  const api = async (ctx, p) => (await ctx.request.get(BASE + '/?gi_test=' + p)).json();
  const made = { users: [] };
  const q = (o) => Object.keys(o).map((k) => k + '=' + encodeURIComponent(o[k])).join('&');

  // ------------------------------------------------------------------ pregătire: administrator + comenzi de probă
  const adm = await newCtx(); const ap = await adm.newPage(); ap.setDefaultTimeout(90000);
  await ap.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await ap.fill('#user_login', 'admin'); await ap.fill('#user_pass', 'password');
  await Promise.all([ap.waitForURL('**/wp-admin/**'), ap.click('#wp-submit')]);
  await api(adm, 'sterge_comenzi'); await api(adm, 'zona_reset'); await api(adm, 'emailuri');
  const perm = await api(adm, 'permisiuni');
  ok(perm.exista && perm.caps.includes('gi_receptie') && perm.caps.includes('gi_zona') && !perm.caps.includes('gi_vouchere') && !perm.caps.includes('gi_echipa') && !perm.caps.includes('edit_posts'), 'rol: „Recepție (Grădina)” are numai dreptul Zonei și al Recepției (fără vouchere, echipă, articole): ' + perm.caps.join(', '));

  const A = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Ana', prenume: 'Popescu' + STAMP, tel: '0711222333', sosire: '2026-12-18', plecare: '2026-12-20', adulti: 2, copii: 1 }));
  const B = await api(adm, 'comanda&' + q({ stare: 'on-hold', nume: 'Bogdan', prenume: 'Ionescu' + STAMP, tel: '0722333444', sosire: '2027-01-05', plecare: '2027-01-06', adulti: 1, plata: 'Plata la locație' }));
  const C = await api(adm, 'comanda&' + q({ stare: 'pending', nume: 'Cora', prenume: 'Nefinalizata' + STAMP }));
  const D = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Dan', prenume: 'Anulat' + STAMP, sosire: '2026-12-25', plecare: '2026-12-27', adulti: 2, copii: 0 }));
  const E = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Elena', prenume: 'Livrare' + STAMP, tel: '0733444555', sosire: '2027-02-01', plecare: '2027-02-03', adulti: 2, copii: 2, plata: 'Plată la livrare', metoda: 'cod' }));
  const F = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Florin', prenume: 'Ștefănescu', tel: '0744555666', sosire: '2027-03-10', plecare: '2027-03-12', adulti: 3, copii: 0 }));
  ok(A.id && B.id && C.id && D.id && E.id && F.id, 'pregătire: șase comenzi de probă (plătită, la locație, nefinalizată, plătită care va fi anulată, plată la livrare în „procesare”, rezervare veche fără cod)');

  // ------------------------------------------------------------------ 1. Codul pe pagina de mulțumire
  const visitor = await newCtx(); const vp = await visitor.newPage(); vp.setDefaultTimeout(90000);
  const codes = {};
  for (const [name, o, want] of [['A', A, true], ['B', B, true], ['C', C, false], ['D', D, true], ['E', E, true]]) {
    const resp = await vp.goto(o.url, { waitUntil: 'load' });
    const n = await vp.locator('.gi-cod-rezervare').count();
    if (!want) {
      ok(resp.status() === 200 && n === 0, `mulțumire: comanda ${name} (nefinalizată) nu are cod de rezervare (${n})`);
      ok((await api(adm, 'cod_comanda&id=' + o.id)).cod === '', `mulțumire: comanda ${name} nu primește cod în baza de date`);
      const wait = await vp.locator('.gi-cod-asteptare').innerText().catch(() => '');
      ok(/apare aici și în emailul de confirmare imediat ce banca confirmă plata/.test(wait), `mulțumire: comanda ${name} (așteaptă banca) spune când apare codul`);
      continue;
    }
    ok(resp.status() === 200 && n === 1, `mulțumire: comanda ${name} arată un singur bloc „Codul tău de rezervare” (${n})`);
    const code = (await vp.locator('.gi-cod-rezervare .gi-cod').innerText()).trim();
    codes[name] = code;
    ok(CODE_RE.test(code), `mulțumire: comanda ${name} — codul ${code} are forma XXXX-XXXX, fără 0, O, 1, I, L`);
    const payload = await decode(vp, '.gi-cod-rezervare .gi-cod-qr', 0);
    ok(payload === 'GI1.' + code + '.' + checksum(code), `mulțumire: comanda ${name} — codul QR se citește și conține numai GI1.${code}.${checksum(code)} (citit: ${payload})`);
    const html = await vp.content();
    const qrAttr = await vp.locator('.gi-cod-rezervare .gi-cod-qr').getAttribute('data-qr-url');
    ok(!/Popescu|Ionescu|Anulat|0711222333|0722333444|client\.test\./.test(qrAttr || ''), `mulțumire: comanda ${name} — în codul QR nu există nume, telefon sau email`);
    ok(html.includes('Arată-l la recepție'), `mulțumire: comanda ${name} — textul spune ce să facă turistul`);
    await vp.reload({ waitUntil: 'load' });
    ok((await vp.locator('.gi-cod-rezervare .gi-cod').innerText()).trim() === code, `mulțumire: comanda ${name} — la reîncărcare codul rămâne același`);
    ok((await api(adm, 'cod_comanda&id=' + o.id)).cod === code, `mulțumire: comanda ${name} — codul se păstrează în comandă`);
  }
  ok(new Set(Object.values(codes)).size === Object.values(codes).length, 'coduri: trei rezervări, trei coduri diferite');

  // ------------------------------------------------------------------ 2. Pagina /cod-rezervare/<cod>/
  {
    const r = await visitor.request.get(BASE + '/cod-rezervare/' + codes.A + '/');
    const html = await r.text();
    const h = r.headers();
    ok(r.status() === 200 && /noindex/.test(h['x-robots-tag'] || '') && /noindex/.test(html) && /no-store|no-cache|private/.test(h['cache-control'] || ''), `pagina codului: 200, noindex (antet și meta), fără memorie intermediară (${r.status()}, ${h['x-robots-tag']}, ${h['cache-control']})`);
    ok(!/Popescu|Ionescu|0711222333|client\.test\.|Strada Probei|450/.test(html), 'pagina codului: fără nume, telefon, email, adresă sau sume');
    const pg = await newCtx(); const p2 = await pg.newPage();
    await p2.goto(BASE + '/cod-rezervare/' + codes.A.toLowerCase() + '/', { waitUntil: 'load' });
    ok((await decode(p2, '.gi-cod-qr-mare', 0)) === 'GI1.' + codes.A + '.' + checksum(codes.A), 'pagina codului: cu litere mici în adresă tot arată codul, iar codul QR mare se citește');
    ok((await p2.locator('.gi-cod-text').innerText()).trim() === codes.A, 'pagina codului: codul scris mare');
    ok((await p2.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)) <= 0, 'pagina codului: fără derulare pe orizontală');
    await pg.close();
    for (const [what, url] of [['un cod care nu există', '/cod-rezervare/ZZZZ-ZZZZ/'], ['un cod prea scurt', '/cod-rezervare/ABC/'], ['un text cu semne', '/cod-rezervare/<script>/']]) {
      const x = await visitor.request.get(BASE + url);
      ok(x.status() === 404, `pagina codului: ${what} dă 404 (${x.status()})`);
    }
    await api(adm, 'stare_comanda&' + q({ id: D.id, stare: 'cancelled' }));
    const dd = await visitor.request.get(BASE + '/cod-rezervare/' + codes.D + '/');
    ok(dd.status() === 404, `pagina codului: după anularea comenzii, pagina codului nu se mai arată (${dd.status()})`);
    const sm = await (await visitor.request.get(BASE + '/wp-sitemap.xml')).text();
    ok(!/cod-rezervare/.test(sm), 'pagina codului: nu apare în harta site-ului');
  }

  // ------------------------------------------------------------------ 3. Emailul de confirmare
  {
    const h = (await api(adm, 'bloc_email&' + q({ id: A.id, text: 0, admin: 0 }))).html;
    const t = (await api(adm, 'bloc_email&' + q({ id: A.id, text: 1, admin: 0 }))).html;
    const a = (await api(adm, 'bloc_email&' + q({ id: A.id, text: 0, admin: 1 }))).html;
    const c = (await api(adm, 'bloc_email&' + q({ id: C.id, text: 0, admin: 0 }))).html;
    ok(h.includes(codes.A) && h.includes('/cod-rezervare/' + codes.A + '/'), 'email (HTML): codul și linkul spre codul QR');
    ok(t.includes('Codul tău de rezervare: ' + codes.A) && t.includes('/cod-rezervare/' + codes.A + '/') && !/<[a-z]/i.test(t), 'email (text): codul și linkul, fără etichete');
    ok(a === '' && c === '', 'email: emailul către administrator și o comandă nefinalizată nu primesc cod');
  }

  // ------------------------------------------------------------------ 4. Recepție: căutare
  const search = async (page, text) => {
    await page.goto(RECEPTIE + '?' + q({ cod: text }), { waitUntil: 'domcontentloaded' });
    return page.locator('.gi-zona').innerText();
  };
  {
    const t0 = await ap.goto(ZONA + 'receptie/', { waitUntil: 'domcontentloaded' });
    const hdr = t0.headers();
    ok(t0.status() === 200 && /camera=\(self\)/.test(hdr['permissions-policy'] || '') && /microphone=\(\)/.test(hdr['permissions-policy'] || ''), `recepție: antetul permite camera numai paginii proprii, nu și microfonul (${hdr['permissions-policy']})`);
    for (const p of ['/zona-echipei/', '/zona-echipei/vouchere/', '/glamping/', '/']) {
      const r = await ap.goto(BASE + p, { waitUntil: 'domcontentloaded' });
      ok(/camera=\(\)/.test(r.headers()['permissions-policy'] || ''), `camera: pe ${p} rămâne oprită (${r.headers()['permissions-policy']})`);
    }
    await ap.goto(ZONA + 'receptie/', { waitUntil: 'domcontentloaded' });
    ok(/noindex/.test((await ap.content())), 'recepție: pagina nu apare în Google');
    ok((await ap.locator('[data-gi-scan] video[hidden]').count()) === 1 && (await ap.locator('[data-gi-scan-open]').isVisible()) && !(await ap.locator('[data-gi-scan-stop]').isVisible()), 'recepție: butonul „Scanează cu camera” se vede, camera e oprită');

    let txt = await search(ap, codes.A);
    ok(/Rezervarea\s+/.test(txt) && txt.includes(codes.A) && /plătită/.test(txt) && txt.includes('Ana Popescu' + STAMP) && txt.includes('0711222333'), 'recepție: cod scris → rezervarea plătită, cu nume și telefon');
    ok(/18\.12\.2026/.test(txt) && /20\.12\.2026/.test(txt) && /2 nopți/.test(txt), 'recepție: sosire 18.12.2026, plecare 20.12.2026, 2 nopți');
    ok(/Adulți\s+2/.test(txt) && /Copii\s+1/.test(txt), 'recepție: 2 adulți, 1 copil, citiți din comandă');
    ok(/Check in/.test(txt) && /Check out/.test(txt), 'recepție: toate câmpurile din comandă rămân vizibile (Check in, Check out)');
    txt = await search(ap, codes.A.toLowerCase().replace('-', ' '));
    ok(txt.includes(codes.A) && /Ana Popescu/.test(txt), 'recepție: cod scris cu litere mici și spațiu în loc de cratimă tot se găsește');
    txt = await search(ap, 'GI1.' + codes.A + '.' + checksum(codes.A));
    ok(txt.includes('Ana Popescu' + STAMP), 'recepție: tot textul din QR (GI1.…) se găsește');
    const bad = checksum(codes.A)[0] + (checksum(codes.A)[1] === 'Z' ? 'Y' : 'Z');
    txt = await search(ap, 'GI1.' + codes.A + '.' + bad);
    ok(/Suma de control/.test(txt) && !/Ana Popescu/.test(txt), 'recepție: suma de control greșită → mesaj, fără rezervare');
    txt = await search(ap, 'ABC');
    ok(/forma așteptată/.test(txt) && !/Ana Popescu/.test(txt), 'recepție: cod prea scurt → mesaj');
    txt = await search(ap, 'ZZZZ-ZZZZ');
    ok(/Nu găsesc rezervarea ZZZZ-ZZZZ/.test(txt), 'recepție: cod necunoscut → „Nu găsesc rezervarea”');
    txt = await search(ap, codes.B);
    ok(/neplătită încă/.test(txt) && /Plata la locație/.test(txt) && /Adulți\s+1/.test(txt) && /Copii\s+nu apar în comandă/.test(txt) && /1 noapte/.test(txt), 'recepție: rezervare cu plata la locație → „neplătită încă”, 1 adult, copii neprecizați, 1 noapte');
    txt = await search(ap, codes.E);
    ok(/de încasat la recepție/.test(txt) && /Plată la livrare/.test(txt) && (await ap.locator('.gi-zona-notice.gi-zona-ok').count()) === 0 && !/Rezervare confirmată și plătită/.test(txt), 'recepție: plata la livrare, deși WooCommerce o pune în „În procesare”, apare „de încasat la recepție”, nu „plătită”');
    ok(/2 nopți/.test(txt) && /Copii\s+2/.test(txt), 'recepție: și pentru plata la livrare se văd datele șederii');
    await search(ap, codes.E); await search(ap, 'GI1.' + codes.E + '.' + checksum(codes.E));
    const hrows = (await api(adm, 'istoric&n=100')).rows.filter((r) => r.actiune === 'receptie_cautare' && r.obiect === codes.E);
    ok(hrows.length === 1, `istoric: trei căutări la rând ale aceleiași rezervări (reîncărcare, cod scris, cod scanat) lasă o singură înregistrare (${hrows.length})`);
    // rezervare veche, fără cod: număr + nume
    const byNum = async (num, name) => { await ap.goto(RECEPTIE + '?' + q({ comanda: num, nume: name }), { waitUntil: 'domcontentloaded' }); return ap.locator('.gi-zona').innerText(); };
    txt = await byNum(String(F.id), 'STEFANESCU');
    ok(/Rezervarea\s+#/.test(txt) && /Florin Ștefănescu/.test(txt) && /10\.03\.2027/.test(txt) && /Adulți\s+3/.test(txt), 'recepție: rezervare veche (fără cod) se găsește după numărul comenzii și numele, scris fără diacritice și cu litere mari');
    ok((await api(adm, 'cod_comanda&id=' + F.id)).cod === '', 'recepție: căutarea după număr nu creează cod în comandă');
    txt = await byNum('#' + F.id, 'ștefănescu');
    ok(/Florin/.test(txt), 'recepție: numărul scris cu # și numele cu diacritice tot se găsește');
    txt = await byNum(String(F.id), 'Popescu');
    ok(/Nu găsesc o rezervare/.test(txt) && !/Florin/.test(txt), 'recepție: număr corect, nume greșit → nu se arată nimic');
    txt = await byNum(String(F.id), '');
    ok(/Nu găsesc o rezervare/.test(txt) && !/Florin/.test(txt), 'recepție: numai numărul, fără nume → nu se arată nimic (nu se pot răsfoi comenzile)');
    txt = await byNum('', 'Ștefănescu');
    ok(/Nu găsesc o rezervare/.test(txt) && !/Florin/.test(txt), 'recepție: numai numele, fără număr → nu se arată nimic');
    txt = await byNum('999999999', 'Ștefănescu');
    ok(/Nu găsesc o rezervare/.test(txt), 'recepție: număr de comandă care nu există → „Nu găsesc”');
    txt = await search(ap, codes.D);
    ok(/nu e finalizată/.test(txt) && /Anulat/i.test(txt), 'recepție: rezervare anulată → avertisment că nu e finalizată');
    txt = await search(ap, '<script>alert(1)</script>');
    ok(/forma așteptată/.test(txt) && !(await ap.content()).includes('<script>alert(1)</script>'), 'recepție: text cu etichete nu se afișează ca etichete');
    // Istoric
    await ap.goto(ZONA + 'istoric/?tip=receptie_', { waitUntil: 'domcontentloaded' });
    const hist = await ap.locator('.gi-zona').innerText();
    ok(hist.includes('A căutat rezervarea ' + codes.A), 'istoric: căutarea apare („A căutat rezervarea …”)');
    ok(!/Popescu|Ionescu|0711222333|0722333444/.test(hist), 'istoric: nu conține numele sau telefonul turiștilor');
    await ap.goto(ZONA + 'istoric/', { waitUntil: 'domcontentloaded' });
    ok(!/A căutat rezervarea/.test(await ap.locator('.gi-zona').innerText()), 'istoric: „Tot” nu amestecă căutările de la Recepție (se văd la „Recepție”)');
    const raw = await api(adm, 'cauta_istoric&q=' + encodeURIComponent('Popescu' + STAMP));
    ok(raw.count === 0, 'istoric: nici în baza de date nu rămân numele turiștilor (' + raw.count + ')');
    await ap.goto(ZONA, { waitUntil: 'domcontentloaded' });
    ok((await ap.locator('.gi-zona-tile:has-text("Recepție"), a:has-text("Recepție")').count()) > 0, 'zona: administratorul vede „Recepție” pe prima pagină');
  }

  // ------------------------------------------------------------------ 5. Ecrane: fără derulare pe orizontală
  {
    const sizes = { 'telefon îngust': [320, 656], 'telefon': [360, 740], 'telefon pe lat': [740, 360], 'calculator': [1440, 900] };
    const cookies = await adm.storageState();
    for (const [name, [w, h]] of Object.entries(sizes)) {
      const phone = Math.min(w, h) <= 430;
      const c = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone, storageState: cookies });
      const p = await c.newPage(); p.setDefaultTimeout(90000);
      const bad = [];
      for (const url of [RECEPTIE, RECEPTIE + '?' + q({ cod: codes.A }), RECEPTIE + '?' + q({ cod: codes.B }), RECEPTIE + '?' + q({ cod: 'ZZZZ-ZZZZ' })]) {
        await p.goto(url, { waitUntil: 'load' });
        const m = await p.evaluate(() => {
          const vw = document.documentElement.clientWidth;
          const wide = [];
          document.querySelectorAll('.gi-zona *').forEach((el) => { const r = el.getBoundingClientRect(); if (r.width && (r.right > vw + 1 || r.left < -1)) { wide.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0]); } });
          const small = [];
          document.querySelectorAll('.gi-zona :is(input:not([type=hidden]), button, a.button)').forEach((el) => { const r = el.getBoundingClientRect(); if (r.width && r.height < 44) { small.push((el.id || el.textContent.trim().slice(0, 16)) + ' ' + Math.round(r.height)); } });
          const hdr = document.querySelector('.gi-site .site-header'); const h1 = document.querySelector('h1');
          const rg = document.createRange(); rg.selectNodeContents(h1); const r1 = rg.getClientRects()[0];
          return { sx: document.documentElement.scrollWidth - document.documentElement.clientWidth, wide: wide.slice(0, 4), small, h1Below: !r1 || !hdr || r1.top >= hdr.getBoundingClientRect().bottom - 1 };
        });
        if (m.sx > 0 || m.wide.length || m.small.length || !m.h1Below) { bad.push(url.replace(BASE, '') + ': ' + JSON.stringify(m)); }
        if (SHOTS) { await p.screenshot({ path: path.join(SHOTS, `recepție-${name.replace(/ /g, '-')}-${bad.length}-${Date.now() % 100000}.png`), fullPage: true }); }
      }
      ok(bad.length === 0, `recepție — ${name}: 4 ecrane fără derulare pe orizontală, elemente ieșite sau prea mici (${bad.join(' | ') || 'în regulă'})`);
      await c.close();
    }
  }

  // ------------------------------------------------------------------ 6. Camera simulată: scanarea găsește rezervarea
  {
    const pg = await newCtx({ deviceScaleFactor: 2 }); const p = await pg.newPage();
    await p.goto(BASE + '/cod-rezervare/' + codes.A + '/', { waitUntil: 'load' });
    const qrPng = path.join(TMP, 'qr.png'); const mjpeg = path.join(TMP, 'camera.mjpeg');
    await p.locator('.gi-cod-qr-mare').screenshot({ path: qrPng });
    let haveVideo = true;
    try {
      execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-loop', '1', '-i', qrPng, '-vf', 'scale=420:420:flags=neighbor,pad=640:480:110:30:white', '-t', '2', '-r', '10', '-c:v', 'mjpeg', '-q:v', '3', mjpeg]);
    } catch (e) { haveVideo = false; }
    await pg.close();
    ok(haveVideo, 'cameră simulată: imaginea cu codul QR a fost transformată în film pentru camera de probă (ffmpeg)');
    if (haveVideo) {
      const cam = await chromium.launch({ args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--use-file-for-fake-video-capture=' + mjpeg] });
      const cctx = await cam.newContext({ viewport: { width: 390, height: 800 }, isMobile: true, hasTouch: true, permissions: ['camera'], storageState: await adm.storageState() });
      const cp = await cctx.newPage(); cp.setDefaultTimeout(90000);
      const errs = []; cp.on('pageerror', (e) => errs.push(e.message));
      await cp.goto(RECEPTIE, { waitUntil: 'load' });
      await cp.click('[data-gi-scan-open]');
      let nav = false;
      try { await cp.waitForURL(/cod=/, { timeout: 30000 }); nav = true; } catch (e) { nav = false; }
      const msg = await cp.locator('[data-gi-scan-msg]').innerText().catch(() => '');
      ok(nav, 'scanare: camera simulată arată codul QR → pagina caută singură rezervarea (' + (nav ? cp.url().replace(BASE, '') : msg) + ')');
      if (nav) {
        await cp.waitForLoadState('domcontentloaded');
        const t = await cp.locator('.gi-zona').innerText();
        ok(t.includes(codes.A) && t.includes('Ana Popescu' + STAMP), 'scanare: rezervarea găsită prin scanare e cea corectă');
        ok(decodeURIComponent(cp.url()).includes('GI1.' + codes.A + '.' + checksum(codes.A)), 'scanare: s-a trimis tot textul din QR (cu suma de control)');
      }
      ok(errs.length === 0, 'scanare: fără erori JavaScript (' + errs.join('; ') + ')');
      await cam.close();
      // un alt cod QR (de exemplu wifi): nu se confundă cu o rezervare, camera rămâne pornită; apăsarea dublă pornește o singură cameră
      const pg2 = await newCtx({ deviceScaleFactor: 2 }); const p2 = await pg2.newPage();
      await p2.goto(BASE + '/cod-rezervare/' + codes.A + '/', { waitUntil: 'load' });
      await p2.evaluate(() => { const el = document.querySelector('.gi-cod-qr-mare'); const x = window.qrcode(0, 'Q'); x.addData('WIFI:T:WPA;S:Gradina;P:parola;;'); x.make(); el.innerHTML = x.createSvgTag({ scalable: true }); });
      const wifiPng = path.join(TMP, 'wifi.png'); const wifiVid = path.join(TMP, 'wifi.mjpeg');
      await p2.locator('.gi-cod-qr-mare').screenshot({ path: wifiPng });
      await pg2.close();
      execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-loop', '1', '-i', wifiPng, '-vf', 'scale=420:420:flags=neighbor,pad=640:480:110:30:white', '-t', '2', '-r', '10', '-c:v', 'mjpeg', '-q:v', '3', wifiVid]);
      const cam2 = await chromium.launch({ args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream', '--use-file-for-fake-video-capture=' + wifiVid] });
      const c2 = await cam2.newContext({ viewport: { width: 390, height: 800 }, isMobile: true, hasTouch: true, permissions: ['camera'], storageState: await adm.storageState() });
      const w = await c2.newPage(); w.setDefaultTimeout(30000);
      await w.addInitScript(() => { window.__gum = 0; const o = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices); navigator.mediaDevices.getUserMedia = (c) => { window.__gum++; return o(c); }; });
      await w.goto(RECEPTIE, { waitUntil: 'load' });
      await w.locator('[data-gi-scan-open]').dblclick();
      await w.waitForFunction(() => /nu e un cod de rezervare/.test(document.querySelector('[data-gi-scan-msg]').textContent), null, { timeout: 25000 }).catch(() => {});
      const wm = await w.locator('[data-gi-scan-msg]').innerText();
      ok(/nu e un cod de rezervare/.test(wm) && !/cod=/.test(w.url()) && (await w.locator('[data-gi-scan-stop]').isVisible()), 'scanare: un cod QR care nu e de rezervare (wifi) nu trimite căutarea; mesaj, iar camera rămâne pornită (' + wm + ')');
      ok((await w.evaluate(() => window.__gum)) === 1, 'scanare: apăsarea dublă pe „Scanează” pornește o singură cameră (' + (await w.evaluate(() => window.__gum)) + ' cereri)');
      await w.click('[data-gi-scan-stop]');
      ok((await w.evaluate(() => { const v = document.querySelector('.gi-scan-video'); return !v.srcObject || v.srcObject.getTracks().every((t) => t.readyState === 'ended'); })), 'scanare: „Oprește camera” oprește cu adevărat camera (fără flux deschis)');
      await cam2.close();
    }
    // camera refuzată: mesajul cere permisiune sau scrierea codului
    const noCam = await browser.newContext({ viewport: { width: 390, height: 800 }, storageState: await adm.storageState() });
    const np = await noCam.newPage(); np.setDefaultTimeout(30000);
    await np.addInitScript(() => { navigator.mediaDevices.getUserMedia = () => Promise.reject(new DOMException('Permission denied', 'NotAllowedError')); });
    await np.goto(RECEPTIE, { waitUntil: 'load' });
    await np.click('[data-gi-scan-open]');
    await np.waitForTimeout(500);
    const m2 = await np.locator('[data-gi-scan-msg]').innerText();
    ok(/Nu am acces la cameră/.test(m2) && (await np.locator('[data-gi-scan-open]').isVisible()) && (await np.locator('#gi-r-cod').isVisible()), 'scanare: dacă telefonul refuză camera, mesajul spune ce să faci, iar câmpul pentru cod rămâne la îndemână');
    await noCam.close();
  }

  // ------------------------------------------------------------------ 7. Contul „Recepție”
  {
    const L = 'receptia.' + STAMP, PW = 'Receptie-Cheie-' + STAMP, EMAIL = L + '@example.com';
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    ok((await ap.locator('input[name="rol"][value="receptie"]').count()) === 1 && (await ap.locator('input[name="rol"][value="vanzari"]').isChecked()), 'echipă: formularul are alegerea rolului (Vânzări sau Recepție), Vânzări e preselectat');
    await ap.check('input[name="rol"][value="receptie"]');
    await ap.fill('#gi-t-nume', 'Recepționera ' + STAMP); await ap.fill('#gi-t-email', EMAIL); await ap.fill('#gi-t-login', L); await ap.fill('#gi-t-parola', PW);
    await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('button:has-text("Creează contul")')]);
    made.users.push(L);
    const u = await api(adm, 'utilizator&login=' + L);
    ok(u.exists && u.roles.length === 1 && u.roles[0] === 'gi_receptie' && u.caps.gi_zona && !u.caps.gi_vouchere && !u.caps.gi_copaci && !u.caps.gi_echipa && !u.caps.edit_posts && !u.caps.upload_files && !u.caps.manage_woocommerce, 'recepție: contul nou are rolul „Recepție” și nimic în plus: ' + JSON.stringify(u.roles));
    ok((await ap.locator('.gi-zona-lista li:has-text("' + L + '")').innerText()).includes('Recepție'), 'echipă: lista arată rolul fiecărui cont (Recepție)');

    const rc = await newCtx(); const rp = await rc.newPage(); rp.setDefaultTimeout(90000);
    await rp.goto(ZONA, { waitUntil: 'domcontentloaded' });
    await rp.fill('#gi-zona-user', L); await rp.fill('#gi-zona-pass', PW);
    await Promise.all([rp.waitForNavigation({ waitUntil: 'domcontentloaded' }), rp.click('.gi-zona-login button[type=submit]')]);
    const home = await rp.locator('.gi-zona').innerText();
    ok(/Recepție/.test(home) && !/Vouchere|Copaci|Echipă|Istoric/.test(home), 'recepție (cont): pe prima pagină vede numai Recepția');
    const txt = await search(rp, codes.B);
    ok(/Bogdan Ionescu/.test(txt) && /neplătită încă/.test(txt), 'recepție (cont): caută o rezervare și o vede');
    for (const s of ['vouchere/', 'copaci/', 'echipa/', 'istoric/', 'tipar/']) {
      const r = await rp.goto(ZONA + s, { waitUntil: 'domcontentloaded' });
      const body = await rp.locator('.gi-zona').innerText();
      ok(!/gi-zona-lista|name="gi_actiune"/.test(await rp.content()) && !/Generează|Creează contul|Adaugă un loc|Ce s-a întâmplat/.test(body), `recepție (cont): /${s} nu arată nimic din vouchere, copaci, echipă sau istoric (${r.status()})`);
    }
    const post = await rc.request.post(ZONA + 'vouchere/', { form: { gi_actiune: 'genereaza', tip: 'suma', valoare: '50', numar: '1', prefix: 'RECEPTIE' + STAMP, _gi_nonce: 'x' }, maxRedirects: 0 });
    ok((await api(adm, 'cupon&cod=RECEPTIE' + STAMP)).exists === false, `recepție (cont): nu poate genera vouchere (${post.status()})`);
    for (const p of ['/wp-admin/', '/wp-admin/profile.php', '/wp-admin/edit.php', '/wp-admin/admin.php?page=wc-orders']) {
      const r = await rc.request.get(BASE + p, { maxRedirects: 0 });
      ok(r.status() === 302 && /zona-echipei/.test(r.headers().location || ''), `recepție (cont): ${p} duce înapoi în Zonă (${r.status()})`);
    }
    const restNonce = (await (await rc.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
    const me = await rc.request.fetch(BASE + '/wp-json/wp/v2/users/me', { method: 'POST', headers: { 'X-WP-Nonce': restNonce }, data: { password: 'Alta-Cheie-Noua-' + STAMP } });
    ok(me.status() >= 400, `recepție (cont): nu poate schimba parola prin REST (${me.status()})`);
    const lp = await (await newCtx()).request.post(BASE + '/wp-login.php?action=lostpassword', { form: { user_login: L }, maxRedirects: 0 });
    const lp2 = await (await newCtx()).request.post(BASE + '/wp-login.php?action=lostpassword', { form: { user_login: 'nu.exista.' + STAMP }, maxRedirects: 0 });
    ok(lp.status() === lp2.status() && (lp.headers().location || '') === (lp2.headers().location || ''), `recepție (cont): „Mi-am uitat parola” răspunde ca pentru un cont inexistent (${lp.status()} / ${lp2.status()})`);
    ok((await api(adm, 'emailuri')).count === 0, 'recepție (cont): „Mi-am uitat parola” nu trimite niciun email');
    await rc.close();
    // proprietara scoate accesul
    await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
    await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.locator('.gi-zona-lista li:has-text("' + L + '") button:has-text("Scoate accesul")').click()]);
    const after = await api(adm, 'utilizator&login=' + L);
    ok(after.exists && !after.caps.gi_zona && !after.roles.includes('gi_receptie'), 'echipă: „Scoate accesul” scoate rolul Recepție');
  }

  // ------------------------------------------------------------------ curățenie și erori PHP
  await api(adm, 'sterge_comenzi');
  for (const l of made.users) { await api(adm, 'sterge_utilizator&login=' + l); }
  await browser.close();
  const phpAfter = logLines();
  ok(!PHP_LOG || phpAfter === phpBefore, `erori PHP: ${PHP_LOG ? (phpAfter - phpBefore) + ' linii noi în jurnal' : 'jurnalul nu a fost dat (GI_PHP_LOG)'}`);
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
