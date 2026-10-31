// Test de la un capăt la altul pentru regulamentul la finalizarea rezervării (WordPress local pornit pe 127.0.0.1:9400, vezi tools/local-wp/README.md).
// Verifică: protecția „doar de consultat”, textul ascuns din REST, bifa blocată până la derulare, respingerea fără bifă,
// păstrarea stării când se schimbă metoda de plată, respingerea ocolirii prin Store API, comanda cu dovada acceptării.
// Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules): node test-regulament-la-plata.js <prefix-capturi>
// Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
const OUT = process.argv[2] || 'regulament-test';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
async function idle(page) {
  await page.waitForFunction(() => !document.querySelector('.blockUI'), null, { timeout: 30000 }).catch(() => {});
  await page.waitForTimeout(700);
}
async function state(page) {
  return page.evaluate(() => {
    const cb = document.querySelector('#gi_roi');
    const box = document.querySelector('[data-gi-roi-box]');
    return { checked: cb.checked, locked: cb.getAttribute('aria-disabled') === 'true', seen: document.querySelector('[name=gi_roi_seen]').value, scrollTop: Math.round(box.scrollTop) };
  });
}

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 900 } });
  const page = await ctx.newPage();
  page.setDefaultTimeout(60000);

  // 1. Pagina regulamentului: doar de consultat; textul nu iese prin REST
  await page.goto(BASE + '/regulament-politici/', { waitUntil: 'networkidle' });
  const prot = await page.evaluate(() => {
    const el = document.querySelector('[data-gi-protejat]');
    const copy = new Event('copy', { bubbles: true, cancelable: true }); el.dispatchEvent(copy);
    const menu = new Event('contextmenu', { bubbles: true, cancelable: true }); el.dispatchEvent(menu);
    return { select: getComputedStyle(el).userSelect, copy: copy.defaultPrevented, menu: menu.defaultPrevented, pdf: document.querySelectorAll('a[href$=".pdf"]').length };
  });
  ok(prot.select === 'none' && prot.copy && prot.menu, 'pagina regulamentului: fără selectare, copiere și clic dreapta');
  ok(prot.pdf === 0, 'pagina regulamentului: niciun link spre fișier PDF');
  await page.emulateMedia({ media: 'print' });
  ok((await page.evaluate(() => getComputedStyle(document.querySelector('[data-gi-protejat]')).display)) === 'none', 'la tipărire, textul regulamentului nu se tipărește');
  await page.emulateMedia({ media: 'screen' });
  const rest = await (await page.request.get(BASE + '/wp-json/wp/v2/pages?slug=regulament-politici')).json();
  ok(rest.length === 1 && rest[0].content.rendered === '' && rest[0].excerpt.rendered === '', 'REST public: textul regulamentului nu se mai trimite');

  // 2. Coș și finalizare
  const prods = await (await page.request.get(BASE + '/wp-json/wc/store/v1/products?search=prob')).json();
  await page.goto(BASE + '/?add-to-cart=' + prods[0].id, { waitUntil: 'networkidle' });
  await page.goto(BASE + '/checkout/', { waitUntil: 'networkidle' });
  await page.waitForSelector('.gi-roi');
  let st = await state(page);
  ok(st.locked && st.seen === '0' && !st.checked, 'la intrare: bifa e blocată, regulamentul nu e derulat');
  await page.click('#gi_roi', { force: true });
  ok(!(await state(page)).checked, 'click pe bifa blocată nu bifează');

  await page.fill('#billing_first_name', 'Test'); await page.fill('#billing_last_name', 'Client');
  await page.fill('#billing_address_1', 'Strada Test 1'); await page.fill('#billing_city', 'Brasov');
  await page.fill('#billing_postcode', '500001'); await page.fill('#billing_phone', '0700000000'); await page.fill('#billing_email', 'test@example.com');
  if (await page.$('#billing_state')) { await page.selectOption('#billing_state', 'BV').catch(() => {}); }
  await idle(page);
  await page.check('#payment_method_cod'); await idle(page);

  // 3. Fără citire și fără bifă: respins
  let ajax = '';
  page.once('response', () => {});
  const waitAjax = page.waitForResponse(r => /wc-ajax=checkout/.test(r.url()), { timeout: 20000 }).then(async r => { ajax = (await r.text()).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 260); }).catch(() => { ajax = 'fără răspuns'; });
  await page.click('#place_order');
  await waitAjax;
  await page.waitForTimeout(1500);
  const message = await page.evaluate(() => { const n = document.querySelector('.woocommerce-NoticeGroup-checkout, .woocommerce-error'); return n ? n.innerText.replace(/\s+/g, ' ').trim() : ''; });
  ok(/citește regulamentul/i.test(message) && /\/checkout\/$/.test(page.url()), 'fără citire și fără bifă: rezervarea e respinsă, cu mesaj' + (message ? '' : ' [niciun mesaj; url=' + page.url() + '; răspuns: ' + ajax + ']'));
  if (message && !/citește regulamentul/i.test(message)) { console.log('         mesaj primit: ' + message.slice(0, 200)); }

  // 4. Derulare, bifare, schimbarea metodei de plată (reîmprospătarea plății): starea rămâne
  await page.evaluate(() => { const b = document.querySelector('[data-gi-roi-box]'); b.scrollTop = b.scrollHeight; b.dispatchEvent(new Event('scroll')); });
  await page.waitForTimeout(300);
  st = await state(page);
  ok(!st.locked && st.seen === '1', 'după derulare: bifa se deblochează');
  await page.check('#gi_roi');
  const methods = await page.$$eval('input[name=payment_method]', els => els.map(e => e.value));
  const other = methods.find(m => m !== 'cod');
  for (const method of [other, 'cod']) {
    await page.check('#payment_method_' + method);
    await idle(page); await page.waitForTimeout(1500);
    st = await state(page);
    ok(st.checked && !st.locked && st.seen === '1', 'după schimbarea metodei de plată în „' + method + '”: bifa și derularea au rămas');
  }

  // 5. Ocolirea prin Store API (cererea directă, fără formular) e respinsă
  const cart = await page.request.get(BASE + '/wp-json/wc/store/v1/cart');
  const nonce = cart.headers()['nonce'] || cart.headers()['x-wc-store-api-nonce'] || '';
  const bypass = await page.request.post(BASE + '/wp-json/wc/store/v1/checkout', {
    headers: { Nonce: nonce, 'X-WC-Store-API-Nonce': nonce, 'Content-Type': 'application/json' },
    data: { billing_address: { first_name: 'Test', last_name: 'Client', address_1: 'Strada Test 1', city: 'Brasov', state: 'BV', postcode: '500001', country: 'RO', email: 'test@example.com', phone: '0700000000' }, payment_method: 'cod' },
  });
  const bypassBody = await bypass.json().catch(() => ({}));
  ok(bypass.status() === 403 && bypassBody.code === 'gi_roi_required', 'cererea directă la Store API (fără bifă) e respinsă (' + bypass.status() + ')');

  // 6. Trimitere cu bifă
  await page.screenshot({ path: OUT + '-checkout-bifat.png', fullPage: true });
  await idle(page);
  await page.click('#place_order');
  await page.waitForURL(/order-received/, { timeout: 90000 });
  const orderId = (page.url().match(/order-received\/(\d+)/) || [])[1];
  ok(!!orderId, 'cu bifă: comanda se creează (' + orderId + ')');

  // 7. Administrare: dovada acceptării
  const admin = await ctx.newPage(); admin.setDefaultTimeout(60000);
  await admin.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await admin.fill('#user_login', 'admin'); await admin.fill('#user_pass', 'password'); await admin.click('#wp-submit');
  await admin.waitForLoadState('networkidle');
  await admin.goto(BASE + '/wp-admin/admin.php?page=wc-orders&action=edit&id=' + orderId, { waitUntil: 'networkidle' });
  if (!(await admin.$('.order_data_column'))) { await admin.goto(BASE + '/wp-admin/post.php?post=' + orderId + '&action=edit', { waitUntil: 'networkidle' }); }
  const text = await admin.evaluate(() => document.body.innerText.replace(/\s+/g, ' '));
  ok(/Regulament acceptat:[^·]*UTC · versiunea [^·]+ · limba ro · derulat[^:]*: da · după \d+ s de la afișare · cod [0-9a-f]{12}/.test(text), 'comanda arată dovada: data, versiunea, limba, derulat, secunde, cod');
  ok(/Regulament acceptat: versiunea/.test(text), 'comanda are nota cu acceptarea');
  ok((text.match(/Regulament acceptat: versiunea/g) || []).length === 1, 'o singură notă de acceptare în comandă');

  await browser.close();
  console.log(failures ? '\n' + failures + ' verificări au picat' : '\nToate verificările au trecut');
  process.exit(failures ? 1 : 0);
})().catch(e => { console.error('EROARE:', e.message.split('\n')[0]); process.exit(1); });
