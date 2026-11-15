// Test pentru versiunea 1.8.0 (harta Grădinii cu „Ești aici”, panouri informative cu cod QR, scriere simplă, document PDF la locuri, sigla pe plăcuțe),
// pe WordPress local pornit pe 127.0.0.1:9400/9401 (vezi tools/local-wp/README.md). NUMAI LOCAL: se autentifică cu admin/password, creează și șterge locuri de probă
// (titlul începe cu „T180”), planul Grădinii și documente PDF.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-8-0.php și gi-test-1-5-0.php) și ImageMagick (comanda `convert`) pentru planul de probă.
// Variabile opționale: GI_BASE, GI_PHP_LOG, GI_SHOTS, GI_TMP.
// Verifică:
//  - tipuri noi de locuri (bucătărie, terasă, Wi-Fi, loc de joacă, informații...); planul: încărcare, înlocuire, scoatere; poziția unui loc prin apăsare pe plan;
//  - „Ești aici” pe pagina locului (numai cu plan și poziție), pagina Harta și ghidul (puncte, listă pe grupe, ciorne absente, ?aici=), „harta” rezervat;
//  - scrierea simplă (#, -, **, link-uri; fără HTML sau javascript:), butoanele de formatare;
//  - documentul PDF: bifa „are sigla”, fișier fals respins, buton public, înlocuire, ștergere; sigla pe plăcuțele de printat; ecrane pe telefon.
const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9401';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(BASE)) {
  console.error('Refuz să rulez pe ' + BASE + ': testul e numai pentru WordPress-ul local.');
  process.exit(2);
}
const PHP_LOG = process.env.GI_PHP_LOG || '';
const TMP = process.env.GI_TMP || fs.mkdtempSync(path.join(os.tmpdir(), 'gi180-'));
let failures = 0;
function ok(condition, text) { console.log((condition ? '[OK]     ' : '[EROARE] ') + text); if (!condition) { failures++; } }
const ZONA = BASE + '/zona-echipei/';
const logLines = () => { try { return PHP_LOG ? fs.readFileSync(PHP_LOG, 'utf8').split('\n').filter(Boolean).length : 0; } catch (e) { return 0; } };

const pdfBytes = (label) => Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n% ' + label + '\ntrailer<</Root 1 0 R>>\n%%EOF\n');

