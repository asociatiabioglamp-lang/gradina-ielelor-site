// Test pentru versiunea 1.3.15, pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md).
// Are nevoie de mu-plugin-ul gi-test-stiluri-straine.php (numai local): cu „?gi_test=stiluri” pagina primește stilurile temei vechi
// și ale altor module, ca pe site-ul real, plus steagul de limbă și bara de jos a temei.
// Verifică, pe telefon (în picioare și pe lat), tabletă și calculator:
//  - prima pagină pe telefon: toate cele trei poze de la intrare se văd pe primul ecran, și pe un browser mai vechi;
//  - niciun titlu nu ajunge sub antet (paginile ofertelor și paginile Contact / Despre noi / Regulament);
//  - steagul de limbă nu stă peste siglă, meniu sau text și nu însoțește derularea;
//  - bara de jos a temei vechi nu apare pe paginile noastre, iar butonul „Rezervă” de pe telefon e liber;
//  - linkurile de peste fotografii sunt albe; subsolul are „Contul meu” și „Coș” și nu mai are „Litigii online (SOL)”;
//  - fără derulare pe orizontală.
// Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules): node test-1-3-15.js
// Iese cu cod 1 dacă o verificare pică.
const { chromium } = require('playwright');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
const Q = '?gi_test=stiluri';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}

const OFFERS = ['/glamping/', '/camping/', '/bungalow/', '/festivaluri-si-tabere/', '/evenimente-private/'];
const CONTENT = ['/contact/', '/despre-noi/', '/regulament-politici/'];
const SIZES = { 'telefon îngust': [320, 656], 'telefon': [360, 740], 'telefon mare': [414, 896], 'telefon pe lat': [740, 360], 'tabletă': [768, 1024], 'calculator': [1440, 900] };

// Rulează în pagină: adună tot ce verificăm.
const MEASURE = () => {
  const vis = (e) => { const c = getComputedStyle(e); const b = e.getBoundingClientRect(); return b.width > 2 && b.height > 2 && c.visibility !== 'hidden' && c.display !== 'none' && parseFloat(c.opacity) > 0.05; };
  const label = (e) => (e.innerText || e.getAttribute('aria-label') || e.getAttribute('alt') || e.className || e.tagName).toString().replace(/\s+/g, ' ').trim().slice(0, 28);
  const flag = document.getElementById('gt_float_wrapper');
  const F = flag ? flag.getBoundingClientRect() : null;
  const hit = (b) => F && !(F.right <= b.left + 1 || b.right <= F.left + 1 || F.bottom <= b.top + 1 || b.bottom <= F.top + 1);
  const over = [];
  if (flag) {
    document.querySelectorAll('a,button,input,select,img,[role="button"]').forEach((e) => {
      if (flag.contains(e) || e.closest('.home-hero-slice,.gi-page-hero-media,.wd-toolbar') || !vis(e)) { return; }
      const b = e.getBoundingClientRect();
      if (b.top <= 320 && b.width <= innerWidth * 0.95 && hit(b)) { over.push(label(e)); }
    });
    document.querySelectorAll('h1,h2,p,.eyebrow,.gi-breadcrumb').forEach((e) => {
      if (flag.contains(e) || !vis(e) || e.getBoundingClientRect().top > 320) { return; }
      const range = document.createRange(); range.selectNodeContents(e);
      for (const b of range.getClientRects()) { if (b.width > 1 && hit(b)) { over.push('text: ' + label(e)); break; } }
    });
  }
  const header = document.querySelector('.gi-site .site-header');
  const hb = header ? header.getBoundingClientRect().bottom : 0;
  const under = [];
  document.querySelectorAll('.gi-page-hero h1,.gi-page-hero .gi-breadcrumb,.gi-page-hero .eyebrow,.gi-content-hero h1,.gi-content-hero .gi-breadcrumb,.home-hero h1,.home-hero .eyebrow').forEach((e) => {
    if (!vis(e)) { return; }
    const range = document.createRange(); range.selectNodeContents(e);
    const rects = range.getClientRects();
    if (rects.length && rects[0].top < hb - 1) { under.push(label(e) + ' la ' + Math.round(rects[0].top) + ' px'); }
  });
  const notWhite = [];
  document.querySelectorAll('.gi-page-hero .gi-breadcrumb a,.gi-page-hero .gi-hero-link,.gi-closing .gi-hero-link,.gi-back-link').forEach((e) => {
    const c = getComputedStyle(e).color;
    if (c !== 'rgb(255, 255, 255)') { notWhite.push(label(e) + ' ' + c); }
  });
  const slices = Array.from(document.querySelectorAll('.home-hero-slice')).map((e) => {
    const b = e.getBoundingClientRect();
    return [Math.round(Math.max(0, Math.min(innerHeight, b.bottom) - Math.max(0, b.top))), Math.round(Math.max(0, Math.min(innerWidth, b.right) - Math.max(0, b.left)))];
  });
  const toolbar = Array.from(document.querySelectorAll('.wd-toolbar')).filter((e) => getComputedStyle(e).display !== 'none' && e.getBoundingClientRect().height > 0).length;
  const cta = document.querySelector('.gi-site .mobile-booking-cta');
  let ctaFree = null;
  if (cta && vis(cta)) {
    const b = cta.getBoundingClientRect();
    const top = document.elementFromPoint(b.left + b.width / 2, b.top + b.height / 2);
    ctaFree = !!top && (top === cta || cta.contains(top));
  }
  return {
    hasForeign: !!document.getElementById('gi-test-stiluri-straine'),
    flag: F ? { left: Math.round(F.left), top: Math.round(F.top), right: Math.round(F.right), position: getComputedStyle(flag).position } : null,
    over: [...new Set(over)], under, notWhite, slices, toolbar, ctaFree,
    scrollX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    bodyPadBottom: getComputedStyle(document.body).paddingBottom,
    footer: Array.from(document.querySelectorAll('.gi-site footer a')).map((a) => a.textContent.replace(/\s+/g, ' ').trim()),
  };
};

