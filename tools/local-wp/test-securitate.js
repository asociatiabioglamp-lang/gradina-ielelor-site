// Test de securitate (pluginul 1.3.10), pe WordPress local pornit pe 127.0.0.1:9400 (vezi tools/local-wp/README.md).
// Are nevoie de mu-plugin-ul gi-test-securitate.php (numai local) și de contul admin / password din Playground.
// Verifică: antetele de protecție, lista conturilor ascunsă vizitatorilor dar vizibilă administratorului,
// paginile de autor, oEmbed fără autor, mesaj unic la autentificare greșită (WordPress și WooCommerce),
// editor de fișiere și XML-RPC oprite, REST-ul WooCommerce (Store API) neatins, fără erori PHP.
// Rulează cu Playwright instalat (NODE_PATH=<folder>/node_modules): node test-securitate.js
// Opțional: GI_PHP_LOG=<cale spre wp-content/gi-php-errors.log>. Iese cu cod 1 dacă o verificare pică.
const { chromium, request } = require('playwright');
const fs = require('fs');
const BASE = process.env.GI_BASE || 'http://127.0.0.1:9400';
const MSG = 'Datele de autentificare nu sunt corecte. Încearcă din nou.';
let failures = 0;
function ok(condition, text) {
  console.log((condition ? '[OK]     ' : '[EROARE] ') + text);
  if (!condition) { failures++; }
}
const LEAK = /not registered|nu este înregistrat|nu e înregistrat|incorrect|este incorect|necunoscut|unknown/i;