(async () => {
  const phpBefore = logLines();
  const planPng = path.join(TMP, 'plan.png');
  execFileSync('convert', ['-size', '1200x800', 'gradient:#cfe5d0-#9fc5a8', planPng]);
  const plan2Png = path.join(TMP, 'plan2.png');
  execFileSync('convert', ['-size', '900x600', 'gradient:#d6e4f0-#9cb7d3', plan2Png]);
  const pdf1 = path.join(TMP, 'fisa-1.pdf'); fs.writeFileSync(pdf1, pdfBytes('unu'));
  const pdf2 = path.join(TMP, 'fisa-2.pdf'); fs.writeFileSync(pdf2, pdfBytes('doi'));
  const fake = path.join(TMP, 'fals.pdf'); fs.writeFileSync(fake, 'Acesta nu e un PDF, e doar text.\n');

  const browser = await chromium.launch();
  const newCtx = (opts) => browser.newContext(Object.assign({ viewport: { width: 1280, height: 1000 } }, opts || {}));
  const api = async (ctx, p) => (await ctx.request.get(BASE + '/?gi_test=' + p)).json();
  const adm = await newCtx(); const ap = await adm.newPage(); ap.setDefaultTimeout(90000);
  ap.on('dialog', (d) => d.accept());
  await ap.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await ap.fill('#user_login', 'admin'); await ap.fill('#user_pass', 'password');
  await Promise.all([ap.waitForURL('**/wp-admin/**'), ap.click('#wp-submit')]);
  await api(adm, 'h180_curata');
  const visitor = await newCtx(); const vp = await visitor.newPage(); vp.setDefaultTimeout(90000);
  const locuri = async () => (await api(adm, 'h180_locuri')).locuri;
  const byTitle = async (t) => (await locuri()).find((l) => l.titlu === t);

  // creează un loc prin formularul din Zonă; întoarce locul (din baza de date)
  const createPlace = async (o) => {
    await ap.goto(ZONA + 'copaci/nou/', { waitUntil: 'domcontentloaded' });
    await ap.fill('#gi-p-titlu', o.titlu);
    await ap.selectOption('#gi-p-tip', o.tip || 'loc');
    if (o.text !== undefined) { await ap.fill('#gi-p-text', o.text); }
    if (o.publica !== false) { await ap.check('input[name="publica"]'); } else { await ap.uncheck('input[name="publica"]'); }
    if (o.pdf) { await ap.setInputFiles('#gi-p-pdf', o.pdf); if (o.sigla) { await ap.check('input[name="pdf_sigla"]'); } }
    await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('.gi-zona-form button.button-primary:has-text("Salvează")')]);
    return byTitle(o.titlu);
  };
  const openPlace = async (id) => ap.goto(ZONA + 'copaci/' + id + '/', { waitUntil: 'domcontentloaded' });
  const saveForm = async () => { await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('.gi-zona-form button.button-primary:has-text("Salvează")')]); };
  const clickMap = async (px, py) => { const im = ap.locator('.gi-harta-editor img'); await im.scrollIntoViewIfNeeded(); const box = await im.boundingBox(); await ap.mouse.click(box.x + box.width * px / 100, box.y + box.height * py / 100); };

  // ------------------------------------------------------------------ 1. Tipuri noi, fără plan
  await ap.goto(ZONA + 'copaci/nou/', { waitUntil: 'domcontentloaded' });
  const options = await ap.locator('#gi-p-tip option').allInnerTexts();
  ok(['Bucătărie', 'Terasă', 'Wi-Fi', 'Loc de joacă', 'Informații', 'Băi și dușuri', 'Vatră (foc de tabără)', 'Copac', 'Plantă'].every((x) => options.includes(x)), 'tipuri: bucătărie, terasă, Wi-Fi, loc de joacă, informații, băi, vatră, copac, plantă (' + options.join(' | ') + ')');
  ok(/încarcă întâi planul/.test(await ap.locator('.gi-zona-form').innerText()) && (await ap.locator('[data-gi-harta-editor]').count()) === 0, 'hartă: fără plan încărcat, formularul locului spune să-l încarci întâi (nu arată alegerea poziției)');
  ok((await ap.locator('.gi-scris-bara button').count()) === 5, 'scriere simplă: cinci butoane de formatare peste caseta de text (Titlu, Subtitlu, Listă, Îngroșat, Link)');
  await ap.goto(ZONA + 'harta/', { waitUntil: 'domcontentloaded' });
  ok(/Planul nu e încărcat încă/.test(await ap.locator('.gi-zona').innerText()), 'hartă: pagina Harta din Zonă spune că planul nu e încărcat');

  // ------------------------------------------------------------------ 2. Locuri fără plan: pagina publică ca înainte
  const buc = await createPlace({ titlu: 'T180 Bucătăria', tip: 'bucatarie', text: 'Aici găsești bucătăria comună.' });
  await vp.goto(buc.url, { waitUntil: 'domcontentloaded' });
  ok(buc && (await vp.locator('.gi-aici, .gi-harta').count()) === 0 && /Bucătărie/i.test(await vp.locator('.eyebrow').textContent()), 'fără plan: pagina locului nu arată nicio hartă și are tipul „Bucătărie”');
  const harta0 = await visitor.request.get(BASE + '/in-gradina/harta/');
  const hTxt0 = await harta0.text();
  ok(harta0.status() === 200 && /Harta și ghidul Grădinii/.test(hTxt0) && /T180 Bucătăria/.test(hTxt0) && !/gi-pin-buton/.test(hTxt0), 'fără plan: pagina Harta și ghidul există, arată lista locurilor și nicio hartă');

  // ------------------------------------------------------------------ 3. Planul
  await ap.goto(ZONA + 'harta/', { waitUntil: 'domcontentloaded' });
  await ap.setInputFiles('#gi-h-plan', planPng);
  await ap.fill('#gi-h-alt', 'Planul de probă al Grădinii');
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('.gi-zona-form button.button-primary:has-text("Salvează")')]);
  let plan = await api(adm, 'h180_plan');
  ok(plan.img > 0 && plan.exista && plan.alt === 'Planul de probă al Grădinii' && (await ap.locator('.gi-harta-previzualizare img').count()) === 1, 'plan: se încarcă, se vede în previzualizare, cu textul alternativ');
  const planUrl1 = plan.url;

  // ------------------------------------------------------------------ 4. Poziția unui loc, prin apăsare pe plan
  await openPlace(buc.id);
  ok((await ap.locator('[data-gi-harta-editor] img').count()) === 1 && /Încă nu e marcat/.test(await ap.locator('[data-gi-harta-stare]').innerText()), 'poziție: cu plan, formularul locului arată planul și „Încă nu e marcat pe hartă”');
  await clickMap(30, 40);
  const px = parseFloat(await ap.inputValue('input[name="poz_x"]')); const py = parseFloat(await ap.inputValue('input[name="poz_y"]'));
  ok(Math.abs(px - 30) < 1.5 && Math.abs(py - 40) < 1.5 && /Marcat pe hartă/.test(await ap.locator('[data-gi-harta-stare]').innerText()) && (await ap.locator('.gi-pin-ales').isVisible()), 'poziție: apăsarea pe plan pune punctul și scrie procentele (' + px + ' / ' + py + ')');
  await saveForm();
  const b2 = await byTitle('T180 Bucătăria');
  ok(Math.abs(parseFloat(b2.x) - px) < 0.2 && Math.abs(parseFloat(b2.y) - py) < 0.2, 'poziție: se păstrează în baza de date (' + b2.x + ' / ' + b2.y + ')');
  await openPlace(buc.id);
  ok(await ap.locator('.gi-pin-ales').isVisible() && /Marcat pe hartă/.test(await ap.locator('[data-gi-harta-stare]').innerText()), 'poziție: la redeschidere punctul e la locul lui');
  // formularul fără poziție nu șterge poziția păstrată când se schimbă doar textul
  await ap.fill('#gi-p-text', 'Aici găsești bucătăria comună și un cuptor.');
  await saveForm();
  ok((await byTitle('T180 Bucătăria')).x === b2.x, 'poziție: o modificare de text nu mută și nu șterge poziția');

  // alte locuri: terasă (cu poziție), loc de joacă (cu poziție), Wi-Fi (fără poziție), o ciornă cu poziție, un panou informativ
  const mk = async (titlu, tip, x, y, publica) => {
    const l = await createPlace({ titlu, tip, publica });
    if (x !== null) { await openPlace(l.id); await clickMap(x, y); await saveForm(); }
    return byTitle(titlu);
  };
  const ter = await mk('T180 Terasa', 'terasa', 60, 55, true);
  const joc = await mk('T180 Locul de joacă', 'joaca', 80, 20, true);
  const wifi = await mk('T180 Wi-Fi', 'wifi', null, null, true);
  const draft = await mk('T180 Ciorna', 'loc', 10, 10, false);
  const info = await createPlace({ titlu: 'T180 Ce poți vizita', tip: 'info', text: '# În apropiere\n\n- Cetatea din Sâmbăta de Sus\n- Mănăstirea Brâncoveanu\n\n**Atenție:** programul se schimbă; vezi [pagina partenerilor](https://example.org/parteneri).' });

  // ------------------------------------------------------------------ 5. „Ești aici”
  await vp.goto(ter.url, { waitUntil: 'load' });
  const aici = await vp.evaluate(() => { const s = document.querySelector('.gi-aici'); if (!s) return null; const cur = s.querySelector('.gi-pin-curent'); return { h2: s.querySelector('h2').textContent, img: !!s.querySelector('.gi-harta-cadru img'), loaded: s.querySelector('img').complete && s.querySelector('img').naturalWidth > 0, left: cur && cur.style.left, top: cur && cur.style.top, label: cur && cur.querySelector('.gi-pin-eticheta') && cur.querySelector('.gi-pin-eticheta').textContent, pins: s.querySelectorAll('.gi-pin').length, link: (s.querySelector('.gi-aici-link a') || {}).href }; });
  ok(aici && aici.h2 === 'Ești aici' && aici.img && aici.loaded && Math.abs(parseFloat(aici.left) - 60) < 1.5 && Math.abs(parseFloat(aici.top) - 55) < 1.5 && aici.label === 'Ești aici', 'ești aici: pagina Terasei arată planul cu punctul „Ești aici” la 60% / 55%' + (aici ? '' : ' (lipsește)'));
  ok(await vp.evaluate(() => { const i = document.querySelector('.gi-aici img'); return !!(i && i.getAttribute('srcset') && i.getAttribute('sizes')); }), 'ești aici: planul se încarcă la mărime potrivită (are srcset), nu originalul');
  ok(aici && aici.pins === 3 && /\/in-gradina\/harta\/\?aici=t180-terasa/.test(aici.link || ''), 'ești aici: planul mic arată și celelalte locuri ca puncte mici (3 publicate cu poziție), cu link spre harta întreagă');
  await vp.goto(wifi.url, { waitUntil: 'domcontentloaded' });
  ok((await vp.locator('.gi-aici').count()) === 0, 'ești aici: un loc fără poziție (Wi-Fi) nu arată hartă');
  const draftResp = await visitor.request.get(draft.url);
  ok(draftResp.status() === 404, 'ești aici: o ciornă nu se vede public (' + draftResp.status() + ')');

  // ------------------------------------------------------------------ 6. Pagina Harta și ghidul
  await vp.goto(BASE + '/in-gradina/harta/', { waitUntil: 'load' });
  const pg = await vp.evaluate(() => ({ title: document.title, pins: document.querySelectorAll('.gi-pin-buton').length, names: Array.from(document.querySelectorAll('.gi-pin-buton')).map((b) => b.getAttribute('data-nume')), groups: Array.from(document.querySelectorAll('.gi-ghid-grupa h2')).map((h) => h.textContent), list: Array.from(document.querySelectorAll('.gi-ghid-lista a span')).map((s) => s.textContent), cardHidden: document.querySelector('[data-gi-harta-fisa]').hidden, robots: (document.querySelector('meta[name=robots]') || {}).content || '' }));
  ok(/Harta și ghidul Grădinii/.test(pg.title) && pg.pins === 3 && !pg.names.includes('T180 Ciorna') && !pg.names.includes('T180 Wi-Fi'), 'harta: pagina publică are 3 puncte (locurile publicate cu poziție), fără ciorne și fără locuri nemarcate (' + pg.names.join(', ') + ')');
  ok(pg.groups.includes('Informații') && pg.groups.includes('Locuri și facilități') && pg.list.includes('T180 Wi-Fi') && pg.list.includes('T180 Ce poți vizita') && !pg.list.includes('T180 Ciorna'), 'ghid: lista pe grupe cuprinde și locurile nemarcate pe hartă (Wi-Fi) și panoul informativ, fără ciorne (' + pg.groups.join(' | ') + ')');
  ok(pg.cardHidden && !/noindex/.test(pg.robots), 'harta: cardul locului ales e ascuns la început; pagina se poate găsi în Google');
  await vp.locator('.gi-pin-buton[data-nume="T180 Locul de joacă"]').click();
  const card = await vp.evaluate(() => { const c = document.querySelector('[data-gi-harta-fisa]'); return { hidden: c.hidden, nume: c.querySelector('[data-gi-harta-nume]').textContent, tip: c.querySelector('[data-gi-harta-tip]').textContent, href: c.querySelector('[data-gi-harta-link]').href }; });
  ok(!card.hidden && card.nume === 'T180 Locul de joacă' && card.tip === 'Loc de joacă' && card.href === joc.url, 'harta: apăsarea pe un punct arată numele, tipul și butonul spre pagina locului');
  await vp.goto(BASE + '/in-gradina/harta/?aici=t180-bucataria', { waitUntil: 'load' });
  const here = await vp.evaluate(() => ({ cur: document.querySelectorAll('.gi-pin-curent').length, shown: !document.querySelector('[data-gi-harta-fisa]').hidden, name: document.querySelector('[data-gi-harta-nume]').textContent, text: (document.querySelector('.gi-aici-text') || {}).textContent }));
  ok(here.cur === 1 && here.shown && here.name === 'T180 Bucătăria' && /Ești aici: T180 Bucătăria/.test(here.text || ''), 'harta: cu ?aici=… locul din cod e marcat „Ești aici” și ales');
  await vp.goto(BASE + '/in-gradina/harta/?aici=t180-ciorna', { waitUntil: 'load' });
  ok((await vp.locator('.gi-pin-curent').count()) === 0, 'harta: ?aici= cu o ciornă nu dezvăluie nimic');

  // ------------------------------------------------------------------ 7. „harta” rezervat
  const rez = await createPlace({ titlu: 'Harta', tip: 'loc' });
  const rezB = await createPlace({ titlu: 'Harta', tip: 'loc' }).catch(() => null);
  const slugs = (await locuri()).filter((l) => l.titlu === 'Harta').map((l) => l.slug);
  const rez1 = await visitor.request.get(BASE + '/in-gradina/harta/');
  ok(slugs.length === 2 && new Set(slugs).size === 2 && slugs.every((s) => /^harta-\d+$/.test(s)), 'adrese: două locuri numite „Harta” primesc adrese diferite, niciuna „harta” (' + slugs.join(', ') + ')');
  const both = await Promise.all((await locuri()).filter((l) => l.titlu === 'Harta').map((l) => visitor.request.get(l.url)));
  ok(both.length === 2 && both.every((r) => r.status() === 200), 'adrese: ambele locuri numite „Harta” se deschid pe adresele lor');
  ok(rez1.status() === 200 && /Harta și ghidul Grădinii/.test(await rez1.text()), 'adrese: /in-gradina/harta/ rămâne pagina hărții');

  // ------------------------------------------------------------------ 8. Scriere simplă
  const text = '# Program\n\n## Vara\n\n- Luni: închis\n- Marți: 10:00–18:00\n\n1. Vii\n2. Vezi\n\n**gros** și *înclinat* și [link](https://example.org/x?a=1&b=2) și [telefon](tel:0721333164)\n\nUn text cu <script>window.__xss=1</script> și un [rău](javascript:window.__xss=2) link.\n\n> citat frumos\n\n---\n\nSfârșit.';
  const fmt = await createPlace({ titlu: 'T180 Formatare', tip: 'info', text });
  await vp.goto(fmt.url, { waitUntil: 'load' });
  const body = await vp.evaluate(() => { const a = document.querySelector('.gi-punct-body'); const q = (s) => Array.from(a.querySelectorAll(s)); return { h2: q('h2').map((x) => x.textContent), h3: q('h3').map((x) => x.textContent), ul: q('ul li').map((x) => x.textContent), ol: q('ol li').map((x) => x.textContent), strong: q('strong').map((x) => x.textContent), em: q('em').map((x) => x.textContent), links: q('a').map((x) => ({ t: x.textContent, h: x.getAttribute('href'), target: x.getAttribute('target'), rel: x.getAttribute('rel') })), quote: q('blockquote').length, hr: q('hr').length, xss: window.__xss === undefined, scripts: q('script').length, html: a.innerHTML }; });
  ok(body.h2.includes('Program') && body.h3.includes('Vara') && body.ul.length === 2 && body.ol.length === 2 && body.strong.includes('gros') && body.em.includes('înclinat') && body.quote === 1 && body.hr === 1, 'scriere simplă: # și ## devin titluri, „- ” listă, „1. ” listă numerotată, ** îngroșat, * înclinat, > citat, --- linie (' + JSON.stringify({ h2: body.h2, ul: body.ul.length, ol: body.ol.length }) + ')');
  const lk = body.links.find((l) => l.t === 'link'); const tel = body.links.find((l) => l.t === 'telefon');
  ok(lk && /^https:\/\/example\.org\/x\?a=1(&|&#038;|&amp;)b=2$/.test(lk.h) && lk.target === '_blank' && /noopener/.test(lk.rel || '') && tel && tel.h === 'tel:0721333164', 'scriere simplă: [text](https://…) devine link care se deschide în filă nouă, [text](tel:…) link de telefon');
  ok(body.xss && body.scripts === 0 && !/<script/i.test(body.html) && !body.links.some((l) => /javascript:/i.test(l.h || '')) && /\[rău\]\(javascript:window/.test(body.html), 'scriere simplă: eticheta <script> se scoate la salvare (rămâne cel mult textul dinăuntru), iar [text](javascript:…) nu devine link; nimic nu se execută');
  // „%” din adrese și din text nu se pierde; etichete HTML vechi (<b>, <hr>) se păstrează; un „<” singur nu e HTML; * din adresă nu strică link-ul
  const pct = await createPlace({ titlu: 'T180 Procente', tip: 'info', text: 'Vezi [meniul](https://example.org/Meniu%20Vara%C8%99.pdf) și 10%de reducere și pret 5 < 10 lei. Un link ciudat: [x](https://a.example/**a**) și **îngroșat**.' });
  await vp.goto(pct.url, { waitUntil: 'load' });
  const pc = await vp.evaluate(() => { const a = document.querySelector('.gi-punct-body'); return { href: (Array.from(a.querySelectorAll('a')).find((x) => x.textContent === 'meniul') || {}).href, text: a.innerText, strong: Array.from(a.querySelectorAll('strong')).map((s) => s.textContent), bad: /<\/?strong>|&gt;x/.test(a.innerText), weird: !!Array.from(a.querySelectorAll('a')).find((x) => x.textContent === 'x') }; });
  ok(/Meniu%20Vara%C8%99\.pdf$/.test(pc.href || '') && /10%de reducere/.test(pc.text) && /pret 5 < 10 lei/.test(pc.text), 'scriere simplă: „%” din link-uri (%20) și din text se păstrează la salvare; un „<” singur rămâne text');
  ok(!pc.bad && pc.weird && pc.strong.includes('îngroșat'), 'scriere simplă: * dintr-o adresă nu strică eticheta link-ului și nu apare cod HTML pe pagină');
  const oldHtml = await createPlace({ titlu: 'T180 Html vechi', tip: 'loc', text: '<b>gros</b> text<hr><small>mic</small>' });
  await vp.goto(oldHtml.url, { waitUntil: 'load' });
  ok((await vp.locator('.gi-punct-body b').count()) === 1 && (await vp.locator('.gi-punct-body hr').count()) === 1 && !/&lt;b&gt;|<b>/.test(await vp.locator('.gi-punct-body').innerText()), 'scriere simplă: un text vechi cu <b>, <hr>, <small> rămâne HTML (nu se arată ca text)');
  // legacy: conținut cu HTML rămâne cum e
  const legacy = await createPlace({ titlu: 'T180 Cu HTML', tip: 'loc', text: '<p>Paragraf <strong>vechi</strong></p><h3>Titlu vechi</h3>' });
  await vp.goto(legacy.url, { waitUntil: 'load' });
  ok((await vp.locator('.gi-punct-body h3').innerText()) === 'Titlu vechi' && (await vp.locator('.gi-punct-body strong').innerText()) === 'vechi', 'scriere simplă: un text cu HTML (din editorul WordPress) rămâne neschimbat');
  // butoanele
  await openPlace(fmt.id);
  await ap.locator('#gi-p-text').fill('');
  await ap.click('.gi-scris-bara button:has-text("Titlu")');
  await ap.keyboard.type('Salut');
  ok((await ap.inputValue('#gi-p-text')) === '# Salut', 'scriere simplă: butonul „Titlu” pune „# ” la începutul rândului');
  await ap.fill('#gi-p-text', 'text'); await ap.locator('#gi-p-text').selectText();
  await ap.click('.gi-scris-bara button:has-text("Îngroșat")');
  ok((await ap.inputValue('#gi-p-text')) === '**text**', 'scriere simplă: butonul „Îngroșat” înconjoară textul ales cu **');

  // ------------------------------------------------------------------ 9. Document PDF
  await openPlace(buc.id);
  await ap.setInputFiles('#gi-p-pdf', pdf1);
  await saveForm();
  let msg = await ap.locator('.gi-zona-notice').allInnerTexts();
  ok(msg.some((m) => /bifează că are sigla și numele Grădinii Ielelor/.test(m)) && !(await byTitle('T180 Bucătăria')).pdf, 'PDF: fără bifa „are sigla și numele” documentul nu se încarcă, iar restul se salvează');
  await openPlace(buc.id);
  await ap.setInputFiles('#gi-p-pdf', fake); await ap.check('input[name="pdf_sigla"]');
  await saveForm();
  msg = await ap.locator('.gi-zona-notice').allInnerTexts();
  ok(msg.some((m) => /alege un fișier PDF/.test(m)) && !(await byTitle('T180 Bucătăria')).pdf, 'PDF: un text numit .pdf (nu începe cu %PDF-) e respins');
  await openPlace(buc.id);
  await ap.setInputFiles('#gi-p-pdf', pdf1); await ap.check('input[name="pdf_sigla"]');
  await saveForm();
  let b3 = await byTitle('T180 Bucătăria');
  ok(b3.pdf > 0 && b3.pdf_exista && /\.pdf$/.test(b3.pdf_url), 'PDF: cu bifa, documentul se încarcă (' + b3.pdf_url.split('/').pop() + ')');
  await vp.goto(buc.url, { waitUntil: 'domcontentloaded' });
  const dl = vp.locator('.gi-punct-pdf a.button');
  const href = await dl.getAttribute('href');
  ok((await dl.count()) === 1 && /Descarcă fișa \(PDF, [\d.,]+ ?(B|KB)\)/.test(await dl.innerText()) && href === b3.pdf_url, 'PDF: pagina publică are butonul „Descarcă fișa (PDF, mărime)”: ' + (await dl.innerText()).trim());
  const got = await visitor.request.get(href);
  ok(got.status() === 200 && /application\/pdf/.test(got.headers()['content-type'] || '') && (await got.body()).slice(0, 5).toString() === '%PDF-', 'PDF: linkul din buton descarcă un PDF adevărat');
  await openPlace(buc.id);
  ok(/Acum:/.test(await ap.locator('.gi-zona-form').innerText()), 'PDF: formularul arată documentul de acum');
  await ap.setInputFiles('#gi-p-pdf', pdf2); await ap.check('input[name="pdf_sigla"]');
  await saveForm();
  const b4 = await byTitle('T180 Bucătăria');
    const oldPdf = await api(adm, 'h180_atas&id=' + b3.pdf);
  ok(b4.pdf !== b3.pdf && b4.pdf_exista && b4.pdf_url !== b3.pdf_url && !oldPdf.atas && !oldPdf.fisier, 'PDF: înlocuirea pune documentul nou și șterge documentul vechi (atașamentul și fișierul)');
  await openPlace(buc.id);
  await ap.setInputFiles('#gi-p-pdf', fake); await ap.check('input[name="pdf_sigla"]'); await ap.check('input[name="pdf_sterge"]');
  await saveForm();
  const keep = await byTitle('T180 Bucătăria');
  ok(keep.pdf === b4.pdf && keep.pdf_exista, 'PDF: dacă fișierul nou e respins, documentul vechi rămâne (nu se pierde nici dacă era bifat „Șterge”)');
  await openPlace(buc.id);
  await ap.check('input[name="pdf_sterge"]');
  await saveForm();
  const b5 = await byTitle('T180 Bucătăria');
  await vp.goto(buc.url, { waitUntil: 'domcontentloaded' });
  ok(!b5.pdf && (await vp.locator('.gi-punct-pdf').count()) === 0 && true, 'PDF: „Șterge documentul” scoate butonul');
  const delPdf = await api(adm, 'h180_atas&id=' + b4.pdf);
  ok(!delPdf.atas && !delPdf.fisier, 'PDF: „Șterge documentul” șterge și atașamentul și fișierul din server');

  // ------------------------------------------------------------------ 10. Sigla pe plăcuțe
  await ap.goto(ZONA + 'copaci/', { waitUntil: 'domcontentloaded' });
  const printHref = await ap.evaluate((id) => { const f = document.querySelector('form[action*="tipar"]'); const n = f.querySelector('input[name="_gi_nonce"]').value; return '/zona-echipei/tipar/?tip=copaci&ids[]=' + id + '&size=8&_gi_nonce=' + n; }, buc.id);
  const pop = await adm.newPage(); await pop.goto(BASE + printHref, { waitUntil: 'load' });
  const tileBrand = await pop.evaluate(() => Array.from(document.querySelectorAll('.gi-tile .brand')).map((b) => { const i = b.querySelector('img'); return { text: b.innerText, loaded: !!(i && i.complete && i.naturalWidth > 0) }; }));
  ok(tileBrand.length === 1 && /Grădina Ielelor/.test(tileBrand[0].text) && tileBrand[0].loaded, 'regula de bază: plăcuța de printat a locului are sigla și numele „Grădina Ielelor”');
  await pop.close();

  // ------------------------------------------------------------------ 10b. Codul QR al ghidului întreg
  await ap.goto(ZONA + 'harta/', { waitUntil: 'domcontentloaded' });
  const ghidQr = await ap.locator('.gi-zona-card:has-text("Codul QR al ghidului întreg") [data-qr-url]').getAttribute('data-qr-url');
  ok(ghidQr === BASE + '/in-gradina/harta/?utm_source=qr&utm_medium=print&utm_campaign=gradina', 'ghid: Zona → Harta arată codul QR al paginii Harta și ghidul (' + ghidQr + ')');
  const ghidHref = await ap.locator('a:has-text("Printează acest cod")').getAttribute('href');
  const ghidPop = await adm.newPage(); await ghidPop.goto(ghidHref, { waitUntil: 'load' });
  const ghidTile = await ghidPop.evaluate(() => { const t = document.querySelector('.gi-tile'); const b = t && t.querySelector('.brand'); const i = b && b.querySelector('img'); return t ? { qr: t.querySelector('.qr').getAttribute('data-qr-url'), title: t.querySelector('strong').textContent, brand: b ? b.innerText : '', logo: !!(i && i.complete && i.naturalWidth > 0) } : null; });
  ok(ghidTile && ghidTile.qr === ghidQr && /Harta și ghidul/.test(ghidTile.title) && /Grădina Ielelor/.test(ghidTile.brand) && ghidTile.logo, 'ghid: plăcuța de printat are codul QR al ghidului, titlul și sigla cu numele Grădinii Ielelor');
  await ghidPop.close();
  const ghidSales = await visitor.request.get(BASE + ghidHref.replace(BASE, ''), { maxRedirects: 0 });
  ok(!/gi-tile/.test(await ghidSales.text()), 'ghid: fără cont, plăcuța de printat nu se arată');

  // ------------------------------------------------------------------ 11. Plan nou: înlocuire; scoatere
  await ap.goto(ZONA + 'harta/', { waitUntil: 'domcontentloaded' });
  ok(/3 din \d+ locuri publicate sunt marcate pe hartă/.test(await ap.locator('.gi-zona').innerText()), 'plan: pagina Harta din Zonă numără locurile marcate (3)');
  const oldPlanId = plan.img;
  await ap.setInputFiles('#gi-h-plan', plan2Png);
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('.gi-zona-form button.button-primary:has-text("Salvează")')]);
  plan = await api(adm, 'h180_plan');
  const oldPlan = await api(adm, 'h180_atas&id=' + oldPlanId);
  ok(plan.exista && plan.img !== oldPlanId && plan.url !== planUrl1 && !oldPlan.atas && !oldPlan.fisier, 'plan: înlocuirea pune planul nou și șterge planul vechi (atașamentul și fișierul)');
  await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('button:has-text("Scoate planul")')]);
  plan = await api(adm, 'h180_plan');
  await vp.goto(ter.url, { waitUntil: 'domcontentloaded' });
  ok(!plan.img && (await vp.locator('.gi-aici').count()) === 0 && (await byTitle('T180 Terasa')).x === ter.x, 'plan: după „Scoate planul” hărțile dispar din pagini, dar pozițiile rămân păstrate');

  // ------------------------------------------------------------------ 12. Ecrane pe telefon
  {
    await ap.goto(ZONA + 'harta/', { waitUntil: 'domcontentloaded' });
    await ap.setInputFiles('#gi-h-plan', planPng);
    await Promise.all([ap.waitForNavigation({ waitUntil: 'domcontentloaded' }), ap.click('.gi-zona-form button.button-primary:has-text("Salvează")')]);
    const state = await adm.storageState();
    for (const [name, w, h] of [['telefon îngust', 320, 656], ['telefon', 390, 844], ['calculator', 1440, 900]]) {
      const phone = w < 500;
      const c = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone, storageState: state });
      const p = await c.newPage(); p.setDefaultTimeout(90000);
      const bad = [];
      for (const url of [ter.url, BASE + '/in-gradina/harta/?aici=t180-terasa', ZONA + 'harta/', ZONA + 'copaci/' + buc.id + '/']) {
        await p.goto(url, { waitUntil: 'load' });
        const m = await p.evaluate(() => {
          const small = Array.from(document.querySelectorAll('.gi-pin-buton')).filter((b) => { const r = b.getBoundingClientRect(); return r.width < 43.5 || r.height < 43.5; }).length;
          const img = document.querySelector('.gi-harta-cadru img');
          return { sx: document.documentElement.scrollWidth - document.documentElement.clientWidth, small, imgOk: img ? img.getBoundingClientRect().width <= document.documentElement.clientWidth + 1 : true };
        });
        if (m.sx > 0 || m.small || !m.imgOk) { bad.push(url.replace(BASE, '') + ': ' + JSON.stringify(m)); }
      }
      ok(bad.length === 0, `hartă — ${name}: 4 pagini fără derulare pe orizontală, puncte ușor de atins (44 px), plan în ecran (${bad.join(' | ') || 'în regulă'})`);
      await c.close();
    }
  }

  // ------------------------------------------------------------------ curățenie
  await api(adm, 'h180_curata');
  await browser.close();
  const phpAfter = logLines();
  ok(!PHP_LOG || phpAfter === phpBefore, `erori PHP: ${PHP_LOG ? (phpAfter - phpBefore) + ' linii noi în jurnal' : 'jurnalul nu a fost dat (GI_PHP_LOG)'}`);
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
