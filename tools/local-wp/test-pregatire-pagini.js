// Test de la un capăt la altul pentru unealta „Pregătește paginile” (WordPress local pornit pe 127.0.0.1:9400, vezi tools/local-wp/README.md).
// Are nevoie de mu-plugin-ul de test gi-test-reset.php (numai local), care readuce site-ul la starea de pe live:
// fără paginile noi, șabloane implicite, Contact cu text vechi. Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules):
//   node test-pregatire-pagini.js
// Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const NOUA = ['bungalow', 'festivaluri-si-tabere', 'evenimente-private', 'despre-noi', 'regulament-politici'];

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  page.setDefaultTimeout(90000);
  const anon = await browser.newContext();
  async function vizitator(path) {
    const r = await anon.request.get(BASE + path, { failOnStatusCode: false });
    return { status: r.status(), body: await r.text() };
  }
  async function rows() {
    return page.evaluate(() => Array.from(document.querySelectorAll('#gi-pagini ~ table tbody tr')).map(tr => Array.from(tr.children).map(td => td.innerText.trim())));
  }
  async function apasa(prefix) {
    await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'networkidle' });
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('input[value^="' + prefix + '"]')]);
    const notice = await page.evaluate(() => { const n = document.querySelector('#gi-pagini ~ .notice'); return n ? n.innerText.replace(/\s+/g, ' ') : ''; });
    const body = await page.evaluate(() => document.body.innerText);
    ok(!/Fatal error|Warning:|Notice:|Deprecated:|Parse error/.test(body), 'fără erori PHP în pagina de setări după „' + prefix + '”');
    return notice;
  }

  // 0. Autentificare și readucerea la starea de pe live
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'password'); await page.click('#wp-submit');
  await page.waitForLoadState('networkidle');
  await page.goto(BASE + '/?gi_reset=1', { waitUntil: 'domcontentloaded' });
  ok(/reset ok/.test(await page.content()), 'starea de pornire e ca pe live (fără paginile noi, șabloane implicite)');

  // 1. Starea inițială arată ce lipsește
  await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'networkidle' });
  let r = await rows();
  ok(NOUA.every(s => r.some(x => x[0] === '/' + s + '/' && /lipsește/.test(x[1]))), 'tabelul arată „lipsește” la cele 5 pagini noi');
  ok(r.some(x => x[0] === '/contact/' && /altul/.test(x[2])) && r.some(x => x[0] === 'Pagina principală' && /altul/.test(x[2])), 'tabelul arată șablon „altul” la Contact și la pagina principală');

  // 2. Pasul 1: creează ciornele
  let notice = await apasa('1. Creează');
  ok(NOUA.every(s => notice.includes('/' + s + '/')) && !/EROARE|ATENȚIE/.test(notice), 'pasul 1: cele 5 pagini create, fără avertismente');
  r = await rows();
  ok(NOUA.every(s => r.some(x => x[0] === '/' + s + '/' && /ciornă \(creată de plugin\)/.test(x[1]) && /potrivit/.test(x[2]))), 'tabelul: cele 5 pagini sunt ciorne, cu șablonul potrivit');
  let v = await vizitator('/despre-noi/');
  ok(v.status === 404 || !/Cine suntem/.test(v.body), 'o ciornă nu se vede vizitatorilor');

  // 3. Idempotență: a doua apăsare nu face duplicate
  notice = await apasa('1. Creează');
  ok(NOUA.every(s => notice.includes('/' + s + '/) există deja')), 'a doua apăsare pe pasul 1 nu creează duplicate');
  const apiPagini = await (await ctx.request.get(BASE + '/wp-json/wp/v2/pages?per_page=100&status=any&context=edit', { failOnStatusCode: false })).json().catch(() => []);
  if (Array.isArray(apiPagini)) {
    for (const s of NOUA) { ok(apiPagini.filter(p => p.slug === s).length === 1, 'o singură pagină cu adresa /' + s + '/'); }
  }

  // 4. Pasul 2: șabloanele pe paginile existente
  notice = await apasa('2. Pune șabloanele');
  ok(/Contact[^.]*șablonul a trecut/.test(notice) && /Pagina principală: șablonul a trecut/.test(notice), 'pasul 2: șablon nou la Contact și la pagina principală');
  r = await rows();
  ok(r.every(x => /potrivit/.test(x[2])), 'tabelul: toate paginile au șablonul potrivit');
  notice = await apasa('2. Pune șabloanele');
  ok(!/a trecut pe cel nou/.test(notice), 'a doua apăsare pe pasul 2 nu schimbă nimic');

  // 5. Pasul 3: textul nou la Contact
  notice = await apasa('3. Pune textul');
  ok(/textul nou e pus/.test(notice), 'pasul 3: textul nou la Contact');
  await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'networkidle' });
  ok((await page.$('input[value^="3. Pune textul"]')) === null, 'după pasul 3, butonul dispare');
  v = await vizitator('/contact/');
  ok(v.status === 200 && /Sună-ne/.test(v.body) && !/TEXT VECHI ELEMENTOR/.test(v.body), '/contact/ arată textul nou, nu pe cel vechi');

  // 6. Pasul 4: publică
  notice = await apasa('4. Publică');
  ok(NOUA.every(s => notice.includes('/' + s + '/')), 'pasul 4: cele 5 pagini publicate');
  for (const [s, text] of [['despre-noi', 'Cine suntem'], ['regulament-politici', 'Rezervare și anulare'], ['bungalow', 'Bungalow'], ['festivaluri-si-tabere', 'Festival'], ['evenimente-private', 'Evenimente']]) {
    v = await vizitator('/' + s + '/');
    ok(v.status === 200 && v.body.includes(text), '/' + s + '/ e publică și conține „' + text + '”');
  }
  ok(!/Fatal error|Warning:|Notice:|Deprecated:/.test((await vizitator('/regulament-politici/')).body), 'fără erori PHP pe /regulament-politici/');

  // 7. Securitate: fără nonce, cererea e refuzată
  const fara = await ctx.request.post(BASE + '/wp-admin/admin-post.php', { form: { action: 'gi_pagini', gi_actiune: 'revino' }, failOnStatusCode: false });
  ok(fara.status() === 403, 'o cerere fără nonce e refuzată (' + fara.status() + ')');
  const anonim = await anon.request.post(BASE + '/wp-admin/admin-post.php', { form: { action: 'gi_pagini', gi_actiune: 'revino' }, failOnStatusCode: false });
  ok(anonim.status() !== 200 && !/Ce s-a făcut/.test(await anonim.text()), 'vizitatorul nelogat nu poate porni nimic (' + anonim.status() + ')');

  // 8. Revino: desface tot, fără să șteargă
  notice = await apasa('Revino');
  ok(/textul vechi e înapoi/.test(notice) && /a trecut pe ciornă/.test(notice) && /șablonul vechi/.test(notice), 'Revino: șabloane, text Contact și publicări desfăcute');
  v = await vizitator('/despre-noi/');
  ok(v.status === 404 || !/Cine suntem/.test(v.body), 'după Revino, Despre noi nu se mai vede');
  v = await vizitator('/contact/');
  ok(/TEXT VECHI ELEMENTOR/.test(v.body), 'după Revino, Contact arată iar textul vechi');
  r = await (async () => { await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'networkidle' }); return rows(); })();
  ok(NOUA.every(s => r.some(x => x[0] === '/' + s + '/' && /ciornă/.test(x[1]))), 'paginile create au rămas (ciorne), nu au fost șterse');

  // 9. Cazurile din verificarea independentă
  async function ajutor(q) { await page.goto(BASE + '/?' + q, { waitUntil: 'domcontentloaded' }); return page.content(); }
  async function stare() { await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'networkidle' }); return rows(); }

  // 9a. Adresă ocupată de un fișier din Media: nu se creează ciorna, cu mesaj clar
  await ajutor('gi_reset=1'); await ajutor('gi_ocupa=bungalow');
  notice = await apasa('1. Creează');
  ok(/EROARE: adresa \/bungalow\/ e ocupată/.test(notice), 'adresă ocupată: pasul 1 refuză, cu mesaj');
  r = await stare();
  ok(r.some(x => x[0] === '/bungalow/' && /lipsește/.test(x[1])) && r.some(x => x[0] === '/despre-noi/' && /ciornă/.test(x[1])), 'adresă ocupată: restul paginilor se creează, Bungalow lipsește');
  await ajutor('gi_elibereaza=bungalow');
  notice = await apasa('1. Creează');
  ok(/Creată ciornă: „Bungalow”/.test(notice), 'după ce adresa se eliberează, Bungalow se creează');

  // 9b. Pagină în coșul de gunoi: recunoscută, fără duplicat
  await ajutor('gi_cos=despre-noi');
  notice = await apasa('1. Creează');
  ok(/Despre noi[^.]*coșul de gunoi/.test(notice), 'pagina din coș e recunoscută (mesaj), fără pagină nouă');
  r = await stare();
  ok(r.some(x => x[0] === '/despre-noi/' && /coș/.test(x[1])), 'tabelul arată pagina din coș');

  // 9c. Blocare la dublă apăsare
  await ajutor('gi_lock=1');
  notice = await apasa('2. Pune șabloanele');
  ok(/Altă acțiune rulează/.test(notice), 'cât timp o acțiune rulează, a doua e refuzată cu mesaj');
  await ajutor('gi_unlock=1');
  notice = await apasa('2. Pune șabloanele');
  ok(!/Altă acțiune rulează/.test(notice) && /șablonul/.test(notice), 'după eliberare, acțiunea merge');
  ok((await ajutor('gi_meta=1')).length > 0, 'ajutorul de test răspunde');

  // 9d. Editări la Contact după pasul 3, apoi Revino: copia textului nou se păstrează
  await ajutor('gi_reset=1');
  await apasa('1. Creează'); await apasa('2. Pune șabloanele'); await apasa('3. Pune textul');
  await ajutor('gi_edit_contact=1');
  notice = await apasa('Revino');
  ok(/Copia textului nou/.test(notice), 'Revino: mesaj despre copia textului nou');
  const meta = await ajutor('gi_meta=1');
  ok(/meta:CONTACT EDITAT DE PROPRIETARA/.test(meta) && /elementor:builder/.test(meta), 'Revino: editările de la Contact sunt păstrate într-o copie, iar modul Elementor revine');
  ok(/TEXT VECHI ELEMENTOR/.test((await vizitator('/contact/')).body), 'Revino: Contact arată iar textul vechi');

  await browser.close();
  console.log(failures ? '\n' + failures + ' verificări au picat' : '\nToate verificările au trecut');
  process.exit(failures ? 1 : 0);
})().catch(e => { console.error('EROARE:', e.message.split('\n')[0]); process.exit(1); });
