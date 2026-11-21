// Test pentru versiunea 1.10.0 („pagini ușoare”), pe WordPress local pornit pe 127.0.0.1:9400/9401 (vezi tools/local-wp/README.md). NUMAI LOCAL: se autentifică cu
// admin/password și schimbă nivelul „Pagini ușoare” (la sfârșit îl pune pe valoarea implicită, 1).
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-10-0.php, care pune în fiecare pagină fișiere de probă cu aceleași nume și locuri ca pe live).
// Variabile opționale: GI_BASE, GI_PHP_LOG, GI_SHOTS.
// Verifică, pe nivelurile 0, 1 și 2:
//  - nivelul 0: nimic nu se scoate (ca înainte);
//  - nivelul 1: pe paginile noi dispar tema giw, Elementor, hoteller, cupoanele, BRW, fonturile Google ale altor module, scripturile lor și codul inline atașat lor; rămâne fișierul „de control”
//    al altui modul; pe prima pagină nu se schimbă nimic; paginile WooCommerce și paginile vechi (alt template) nu se ating; Zona echipei neatinsă;
//  - nivelul 2: și prima pagină se curăță, dar BRW rămâne; pagina cu formular de rezervare își păstrează BRW; pagina cu blocuri WooCommerce își păstrează fișierele WooCommerce;
//  - <link rel="modulepreload"> și <script type="module"> ale cuponelor dispar, cele ale altor module rămân;
//  - pagina rămâne întreagă (CSS-ul și JS-ul nostru, titlul, subsolul), fără erori JavaScript, pe calculator și pe telefon; setările (listă, valori nepermise); fără erori PHP.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(BASE)) {
  console.error('Refuz să rulez pe ' + BASE + ': testul e numai pentru WordPress-ul local.');
  process.exit(2);
}
const PHP_LOG = process.env.GI_PHP_LOG || '';
const SHOTS = process.env.GI_SHOTS || '';
if (SHOTS) { fs.mkdirSync(SHOTS, { recursive: true }); }
let failures = 0;
function ok(condition, text) { console.log((condition ? '[OK]     ' : '[EROARE] ') + text); if (!condition) { failures++; } }
const logLines = () => { try { return PHP_LOG ? fs.readFileSync(PHP_LOG, 'utf8').split('\n').filter(Boolean).length : 0; } catch (e) { return 0; } };

// fișierele de probă (aceleași nume ca pe live)
const THEME = ['bootstrap', 'woodmart-style', 'wd-header-cart', 'xts-style-theme_settings_default', 'woodmart-theme'];
const ELEMENTOR = ['elementor-frontend', 'elementor-post-2374', 'elementor-icons', 'elementor-pro', 'elementor-pro-frontend'];
const HOTELLER = ['hoteller-elementor'];
const COUPONS = ['acfwf-wc-cart-block-integration'];
const FONTS = ['ovabrw-google-font', 'xts-google-fonts', 'google-fonts-1'];
const BRW = ['ovabrw-frontend', 'ova-timepicker', 'ova-calendar'];
const HEAVY_NO_BRW = [...THEME, ...ELEMENTOR, ...HOTELLER, ...COUPONS, ...FONTS.filter((f) => f !== 'ovabrw-google-font')];
const CONTROL = ['control-alt-modul'];