(async () => {
  const anon = await request.newContext({ baseURL: BASE, maxRedirects: 0 });
  const follow = await request.newContext({ baseURL: BASE });

  // 1. Antete de protecție pe paginile publice
  for (const path of ['/', '/glamping/', '/pagina-care-nu-exista-xyz/']) {
    const r = await follow.get(path);
    const h = r.headers();
    ok(h['x-frame-options'] === 'SAMEORIGIN' && h['x-content-type-options'] === 'nosniff' && h['referrer-policy'] === 'strict-origin-when-cross-origin' && /camera=\(\)/.test(h['permissions-policy'] || ''),
      `antete de protecție pe ${path} (HTTP ${r.status()})`);
    ok(!h['strict-transport-security'], `fără HSTS pe HTTP (${path}): HSTS se trimite numai prin HTTPS`);
  }
  const hs = await (await follow.get('/?gi_test=hsts')).json();
  ok(hs.ssl === true && hs.https['Strict-Transport-Security'] === 'max-age=2592000', 'prin HTTPS: Strict-Transport-Security max-age=2592000 (30 de zile), fără includeSubDomains');
  ok(!/includeSubDomains|preload/.test(hs.https['Strict-Transport-Security'] || ''), 'HSTS fără subdomenii și fără preload');

  // 2. Versiunea WordPress nu mai apare în cod
  const home = await (await follow.get('/')).text();
  ok(!/<meta name="generator"/i.test(home), 'fără <meta name="generator"> în pagina principală');

  // 3. REST: vizitatorii nu mai văd conturile; restul REST funcționează
  const u = await anon.get('/wp-json/wp/v2/users');
  ok(u.status() === 404 && (await u.json()).code === 'rest_no_route', 'vizitator: /wp-json/wp/v2/users → 404 rest_no_route');
  const u1 = await anon.get('/wp-json/wp/v2/users/1');
  ok(u1.status() === 404, 'vizitator: /wp-json/wp/v2/users/1 → 404');
  const idx = await (await anon.get('/wp-json/')).json();
  const routes = Object.keys(idx.routes || {});
  ok(!routes.includes('/wp/v2/users') && !routes.includes('/wp/v2/users/(?P<id>[\\d]+)'), 'vizitator: indexul REST nu mai listează lista de conturi și contul după număr');
  const pages = await anon.get('/wp-json/wp/v2/pages');
  ok(pages.status() === 200, 'vizitator: restul REST (pagini) merge, HTTP ' + pages.status());
  const store = await anon.get('/wp-json/wc/store/v1/cart');
  ok(store.status() === 200, 'vizitator: WooCommerce Store API (coș) merge, HTTP ' + store.status());
  const frag = await anon.post('/?wc-ajax=get_refreshed_fragments');
  ok(frag.status() === 200, 'vizitator: WooCommerce wc-ajax merge, HTTP ' + frag.status());

  // 4. Pagini de autor
  for (const [url, label] of [['/?author=1', '/?author=1'], ['/?author_name=admin', '/?author_name=admin'], ['/author/admin/', '/author/admin/']]) {
    const r = await anon.get(url);
    const loc = r.headers()['location'] || '';
    ok(r.status() === 301 && (loc === BASE + '/' || loc === '/'), `vizitator: ${label} → 301 spre prima pagină (${r.status()} ${loc})`);
  }

  // 5. oEmbed fără autor
  const oe = await anon.get('/wp-json/oembed/1.0/embed?url=' + encodeURIComponent(BASE + '/hello-world/'));
  if (oe.status() === 200) {
    const j = await oe.json();
    ok(!('author_name' in j) && !('author_url' in j), 'oEmbed: fără author_name și author_url');
  } else {
    console.log('[omis]   oEmbed: articolul „hello-world” nu există local (HTTP ' + oe.status() + ')');
  }

  // 6. XML-RPC: metodele cu autentificare (cele folosite la atacuri cu parole) sunt oprite, chiar și cu parola corectă.
  // WordPress lasă deschise system.listMethods și altele fără autentificare; nu pot fi oprite din plugin.
  const rpc = (user, pass) => '<?xml version="1.0"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value><string>' + user + '</string></value></param><param><value><string>' + pass + '</string></value></param></params></methodCall>';
  for (const [u2, p2] of [['admin', 'password'], ['nu_exista_xyz', 'x']]) {
    const xml = await anon.post('/xmlrpc.php', { headers: { 'Content-Type': 'text/xml' }, data: rpc(u2, p2) });
    const xb = await xml.text();
    ok(/<int>405<\/int>/.test(xb) && /disabled/i.test(xb), `XML-RPC: autentificarea (${u2}) e refuzată cu „servicii oprite” (405), nu cu „parolă greșită”`);
  }

  // 7. Autentificare greșită: același mesaj, în WordPress și în WooCommerce
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1200, height: 900 } });
  const page = await ctx.newPage();
  page.setDefaultTimeout(60000);
  async function wpLoginError(user, pass) {
    await page.context().clearCookies();
    await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login', user); await page.fill('#user_pass', pass); await page.click('#wp-submit');
    await page.waitForSelector('#login_error');
    return (await page.textContent('#login_error')).replace(/\s+/g, ' ').trim();
  }
  const e1 = await wpLoginError('nu_exista_xyz', 'parola-gresita-1');
  const e2 = await wpLoginError('admin', 'parola-gresita-2');
  ok(e1.includes(MSG) && e2.includes(MSG), 'wp-login: același mesaj pentru nume inexistent și pentru parolă greșită');
  ok(!LEAK.test(e1) && !LEAK.test(e2), 'wp-login: mesajul nu spune dacă numele există („' + e2.slice(0, 80) + '”)');

  async function wooLoginError(user, pass) {
    await page.context().clearCookies();
    await page.goto(BASE + '/my-account/', { waitUntil: 'domcontentloaded' });
    await page.fill('#username', user); await page.fill('#password', pass);
    await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[name="login"]')]);
    const notice = page.locator('.woocommerce-error, .woocommerce-notices-wrapper [role="alert"], [role="alert"]').filter({ hasText: /\S/ }).first();
    await notice.waitFor({ state: 'attached', timeout: 20000 });
    return (await notice.textContent()).replace(/\s+/g, ' ').trim();
  }
  const w1 = await wooLoginError('nu_exista_xyz', 'parola-gresita-1');
  const w2 = await wooLoginError('admin', 'parola-gresita-2');
  ok(w1.includes(MSG) && w2.includes(MSG), 'WooCommerce (Contul meu): același mesaj pentru nume inexistent și parolă greșită');
  ok(!LEAK.test(w1) && !LEAK.test(w2), 'WooCommerce: mesajul nu spune dacă numele există („' + w2.slice(0, 80) + '”)');

  // 8. Administratorul: vede lista, poate edita, dar nu are editor de fișiere
  await page.context().clearCookies();
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', 'admin'); await page.fill('#user_pass', 'password'); await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**');
  const nonce = (await (await page.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce')).text()).trim();
  const adminUsers = await page.request.get(BASE + '/wp-json/wp/v2/users', { headers: { 'X-WP-Nonce': nonce } });
  const au = adminUsers.status() === 200 ? await adminUsers.json() : [];
  ok(adminUsers.status() === 200 && Array.isArray(au) && au.some((x) => x.slug === 'admin'), 'administrator: /wp-json/wp/v2/users merge și arată conturile (HTTP ' + adminUsers.status() + ')');
  const ar = await page.request.get(BASE + '/?author=1', { maxRedirects: 0 });
  ok(!((ar.headers()['location'] || '') === BASE + '/' || (ar.headers()['location'] || '') === '/'), 'administrator: ?author=1 nu e dus pe prima pagină');
  const editor = await page.goto(BASE + '/wp-admin/plugin-editor.php', { waitUntil: 'domcontentloaded' });
  ok(editor.status() === 403 || !(await page.$('#newcontent')), 'administrator: editorul de plugin-uri nu mai e disponibil (HTTP ' + editor.status() + ')');
  const themeEditor = await page.goto(BASE + '/wp-admin/theme-editor.php', { waitUntil: 'domcontentloaded' });
  ok(themeEditor.status() === 403 || !(await page.$('#newcontent')), 'administrator: editorul de teme nu mai e disponibil (HTTP ' + themeEditor.status() + ')');
  await page.goto(BASE + '/wp-admin/options-general.php?page=gi-integration', { waitUntil: 'domcontentloaded' });
  ok(!!(await page.$('#wpbody-content')) && !/nu ai voie|not allowed to access/i.test(await page.textContent('body')), 'administrator: pagina Setări → Grădina Ielelor se deschide');

  // 9. Fără erori PHP provocate de plugin
  const logPath = process.env.GI_PHP_LOG;
  if (logPath && fs.existsSync(logPath)) {
    const bad = fs.readFileSync(logPath, 'utf8').split('\n').filter((l) => /gradina-ielelor-integration/.test(l) && /(Fatal|Warning|Notice|Deprecated|Parse)/i.test(l));
    ok(bad.length === 0, 'jurnal PHP: fără erori din plugin' + (bad.length ? ' — ' + bad.slice(0, 2).join(' | ') : ''));
  } else if (logPath) {
    ok(true, 'jurnal PHP: fără erori (fișierul nu a fost creat)');
  } else {
    console.log('[omis]   jurnalul PHP nu e indicat (GI_PHP_LOG)');
  }

  await browser.close();
  await anon.dispose(); await follow.dispose();
  console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
  process.exit(failures ? 1 : 0);
})();
