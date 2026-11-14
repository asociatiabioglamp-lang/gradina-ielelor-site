// Test pentru versiunea 1.3.14, pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md).
// Are nevoie de mu-plugin-urile gi-test-1-3-14.php (numai local) și de produsul „Produs de probă” cu plata la locație.
// Verifică: textele în română la coș, plată, mulțumire și cont (și că administrarea și emailurile rămân neatinse);
// reparațiile SEO (adresa dev24, schema, harta site-ului, numele site-ului, titlurile vechi); mesajul cu adulții și termenii
// (pe o copie a formularului de pe live); tariful corectat la camping.
// Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules): node test-1-3-14.js
// Iese cu cod 1 dacă o verificare pică.
const { chromium, request } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const has = (t, s) => t.includes(s);

(async () => {
  const follow = await request.newContext({ baseURL: BASE });
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 1000 } });
  const page = await ctx.newPage();
  page.setDefaultTimeout(60000);
  const text = async () => (await page.textContent('body')).replace(/\s+/g, ' ');

  // 0. Pregătirea mediului local ca pe live: coșul clasic, „Ai deja cont?” la plată și înregistrare în Contul meu
  const actx = await browser.newContext();
  const adm = await actx.newPage();
  adm.setDefaultTimeout(60000);
  await adm.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await adm.fill('#user_login', 'admin'); await adm.fill('#user_pass', 'password'); await adm.click('#wp-submit');
  await adm.waitForURL('**/wp-admin/**');
  const nonce = (await (await adm.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  const H = { 'X-WP-Nonce': nonce };
  for (const slug of ['cart']) {
    const found = await (await adm.request.get(BASE + '/wp-json/wp/v2/pages?slug=' + slug + '&context=edit', { headers: H })).json();
    if (found[0]) { await adm.request.post(BASE + '/wp-json/wp/v2/pages/' + found[0].id, { headers: H, data: { content: '[woocommerce_cart]' } }); }
  }
  for (const key of ['woocommerce_enable_checkout_login_reminder', 'woocommerce_enable_myaccount_registration']) {
    await adm.request.put(BASE + '/wp-json/wc/v3/settings/account/' + key, { headers: H, data: { value: 'yes' } });
  }
  // pagina „Termeni și condiții” setată în WooCommerce, ca pe live (apare bifa cu propoziția implicită)
  const termsPages = await (await adm.request.get(BASE + '/wp-json/wp/v2/pages?slug=despre-noi&context=edit', { headers: H })).json();
  if (termsPages[0]) { await adm.request.put(BASE + '/wp-json/wc/v3/settings/advanced/woocommerce_terms_page_id', { headers: H, data: { value: String(termsPages[0].id) } }); }

  // 1. Coș
  await page.goto(BASE + '/?add-to-cart=30', { waitUntil: 'networkidle' });
  await page.goto(BASE + '/cart/', { waitUntil: 'networkidle' });
  let t = await text();
  ok(['Totalul rezervării', 'Continuă spre plată', 'Actualizează coșul', 'Aplică codul', 'Preț'].every((s) => has(t, s)), 'coș: „Totalul rezervării”, „Continuă spre plată”, „Actualizează coșul”, „Aplică codul”, „Preț”');
  ok(!/Cart totals|Proceed to checkout|Update cart|Apply coupon/.test(t), 'coș: nu mai apar „Cart totals”, „Proceed to checkout”, „Update cart”, „Apply coupon”');

  // 2. Finalizare
  await page.goto(BASE + '/checkout/', { waitUntil: 'networkidle' });
  await page.waitForSelector('#billing_first_name');
  t = await text();
  ok(['Date de facturare', 'Rezervarea ta', 'Prenume', 'Telefon', 'Adresa de email', 'Observații', 'Finalizează rezervarea', 'Ai deja cont?'].every((s) => has(t, s)), 'plată: „Date de facturare”, „Rezervarea ta”, „Prenume”, „Telefon”, „Adresa de email”, „Observații”, „Finalizează rezervarea”, „Ai deja cont?”');
  ok(!/Billing details|Your order|First name|Place order|Returning customer/.test(t), 'plată: nu mai apar „Billing details”, „Your order”, „First name”, „Place order”, „Returning customer”');
  ok(has(t, '(opțional)') && !/\(optional\)/.test(t), 'plată: „(opțional)” (nu „(optional)”) la câmpurile facultative');
  ok(has(t, 'Am citit și sunt de acord cu termenii și condițiile site-ului') && !/I have read and agree/.test(t), 'plată: bifa de termeni e în română, întreagă („Am citit și sunt de acord cu termenii și condițiile site-ului”)');
  // validare: formular gol → mesaje în română (întâi trecem de caseta cu regulamentul și de bifa de termeni)
  await page.evaluate(() => { const c = document.querySelector('#gi_roi'); if (c) { c.removeAttribute('aria-disabled'); c.checked = true; } const r = document.querySelector('[name=gi_roi_seen]'); if (r) { r.value = '1'; } });
  if (await page.$('#terms')) { await page.locator('#terms').check().catch(() => {}); }
  await page.locator('#payment_method_cod').check().catch(() => {});
  await page.click('#place_order');
  await page.waitForSelector('.woocommerce-NoticeGroup-checkout', { state: 'attached', timeout: 30000 }).catch(() => {});
  const err = ((await page.locator('.woocommerce-NoticeGroup-checkout').first().textContent().catch(() => '')) || '').replace(/\s+/g, ' ');
  ok(/Am găsit următoarele probleme/.test(err) && /\(facturare\) este un câmp obligatoriu/.test(err) && !/The following problems|is a required field/.test(err), 'plată: formular gol → „Am găsit următoarele probleme” și „… (facturare) este un câmp obligatoriu” („' + err.slice(0, 90) + '”)');
  const ph = await page.$$eval('input[placeholder], textarea[placeholder]', (els) => els.map((e) => e.placeholder));
  ok(ph.includes('Strada și numărul') && ph.some((x) => /Observații despre rezervare/.test(x)) && !ph.some((x) => /House number|Notes about your order|Apartment, suite/.test(x)), 'plată: și textele din interiorul câmpurilor (placeholder) sunt în română');

  // 3. Comandă cu plata la locație → pagina de mulțumire
  await page.fill('#billing_first_name', 'Test'); await page.fill('#billing_last_name', 'Test'); await page.fill('#billing_address_1', 'Strada 1');
  await page.fill('#billing_city', 'Brasov'); await page.fill('#billing_phone', '0700000000'); await page.fill('#billing_email', 'test@example.com');
  if (await page.$('#billing_postcode')) { await page.fill('#billing_postcode', '500001').catch(() => {}); }
  await page.locator('#payment_method_cod').check().catch(() => {});
  if (await page.$('#terms')) { await page.locator('#terms').check().catch(() => {}); }
  await page.evaluate(() => { const c = document.querySelector('#gi_roi'); if (c) { c.removeAttribute('aria-disabled'); c.checked = true; } const r = document.querySelector('[name=gi_roi_seen]'); if (r) { r.value = '1'; } });
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle', timeout: 60000 }).catch(() => {}), page.click('#place_order')]);
  await page.waitForTimeout(1500);
  t = await text();
  ok(/order-received/.test(page.url()), 'comanda a fost plasată (' + page.url().replace(BASE, '').slice(0, 40) + ')');
  ok(['Mulțumim! Am primit rezervarea ta.', 'Număr rezervare:', 'Detaliile rezervării', 'Metodă de plată:'].every((s) => has(t, s)), 'mulțumire: „Mulțumim! Am primit rezervarea ta.”, „Număr rezervare:”, „Detaliile rezervării”, „Metodă de plată:”');
  ok(!/Thank you\. Your order|Order number|Order details|Payment method:/.test(t), 'mulțumire: nu mai apar „Thank you…”, „Order number”, „Order details”');

  // 4. Cont (vizitator)
  await page.context().clearCookies();
  await page.goto(BASE + '/my-account/', { waitUntil: 'networkidle' });
  t = await text();
  ok(['Autentifică-te', 'Ai uitat parola?', 'Înregistrare'].every((s) => has(t, s)) && !/Lost your password|Remember me/.test(t), 'cont: „Autentifică-te”, „Ai uitat parola?”, „Înregistrare” (și nu „Lost your password?”)');

  // 5. Administrarea rămâne neatinsă
  await adm.goto(BASE + '/wp-admin/edit.php?post_type=shop_order', { waitUntil: 'domcontentloaded' });
  t = (await adm.textContent('body')).replace(/\s+/g, ' ');
  ok(/Orders/.test(t) && !/Rezervări|Detaliile rezervării/.test(t), 'administrare: pagina Comenzi rămâne în engleză (nu e atinsă)');

  // 6. Emailurile: nicio traducere după începerea unui email
  const em = await (await follow.get('/?gi_test=email')).json();
  ok(em.inainte === 'Detaliile rezervării' && em.dupa === 'Order details', 'emailuri: înainte de email se traduce („' + em.inainte + '”), după începerea unui email NU („' + em.dupa + '”)');

  const em2 = await (await follow.get('/?gi_test=email2')).json();
  ok(em2.in_email === 'Order details' && em2.dupa_trimitere === 'Detaliile rezervării' && em2.text_simplu === 'Order details', 'emailuri: în email NU se traduce, după trimiterea emailului se traduce din nou, în emailul „text simplu” NU se traduce');

  const brw = await (await follow.get('/?gi_test=brw')).json();
  ok(brw.obligatoriu === 'Completează acest câmp.' && /Bifează/.test(brw.termeni) && /nu se pot face rezervări/.test(brw.zi) && brw.neatins === 'Some other string', 'formularul de rezervare: mesajele BRW sunt în română („' + brw.obligatoriu + '”), restul textelor rămân neatinse');
  ok(brw.tipar === 'Nu avem disponibilitate în intervalul', 'greșeala de tipar „dispobibilitate” dintr-o traducere existentă e corectată');

  // 7. SEO
  const seo = await (await follow.get('/?gi_test=seo')).json();
  const home = new URL(seo.home); const realOrigin = home.origin;
  ok(seo.canonical === realOrigin + '/shop/', 'SEO: canonical cu „dev24” → adresa reală (' + seo.canonical + ')');
  ok(seo.og_url === realOrigin + '/blog/', 'SEO: og:url cu „dev24” → adresa reală');
  ok(seo.canonical_ok === realOrigin + '/glamping/', 'SEO: un canonical corect rămâne neschimbat');
  ok(seo.graph[0]['@id'] === realOrigin + '/shop/#webpage' && seo.graph[0].nested.x === realOrigin + '/shop/#breadcrumb' && seo.graph[0].nested.ok === 'https://exemplu.ro/pagina/', 'SEO: în datele structurate, „dev24” (și în arbori) → adresa reală; alte adrese neatinse');
  ok(Array.isArray(seo.org['@type']) && seo.org['@type'].includes('Campground') && seo.org['@type'].includes('Organization') && seo.org.telephone === '+40721333164' && seo.org.address.addressLocality === 'Sâmbăta de Sus' && seo.org.email === 'rezervari@gradina-ielelor.ro', 'SEO: Organizația devine și „Campground”, cu telefon, email și localitate');
  ok(seo.org_cu_telefon.telephone === '+40000000000', 'SEO: un telefon deja completat în Yoast nu se schimbă');
  ok(seo.entry_loc.loc === realOrigin + '/x/', 'SEO: adresa din harta site-ului cu „dev24” → adresa reală');
  ok(seo.og_image === realOrigin + '/wp-content/uploads/a.jpg', 'SEO: imaginea og:image cu „dev24” → adresa reală');
  ok(seo.host_lung === 'https://dev24.gradina-ielelor.ro.exemplu.com/x/', 'SEO: o adresă străină care doar începe la fel ca „dev24” NU se schimbă');
  ok(seo.website.name === 'Grădina Ielelor' && seo.og_site === 'Grădina Ielelor', 'SEO: numele site-ului în datele structurate și în og:site_name devine „Grădina Ielelor”');
  ok(seo.titlu_scurtat === 'Despre noi | Grădina Ielelor', 'SEO: numele site-ului din titlu devine „Grădina Ielelor” („' + seo.titlu_scurtat + '”)');
  ok(seo.titlu_fara_nume === 'Locatie Glamping Romania - Camping, Brasov, Gradina Ielelor', 'SEO: titlul paginii principale din Yoast (fără numele site-ului) rămâne neschimbat');

  // 1.3.15: meniurile vechi duc direct la paginile noi; subsolul fără linkul SOL
  const bung = (await (await follow.get('/bungalow/')).url()) || '';
  ok(seo.meniu[2] === realOrigin + '/glamping/' && seo.meniu[3] === 'https://exemplu.ro/tarife/' && /\/tarife\/\?x=1$/.test(seo.meniu[4]), 'meniu: linkurile obișnuite, cele externe și cele cu parametri rămân neschimbate');
  ok(!seo.legale.some((l) => /SOL|Litigii online/i.test(l)) && seo.legale.some((l) => /ANPC/.test(l)), 'subsol: fără „Litigii online (SOL)”, cu ANPC (' + seo.legale.join(', ') + ')');

  // harta site-ului: paginile vechi care redirecționează + coș/plată/cont
  const mkpage = async (slug, title) => {
    const ex = await (await adm.request.get(BASE + '/wp-json/wp/v2/pages?slug=' + slug + '&status=publish', { headers: H })).json();
    if (ex.length) { return ex[0].id; }
    const r = await adm.request.post(BASE + '/wp-json/wp/v2/pages', { headers: H, data: { title, slug, status: 'publish', content: 'vechi' } });
    return (await r.json()).id;
  };
  const idTarife = await mkpage('tarife', 'Tarife (vechi)');
  const idBungalow = await mkpage('cazare-bungalow', 'Cazare Bungalow (vechi)');
  const seo2 = await (await follow.get('/?gi_test=seo&id=' + idTarife)).json();
  ok(seo2.exclude.includes(7), 'harta site-ului: ce era deja exclus rămâne exclus');
  ok(seo2.pagina_cos.every((id) => seo2.exclude.includes(id)), 'harta site-ului: coș, finalizare și cont sunt excluse (' + seo2.pagina_cos.join(', ') + ')');
  ok(seo2.exclude.includes(idBungalow), 'harta site-ului: „cazare-bungalow” (redirecționează spre Bungalow) e exclusă');
  ok(/\/bungalow\/$/.test(seo2.meniu[0]) && !/cazare-bungalow/.test(seo2.meniu[0]), 'meniu: „Cazare Bungalow” (adresa veche) duce direct la pagina Bungalow (' + seo2.meniu[0].replace(realOrigin, '') + ')');
  ok(seo2.meniu[1] === realOrigin + '/', 'meniu: „Tarife” (adresa veche) duce direct la prima pagină');
  ok(/\/bungalow\/#galerie$/.test(seo2.meniu[5]), 'meniu: ancora din link se păstrează (' + seo2.meniu[5].replace(realOrigin, '') + ')');
  ok(seo2.entry_scos === false && seo2.entry_pastrat && seo2.entry_pastrat.loc === 'x', 'harta site-ului: intrarea unei pagini excluse e sărită, a unei pagini obișnuite rămâne');

  // titluri vechi din Yoast la Glamping
  const leg = await (await follow.get('/glamping/?gi_test=legacy')).text();
  const titleOf = (h) => (h.match(/<title[^>]*>([^<]*)<\/title>/i) || [, ''])[1].replace(/&#8211;|&ndash;/g, '-').trim();
  ok(!/Sambata se Sus/.test(titleOf(leg)) && /Glamping Brașov/.test(titleOf(leg)), 'titlu vechi din Yoast („Sambata se Sus”) → titlul din plugin (' + titleOf(leg) + ')');
  ok(!/Facilitati All Inclusive|Sambata se Sus/.test((leg.match(/<meta name="description" content="([^"]*)"/) || [, ''])[1]), 'descriere veche din Yoast → descrierea din plugin');
  const good = await (await follow.get('/glamping/?gi_test=yoastok')).text();
  ok(titleOf(good) === 'Glamping la Grădina Ielelor, sub munte', 'un titlu nou, corect, scris în Yoast are în continuare prioritate (' + titleOf(good) + ')');

  // 8. Tarif camping
  const camp = await (await follow.get('/camping/')).text();
  ok(/30 lei/.test(camp) && /80 lei/.test(camp) && /doi adulți costă 140 lei/.test(camp) && /Pentru fiecare adult</.test(camp), 'camping: apar 80 lei (loc), 30 lei (adult) și exemplul cu 140 lei');
  const prices = (camp.match(/<dt>[^<]*<\/dt>\s*<dd>[^<]*<small>/g) || []).length;
  ok(prices === 3, 'camping: tabelul de prețuri are 3 rânduri (cort, rulotă, adult în plus), găsite: ' + prices);
  const hub = await (await follow.get('/')).text();
  ok(/80 lei loc \+ 30 lei \/ adult, pe noapte/.test(hub), 'prima pagină: cardul Camping spune „80 lei loc + 30 lei / adult, pe noapte”');

  // 9. Mesajul cu adulții și termenii (formularul BRW de pe live, copiat în tools/local-wp/fixtures)
  const fixture = fs.readFileSync(path.join(__dirname, 'fixtures', 'formular-camping.html'), 'utf8');
  const js = fs.readFileSync(path.join(__dirname, '..', '..', 'gradina-ielelor-integration', 'assets', 'js', 'produs.js'), 'utf8');
  const fp = await browser.newPage();
  await fp.setContent(fixture);
  await fp.evaluate(() => { document.querySelector('form').addEventListener('submit', (e) => e.preventDefault()); window.__prevented = null; document.addEventListener('click', (e) => { if (e.target.closest && e.target.closest('button[type=submit]')) { window.__prevented = e.defaultPrevented; } }); });
  await fp.addScriptTag({ content: js });
  await fp.click('button[type=submit]');
  let msgs = await fp.$$eval('.gi-camp-msg', (els) => els.map((e) => e.textContent));
  ok(msgs.length === 2 && /adulți/.test(msgs[0]) && /termenii/.test(msgs[1]), 'formular: fără adulți și fără termeni apar două mesaje vizibile (' + msgs.length + ')');
  ok((await fp.evaluate(() => window.__prevented)) === false, 'formular: scriptul NU oprește trimiterea (nu apelează preventDefault)');
  await fp.selectOption('select[name="ovabrw_service[]"] >> nth=0', { index: 2 });
  msgs = await fp.$$eval('.gi-camp-msg', (els) => els.length);
  ok(msgs === 0, 'formular: după ce alegi adulții, mesajele dispar');
  await fp.click('button[type=submit]');
  msgs = await fp.$$eval('.gi-camp-msg', (els) => els.map((e) => e.textContent));
  ok(msgs.length === 1 && /termenii/.test(msgs[0]), 'formular: cu adulți aleși, dar fără termeni, apare numai mesajul despre termeni');
  await fp.check('input[name="ovabrw-booking-terms-conditions"]');
  await fp.click('button[type=submit]');
  msgs = await fp.$$eval('.gi-camp-msg', (els) => els.length);
  ok(msgs === 0, 'formular: cu adulți și termeni, nu apare niciun mesaj');
  await fp.close();

  await browser.close();
  await follow.dispose();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})();
