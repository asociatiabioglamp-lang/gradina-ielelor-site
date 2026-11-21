// Test pentru versiunea 1.9.0 (banner de cookie-uri, Tag Manager încărcat numai după acord, plasa de siguranță pentru codurile fostului administrator),
// pe WordPress local pornit pe 127.0.0.1:9400/9401 (vezi tools/local-wp/README.md). NUMAI LOCAL: se autentifică cu admin/password și schimbă opțiunile pluginului
// (la sfârșit le pune la valorile implicite). Cererile către Google, Facebook și TikTok sunt interceptate: nu pleacă nimic în afară.
// Are nevoie de: mu-plugin-urile din tools/local-wp/mu-plugins (inclusiv gi-test-1-9-0.php).
// Variabile opționale: GI_BASE, GI_PHP_LOG, GI_SHOTS.
// Verifică:
//  - prima vizită: bannerul apare (dialog, text, trei butoane la fel de vizibile, focus), starea implicită „refuzat”, NICIUN cod de măsurare, niciun cookie;
//  - „Refuz”: cookie 1:0:0, fără Tag Manager nici după reîncărcare; „Statistici”: cookie 1:1:0, Tag Manager se încarcă, fără reîncărcare;
//    ordinea în dataLayer (implicit → alegerea salvată → pornirea GTM); retragerea: reîncărcare, cookie-urile de măsurare șterse, fără Tag Manager;
//  - versiune de cookie veche sau cookie stricat: se întreabă din nou; tastatură (Tab, Enter, Escape); link spre politică; subsol; shortcode;
//  - telefon: banner în ecran, fără scroll orizontal, butoane ≥ 44 px, butonul „Rezervă cazare” ascuns cât e deschis, butonul mic de redeschidere fără suprapunere;
//  - plasa de siguranță: codurile vechi (Facebook, TikTok, Analytics, Tag Manager vechi) dispar din pagină, scriptul asociației rămâne; HTML-ul e același pentru toți (cache);
//  - Zona echipei fără banner; setările (cod invalid → gol, cod valid, comutator oprit → nimic); fără erori PHP.
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
const OLD_IDS = ['371463157145157', 'D7P8DJBC77U3CRQJR7P0', 'G-T0Y2TVCYBD', 'GTM-T5L2SC7K'];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  const phpBefore = logLines();
  const browser = await chromium.launch();

  async function visitor(opts) {
    const ctx = await browser.newContext(opts || { viewport: { width: 1280, height: 900 } });
    const reqs = [];
    ctx.on('request', (r) => { if (/googletagmanager|google-analytics|facebook\.|tiktok\./.test(r.url())) { reqs.push(r.url()); } });
    await ctx.route('https://www.googletagmanager.com/**', (route) => {
      const u = new URL(route.request().url());
      if (u.pathname === '/gtm.js') {
        return route.fulfill({ contentType: 'application/javascript', body: 'window.__gtm_stub=(window.__gtm_stub||[]).concat(' + JSON.stringify(u.searchParams.get('id')) + ');' });
      }
      return route.fulfill({ status: 200, contentType: 'application/javascript', body: '' });
    });
    await ctx.route(/https:\/\/([a-z0-9.-]*\.)?(facebook\.(com|net)|tiktok\.com)\/.*/, (route) => route.abort());
    return { ctx, reqs };
  }
  const gtmReqs = (reqs) => reqs.filter((u) => /googletagmanager\.com\/gtm\.js/.test(u));
  const cookieOf = async (ctx, name) => { const c = (await ctx.cookies(BASE)).find((x) => x.name === name); return c ? decodeURIComponent(c.value) : null; };
  const dl = (p) => p.evaluate(() => (window.dataLayer || []).map((e) => (e && e.length !== undefined && typeof e !== 'string' ? Array.from(e) : e)));
  const shot = async (p, name) => { if (SHOTS) { await p.screenshot({ path: path.join(SHOTS, name + '.png') }); } };

  // ---------- pregătire (administrator) ----------
  const adminCtx = await browser.newContext();
  const ap = await adminCtx.newPage();
  await ap.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await ap.fill('#user_login', 'admin');
  await ap.fill('#user_pass', 'password');
  await ap.click('#wp-submit');
  await ap.waitForURL(/wp-admin/);
  const reset = await (await ap.request.get(BASE + '/?gi_test=c190_reset')).json();
  ok(reset.consent_enabled === 1 && reset.gtm_id === 'GTM-5XNZX5QT', 'implicit: bannerul pornit și containerul asociației (GTM-5XNZX5QT) (' + JSON.stringify(reset) + ')');
  const pagini = await (await ap.request.get(BASE + '/?gi_test=c190_pagini')).json();
  ok(/politica-cookies/.test(pagini.politica) && /test-cookie-shortcode/.test(pagini.shortcode), 'pagina „Cookies” și pagina cu shortcode create pentru test');

  // ---------- 1. prima vizită (desktop) ----------
  {
    const { ctx, reqs } = await visitor();
    const p = await ctx.newPage();
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    const banner = p.locator('#gi-cookie');
    ok(await banner.isVisible(), 'prima vizită: bannerul se vede');
    ok((await banner.getAttribute('role')) === 'dialog' && !!(await banner.getAttribute('aria-labelledby')), 'bannerul e un dialog cu titlu (accesibilitate)');
    ok(/Cookie-uri pe site/.test(await p.locator('#gi-cookie-title').innerText()), 'titlul „Cookie-uri pe site”');
    const main = p.locator('#gi-cookie [data-gi-view="main"]');
    const ba = await main.locator('[data-gi-act="accept"]').boundingBox();
    const br = await main.locator('[data-gi-act="reject"]').boundingBox();
    const bs = await main.locator('[data-gi-act="settings"]').boundingBox();
    ok(ba && br && bs && ba.height >= 44 && Math.abs(ba.height - br.height) < 1 && Math.abs(ba.width - br.width) < 2, '„Accept toate” și „Refuz” au aceeași mărime (≥ 44 px înălțime): ' + JSON.stringify([ba && [ba.width, ba.height], br && [br.width, br.height]]));
    ok(bs && bs.height >= 44, '„Alege” are cel puțin 44 px înălțime');
    ok(await p.evaluate(() => document.activeElement && document.activeElement.id === 'gi-cookie'), 'focusul e pe dialog');
    const list = await dl(p);
    const def = list.find((e) => e[0] === 'consent' && e[1] === 'default');
    ok(def && def[2].analytics_storage === 'denied' && def[2].ad_storage === 'denied' && def[2].ad_user_data === 'denied' && def[2].ad_personalization === 'denied', 'Consent Mode: implicit totul „refuzat” (analytics_storage, ad_storage, ad_user_data, ad_personalization)');
    ok(gtmReqs(reqs).length === 0 && reqs.length === 0, 'înainte de acord NU pleacă nicio cerere spre Tag Manager, Google, Facebook sau TikTok (' + reqs.length + ')');
    ok(await p.evaluate(() => !document.querySelector('script[src*="googletagmanager"]') && !window.__gtm_stub), 'nu există script Tag Manager în pagină');
    ok((await cookieOf(ctx, 'gi_consent')) === null, 'niciun cookie de alegere înainte de a alege');
    ok(await p.locator('#gi-cookie a[href*="politica-cookies"]').count() > 0, 'link spre pagina „Cookies”');
    await shot(p, 'desktop-banner');

    // ---------- 2. Refuz ----------
    await main.locator('[data-gi-act="reject"]').click();
    ok(!(await banner.isVisible()), '„Refuz”: bannerul se închide');
    ok((await cookieOf(ctx, 'gi_consent')) === '1:0:0', '„Refuz”: cookie 1:0:0 (' + (await cookieOf(ctx, 'gi_consent')) + ')');
    const ck = (await ctx.cookies(BASE)).find((x) => x.name === 'gi_consent');
    ok(ck && ck.sameSite === 'Lax' && ck.expires > Date.now() / 1000 + 170 * 86400 && ck.expires < Date.now() / 1000 + 190 * 86400, 'cookie: SameSite=Lax, ~180 de zile');
    ok(await p.locator('.gi-cookie-reopen').isVisible(), 'după alegere apare butonul mic „Setări cookie-uri”');
    await shot(p, 'desktop-dupa-alegere');
    await p.reload({ waitUntil: 'networkidle' });
    ok(!(await banner.isVisible()) && await p.locator('.gi-cookie-reopen').isVisible(), 'după reîncărcare: fără banner, cu butonul mic');
    ok(gtmReqs(reqs).length === 0, 'după „Refuz” Tag Manager nu se încarcă, nici după reîncărcare');
    const list2 = await dl(p);
    ok(list2.find((e) => e[0] === 'consent' && e[1] === 'update' && e[2].analytics_storage === 'denied'), 'alegerea salvată se aplică din prima clipă (update: refuzat)');

    // ---------- 3. Statistici ----------
    await p.evaluate(() => { window.__marker = 'acelasi-document'; });
    await p.locator('.gi-cookie-reopen').click();
    ok(await banner.isVisible() && await p.locator('#gi-cookie [data-gi-view="settings"]').isVisible(), 'butonul mic deschide „Alege ce cookie-uri accepți”');
    const need = p.locator('#gi-ck-need');
    ok(await need.isChecked() && await need.isDisabled(), '„Necesare”: bifat și nu se poate debifa');
    ok(!(await p.locator('#gi-ck-stats').isChecked()) && !(await p.locator('#gi-ck-mkt').isChecked()), 'Statistici și Marketing pornesc nebifate (după „Refuz”)');
    ok(await p.locator('#gi-cookie [data-gi-act="close"]').isVisible(), 'după o alegere există „Închide”');
    await shot(p, 'desktop-setari');
    await p.locator('#gi-ck-stats').check();
    await p.locator('#gi-cookie [data-gi-act="save"]').click();
    await p.waitForFunction(() => !!window.__gtm_stub, null, { timeout: 5000 }).catch(() => {});
    ok((await cookieOf(ctx, 'gi_consent')) === '1:1:0', 'statistici: cookie 1:1:0');
    ok(gtmReqs(reqs).length === 1 && /id=GTM-5XNZX5QT/.test(gtmReqs(reqs)[0]), 'Tag Manager se încarcă (o dată) cu containerul asociației: ' + gtmReqs(reqs).join(','));
    ok(await p.evaluate(() => window.__marker === 'acelasi-document'), 'fără reîncărcare la acceptare');
    const list3 = await dl(p);
    const upd = list3.filter((e) => e[0] === 'consent' && e[1] === 'update').pop();
    ok(upd && upd[2].analytics_storage === 'granted' && upd[2].ad_storage === 'denied', 'consimțământ: statistici acordat, marketing refuzat');
    ok(list3.some((e) => e && e.event === 'gi_consent_update' && e.gi_consent_statistici === true && e.gi_consent_marketing === false), 'eveniment dataLayer gi_consent_update');

    // ---------- 4. reîncărcare cu acord: ordinea ----------
    await p.reload({ waitUntil: 'networkidle' });
    const list4 = await dl(p);
    const iDef = list4.findIndex((e) => e[0] === 'consent' && e[1] === 'default');
    const iUpd = list4.findIndex((e) => e[0] === 'consent' && e[1] === 'update');
    const iGtm = list4.findIndex((e) => e && e.event === 'gtm.js');
    ok(iDef >= 0 && iUpd > iDef && iGtm > iUpd, 'ordinea în dataLayer: implicit (' + iDef + ') → alegerea salvată (' + iUpd + ') → pornirea Tag Manager (' + iGtm + ')');
    ok(await p.evaluate(() => (window.__gtm_stub || []).length === 1), 'Tag Manager pornește o singură dată pe pagină');
    ok(!(await banner.isVisible()), 'cu alegerea făcută, bannerul nu mai apare');

    // ---------- 5. retragerea ----------
    await ctx.addCookies([{ name: '_ga', value: 'GA1.1.1.1', url: BASE }, { name: '_ga_ABC', value: 'x', url: BASE }, { name: 'wp_test_pastrat', value: '1', url: BASE }]);
    const before = gtmReqs(reqs).length;
    await p.locator('.gi-cookie-reopen').click();
    ok(await p.locator('#gi-ck-stats').isChecked(), 'redeschis: Statistici apare bifat');
    await p.locator('#gi-ck-stats').uncheck();
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.locator('#gi-cookie [data-gi-act="save"]').click()]);
    ok((await cookieOf(ctx, 'gi_consent')) === '1:0:0', 'retragere: cookie 1:0:0');
    ok((await cookieOf(ctx, '_ga')) === null && (await cookieOf(ctx, '_ga_ABC')) === null, 'retragere: cookie-urile de măsurare (_ga, _ga_*) sunt șterse');
    ok((await cookieOf(ctx, 'wp_test_pastrat')) === '1', 'celelalte cookie-uri rămân');
    ok(gtmReqs(reqs).length === before, 'după retragere și reîncărcare Tag Manager nu mai pornește');

    // ---------- 6. accept toate din setări ----------
    await p.locator('.gi-cookie-reopen').click();
    await p.locator('#gi-cookie [data-gi-view="settings"] [data-gi-act="accept"]').click();
    await p.waitForFunction(() => !!window.__gtm_stub, null, { timeout: 5000 }).catch(() => {});
    ok((await cookieOf(ctx, 'gi_consent')) === '1:1:1', '„Accept toate”: cookie 1:1:1');
    const upd2 = (await dl(p)).filter((e) => e[0] === 'consent' && e[1] === 'update').pop();
    ok(upd2 && upd2[2].analytics_storage === 'granted' && upd2[2].ad_storage === 'granted' && upd2[2].ad_user_data === 'granted' && upd2[2].ad_personalization === 'granted', 'toate categoriile acordate');
    await ctx.close();
  }

  // ---------- 7. cookie vechi sau stricat ----------
  for (const bad of ['0%3A1%3A1', '%E0%A4%A', '1%3A2', '']) {
    const { ctx } = await visitor();
    if (bad) { await ctx.addCookies([{ name: 'gi_consent', value: bad, url: BASE }]); }
    const p = await ctx.newPage();
    const errs = [];
    p.on('pageerror', (e) => errs.push(e.message));
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok(await p.locator('#gi-cookie').isVisible(), 'cookie „' + bad + '” (' + (bad ? 'vechi sau stricat' : 'lipsește') + '): se întreabă din nou');
    ok(errs.length === 0, 'fără erori JavaScript pentru cookie „' + bad + '”');
    await ctx.close();
  }

  // ---------- 8. tastatură ----------
  {
    const { ctx } = await visitor();
    const p = await ctx.newPage();
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    await p.keyboard.press('Tab');
    ok(await p.evaluate(() => document.activeElement && document.activeElement.getAttribute('data-gi-act') === 'accept'), 'Tab: primul pe care ajungi e „Accept toate”');
    await p.keyboard.press('Tab'); await p.keyboard.press('Tab');
    ok(await p.evaluate(() => document.activeElement && document.activeElement.getAttribute('data-gi-act') === 'settings'), 'Tab, Tab: „Alege”');
    await p.keyboard.press('Enter');
    ok(await p.locator('#gi-cookie [data-gi-view="settings"]').isVisible(), 'Enter pe „Alege” deschide setările');
    ok(!(await p.locator('#gi-cookie [data-gi-act="close"]').isVisible()), 'fără alegere făcută, „Închide” nu există (nu se poate închide fără alegere)');
    await p.keyboard.press('Escape');
    ok(await p.locator('#gi-cookie [data-gi-view="main"]').isVisible() && await p.locator('#gi-cookie').isVisible(), 'Escape: înapoi la prima vedere, bannerul rămâne (nu s-a ales nimic)');
    await ctx.close();
  }

  // ---------- 9. telefon ----------
  {
    const { ctx } = await visitor({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
    const p = await ctx.newPage();
    await p.goto(BASE + '/glamping/', { waitUntil: 'networkidle' });
    const box = await p.locator('#gi-cookie').boundingBox();
    ok(box && box.x >= 0 && box.x + box.width <= 390.5 && box.y >= 0 && box.y + box.height <= 844.5, 'telefon: bannerul încape în ecran ' + JSON.stringify(box));
    ok(await p.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), 'telefon: fără scroll orizontal');
    const heights = await p.evaluate(() => Array.from(document.querySelectorAll('#gi-cookie [data-gi-view="main"] button')).map((b) => Math.round(b.getBoundingClientRect().height)));
    ok(heights.length === 3 && heights.every((h) => h >= 44), 'telefon: butoanele ≥ 44 px: ' + heights.join(','));
    const cta = p.locator('.gi-site .mobile-booking-cta').first();
    if (await cta.count()) {
      ok(!(await cta.isVisible()), 'telefon: butonul fix „Rezervă cazare” e ascuns cât timp bannerul e deschis');
    } else {
      ok(true, 'telefon: pagina nu are butonul fix de rezervare în această configurație local (verificarea se face pe live)');
    }
    await shot(p, 'telefon-banner');
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="settings"]').click();
    ok(await p.evaluate(() => { const c = document.getElementById('gi-cookie'); return c.scrollHeight <= window.innerHeight || getComputedStyle(c).overflowY !== 'visible'; }), 'telefon: setările se pot derula dacă nu încap');
    await shot(p, 'telefon-setari');
    await p.locator('#gi-cookie [data-gi-view="settings"] [data-gi-act="reject"]').click();
    if (await cta.count()) {
      await p.evaluate(() => window.scrollTo(0, 800)); // butonul fix al site-ului apare numai după derulare (comportamentul lui propriu)
      await sleep(600);
      ok(await cta.isVisible(), 'telefon: după alegere (și după derulare), butonul „Rezervă cazare” reapare');
      const r1 = await cta.boundingBox();
      const r2 = await p.locator('.gi-cookie-reopen').boundingBox();
      const overlap = r1 && r2 && !(r2.x + r2.width <= r1.x || r2.x >= r1.x + r1.width || r2.y + r2.height <= r1.y || r2.y >= r1.y + r1.height);
      ok(r2 && r2.width >= 40 && !overlap, 'telefon: butonul mic de redeschidere (≥ 40 px) nu se suprapune cu „Rezervă cazare”');
      ok(r2 && r2.y + r2.height <= 844 - 70, 'telefon: butonul mic stă deasupra barei de jos (de 55 px) a temei vechi, pe orice pagină (jos la ' + (r2 ? Math.round(844 - r2.y - r2.height) : '?') + ' px)');
    }
    await shot(p, 'telefon-dupa-alegere');
    await ctx.close();
  }

  // ---------- 10. plasa de siguranță pentru codurile vechi + cache ----------
  {
    const { ctx, reqs } = await visitor();
    const raw = await (await ap.request.get(BASE + '/?c190_vechi=1')).text();
    ok(OLD_IDS.every((id) => !raw.includes(id)), 'plasa de siguranță: niciun ID vechi în HTML-ul trimis (' + OLD_IDS.filter((id) => raw.includes(id)).join(',') + ')');
    ok(raw.includes('gi-test-etalon') && raw.includes('window.__etalon_ramas'), 'scriptul asociației (etalon) rămâne în pagină');
    ok(!/facebook\.com\/tr\?id=/.test(raw) && !/fbq\(/.test(raw) && !/ttq\.load/.test(raw), 'dispar și imaginea Facebook din <noscript>, fbq și ttq');
    const p = await ctx.newPage();
    await p.goto(BASE + '/?c190_vechi=1', { waitUntil: 'networkidle' });
    ok(reqs.length === 0, 'în browser: nicio cerere către Facebook, TikTok, Google sau Tag Manager (' + reqs.join(' ') + ')');
    ok(await p.evaluate(() => window.__etalon_ramas === true), 'scriptul etalon rulează');
    const html1 = await (await ap.request.get(BASE + '/')).text();
    const html2 = await (await ctx.request.get(BASE + '/', { headers: { Cookie: 'gi_consent=1%3A1%3A1' } })).text();
    const cut = (h) => { const a = h.indexOf('<div id="gi-cookie"'); const b = h.indexOf('gi-cookie-reopen', a); return a >= 0 && b > a ? h.slice(a, b) : ''; };
    ok(cut(html1).length > 500 && cut(html1) === cut(html2), 'cache: bannerul are același HTML cu sau fără cookie de alegere (alegerea se aplică numai în browser)');
    const heads = (h) => (h.match(/<script id="gi-consent-default">[\s\S]*?<\/script>/) || [''])[0];
    ok(heads(html1).length > 100 && heads(html1) === heads(html2), 'cache: scriptul de stare implicită e identic pentru toți');
    ok(await ap.request.get(BASE + '/?c190_vechi=1').then((r) => r.headers()['content-type'] || '').then((t) => /text\/html/.test(t)), 'pagina rămâne text/html');
    await ctx.close();
  }

  // ---------- 10b. cod GTM4WP și cazuri-limită ----------
  {
    const raw = await (await ap.request.get(BASE + '/?c190_gtm4wp=1')).text();
    ok(!raw.includes('googletagmanager.com/gtm.js') && !raw.includes('googletagmanager.com/ns.html'), 'GTM4WP: codul containerului din pagină (script și iframe din noscript) e scos de plasa de siguranță');
    const lim = await (await ap.request.get(BASE + '/?c190_limite=1')).text();
    ok(!lim.includes('c190-mare') && !lim.includes('x'.repeat(200)), 'script foarte mare (> 1 MB) cu ID vechi: scos, fără să depășească limitele PCRE');
    ok(lim.includes('c190-dupa-comentariu') && lim.includes('c190-legitim'), 'scripturile legitime de lângă un <script> neînchis dintr-un comentariu rămân');
    ok(lim.includes('<!-- vechi: <script async'), 'comentariul HTML rămâne neatins');
    const { ctx, reqs } = await visitor();
    const p = await ctx.newPage();
    await p.goto(BASE + '/?c190_gtm4wp=1', { waitUntil: 'networkidle' });
    ok(gtmReqs(reqs).length === 0, 'cu GTM4WP încă activ: înainte de acord nu pleacă nicio cerere spre Tag Manager (' + reqs.length + ')');
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="accept"]').click();
    await p.waitForFunction(() => !!window.__gtm_stub, null, { timeout: 5000 }).catch(() => {});
    await sleep(300);
    ok(gtmReqs(reqs).length === 1, 'după acord: Tag Manager se încarcă o singură dată, nu de două ori (' + gtmReqs(reqs).length + ')');
    await ctx.close();
  }

  // ---------- 10c. atribuirea comenzilor WooCommerce (cookie-uri sbjs_*) ----------
  {
    const { ctx } = await visitor();
    const p = await ctx.newPage();
    const sb = async () => (await ctx.cookies(BASE)).filter((c) => /^sbjs_/.test(c.name)).length;
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok((await sb()) === 0, 'înainte de acord: niciun cookie sbjs_* (atribuirea comenzilor WooCommerce)');
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="reject"]').click();
    await p.goto(BASE + '/glamping/', { waitUntil: 'networkidle' });
    ok((await sb()) === 0, 'după „Refuz”, pe altă pagină: tot niciun cookie sbjs_*');
    await p.locator('.gi-cookie-reopen').click();
    await p.locator('#gi-cookie [data-gi-view="settings"] [data-gi-act="accept"]').click();
    await sleep(1500);
    ok((await sb()) > 0, 'după „Accept toate”: scripturile de atribuire pornesc și apar cookie-urile sbjs_* (' + (await sb()) + ')');
    await p.locator('.gi-cookie-reopen').click();
    await p.locator('#gi-ck-stats').uncheck();
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.locator('#gi-cookie [data-gi-act="save"]').click()]);
    ok((await sb()) === 0, 'retragerea statisticilor șterge și cookie-urile sbjs_*');
    await ctx.close();
  }

  // ---------- 10d. ștergerea cookie-urilor pe categorii ----------
  {
    const { ctx } = await visitor();
    await ctx.addCookies([{ name: '_ga', value: 'GA1.1.1.1', url: BASE }, { name: '_fbp', value: 'fb.1.1.1', url: BASE }]);
    const p = await ctx.newPage();
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok((await cookieOf(ctx, '_ga')) === null && (await cookieOf(ctx, '_fbp')) === null, 'cine n-a ales încă și vine cu _ga și _fbp de la vizite mai vechi: se șterg la prima pagină');
    await ctx.close();
    const v2 = await visitor();
    await v2.ctx.addCookies([{ name: 'gi_consent', value: '1%3A1%3A1', url: BASE }, { name: '_ga', value: 'GA1.1.1.1', url: BASE }, { name: '_fbp', value: 'fb.1.1.1', url: BASE }]);
    const p2 = await v2.ctx.newPage();
    await p2.goto(BASE + '/', { waitUntil: 'networkidle' });
    await p2.locator('.gi-cookie-reopen').click();
    await p2.locator('#gi-ck-mkt').uncheck();
    await Promise.all([p2.waitForNavigation({ waitUntil: 'networkidle' }), p2.locator('#gi-cookie [data-gi-act="save"]').click()]);
    ok((await cookieOf(v2.ctx, '_ga')) === 'GA1.1.1.1' && (await cookieOf(v2.ctx, '_fbp')) === null, 'se retrage numai marketingul: _fbp se șterge, _ga (statistici) rămâne');
    ok((await cookieOf(v2.ctx, 'gi_consent')) === '1:1:0', 'cookie 1:1:0 după retragerea marketingului');
    await v2.ctx.close();
  }

  // ---------- 10e. contur de focus peste stilurile vechi + focus ----------
  {
    const { ctx } = await visitor();
    const p = await ctx.newPage();
    await p.goto(BASE + '/?gi_test=stiluri', { waitUntil: 'networkidle' });
    await p.keyboard.press('Tab');
    const ring = await p.evaluate(() => { const e = document.activeElement; const s = e && getComputedStyle(e); return e && s ? { act: e.getAttribute('data-gi-act'), style: s.outlineStyle, width: s.outlineWidth } : null; });
    ok(ring && ring.act === 'accept' && ring.style !== 'none' && parseFloat(ring.width) >= 2, 'cu stilurile vechi ale temei, conturul de focus rămâne vizibil: ' + JSON.stringify(ring));
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="settings"]').click();
    await p.keyboard.press('Escape');
    ok(await p.evaluate(() => document.getElementById('gi-cookie').contains(document.activeElement)), 'Escape din setări: focusul rămâne în banner (nu cade pe pagină)');
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="reject"]').click();
    ok(await p.evaluate(() => document.activeElement && document.activeElement.classList.contains('gi-cookie-reopen')), 'după alegere, focusul trece pe butonul mic „Setări cookie-uri”');
    await p.evaluate(() => { const b = document.createElement('div'); b.id = 'lightbox-probă'; document.body.appendChild(b); });
    await p.locator('.gi-cookie-reopen').click();
    await p.locator('body').press('Escape'); // focusul e în banner: se închide
    ok(!(await p.locator('#gi-cookie').isVisible()), 'Escape cu focusul în banner îl închide (după o alegere)');
    await ctx.close();
  }

  // ---------- 11. subsol, shortcode, politică ----------
  {
    const { ctx } = await visitor();
    const p = await ctx.newPage();
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="reject"]').click();
    const foot = p.locator('footer a[data-gi-cookie-open]');
    ok(await foot.count() === 1 && /Setări cookie-uri/.test(await foot.innerText()), 'subsol: linkul „Setări cookie-uri”');
    await foot.scrollIntoViewIfNeeded();
    await foot.click();
    ok(await p.locator('#gi-cookie [data-gi-view="settings"]').isVisible(), 'linkul din subsol deschide setările');
    await p.locator('#gi-cookie [data-gi-act="close"]').click();
    ok(!(await p.locator('#gi-cookie').isVisible()) && await p.locator('.gi-cookie-reopen').isVisible(), '„Închide” (după o alegere) ascunde setările');
    ok(/politica-cookies/.test(await p.locator('footer a[href*="politica-cookies"]').first().getAttribute('href')), 'subsolul trimite și la pagina „Cookies”');
    const s = await ctx.newPage();
    await s.goto(pagini.shortcode, { waitUntil: 'networkidle' });
    const btn = s.locator('main .gi-cookie-link, .entry-content .gi-cookie-link, .gi-cookie-link').first();
    ok(await btn.count() === 1, 'shortcode [gi_cookie_setari]: butonul apare');
    await btn.click();
    ok(await s.locator('#gi-cookie [data-gi-view="settings"]').isVisible(), 'butonul din shortcode deschide setările');
    const h = await ctx.newPage();
    await h.goto(BASE + '/#setari-cookie', { waitUntil: 'networkidle' });
    ok(await h.locator('#gi-cookie [data-gi-view="settings"]').isVisible(), 'adresa #setari-cookie deschide setările');
    await ctx.close();
  }

  // ---------- 12. Zona echipei fără banner ----------
  {
    const { ctx, reqs } = await visitor();
    const p = await ctx.newPage();
    const resp = await p.goto(BASE + '/zona-echipei/', { waitUntil: 'networkidle' });
    ok(resp && resp.status() === 200, 'Zona echipei se deschide');
    ok(await p.locator('#gi-cookie').count() === 0 && await p.locator('#gi-consent-default').count() === 0 && await p.locator('script[src*="consent.js"]').count() === 0, 'Zona echipei: fără banner, fără stare Consent Mode, fără consent.js');
    ok(reqs.length === 0, 'Zona echipei: nicio cerere de măsurare');
    await ctx.close();
  }

  // ---------- 13. setările ----------
  {
    await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    const row = ap.locator('tr:has-text("Cookie-uri și măsurare")');
    ok(await row.count() === 1, 'Setări: rândul „Cookie-uri și măsurare”');
    ok(/GTM4WP[^]*oprit/.test(await row.innerText()), 'Setări: GTM4WP apare „oprit” (nu e instalat local)');
    ok(/găsită/.test(await row.innerText()), 'Setări: pagina „Cookies” găsită');
    await ap.evaluate(() => { document.querySelector('form[action="options.php"]').noValidate = true; }); // validarea din browser (pattern) se ocolește: aici se testează curățarea din server
    const purj0 = (await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json());
    ok(purj0.versiune_cache === '1.9.0' && purj0.purjari >= 1, 'după instalare/actualizare se șterge memoria serverului (LiteSpeed): ' + JSON.stringify(purj0));
    await ap.fill('input[name="gi_integration_options[gtm_id]"]', 'gtm-ab');
    await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
    let o = await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json();
    ok(o.gtm_id === 'GTM-5XNZX5QT' && o.consent_enabled === 1, 'cod invalid („gtm-ab”) → se păstrează codul vechi, nu se șterge în tăcere (' + JSON.stringify(o) + ')');
    const purjCod = o.purjari;
    ok(/nu arată ca un cod GTM/.test(await ap.locator('body').innerText()), 'Setări: mesaj de eroare vizibil pentru codul invalid');
    await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    await ap.evaluate(() => { document.querySelector('form[action="options.php"]').noValidate = true; });
    await ap.fill('input[name="gi_integration_options[gtm_id]"]', '');
    await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
    o = await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json();
    ok(o.gtm_id === '', 'cod golit de proprietară → se salvează gol (fără măsurare)');
    await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    ok(await ap.locator('.notice-info:has-text("lipsește codul Tag Manager")').count() === 1, 'Setări: mesaj „lipsește codul Tag Manager” când e gol');
    {
      const { ctx, reqs } = await visitor();
      const p = await ctx.newPage();
      await p.goto(BASE + '/', { waitUntil: 'networkidle' });
      await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="accept"]').click();
      await sleep(400);
      ok((await cookieOf(ctx, 'gi_consent')) === '1:1:1' && gtmReqs(reqs).length === 0, 'fără cod Tag Manager: se păstrează alegerea, dar nu se încarcă nimic');
      await ctx.close();
    }
    await ap.evaluate(() => { document.querySelector('form[action="options.php"]').noValidate = true; });
    await ap.fill('input[name="gi_integration_options[gtm_id]"]', ' gtm-test1234 ');
    await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
    o = await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json();
    ok(o.gtm_id === 'GTM-TEST1234', 'cod valid, cu spații și litere mici, se curăță: ' + o.gtm_id);
    ok(o.purjari > purjCod, 'o schimbare reală a setărilor șterge memoria serverului (LiteSpeed): ' + purjCod + ' → ' + o.purjari);
    {
      const { ctx, reqs } = await visitor();
      const p = await ctx.newPage();
      await p.goto(BASE + '/', { waitUntil: 'networkidle' });
      await p.locator('#gi-cookie [data-gi-view="main"] [data-gi-act="accept"]').click();
      await p.waitForFunction(() => !!window.__gtm_stub, null, { timeout: 5000 }).catch(() => {});
      ok(gtmReqs(reqs).length === 1 && /id=GTM-TEST1234/.test(gtmReqs(reqs)[0]), 'se încarcă containerul din setări (GTM-TEST1234)');
      await ctx.close();
    }
    // comutator oprit
    await ap.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
    await ap.uncheck('input[name="gi_integration_options[consent_enabled]"]');
    await Promise.all([ap.waitForNavigation(), ap.click('input#submit, #submit')]);
    o = await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json();
    ok(o.consent_enabled === 0, 'comutatorul „Afișează bannerul” oprit se salvează');
    const off = await (await ap.request.get(BASE + '/?c190_vechi=1')).text();
    ok(!off.includes('id="gi-cookie"') && !off.includes('gi-consent-default') && !off.includes('consent.js') && !off.includes('gi-cookie-reopen') && !/data-gi-cookie-open/.test(off), 'oprit: nu apare nimic din modul (nici banner, nici stare Consent Mode, nici script, nici link în subsol)');
    ok(OLD_IDS.every((id) => !off.includes(id)), 'oprit: plasa de siguranță pentru codurile vechi rămâne activă');
    await ap.request.get(BASE + '/?gi_test=c190_reset');
    const back = await (await ap.request.get(BASE + '/?gi_test=c190_optiuni')).json();
    ok(back.consent_enabled === 1 && back.gtm_id === 'GTM-5XNZX5QT', 'valorile implicite revin');
  }

  ok(logLines() === phpBefore, 'fără erori PHP noi în jurnal (' + (logLines() - phpBefore) + ')');
  await browser.close();
  console.log(failures === 0 ? '\nTOATE VERIFICĂRILE AU TRECUT' : '\n' + failures + ' VERIFICĂRI AU EȘUAT');
  process.exit(failures === 0 ? 0 : 1);
})().catch((e) => { console.error(e); process.exit(1); });
