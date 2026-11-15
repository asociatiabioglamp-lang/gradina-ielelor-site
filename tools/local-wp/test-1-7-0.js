// Test pentru versiunea 1.7.0 (fișa de cazare și registrul fișelor), pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md).
// NUMAI LOCAL: se autentifică cu admin/password, creează și șterge comenzi, fișe și conturi de probă.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-7-0.php, gi-test-1-6-0.php, gi-test-1-5-0.php, gi-test-1-3-14.php),
// WooCommerce activ, produsul „Rezervare de probă” (gi-local-setup.php) și biblioteca de test jsqr:  npm install jsqr  și  NODE_PATH să includă node_modules.
// Variabile opționale: GI_PHP_LOG (calea spre wp-content/gi-php-errors.log), GI_SHOTS (folder pentru capturi).
// Verifică:
//  - butonul „Fișa de cazare” din Recepție (numai pentru rezervări finalizate); formularul: adultul k din N, domiciliul și telefonul din rezervare, recepționerul;
//  - citirea actului în browser: cifre de control, valabilitate, completarea câmpurilor, CNP numai dacă se potrivește; text brut șters după citire;
//  - înscrierea: fără semnături nu pleacă nimic; cu semnături se păstrează în tabel, fișa se afișează în română și engleză, cu semnături, serie, GDPR, tipar;
//  - greșeli (CNP, data, act expirat, același act de două ori, al treilea adult): mesaj, iar datele scrise și semnăturile rămân în formular;
//  - recepția: poate înscrie și vede fișa abia înscrisă, dar nu vede registrul, nu șterge, nu descarcă; administratorul: registru, căutare, CSV (cu protecție la formule),
//    ștergere, setări (unitate, prefix, text GDPR), termen de păstrare; Istoric fără nume, CNP sau telefon; ecrane pe telefon fără derulare pe orizontală.
// Rulează: NODE_PATH="$(npm root -g):<folder>/node_modules" node tools/local-wp/test-1-7-0.js     Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(BASE)) {
  console.error('Refuz să rulez pe ' + BASE + ': testul e numai pentru WordPress-ul local (GI_BASE=http://127.0.0.1:9400).');
  process.exit(2);
}
const M = require(path.join(__dirname, '..', '..', 'gradina-ielelor-integration', 'assets', 'js', 'mrz.js'));
const SHOTS = process.env.GI_SHOTS || '';
const PHP_LOG = process.env.GI_PHP_LOG || '';
let failures = 0;
function ok(condition, text) { console.log((condition ? '[OK]     ' : '[EROARE] ') + text); if (!condition) { failures++; } }
const STAMP = String(Date.now()).slice(-6);
const ZONA = BASE + '/zona-echipei/';
const RECEPTIE = ZONA + 'receptie/';
const YEAR = new Date().getUTCFullYear();
const logLines = () => { try { return PHP_LOG ? fs.readFileSync(PHP_LOG, 'utf8').split('\n').filter(Boolean).length : 0; } catch (e) { return 0; } };
const q = (o) => Object.keys(o).map((k) => k + '=' + encodeURIComponent(o[k])).join('&');

// ---- acte de probă ----
function cnpWith(s, ymd6, rest5) {
  const base = String(s) + ymd6 + rest5; const w = '279146358279'; let sum = 0;
  for (let i = 0; i < 12; i++) sum += +base[i] * +w[i];
  let c = sum % 11; if (c === 10) c = 1; return base + c;
}
function buildTD1(o) {
  const pad = (s, n) => (s + '<'.repeat(n)).slice(0, n);
  const chk = (s) => M.check(s);
  const l1 = pad('IDROU', 5) + pad(o.doc, 9) + chk(pad(o.doc, 9)) + pad(o.opt1 || '', 15);
  const l2a = o.dob + chk(o.dob) + o.sex + o.exp + chk(o.exp) + pad('ROU', 3) + pad('', 11);
  const comp = chk(l1.slice(5, 30) + l2a.slice(0, 7) + l2a.slice(8, 15) + l2a.slice(18, 29));
  return [l1, l2a + comp, pad(o.sur + '<<' + o.given.replace(/ /g, '<'), 30)].join('\n');
}
const CNP1 = cnpWith(2, '900314', '08001');
const MRZ1 = buildTD1({ doc: 'XY123456', dob: '900314', sex: 'F', exp: '350101', sur: 'POPESCU', given: 'MARIA ELENA', opt1: CNP1 });
const MRZ_BAD = MRZ1.split('\n').map((l, i) => (i === 1 ? l.replace('900314', '900315') : l)).join('\n'); // cifră greșită în data nașterii (numai pe rândul 2)
const MRZ_EXPIRED = buildTD1({ doc: 'AB654321', dob: '800101', sex: 'M', exp: '200101', sur: 'VECHI', given: 'ION' });
const MRZ_AMBIGUOUS = buildTD1({ doc: 'AB654322', dob: '081201', sex: 'F', exp: '350101', sur: 'AMBIGUA', given: 'ANA' });