(async () => {
  const phpBefore = logLines();
  const browser = await chromium.launch();
  const adminCtx = await browser.newContext();
  const ap = await adminCtx.newPage();
  await ap.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await ap.fill('#user_login', 'admin');
  await ap.fill('#user_pass', 'password');
  await ap.click('#wp-submit');
  await ap.waitForURL(/wp-admin/);
  const pagini = await (await ap.request.get(BASE + '/?gi_test=c1100_pagini')).json();
  const setLevel = async (n) => (await (await ap.request.get(BASE + '/?gi_test=c1100_nivel&n=' + n)).json()).nivel;

  const vctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const ids = (html, kind) => {
    const set = new Set();
    const re = kind === 'css' ? /<link[^>]+id=['"]([^'"]+)-css['"]/gi : /<script[^>]+id=['"]([^'"]+)-js['"]/gi;
    let m; while ((m = re.exec(html))) { set.add(m[1]); }
    return set;
  };
  const get = async (url) => (await vctx.request.get(url.startsWith('http') ? url : BASE + url)).text();
  const present = (html, names) => names.filter((n) => ids(html, 'css').has(n) || ids(html, 'js').has(n));
  const missingOf = (html, names) => names.filter((n) => !(ids(html, 'css').has(n) || ids(html, 'js').has(n)));

  const PAGES = [
    ['dedicată (Glamping)', '/glamping/'],
    ['simplă (Despre noi)', '/despre-noi/'],
    ['Contact', '/contact/'],
    ['Harta', '/in-gradina/harta/'],
  ];

  // ---------- nivelul 0: nimic nu se scoate ----------
  ok((await setLevel(0)) === 0, 'nivel 0 pus');
  const wooOf = (x) => ['wc-cart-fragments', 'woocommerce', 'jquery-blockui', 'wc-add-to-cart', 'js-cookie'].filter((n) => ids(x, 'js').has(n));
  const wooBaseSimple = wooOf(await get('/despre-noi/'));
  const wooBaseWoo = wooOf(await get(pagini.woo));
  ok(wooBaseSimple.length > 0 && wooBaseWoo.length > 0, 'nivel 0: WooCommerce încarcă fișiere de coș pe pagina simplă (' + wooBaseSimple.join(',') + ') și pe cea cu WooCommerce (' + wooBaseWoo.join(',') + '): baza de comparație');
  for (const [name, url] of [...PAGES, ['prima pagină', '/']]) {
    const h = await get(url);
    const all = [...HEAVY_NO_BRW, ...BRW, ...CONTROL, 'ovabrw-google-font'];
    ok(missingOf(h, all).length === 0, 'nivel 0, ' + name + ': toate fișierele de probă rămân (lipsesc: ' + missingOf(h, all).join(',') + ')');
    ok(/advanced-coupons-for-woocommerce-free\/dist\/common\/NoticesPlugin/.test(h), 'nivel 0, ' + name + ': modulepreload al cuponelor rămâne');
  }

  // ---------- nivelul 1 ----------
  ok((await setLevel(1)) === 1, 'nivel 1 pus');
  for (const [name, url] of PAGES) {
    const h = await get(url);
    ok(present(h, [...HEAVY_NO_BRW, ...BRW, 'ovabrw-google-font']).length === 0, 'nivel 1, ' + name + ': tema, Elementor, hoteller, cupoane, fonturi și BRW dispar (au rămas: ' + present(h, [...HEAVY_NO_BRW, ...BRW, 'ovabrw-google-font']).join(',') + ')');
    ok(missingOf(h, CONTROL).length === 0, 'nivel 1, ' + name + ': fișierul unui alt modul rămâne');
    ok(!h.includes('__elementor_inline') && h.includes('__control_inline'), 'nivel 1, ' + name + ': codul inline al fișierelor scoase dispare, cel al altui modul rămâne');
    ok(!/advanced-coupons-for-woocommerce-free/.test(h) && /alt-modul\/js\/control-modul/.test(h), 'nivel 1, ' + name + ': modulepreload și modulul cuponelor dispar, cele ale altui modul rămân');
    ok(/gradina-ielelor-integration\/assets\/css\/site\.css/.test(h) && /<footer/.test(h) && /<h1/.test(h), 'nivel 1, ' + name + ': pagina rămâne întreagă (CSS-ul nostru, titlu, subsol)');
  }
  {
    const h = await get('/');
    const all = [...HEAVY_NO_BRW, ...BRW, ...CONTROL, 'ovabrw-google-font'];
    ok(missingOf(h, all).length === 0, 'nivel 1, prima pagină: nu se schimbă nimic (lipsesc: ' + missingOf(h, all).join(',') + ')');
  }
  for (const [name, url] of [['pagina de probă (alt template)', '/sample-page/'], ['magazin WooCommerce', '/shop/'], ['Zona echipei', '/zona-echipei/']]) {
    const resp = await vctx.request.get(BASE + url);
    const h = await resp.text();
    ok(resp.status() === 200, name + ': se deschide (' + resp.status() + ')');
    ok(missingOf(h, [...HEAVY_NO_BRW, ...BRW, ...CONTROL]).length === 0, 'nivel 1, ' + name + ': neatinsă (lipsesc: ' + missingOf(h, [...HEAVY_NO_BRW, ...BRW, ...CONTROL]).join(',') + ')');
  }
  {
    const h = await get(pagini.brw);
    ok(present(h, BRW).length === BRW.length && present(h, HEAVY_NO_BRW).length === 0, 'nivel 1, pagina cu [ovabrw_search]: BRW rămâne, restul dispare (BRW: ' + present(h, BRW).join(',') + ')');
    const w = await get(pagini.woo);
    const base = await get('/despre-noi/');
    ok(wooOf(base).length === 0, 'nivel 1, pagina simplă: fișierele WooCommerce de coș dispar (au rămas: ' + wooOf(base).join(',') + ')');
    ok(present(w, HEAVY_NO_BRW).length === 0, 'nivel 1, pagina cu WooCommerce: tema și Elementor tot dispar');
    ok(wooOf(w).join(',') === wooBaseWoo.join(','), 'nivel 1, pagina cu blocuri WooCommerce: fișierele WooCommerce rămân toate (' + wooOf(w).join(',') + ')');
  }

  // ---------- nivelul 2 ----------
  ok((await setLevel(2)) === 2, 'nivel 2 pus');
  {
    const h = await get('/');
    ok(present(h, HEAVY_NO_BRW).length === 0, 'nivel 2, prima pagină: tema, Elementor, hoteller, cupoane, fonturi dispar (au rămas: ' + present(h, HEAVY_NO_BRW).join(',') + ')');
    ok(present(h, [...BRW, 'ovabrw-google-font']).length === BRW.length + 1, 'nivel 2, prima pagină: BRW rămâne, cu fontul lui (lipsesc: ' + missingOf(h, [...BRW, 'ovabrw-google-font']).join(',') + ')');
    ok(missingOf(h, CONTROL).length === 0 && !/advanced-coupons-for-woocommerce-free/.test(h), 'nivel 2, prima pagină: fișierul altui modul rămâne, modulele cuponelor dispar');
    ok(/gradina-ielelor-integration\/assets\/css\/home\.css/.test(h) && /ovabrw_search|gi-fake-brw|data-gi-booking|ovabrw/i.test(h), 'nivel 2, prima pagină: stilurile noastre și formularul de rezervare rămân');
  }
  for (const [name, url] of PAGES) {
    const h = await get(url);
    ok(present(h, [...HEAVY_NO_BRW, ...BRW]).length === 0, 'nivel 2, ' + name + ': la fel ca la nivelul 1');
  }
  for (const [name, url] of [['magazin WooCommerce', '/shop/'], ['pagina de probă', '/sample-page/']]) {
    const h = await get(url);
    ok(missingOf(h, [...HEAVY_NO_BRW, ...BRW, ...CONTROL]).length === 0, 'nivel 2, ' + name + ': tot neatinsă');
  }

  // ---------- în browser: fără erori, telefon și calculator ----------
  for (const [label, vp, mobile] of [['calculator', { width: 1440, height: 900 }, false], ['telefon', { width: 390, height: 844 }, true]]) {
    const ctx = await browser.newContext({ viewport: vp, isMobile: mobile, hasTouch: mobile });
    for (const [name, url] of [['Glamping', '/glamping/'], ['Despre noi', '/despre-noi/'], ['prima pagină', '/']]) {
      const p = await ctx.newPage();
      const errs = [];
      p.on('pageerror', (e) => errs.push(e.message));
      await p.goto(BASE + url, { waitUntil: 'networkidle' });
      const sw = await p.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth);
      const heading = await p.locator('h1').first().isVisible();
      ok(errs.length === 0 && sw && heading, label + ', ' + name + ' (nivel 2): fără erori JavaScript, fără derulare pe orizontală, titlul se vede (' + errs.join('|') + ')');
      if (SHOTS && url !== '/despre-noi/') { await p.screenshot({ path: path.join(SHOTS, 'usoare-' + label + '-' + (url === '/' ? 'acasa' : 'glamping') + '.png') }); }
      await p.close();
    }
    await ctx.close();
  }

  // ---------- setările ----------
  await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
  const sel = ap.locator('select[name="gi_integration_options[light_pages]"]');
  ok(await sel.count() === 1 && (await sel.locator('option').count()) === 3, 'Setări: lista „Pagini ușoare” cu trei variante');
  ok(await sel.inputValue() === '2', 'Setări: arată valoarea curentă (2)');
  await sel.selectOption('0');
  await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
  ok((await setLevelRead()) === 0, 'Setări: „Oprit” se salvează');
  await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
  await ap.evaluate(() => { const s = document.querySelector('select[name="gi_integration_options[light_pages]"]'); const o = document.createElement('option'); o.value = '7'; o.textContent = 'x'; s.appendChild(o); s.value = '7'; });
  await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
  ok((await setLevelRead()) === 2, 'valoare nepermisă (7) → se reduce la 2');
  async function setLevelRead() { return (await (await ap.request.get(BASE + '/?gi_test=c1100_nivel')).json()).nivel; }

  ok((await setLevel(1)) === 1, 'valoarea implicită (1) revine');
  ok(logLines() === phpBefore, 'fără erori PHP noi în jurnal (' + (logLines() - phpBefore) + ')');
  await browser.close();
  console.log(failures === 0 ? '\nTOATE VERIFICĂRILE AU TRECUT' : '\n' + failures + ' VERIFICĂRI AU EȘUAT');
  process.exit(failures === 0 ? 0 : 1);
})().catch((e) => { console.error(e); process.exit(1); });
