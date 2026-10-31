// Test de la un capăt la altul pentru regulamentul la finalizarea rezervării (WordPress local pornit pe 127.0.0.1:9400, vezi tools/local-wp/README.md).
// Verifică: protecția „doar de consultat” a paginii regulamentului, bifa blocată până la citire, respingerea fără bifă,
// păstrarea stării la reîmprospătarea plății, crearea comenzii și dovada acceptării în comandă (administrare).
// Utilizare, din folderul cu Playwright instalat: node test-regulament-la-plata.js <prefix-capturi>
const { chromium } = require('playwright');
const BASE = 'http://127.0.0.1:9400';
const OUT = process.argv[2];
const log = (...a) => console.log(...a);
(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 900 }, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  page.setDefaultTimeout(60000);

  // 1. Pagina regulamentului: doar de consultat
  await page.goto(BASE + '/regulament-politici/', { waitUntil: 'networkidle' });
  const prot = await page.evaluate(() => {
    const el = document.querySelector('[data-gi-protejat]');
    const cs = getComputedStyle(el);
    const ev = new Event('copy', { bubbles: true, cancelable: true });
    el.dispatchEvent(ev);
    const ctxm = new Event('contextmenu', { bubbles: true, cancelable: true }); el.dispatchEvent(ctxm);
    return { userSelect: cs.userSelect || cs.webkitUserSelect, copyPrevented: ev.defaultPrevented, ctxPrevented: ctxm.defaultPrevented, pdfLinks: document.querySelectorAll('a[href$=".pdf"]').length };
  });
  log('regulament pagină:', JSON.stringify(prot));
  await page.emulateMedia({ media: 'print' });
  const printHidden = await page.evaluate(() => getComputedStyle(document.querySelector('[data-gi-protejat]')).display);
  log('la tipărire, conținutul are display =', printHidden);
  await page.emulateMedia({ media: 'screen' });

  // 2. Cos + finalizare
  const prods = await (await page.request.get(BASE + '/wp-json/wc/store/v1/products?search=prob')).json();
  const pid = prods[0].id;
  await page.goto(BASE + '/?add-to-cart=' + pid, { waitUntil: 'networkidle' });
  await page.goto(BASE + '/checkout/', { waitUntil: 'networkidle' });
  await page.waitForSelector('.gi-roi');
  const st0 = await page.evaluate(() => ({ disabled: document.querySelector('#gi_roi').disabled, hintVisible: !document.querySelector('[data-gi-roi-hint]').hidden, boxScrolls: (b => b.scrollHeight > b.clientHeight)(document.querySelector('[data-gi-roi-box]')), seen: document.querySelector('[name=gi_roi_seen]').value }));
  log('la intrare:', JSON.stringify(st0));
  await page.fill('#billing_first_name', 'Test'); await page.fill('#billing_last_name', 'Client');
  await page.fill('#billing_address_1', 'Strada Test 1'); await page.fill('#billing_city', 'Brasov');
  await page.fill('#billing_postcode', '500001'); await page.fill('#billing_phone', '0700000000'); await page.fill('#billing_email', 'test@example.com');
  if (await page.$('#billing_state')) { try { await page.selectOption('#billing_state', 'BV'); } catch (e) {} }
  await page.waitForTimeout(1500);
  await page.screenshot({ path: OUT + '-checkout-mobil.png', fullPage: true });

  // 3. Fără citire: bifa e blocată; trimiterea e respinsă
  await page.check('#payment_method_cod').catch(() => {});
  await page.waitForTimeout(1200);
  page.on('response', async r => { if (/wc-ajax=checkout/.test(r.url())) { let t = ''; try { t = (await r.text()).replace(/\s+/g, ' ').slice(0, 300); } catch (e) {} log('  [checkout ajax]', r.status(), t); } });
  await page.waitForFunction(() => !document.querySelector('.blockUI'), null, { timeout: 30000 }).catch(() => {});
  await page.waitForTimeout(800);
  await page.click('#place_order');
  await page.waitForTimeout(5000);
  log('place_order activ:', await page.isEnabled('#place_order'), '| gi_roi disabled:', await page.evaluate(() => document.querySelector('#gi_roi').disabled));
  const msg = await page.evaluate(() => { const n = document.querySelector('.woocommerce-NoticeGroup-checkout, .woocommerce-error'); return n ? n.innerText.replace(/\s+/g, ' ').trim() : 'NICIUN MESAJ; url=' + location.href; });
  log('fără citire, mesaj:', msg);
  await page.screenshot({ path: OUT + '-fara-citire.png', fullPage: true });

  // 4. Citește (derulează), bifează, reîmprospătează plata, verifică starea
  await page.evaluate(() => { const b = document.querySelector('[data-gi-roi-box]'); b.scrollTop = b.scrollHeight; b.dispatchEvent(new Event('scroll')); });
  await page.waitForTimeout(300);
  const st1 = await page.evaluate(() => ({ disabled: document.querySelector('#gi_roi').disabled, seen: document.querySelector('[name=gi_roi_seen]').value }));
  log('după derulare:', JSON.stringify(st1));
  await page.check('#gi_roi');
  await page.fill('#billing_postcode', '500002'); await page.press('#billing_postcode', 'Tab');
  await page.waitForTimeout(2500);
  const st2 = await page.evaluate(() => ({ checked: document.querySelector('#gi_roi').checked, disabled: document.querySelector('#gi_roi').disabled, seen: document.querySelector('[name=gi_roi_seen]').value }));
  log('după reîmprospătarea plății:', JSON.stringify(st2));
  await page.screenshot({ path: OUT + '-checkout-bifat.png', fullPage: true });

  // 5. Trimite
  await page.waitForFunction(() => !document.querySelector('.blockUI'), null, { timeout: 30000 }).catch(() => {});
  await page.click('#place_order');
  await page.waitForURL(/order-received/, { timeout: 90000 });
  const orderId = (page.url().match(/order-received\/(\d+)/) || [])[1];
  log('comandă creată:', orderId);

  // 6. Administrare: dovada acceptării
  const admin = await ctx.newPage(); admin.setDefaultTimeout(60000);
  await admin.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await admin.fill('#user_login', 'admin'); await admin.fill('#user_pass', 'password'); await admin.click('#wp-submit');
  await admin.waitForLoadState('networkidle');
  let url = BASE + '/wp-admin/admin.php?page=wc-orders&action=edit&id=' + orderId;
  await admin.goto(url, { waitUntil: 'networkidle' });
  if (!(await admin.$('.order_data_column'))) { await admin.goto(BASE + '/wp-admin/post.php?post=' + orderId + '&action=edit', { waitUntil: 'networkidle' }); }
  const proof = await admin.evaluate(() => { const t = document.body.innerText; const i = t.indexOf('Regulament acceptat'); return i >= 0 ? t.slice(i, i + 160).replace(/\s+/g, ' ') : 'LIPSEȘTE'; });
  log('în comandă:', proof);
  const notes = await admin.evaluate(() => (document.querySelector('#woocommerce-order-notes') || { innerText: '' }).innerText.replace(/\s+/g, ' ').slice(0, 300));
  log('notele comenzii:', notes);
  await browser.close();
})().catch(e => { console.error('EROARE:', e.message.split('\n')[0]); process.exit(1); });