(async () => {
  const phpBefore = logLines();
  const browser = await chromium.launch();
  const newCtx = (opts) => browser.newContext(Object.assign({ viewport: { width: 1280, height: 1000 } }, opts || {}));
  const api = async (ctx, p) => (await ctx.request.get(BASE + '/?gi_test=' + p)).json();
  const made = { users: [] };

  // ------------------------------------------------------------------ pregătire
  const adm = await newCtx(); const ap = await adm.newPage(); ap.setDefaultTimeout(90000);
  ap.on('dialog', (d) => d.accept());
  await ap.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await ap.fill('#user_login', 'admin'); await ap.fill('#user_pass', 'password');
  await Promise.all([ap.waitForURL('**/wp-admin/**'), ap.click('#wp-submit')]);
  await api(adm, 'sterge_comenzi'); await api(adm, 'fise_curata'); await api(adm, 'zona_reset');
  const A = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Maria', prenume: 'Popescu', tel: '0711222333', sosire: '2027-02-10', plecare: '2027-02-12', adulti: 2, copii: 1 }));
  const C = await api(adm, 'comanda&' + q({ stare: 'pending', nume: 'Cora', prenume: 'Nefinalizata' }));
  const F = await api(adm, 'comanda&' + q({ stare: 'processing', nume: 'Florin', prenume: 'Ștefănescu', tel: '0744555666', sosire: '2027-03-10', plecare: '2027-03-12', adulti: 1, copii: 0, fara_cod: 1 }));
  const visitor = await newCtx(); const vp = await visitor.newPage();
  await vp.goto(A.url, { waitUntil: 'load' });
  const CODE = (await vp.locator('.gi-cod-rezervare .gi-cod').innerText()).trim();
  ok(/^[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(CODE), 'pregătire: rezervarea A are codul ' + CODE);
  const rows = async () => (await api(adm, 'fise_randuri')).rows;
  // Căutarea în registru: formular POST (nimic din ce se caută nu ajunge în adresa paginii).
  const regSearch = async (text) => {
    await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
    await ap.fill('#gi-reg-q', text);
    await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('form:has(input[value="cauta"]) button.button-primary')]);
    return ap.locator('.gi-zona').innerText();
  };

  // ------------------------------------------------------------------ 1. Din Recepție la formular
  await ap.goto(RECEPTIE + '?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  const btn = ap.locator('a.button:has-text("Fișa de cazare")');
  ok((await btn.count()) === 1, 'recepție: rezervarea finalizată are butonul „Fișa de cazare”');
  await ap.goto(RECEPTIE + '?' + q({ comanda: F.id, nume: 'stefanescu' }), { waitUntil: 'domcontentloaded' });
  ok((await ap.locator('a.button:has-text("Fișa de cazare")').count()) === 1, 'recepție: și rezervarea veche (fără cod, găsită după număr și nume) are butonul');
  await ap.goto(RECEPTIE + '?' + q({ comanda: C.id, nume: 'Nefinalizata' }), { waitUntil: 'domcontentloaded' });
  ok((await ap.locator('a.button:has-text("Fișa de cazare")').count()) === 0, 'recepție: rezervarea nefinalizată nu are buton de fișă');
  await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  const form = await ap.locator('.gi-zona').innerText();
  ok(/Adultul 1 din 2/.test(form) && /Copiii nu au fișă/.test(form), 'formular: „Adultul 1 din 2”, copiii nu au fișă');
  ok((await ap.inputValue('#gi-f-dom')).includes('Strada Probei 1') && (await ap.inputValue('#gi-f-tel')) === '0711222333', 'formular: domiciliul și telefonul vin din rezervare');
  ok((await ap.inputValue('#gi-f-sosire')) === '2027-02-10' && (await ap.inputValue('#gi-f-plecare')) === '2027-02-12', 'formular: sosirea și plecarea vin din rezervare');
  ok((await ap.inputValue('#gi-f-recep')).length > 1, 'formular: numele recepționerului e precompletat cu contul curent');
  const notice0 = await ap.locator('.gi-zona-notice').allInnerTexts();
  ok(notice0.some((n) => /cel provizoriu/.test(n) && /lipsesc: operatorul/.test(n)), 'formular: administratorul vede că datele unității lipsesc și că textul despre datele personale e cel provizoriu');
  ok(/noindex/.test(await ap.content()) && (await ap.locator('textarea[name="mrz_text"]').count()) === 0, 'formular: nu apare în Google, iar câmpul pentru zona de jos a actului nu se trimite (nu are nume)');

  // ------------------------------------------------------------------ 2. Citirea actului
  const fillMrz = async (text) => { await ap.fill('#gi-f-mrz', text); await ap.click('[data-gi-mrz-read]'); };
  await fillMrz(MRZ_BAD);
  let checks = await ap.locator('[data-gi-mrz-checks]').innerText();
  ok(/datei nașterii NU se potrivește/.test(checks) && (await ap.locator('[data-gi-mrz-checks] li.bad').count()) >= 1, 'act: cifră greșită în data nașterii → „NU se potrivește”, pe roșu');
  await fillMrz(MRZ_EXPIRED);
  checks = await ap.locator('[data-gi-mrz-checks]').innerText();
  ok(/Actul a expirat la 01\.01\.2020/.test(checks), 'act: expirat → „Actul a expirat la 01.01.2020”');
  await fillMrz(MRZ_AMBIGUOUS);
  ok(/Anul nașterii din act are două cifre/.test(await ap.locator('[data-gi-mrz-checks]').innerText()) && (await ap.inputValue('#gi-f-dob')) === '1908-12-01', 'act: anul nașterii din două cifre care ar da o persoană sub 18 ani → avertisment roșu să se verifice în act (se alege 1908, nu se ascunde)');
  await fillMrz('ceva scris de mână');
  ok(/Nu recunosc rândurile/.test(await ap.locator('[data-gi-mrz-checks]').innerText()), 'act: text oarecare → mesaj clar');
  await fillMrz(MRZ1);
  checks = await ap.locator('[data-gi-mrz-checks]').innerText();
  ok((await ap.locator('[data-gi-mrz-checks] li.ok').count()) >= 5 && (await ap.locator('[data-gi-mrz-checks] li.bad').count()) === 0 && /CNP-ul din act e valid/.test(checks), 'act: toate cifrele de control se potrivesc, actul e valabil, CNP valid');
  const val = async (n) => ap.inputValue('#gi-f-' + n);
  ok((await val('nume')) === 'POPESCU' && (await val('prenume')) === 'MARIA ELENA' && (await val('dob')) === '1990-03-14' && (await val('sex')) === 'F' && (await val('cet')) === 'România' && (await val('tip')) === 'Carte de identitate' && (await val('doc')) === 'XY123456' && (await val('exp')) === '2035-01-01' && (await val('cnp')) === CNP1, 'act: câmpurile se completează din act (nume, prenume, naștere, sex, cetățenie, tip, serie, valabilitate, CNP)');
  ok((await ap.inputValue('#gi-f-mrz')) === '', 'act: textul brut al zonei de jos se șterge după citire');
  ok(/citit din act/.test(await ap.locator('[data-gi-sursa="nume"]').innerText()), 'act: câmpurile citite sunt marcate „citit din act”');
  await ap.fill('#gi-f-nume', 'POPESCU-ION');
  ok((await ap.locator('[data-gi-sursa="nume"]').innerText()) === '', 'act: ce corectezi de mână nu mai e marcat „citit din act”');
  await ap.fill('#gi-f-nume', 'POPESCU');
  await ap.fill('#gi-f-loc', 'Brașov, România');

  // ------------------------------------------------------------------ 3. Semnături și înscriere
  const sign = async (page, name) => {
    const loc = page.locator('canvas[data-gi-pad="' + name + '"]'); await loc.scrollIntoViewIfNeeded();
    const box = await loc.boundingBox();
    await page.mouse.move(box.x + 20, box.y + 70); await page.mouse.down();
    for (let i = 1; i <= 12; i++) { await page.mouse.move(box.x + 20 + i * 18, box.y + 70 + (i % 2 ? -25 : 25)); }
    await page.mouse.up();
  };
  await ap.check('input[name="gdpr"]');
  await ap.click('[data-gi-fisa-submit]');
  ok(/Lipsește: semnătura turistului și semnătura recepționerului/.test(await ap.locator('.gi-fisa-form .gi-zona-notice').innerText()) && (await rows()).length === 0, 'înscriere: fără semnături nu pleacă nimic, mesajul spune ce lipsește');
  await sign(ap, 'sigT');
  await ap.click('[data-gi-fisa-submit]');
  ok(/Lipsește: semnătura recepționerului/.test(await ap.locator('.gi-fisa-form .gi-zona-notice').innerText()), 'înscriere: cu o singură semnătură mesajul cere pe cealaltă');
  await sign(ap, 'sigR');
  await ap.uncheck('input[name="gdpr"]');
  const blocked = await ap.evaluate(() => !document.querySelector('[data-gi-fisa]').checkValidity());
  ok(blocked, 'înscriere: fără bifa despre datele personale formularul nu se trimite');
  await ap.check('input[name="gdpr"]');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  const r1 = await rows();
  ok(r1.length === 1, 'înscriere: s-a păstrat o fișă în tabel (' + r1.length + ')');
  const f1 = r1[0] || {};
  ok(f1.serie === 'GI-' + YEAR + '-00001' && f1.nume === 'POPESCU' && f1.prenume === 'MARIA ELENA' && f1.nastere === '1990-03-14' && f1.sex === 'F' && f1.cnp === CNP1 && f1.serie_act === 'XY123456' && f1.cetatenie === 'România' && f1.loc_nastere === 'Brașov, România' && f1.adult_nr === '1' && f1.rezervare === CODE && String(f1.comanda) === String(A.id) && f1.sosire === '2027-02-10' && f1.plecare === '2027-02-12', 'înscriere: datele din fișă sunt cele din act și din rezervare, seria GI-' + YEAR + '-00001, adultul 1');
  ok((f1.semn_t || 0) > 300 && (f1.semn_r || 0) > 300 && f1.semn_t_start === 'data:image/png;base64,', 'înscriere: ambele semnături se păstrează ca imagini PNG (' + f1.semn_t + ' / ' + f1.semn_r + ' caractere)');
  ok(/luat cunoștință de prelucrarea datelor/.test(f1.gdpr_text || ''), 'înscriere: se păstrează textul bifat despre datele personale');
  const sheet = await ap.locator('.gi-zona').innerText();
  ok(/Fișă de cazare/.test(sheet) && /Accommodation registration form/.test(sheet) && /Surname/.test(sheet) && /Date of birth/.test(sheet) && /GI-\d{4}-00001/.test(sheet) && /POPESCU/.test(sheet) && /14\.03\.1990/.test(sheet) && new RegExp(CNP1).test(sheet) && /10\.02\.2027/.test(sheet) && /2 nopți \/ nights/.test(sheet), 'fișă: română și engleză, serie, nume, data nașterii, CNP, sosire, plecare, nopți');
  const sheetBrand = await ap.evaluate(() => { const b = document.querySelector('.gi-fisa-sheet .gi-print-brand'); const i = b && b.querySelector('img'); return b ? { text: b.innerText, alt: i && i.alt, loaded: !!(i && i.complete && i.naturalWidth > 0) } : null; });
  ok(sheetBrand && /Grădina Ielelor/.test(sheetBrand.text) && sheetBrand.alt === 'Grădina Ielelor' && sheetBrand.loaded, 'regula de bază: fișa de cazare are sigla și numele „Grădina Ielelor”' + (sheetBrand ? '' : ' (lipsește)'));
  ok((await ap.locator('.gi-fisa-semnaturi img').count()) === 2 && (await ap.locator('.gi-fisa-semnaturi img[src^="data:image/png;base64,"]').count()) === 2, 'fișă: cele două semnături apar ca imagini');
  ok(/Fișa a fost înscrisă: GI-/.test(await ap.locator('.gi-zona-notice').innerText()) && (await ap.locator('button:has-text("Printează fișa")').count()) === 1 && (await ap.locator('a:has-text("Următorul adult")').count()) === 1, 'fișă: mesaj de reușită, buton de tipărire și „Următorul adult”');
  // tipar
  await ap.emulateMedia({ media: 'print' });
  const printState = await ap.evaluate(() => ({ header: getComputedStyle(document.querySelector('.site-header')).display, notice: document.querySelector('.gi-zona-notice') ? getComputedStyle(document.querySelector('.gi-zona-notice')).display : 'none', btns: getComputedStyle(document.querySelector('.gi-zona .no-print')).display, sheet: getComputedStyle(document.querySelector('.gi-fisa-sheet')).display }));
  ok(printState.header === 'none' && printState.notice === 'none' && printState.btns === 'none' && printState.sheet !== 'none', 'tipar: antetul, mesajele și butoanele dispar, fișa rămâne');
  const pdf = await ap.pdf({ format: 'A4' }); ok(pdf.length > 3000 && (pdf.toString('latin1').match(/\/Type\s*\/Page[^s]/g) || []).length === 1, 'tipar: fișa încape pe o singură pagină A4 (' + (pdf.toString('latin1').match(/\/Type\s*\/Page[^s]/g) || []).length + ' pagină)');
  await ap.emulateMedia({ media: 'screen' });

  // ------------------------------------------------------------------ 4. Adultul 2: greșeli, apoi reușită
  await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  ok(/Adultul 2 din 2/.test(await ap.locator('.gi-zona').innerText()), 'adultul 2: „Adultul 2 din 2”');
  const setAll = async (o) => { for (const [k, v] of Object.entries(o)) { await ap.fill('#gi-f-' + k, v); } };
  const base2 = { nume: 'MUELLER', prenume: 'HANS', dob: '1985-05-20', cet: 'Germania', tip: 'Pașaport', doc: 'C01X00T47', exp: '2032-06-01', dom: 'Hauptstrasse 1, Berlin' };
  await setAll(base2);
  await sign(ap, 'sigT'); await sign(ap, 'sigR'); await ap.check('input[name="gdpr"]');
  const trySubmit = async () => { await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('[data-gi-fisa-submit]')]); await ap.waitForTimeout(500); return ap.locator('.gi-zona-notice').allInnerTexts().then((a) => a.join(' ')); };
  // CNP greșit
  await ap.fill('#gi-f-cnp', '1234567890123');
  let err = await trySubmit();
  ok(/CNP-ul nu e valabil/.test(err) && (await rows()).length === 1, 'greșeală: CNP cu cifra de control greșită → mesaj, nimic păstrat');
  ok((await ap.inputValue('#gi-f-nume')) === 'MUELLER' && (await ap.inputValue('#gi-f-dom')) === 'Hauptstrasse 1, Berlin' && (await ap.inputValue('[name="sigT"]')).startsWith('data:image/png') && (await ap.locator('input[name="gdpr"]').isChecked()), 'greșeală: datele scrise, semnăturile și bifa rămân în formular');
  ok((await ap.evaluate(() => { const c = document.getElementById('gi-sigT'); const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data; for (let i = 3; i < d.length; i += 4) { if (d[i] > 0) return true; } return false; })), 'greșeală: semnătura se redesenează în câmp (nu trebuie făcută din nou)');
  // CNP potrivit cu data? (CNP al altcuiva, valid)
  await ap.fill('#gi-f-cnp', CNP1);
  err = await trySubmit();
  ok(/nu se potrivește cu data nașterii/.test(err), 'greșeală: CNP valid, dar al altei persoane (altă dată de naștere) → refuzat');
  await ap.fill('#gi-f-cnp', '');
  // același act ca adultul 1
  await ap.fill('#gi-f-doc', 'xy 123456');
  err = await trySubmit();
  ok(/există deja o fișă cu același act/.test(err), 'greșeală: același act ca la adultul 1 (scris altfel, cu spațiu și litere mici) → refuzat');
  await ap.fill('#gi-f-doc', 'C01X00T47');
  // act expirat prin câmp
  await ap.evaluate(() => { document.getElementById('gi-f-exp').removeAttribute('min'); }); await ap.fill('#gi-f-exp', '2020-01-01');
  err = await trySubmit();
  ok(/Actul a expirat la 01\.01\.2020/.test(err), 'greșeală: act expirat scris de mână → refuzat');
  await ap.evaluate(() => { document.getElementById('gi-f-exp').removeAttribute('min'); }); await ap.fill('#gi-f-exp', '2032-06-01');
  // plecare înainte de sosire
  await ap.fill('#gi-f-plecare', '2027-02-09');
  err = await trySubmit();
  ok(/Plecarea trebuie să fie după sosire/.test(err), 'greșeală: plecare înainte de sosire → refuzat');
  await ap.fill('#gi-f-plecare', '2027-02-12');
  // nume cu semne
  await ap.fill('#gi-f-prenume', 'HANS<script>');
  err = await trySubmit();
  ok(/prenumele folosește numai litere/.test(err) || /numai litere/.test(err), 'greșeală: prenume cu semne ciudate → refuzat');
  await ap.fill('#gi-f-prenume', 'HANS');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  const r2 = await rows();
  ok(r2.length === 2 && r2[1].serie === 'GI-' + YEAR + '-00002' && r2[1].cnp === '' && r2[1].adult_nr === '2' && r2[1].cetatenie === 'Germania', 'adultul 2: pașaport străin, fără CNP, seria 00002, adultul 2');

  // ------------------------------------------------------------------ 4b. Semnături și câmpuri: cazuri limită
  {
    await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
    await ap.check('input[name="depaseste"]').catch(() => {});
    // semnătura rămâne la rotirea telefonului / schimbarea lățimii
    await sign(ap, 'sigT');
    const wBefore = await ap.evaluate(() => document.getElementById('gi-sigT').width);
    const ink = () => ap.evaluate(() => { const c = document.getElementById('gi-sigT'); const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data; let n = 0; for (let i = 3; i < d.length; i += 4) { if (d[i] > 0) n++; } return n; });
    const inkBefore = await ink();
    await ap.setViewportSize({ width: 900, height: 1000 });
    await ap.evaluate(() => window.dispatchEvent(new Event('resize')));
    await sign(ap, 'sigT'); // atingerea următoare face „setup()”, vede lățimea nouă și trebuie să păstreze ce era desenat
    await sign(ap, 'sigR');
    const inkAfter = await ink();
    const wAfter = await ap.evaluate(() => document.getElementById('gi-sigT').width);
    ok(wBefore !== wAfter && inkBefore > 100 && inkAfter > 100, 'semnătură: la schimbarea lățimii ecranului (' + wBefore + ' → ' + wAfter + ' px) desenul rămâne (' + inkBefore + ' → ' + inkAfter + ' pixeli)');
    await ap.setViewportSize({ width: 1280, height: 1000 });
    // semnătură goală trimisă direct (ocolind JavaScript-ul): serverul o respinge
    const gd = (await api(adm, 'gd')).gd;
    const fields = await ap.evaluate(() => Object.fromEntries(new FormData(document.querySelector('[data-gi-fisa]'))));
    const blank = await ap.evaluate(() => { const c = document.createElement('canvas'); c.width = 320; c.height = 140; return c.toDataURL('image/png'); });
    Object.assign(fields, { f_nume: 'GOALA', f_prenume: 'SEMNATURA', f_dob: '1990-01-01', f_cet: 'România', f_tip: 'Carte de identitate', f_doc: 'GO000111', f_dom: 'Brașov', f_recep: 'Test', gdpr: '1', depaseste: '1', sigT: blank, sigR: blank });
    if (gd) {
      const rb = await adm.request.post(ZONA + 'fisa/', { form: fields, maxRedirects: 0 });
      const blankText = await rb.text();
      ok(/Lipsește o semnătură/.test(blankText) && !(await rows()).some((r) => r.nume === 'GOALA'), 'semnătură: un PNG gol (fără niciun pixel desenat) trimis direct la server e respins');
    } else {
      console.log('[OK]     semnătură: PHP-ul local nu are extensia GD, deci verificarea pixelilor de pe server se sare (se face în browser)');
    }
    // cetățenie cu „<” (cum o dă unele cititoare pentru actele germane) e respinsă
    fields.sigT = (await ap.inputValue('[name="sigT"]')) || fields.sigT;
    Object.assign(fields, { f_cet: 'D<<', f_doc: 'GO000222' });
    const rc2 = await adm.request.post(ZONA + 'fisa/', { form: Object.assign({}, fields, { sigT: await ap.evaluate(() => document.getElementById('gi-sigT').toDataURL('image/png')), sigR: await ap.evaluate(() => document.getElementById('gi-sigR').toDataURL('image/png')) }), maxRedirects: 0 });
    ok(/Cetățenia: fără semnele/.test(await rc2.text()) && !(await rows()).some((r) => r.nume === 'GOALA'), 'cetățenie: „D<<” (cod cu umplutură) e respins, nu se păstrează');
  }

  // ------------------------------------------------------------------ 5. Adultul 3: toți adulții au fișă
  await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  ok(/Toți cei 2 adulți au deja fișă/.test(await ap.locator('.gi-zona').innerText()) && (await ap.locator('input[name="depaseste"]').count()) === 1, 'al treilea adult: „Toți cei 2 adulți au deja fișă”, cu bifa „Adaug încă o fișă”');
  await setAll({ nume: 'TERCER', prenume: 'ALIN', dob: '1999-01-02', cet: 'România', tip: 'Carte de identitate', doc: 'ZZ111222', dom: 'Brașov' });
  await sign(ap, 'sigT'); await sign(ap, 'sigR'); await ap.check('input[name="gdpr"]');
  err = await trySubmit();
  ok(/Toți cei 2 adulți din rezervare au deja fișă/.test(err) && (await rows()).length === 2, 'al treilea adult: fără bifă → refuzat');
  await ap.check('input[name="depaseste"]');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  ok((await rows()).length === 3, 'al treilea adult: cu bifa „Adaug încă o fișă” se înscrie');

  // ------------------------------------------------------------------ 6. Rezervare veche (fără cod), cu domiciliu cu semne de formulă
  await ap.goto(ZONA + 'fisa/?' + q({ comanda: F.id, nume: 'Ștefănescu' }), { waitUntil: 'domcontentloaded' });
  await setAll({ nume: 'STEFANESCU', prenume: 'FLORIN', dob: '1975-07-07', cet: 'România', tip: 'Carte de identitate', doc: 'KL999888', dom: '=HYPERLINK("http://x.example")' });
  await sign(ap, 'sigT'); await sign(ap, 'sigR'); await ap.check('input[name="gdpr"]');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  const r4 = await rows();
  ok(r4.length === 4 && r4[3].rezervare === '#' + F.id && String(r4[3].comanda) === String(F.id), 'rezervare veche: fișa se leagă de comandă (' + (r4[3] && r4[3].rezervare) + ')');
  const nef = await (await visitor.request.get(BASE + '/zona-echipei/fisa/?' + q({ cod: CODE }))).text();
  ok(/id="gi-zona-user"/.test(nef) && !/POPESCU|XY123456/.test(nef), 'acces: fără cont, formularul de fișă nu arată nimic, ci cere autentificarea');

  // ------------------------------------------------------------------ 7. Contul „Recepție”
  const L = 'recep.fisa.' + STAMP, PW = 'Receptie-Fisa-' + STAMP + 'x';
  await ap.goto(ZONA + 'echipa/', { waitUntil: 'domcontentloaded' });
  await ap.check('input[name="rol"][value="receptie"]');
  await ap.fill('#gi-t-nume', 'Recepționera Fișe'); await ap.fill('#gi-t-email', L + '@example.com'); await ap.fill('#gi-t-login', L); await ap.fill('#gi-t-parola', PW);
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('button:has-text("Creează contul")')]);
  made.users.push(L);
  const rc = await newCtx(); const rp = await rc.newPage(); rp.setDefaultTimeout(90000);
  await rp.goto(ZONA, { waitUntil: 'domcontentloaded' });
  await rp.fill('#gi-zona-user', L); await rp.fill('#gi-zona-pass', PW);
  await Promise.all([rp.waitForNavigation({ waitUntil: 'domcontentloaded' }), rp.click('.gi-zona-login button[type=submit]')]);
  ok(!/Registrul fișelor/.test(await rp.locator('.gi-zona').innerText()), 'recepție (cont): pe prima pagină nu vede „Registrul fișelor”');
  const rr = await rp.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  const regTxt = await rp.locator('.gi-zona').innerText();
  ok(/numai proprietara/.test(regTxt) && !/POPESCU|MUELLER|GI-\d{4}-\d{5}/.test(regTxt) && (await rp.locator('table').count()) === 0, 'recepție (cont): /registru/ nu arată nicio fișă (' + rr.status() + ')');
  const csvRc = await rc.request.post(ZONA + 'registru/', { form: { gi_actiune: 'csv', _gi_nonce: 'x' }, maxRedirects: 0 });
  ok(!/POPESCU/.test(await csvRc.text()) && !/text\/csv/.test(csvRc.headers()['content-type'] || ''), 'recepție (cont): descărcarea CSV e refuzată (' + csvRc.status() + ')');
  await rp.goto(ZONA + 'fisa/' + r4[0].id + '/', { waitUntil: 'domcontentloaded' });
  ok(/nu ai voie să o vezi/.test(await rp.locator('.gi-zona').innerText()) && !/POPESCU/.test(await rp.locator('.gi-zona').innerText()), 'recepție (cont): fișa altcuiva nu se deschide');
  const delTry = await rc.request.post(ZONA + 'registru/', { form: { gi_actiune: 'sterge', fisa_id: r4[0].id, _gi_nonce: 'x' }, maxRedirects: 0 });
  ok((await rows()).length === 4, 'recepție (cont): nu poate șterge fișe (' + delTry.status() + ')');
  // recepția înscrie o fișă și o vede
  await rp.goto(ZONA + 'fisa/?' + q({ comanda: F.id, nume: 'stefanescu' }), { waitUntil: 'domcontentloaded' });
  ok(/Toți cei 1 adulți au deja fișă/.test(await rp.locator('.gi-zona').innerText()), 'recepție (cont): rezervarea veche (un adult, fișa făcută) spune că toți adulții au fișă');
  await rp.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  await rp.fill('#gi-f-nume', 'RECEPTIE'); await rp.fill('#gi-f-prenume', 'OSPAT'); await rp.fill('#gi-f-dob', '1992-12-12'); await rp.fill('#gi-f-cet', 'România'); await rp.fill('#gi-f-tip', 'Carte de identitate'); await rp.fill('#gi-f-doc', 'RC555444');
  await rp.check('input[name="depaseste"]').catch(() => {});
  for (const n of ['sigT', 'sigR']) { const loc = rp.locator('canvas[data-gi-pad="' + n + '"]'); await loc.scrollIntoViewIfNeeded(); const box = await loc.boundingBox(); await rp.mouse.move(box.x + 20, box.y + 60); await rp.mouse.down(); for (let i = 1; i <= 8; i++) { await rp.mouse.move(box.x + 20 + i * 20, box.y + 60 + (i % 2 ? -20 : 20)); } await rp.mouse.up(); }
  await rp.check('input[name="gdpr"]');
  await Promise.all([rp.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), rp.click('[data-gi-fisa-submit]')]);
  const r5 = await rows();
  ok(r5.length === 5 && r5[4].nume === 'RECEPTIE', 'recepție (cont): înscrie o fișă');
  ok(/RECEPTIE/.test(await rp.locator('.gi-zona').innerText()) && (await rp.locator('.gi-fisa-sheet').count()) === 1, 'recepție (cont): vede fișa abia înscrisă');
  ok(String(r5[4].creat_de) !== '' && r5[4].receptioner.length > 1, 'recepție (cont): fișa păstrează cine a înscris-o');
  await rc.close();

  // ------------------------------------------------------------------ 8. Registrul (administrator)
  await ap.goto(ZONA, { waitUntil: 'domcontentloaded' });
  ok(/Registrul fișelor/.test(await ap.locator('.gi-zona').innerText()), 'registru: administratorul are dala „Registrul fișelor”');
  let reg = await regSearch('');
  ok(/5 fișe în total/.test(reg) && /POPESCU MARIA ELENA/.test(reg) && /MUELLER HANS/.test(reg) && /GI-\d{4}-00005/.test(reg), 'registru: lista cu toate cele 5 fișe');
  reg = await regSearch('mueller');
  ok(/1 fișe găsite/.test(reg) && /MUELLER/.test(reg) && !/POPESCU/.test(reg), 'registru: căutare după nume');
  {
    const n = await ap.locator('form:has(input[value="csv"]) input[name="_gi_nonce"]').getAttribute('value');
    const qv = await ap.locator('form:has(input[value="csv"]) input[name="q"]').getAttribute('value');
    const part = await adm.request.post(ZONA + 'registru/', { form: { gi_actiune: 'csv', _gi_nonce: n, q: qv } });
    const partLines = (await part.text()).replace(/^\uFEFF/, '').split('\r\n').filter(Boolean);
    ok(qv === 'mueller' && partLines.length === 2 && partLines[1].includes('"MUELLER"') && !/POPESCU/.test(partLines.join('')), 'CSV: după o căutare, formularul trimite căutarea și se descarcă numai fișele găsite (' + (partLines.length - 1) + ')');
  }
  ok(!/mueller/i.test(ap.url()) && !/[?&]q=/.test(ap.url()), 'registru: ce cauți nu apare în adresa paginii (' + ap.url().replace(BASE, '') + ')');
  ok(/MUELLER/.test(await (async () => { await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' }); return ap.locator('.gi-zona').innerText(); })()), 'registru: căutarea se păstrează la reîncărcarea paginii (15 minute)');
  reg = await regSearch(CNP1);
  ok(/POPESCU/.test(reg), 'registru: căutare după CNP');
  reg = await regSearch('GI-' + YEAR + '-00003');
  ok(/TERCER/.test(reg), 'registru: căutare după serie');
  reg = await regSearch('nimeni-nu-se-cheama-asa');
  ok(/Nicio fișă pentru această căutare/.test(reg), 'registru: căutare fără rezultat');
  reg = await regSearch('');
  ok(/5 fișe în total/.test(reg), 'registru: căutare goală arată din nou toate fișele');
  await ap.goto(ZONA + 'registru/' + r5[0].id + '/', { waitUntil: 'domcontentloaded' });
  ok((await ap.locator('.gi-fisa-sheet').count()) === 1 && /POPESCU/.test(await ap.locator('.gi-fisa-sheet').innerText()), 'registru: o fișă se deschide din registru');
  // CSV
  await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  ok((await ap.locator('a[href*="export=csv"]').count()) === 0, 'CSV: nu mai există link de descărcare cu căutarea în adresă (se descarcă printr-un formular)');
  const csvNonce = await ap.locator('form:has(input[value="csv"]) input[name="_gi_nonce"]').getAttribute('value');
  const csvRes = await adm.request.post(ZONA + 'registru/', { form: { gi_actiune: 'csv', _gi_nonce: csvNonce } });
  const csv = await csvRes.body(); const csvText = csv.toString('utf8');
  ok(csvRes.status() === 200 && /text\/csv/.test(csvRes.headers()['content-type'] || '') && csv[0] === 0xEF && csv[1] === 0xBB && csv[2] === 0xBF && /attachment; filename="registru-fise-/.test(csvRes.headers()['content-disposition'] || ''), 'CSV: se descarcă ca fișier, cu semnul UTF-8 pentru Excel');
  const lines = csvText.replace(/^﻿/, '').split('\r\n').filter(Boolean);
  ok(lines.length === 6 && /^"Seria";"Rezervare";"Comanda"/.test(lines[0]) && lines[1].includes('"RECEPTIE"') && lines.some((l) => l.includes('"POPESCU"') && l.includes('"' + CNP1 + '"') && l.includes('"14.03.1990"')), 'CSV: antet și 5 rânduri, cu CNP și datele în format zz.ll.aaaa');
  ok(lines.some((l) => l.includes(`"'=HYPERLINK(""http://x.example"")"`)), 'CSV: o celulă care începe cu „=” e prefixată cu apostrof (nu se execută ca formulă în Excel)');
  ok(!/semn|data:image/.test(csvText), 'CSV: fără imaginile semnăturilor');
  const csvNo = await adm.request.post(ZONA + 'registru/', { form: { gi_actiune: 'csv', _gi_nonce: 'gresit' }, maxRedirects: 0 });
  ok(csvNo.status() === 302 && !/POPESCU/.test(await csvNo.text()), 'CSV: fără codul de siguranță valabil nu se descarcă nimic (' + csvNo.status() + ')');
  const csvGet = await adm.request.get(ZONA + 'registru/?export=csv&_gi_nonce=' + csvNonce, { maxRedirects: 0 });
  ok(!/text\/csv/.test(csvGet.headers()['content-type'] || '') && !/attachment/.test(csvGet.headers()['content-disposition'] || ''), 'CSV: descărcarea prin adresă (GET) nu mai merge (se afișează pagina obișnuită, nu un fișier)');
  // ștergere
  await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.locator('tr:has-text("TERCER") button:has-text("Șterge")').click()]);
  ok((await rows()).length === 4 && /GI-\d{4}-00003 a fost ștearsă/.test(await ap.locator('.gi-zona-notice').innerText()), 'registru: „Șterge” scoate fișa (cu confirmare) și spune care');

  await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.locator('tr:has-text("RECEPTIE") button:has-text("Șterge")').click()]);
  ok((await rows()).length === 3, 'registru: se șterge și ultima fișă (numărul cel mai mare)');

  // ------------------------------------------------------------------ 9. Setări, serie nouă, termen de păstrare
  await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  await ap.fill('#gi-s-operator', 'Operator Probă SRL'); await ap.fill('#gi-s-cui', 'RO123456'); await ap.fill('#gi-s-clasificare', '1 stea'); await ap.fill('#gi-s-prefix', 'tst-1');
  await ap.fill('#gi-s-gdpr', 'Text de probă despre datele personale, ' + STAMP + '.'); await ap.fill('#gi-s-pastrare', '12');
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('button:has-text("Salvează setările")')]);
  ok(/Setările fișei au fost salvate/.test(await ap.locator('.gi-zona-notice').innerText()) && (await ap.inputValue('#gi-s-prefix')) === 'TST1', 'setări: se salvează, prefixul se curăță (TST1)');
  await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  ok(new RegExp('Text de probă despre datele personale, ' + STAMP).test(await ap.locator('.gi-fisa-gdpr').innerText()), 'setări: textul bifei despre datele personale e cel nou');
  await setAll({ nume: 'NOUA', prenume: 'SERIE', dob: '1990-01-01', cet: 'România', tip: 'Carte de identitate', doc: 'NN123123', dom: 'Brașov' });
  await ap.check('input[name="depaseste"]').catch(() => {});
  await sign(ap, 'sigT'); await sign(ap, 'sigR'); await ap.check('input[name="gdpr"]');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  const sh2 = await ap.locator('.gi-fisa-sheet').innerText();
  ok(/TST1-\d{4}-00006/.test(sh2) && !/-00005\b/.test(sh2) && /Operator Probă SRL/.test(sh2) && /CUI RO123456/.test(sh2) && /1 stea/.test(sh2) && /Text de probă despre datele personale/.test(sh2), 'setări: fișa nouă are seria TST1-…, datele unității și textul nou');
  // Textul despre datele personale se schimbă între timp (altă filă): fișa nu se înscrie cu un text pe care turistul nu l-a văzut
  await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
  ok(!(await ap.locator('.gi-zona-notice').allInnerTexts()).some((n) => /cel provizoriu|lipsesc/.test(n)), 'formular: după ce unitatea și textul sunt completate, avertismentul dispare');
  await setAll({ nume: 'RAZGANDIT', prenume: 'TEXT', dob: '1991-02-03', cet: 'România', tip: 'Carte de identitate', doc: 'GH777666', dom: 'Brașov' });
  await ap.check('input[name="depaseste"]').catch(() => {});
  await sign(ap, 'sigT'); await sign(ap, 'sigR'); await ap.check('input[name="gdpr"]');
  const ap2 = await adm.newPage(); ap2.setDefaultTimeout(90000);
  await ap2.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  await ap2.fill('#gi-s-gdpr', 'Text schimbat între timp, ' + STAMP + '.');
  await Promise.all([ap2.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap2.click('button:has-text("Salvează setările")')]);
  await ap2.close();
  const beforeChange = (await rows()).length;
  err = await trySubmit();
  ok(/Textul despre datele personale s-a schimbat/.test(err) && (await rows()).length === beforeChange, 'GDPR: dacă textul s-a schimbat cât timp se completa, fișa nu se înscrie (' + err.slice(0, 80) + ')');
  ok(!(await ap.locator('input[name="gdpr"]').isChecked()) && /Text schimbat între timp/.test(await ap.locator('.gi-fisa-gdpr').innerText()) && (await ap.inputValue('#gi-f-nume')) === 'RAZGANDIT', 'GDPR: formularul arată textul nou, bifa e scoasă, restul datelor rămân');
  await ap.check('input[name="gdpr"]');
  await Promise.all([ap.waitForURL(/\/zona-echipei\/fisa\/\d+\/$/), ap.click('[data-gi-fisa-submit]')]);
  ok(/Text schimbat între timp/.test(await ap.locator('.gi-fisa-sheet').innerText()), 'GDPR: fișa păstrează textul bifat de turist (cel nou)');
  await ap.goto(ZONA + 'registru/' + r5[0].id + '/', { waitUntil: 'domcontentloaded' });
  const oldSheet = await ap.locator('.gi-fisa-sheet').innerText();
  ok(!/Operator Probă SRL/.test(oldSheet) && !/Text de probă despre datele/.test(oldSheet), 'setări: fișa veche rămâne cum a fost (fără datele noi ale unității)');
  const nowRows = await rows();
  await api(adm, 'fise_vechi&' + q({ id: nowRows[0].id, luni: 13 }));
  await ap.goto(ZONA + 'registru/', { waitUntil: 'domcontentloaded' });
  ok(/Termen de păstrare: 12 luni/.test(await ap.locator('.gi-zona').innerText()) && /1 fișe au trecut de termen/.test(await ap.locator('.gi-zona').innerText()), 'termen: o fișă mai veche de 12 luni e numărată');
  const before = (await rows()).length;
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.locator('button:has-text("Șterge fișele care au trecut de termen")').click()]);
  ok((await rows()).length === before - 1 && /1 fișe mai vechi de 12 luni au fost șterse/.test(await ap.locator('.gi-zona-notice').innerText()), 'termen: se șterge numai fișa trecută de termen');

  // ------------------------------------------------------------------ 10. Istoric fără date personale
  await ap.goto(ZONA + 'istoric/?tip=fisa_', { waitUntil: 'domcontentloaded' });
  const hist = await ap.locator('.gi-zona').innerText();
  ok(/A înscris fișa GI-\d{4}-00001/.test(hist) && /A șters fișa GI-\d{4}-00003/.test(hist) && /A descărcat registrul fișelor/.test(hist) && /A schimbat setările fișei/.test(hist) && /fișe mai vechi de 12 luni/.test(hist), 'istoric: înscrieri, ștergeri, descărcarea CSV, setările și ștergerea după termen apar');
  const pii = ['POPESCU', 'MUELLER', 'TERCER', 'STEFANESCU', CNP1, 'XY123456', 'C01X00T47', '0711222333', 'Hauptstrasse'];
  let leaked = [];
  for (const p of pii) { const c = (await api(adm, 'cauta_istoric&q=' + encodeURIComponent(p))).count; if (c) leaked.push(p + ':' + c); }
  ok(!leaked.length && !pii.some((p) => hist.includes(p)), 'istoric: nu conține nume, CNP, acte sau telefoane ale turiștilor (' + (leaked.join(', ') || 'curat') + ')');
  ok(!/Operator Probă SRL|RO123456/.test(JSON.stringify((await api(adm, 'istoric&n=50')).rows)), 'istoric: nici datele unității nu se scriu la setări (doar numele câmpurilor schimbate)');

  // ------------------------------------------------------------------ 10b. Două tablete trimit deodată aceeași fișă
  {
    await ap.goto(ZONA + 'fisa/?' + q({ cod: CODE }), { waitUntil: 'domcontentloaded' });
    await ap.check('input[name="depaseste"]').catch(() => {});
    await sign(ap, 'sigT'); await sign(ap, 'sigR');
    const base = await ap.evaluate(() => Object.fromEntries(new FormData(document.querySelector('[data-gi-fisa]'))));
    const sigT = await ap.evaluate(() => document.getElementById('gi-sigT').toDataURL('image/png'));
    const sigR = await ap.evaluate(() => document.getElementById('gi-sigR').toDataURL('image/png'));
    const race = Object.assign({}, base, { f_nume: 'CURSA', f_prenume: 'DUBLA', f_dob: '1988-08-08', f_cet: 'România', f_tip: 'Carte de identitate', f_doc: 'RACE0001', f_dom: 'Brașov', f_recep: 'Test', gdpr: '1', depaseste: '1', sigT, sigR });
    const both = await Promise.all([adm.request.post(ZONA + 'fisa/', { form: race, maxRedirects: 0 }), adm.request.post(ZONA + 'fisa/', { form: race, maxRedirects: 0 })]);
    const texts = await Promise.all(both.map((r) => r.text()));
    const inscrise = (await rows()).filter((r) => r.nume === 'CURSA');
    ok(inscrise.length === 1 && both.filter((r) => r.status() === 302).length === 1 && texts.some((x) => /există deja o fișă cu același act/.test(x)), 'concurență: aceeași fișă trimisă de două ori în același timp se înscrie o singură dată, a doua primește „există deja o fișă cu același act” (' + both.map((r) => r.status()).join('/') + ', ' + inscrise.length + ' înscrisă)');
  }

  // ------------------------------------------------------------------ 11. Ecrane
  {
    const state = await adm.storageState();
    const sizes = { 'telefon îngust': [320, 656], 'telefon': [360, 740], 'telefon pe lat': [740, 360], 'calculator': [1440, 900] };
    const lastId = (await rows()).slice(-1)[0].id;
    for (const [name, [w, h]] of Object.entries(sizes)) {
      const phone = Math.min(w, h) <= 430;
      const c = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone, storageState: state });
      const p = await c.newPage(); p.setDefaultTimeout(90000);
      const bad = [];
      for (const url of [ZONA + 'fisa/?' + q({ cod: CODE }), ZONA + 'fisa/' + lastId + '/', ZONA + 'registru/', ZONA + 'registru/' + lastId + '/']) {
        await p.goto(url, { waitUntil: 'load' });
        const m = await p.evaluate(() => {
          const vw = document.documentElement.clientWidth; const wide = [];
          document.querySelectorAll('.gi-zona *').forEach((el) => { if (el.closest('.gi-zona-tabel')) return; const r = el.getBoundingClientRect(); if (r.width && (r.right > vw + 1 || r.left < -1)) wide.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0]); });
          const small = [];
          document.querySelectorAll('.gi-zona :is(input:not([type=hidden]):not([type=checkbox]), select, textarea, button, a.button)').forEach((el) => { if (el.closest('.gi-zona-tabel')) return; const r = el.getBoundingClientRect(); if (r.width && r.height < 44) small.push((el.id || el.name || el.textContent.trim().slice(0, 18)) + ' ' + Math.round(r.height)); });
          const hdr = document.querySelector('.gi-site .site-header'); const h1 = document.querySelector('h1'); const rg = document.createRange(); rg.selectNodeContents(h1); const r1 = rg.getClientRects()[0];
          return { sx: document.documentElement.scrollWidth - document.documentElement.clientWidth, wide: wide.slice(0, 4), small: small.slice(0, 5), h1Below: !r1 || !hdr || r1.top >= hdr.getBoundingClientRect().bottom - 1 };
        });
        if (m.sx > 0 || m.wide.length || m.small.length || !m.h1Below) bad.push(url.replace(BASE, '') + ': ' + JSON.stringify(m));
        if (SHOTS) { await p.screenshot({ path: path.join(SHOTS, `fisa-${name.replace(/ /g, '-')}-${Date.now() % 100000}.png`), fullPage: true }); }
      }
      ok(bad.length === 0, `fișă și registru — ${name}: 4 ecrane fără derulare pe orizontală, elemente ieșite sau prea mici (${bad.join(' | ') || 'în regulă'})`);
      await c.close();
    }
  }

  // ------------------------------------------------------------------ curățenie
  await api(adm, 'sterge_comenzi'); await api(adm, 'fise_curata');
  for (const l of made.users) { await api(adm, 'sterge_utilizator&login=' + l); }
  await browser.close();
  const phpAfter = logLines();
  ok(!PHP_LOG || phpAfter === phpBefore, `erori PHP: ${PHP_LOG ? (phpAfter - phpBefore) + ' linii noi în jurnal' : 'jurnalul nu a fost dat (GI_PHP_LOG)'}`);
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