(async () => {
  const browser = await chromium.launch();

  async function open(size, path, opts) {
    const [w, h] = SIZES[size];
    const phone = Math.min(w, h) <= 430;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: phone, hasTouch: phone });
    const page = await ctx.newPage();
    page.setDefaultTimeout(90000);
    if (opts && opts.oldBrowser) {
      // Un browser mai vechi nu cunoaște unitatea „svh” și aruncă declarațiile care o conțin.
      await page.route(/gradina-ielelor-integration\/assets\/css\/.*\.css/, async (route) => {
        const resp = await route.fetch();
        const css = (await resp.text()).replace(/[a-z-]+\s*:[^;{}]*svh[^;{}]*;?/gi, '');
        await route.fulfill({ response: resp, body: css });
      });
    }
    const resp = await page.goto(BASE + path + Q, { waitUntil: 'load' });
    await page.waitForTimeout(500);
    const m = await page.evaluate(MEASURE);
    m.status = resp.status(); m.w = w; m.h = h;
    await ctx.close();
    return m;
  }

  // 1. Prima pagină pe telefon: cele trei poze, butonul „Rezervă” liber, steagul la locul lui
  for (const size of ['telefon îngust', 'telefon', 'telefon mare']) {
    const m = await open(size, '/');
    ok(m.status === 200 && m.hasForeign, `prima pagină, ${size}: pagina se deschide cu stilurile străine simulate`);
    ok(m.slices.length === 3 && m.slices.every((s) => s[0] >= 90 && s[1] >= 90), `prima pagină, ${size}: cele trei poze se văd pe primul ecran (${m.slices.map((s) => s.join('×')).join(', ')})`);
    ok(m.toolbar === 0, `prima pagină, ${size}: bara de jos a temei vechi nu apare`);
    ok(m.ctaFree !== false, `prima pagină, ${size}: butonul „Rezervă” de jos nu e acoperit` + (m.ctaFree === null ? ' (ascuns sus, apare la derulare)' : ''));
    ok(m.flag && m.flag.position === 'absolute' && m.over.length === 0 && m.flag.left >= 0 && m.flag.right <= m.w, `prima pagină, ${size}: steagul nu stă peste nimic și nu însoțește derularea (${m.over.join(' | ') || 'liber'})`);
    ok(m.under.length === 0, `prima pagină, ${size}: niciun text sub antet (${m.under.join(' | ') || 'în regulă'})`);
    ok(m.scrollX <= 0, `prima pagină, ${size}: fără derulare pe orizontală`);
  }
  const old = await open('telefon', '/', { oldBrowser: true });
  ok(old.slices.length === 3 && old.slices.every((s) => s[0] >= 90 && s[1] >= 90), `prima pagină, telefon cu browser mai vechi: cele trei poze se văd (${old.slices.map((s) => s.join('×')).join(', ')})`);
  for (const size of ['telefon pe lat', 'tabletă', 'calculator']) {
    const m = await open(size, '/');
    ok(m.slices.length === 3 && m.slices.every((s) => s[0] >= 90 && s[1] >= 90), `prima pagină, ${size}: cele trei poze se văd`);
    ok(m.toolbar === 0 && m.bodyPadBottom === '0px', `prima pagină, ${size}: fără bara temei vechi și fără spațiu gol în josul paginii (${m.bodyPadBottom})`);
    ok(m.flag && m.flag.position === 'absolute' && m.over.length === 0 && m.under.length === 0 && m.scrollX <= 0, `prima pagină, ${size}: steag liber, niciun text sub antet, fără derulare pe orizontală (${m.over.concat(m.under).join(' | ') || 'în regulă'})`);
  }

  // 2. Paginile ofertelor și paginile de conținut, pe toate ecranele
  for (const path of OFFERS.concat(CONTENT)) {
    for (const size of Object.keys(SIZES)) {
      const m = await open(size, path);
      const problems = [];
      if (m.status !== 200 || !m.hasForeign) { problems.push('pagina nu s-a deschis (' + m.status + ')'); }
      if (m.under.length) { problems.push('sub antet: ' + m.under.join(' | ')); }
      if (m.over.length) { problems.push('steag peste: ' + m.over.join(' | ')); }
      if (!m.flag || m.flag.position !== 'absolute' || m.flag.left < 0 || m.flag.right > m.w) { problems.push('steag în afara ecranului sau fix'); }
      if (m.notWhite.length) { problems.push('link nealb peste fotografie: ' + m.notWhite.join(' | ')); }
      if (m.toolbar) { problems.push('apare bara temei vechi'); }
      if (m.ctaFree === false) { problems.push('butonul „Rezervă” e acoperit'); }
      if (m.scrollX > 0) { problems.push('derulare pe orizontală ' + m.scrollX + ' px'); }
      ok(problems.length === 0, `${path} — ${size}: titlu sub antet, steag, linkuri, bară, derulare (${problems.join('; ') || 'în regulă'})`);
    }
  }

  // 3. Subsol
  const f = await open('calculator', '/glamping/');
  ok(f.footer.includes('Contul meu') && f.footer.includes('Coș'), 'subsol: are „Contul meu” și „Coș”');
  ok(!f.footer.some((t) => /SOL/.test(t)) && f.footer.includes('ANPC'), 'subsol: fără „Litigii online (SOL)”, cu ANPC');

  // 4. Paginile cu tema veche (produs): regula pentru steag e tipărită, iar steagul nu mai e fix
  {
    const ctx = await browser.newContext({ viewport: { width: 360, height: 740 }, isMobile: true, hasTouch: true });
    const page = await ctx.newPage();
    await page.goto(BASE + '/?p=30&gi_test=stiluri', { waitUntil: 'load', timeout: 90000 });
    const r = await page.evaluate(() => {
      const s = document.getElementById('gi-telefon-tema-veche'); const flag = document.getElementById('gt_float_wrapper');
      const b = flag ? flag.getBoundingClientRect() : null;
      return { css: s ? s.textContent : '', inHead: !!(s && s.closest('head')), position: flag ? getComputedStyle(flag).position : '', left: b ? Math.round(b.left) : -1, top: b ? Math.round(b.top) : -1 };
    });
    ok(r.inHead && /#gt_float_wrapper/.test(r.css), 'pagină cu tema veche: regula pentru steag e în pagină');
    ok(r.position === 'absolute' && r.left === 8 && r.top === 54, `pagină cu tema veche, telefon: steagul stă sub butonul „Meniu” (${r.left},${r.top}) și nu însoțește derularea`);
    await page.setViewportSize({ width: 1440, height: 900 });
    const d = await page.evaluate(() => { const b = document.getElementById('gt_float_wrapper').getBoundingClientRect(); return { right: Math.round(innerWidth - b.right), top: Math.round(b.top) }; });
    ok(d.right === 10 && d.top === 66, `pagină cu tema veche, calculator: steagul stă în dreapta, sub coș (${d.right} px de margine, ${d.top} px de sus)`);
    // pe paginile noastre regula pentru tema veche nu se tipărește
    await page.goto(BASE + '/glamping/' + Q, { waitUntil: 'load', timeout: 90000 });
    ok(!(await page.$('#gi-telefon-tema-veche')), 'pagină nouă: regula pentru tema veche nu se tipărește');
    await ctx.close();
  }

  await browser.close();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})();
