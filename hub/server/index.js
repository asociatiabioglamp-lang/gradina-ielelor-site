import express from 'express';
import cors from 'cors';
import { ImapFlow } from 'imapflow';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { execFile } from 'child_process';
import { promisify } from 'util';

const runCmd = promisify(execFile);
const PS_EXE = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, 'data');
const DB_FILE = path.join(DATA_DIR, 'db.json');
const PORT = 8787;
const FRONTEND_SETTINGS = `http://localhost:7100/?tab=setari`;
const IMAP_HOST = 'outlook.office365.com';

fs.mkdirSync(DATA_DIR, { recursive: true });

// ---------------------------------------------------------------- db
const seed = {
  reviews: [
    { id: 'r1', author: 'Recenzent Google 1', rating: 5, source: 'Google', date: '2026-08-14', status: 'new',
      text: 'Am stat aici doua nopti, un camping superb! Nu ne-a lipsit nimic, exista o chicineta echipata cu tot ce este nevoie unde am putut sa gatim, bai si dusuri curate cu apa calda, loc de facut gratare, loc de facut foc. Superb! Si un view minuntat 🙂 deja ne este dor de Furnica (cățelușa din camping). Se poate plati si cu cardul de vacanta la ei pe website.',
      reply: '' },
    { id: 'r2', author: 'Recenzent Google 2', rating: 5, source: 'Google', date: '2026-08-02', status: 'new',
      text: 'Locația este extraordinară, glampingul este spațios, curat. Este o bucurie sa te trezești și să ai parte de o asemenea priveliște. Recomand cu căldură pentru familiile cu copii.',
      reply: '' },
    { id: 'r3', author: 'Recenzent Google 3', rating: 5, source: 'Google', date: '2026-07-27', status: 'replied',
      text: 'Am fost plăcut surprins de toate condițiile existente, totul curat, impecabil, priveliștea superbă, gazda foarte amabilă, totul a fost la superlativ.',
      reply: 'Mulțumim frumos! Ne bucurăm că ați avut parte de o ședere fără cusur. Vă mai așteptăm în Grădină!' },
    { id: 'r4', author: 'Recenzent Google 4', rating: 5, source: 'Google', date: '2026-07-19', status: 'new',
      text: 'O locatie de vis pentru petrecerea unui weekend de poveste, conditiile de aici se ridica peste nivelul multor campinguri din afara tarii.',
      reply: '' },
    { id: 'r5', author: 'Recenzent Google 5', rating: 5, source: 'Google', date: '2026-07-11', status: 'new',
      text: 'Loc de campare idilic. Foarte curat (și la baie și la dușuri și la Bucătărie impecabil) amenajat cu atenție la detalii, cu vedere la munții Fagaraș. Spațiul este generos, cu locuri pentru rulote, corturi și glamping.',
      reply: '' },
    { id: 'r6', author: 'Recenzent Google 6', rating: 5, source: 'Google', date: '2026-07-05', status: 'new',
      text: 'Foarte frumos, curat și bine dotat (baie, dușuri cu apa calda, chicineta, foișor, sursa apa - put de mare adâncime, locație împrejmuită), spațios (aprox 6 ha), linistit...',
      reply: '' },
    { id: 'r7', author: 'Recenzent Google 7', rating: 5, source: 'Google', date: '2026-06-28', status: 'new',
      text: 'O alegere foarte bună pentru o vacanță. Ai de toate: liniște, peisaj, băi, dușuri cu apă caldă, chicinetă cu frigider si aragaz, hamace, locuri destinate copiilor și mai ales CURĂȚENIE!...',
      reply: '' },
    { id: 'r8', author: 'Recenzent Google 8', rating: 5, source: 'Google', date: '2026-06-20', status: 'new',
      text: 'Un loc absolut superb. Recomand din toata inima. Am stat la glamping, care arata impecabil, foarte curat si totul cu mult bun gust....',
      reply: '' },
  ],
  posts: [
    { id: 'p1', platform: 'Instagram + Facebook', date: '2026-10-21', title: 'Toamna în Grădina Ielelor', status: 'planificat', content: 'Fotografii toamnă + caption "Toamna în Grădina Ielelor e liniștea pe care o cauți tot anul…"' },
    { id: 'p2', platform: 'TikTok + Reels', date: '2026-10-23', title: 'O zi de toamnă', status: 'planificat', content: 'Video 20–30 sec: plimbare, frunze, ceață pe lac, foc în vatră' },
    { id: 'p3', platform: 'Instagram + Facebook', date: '2026-10-28', title: 'Legenda ielelor (carusel)', status: 'planificat', content: 'Carusel 5 slide-uri: cine sunt ielele, hora, de ce nu le spui numele' },
    { id: 'p4', platform: 'TikTok + Reels', date: '2026-10-31', title: 'Noaptea Ielelor — video de semnătură', status: 'planificat', content: 'Filmat pe înserat, felinare, ceață, narrație cu legenda — se repostă anual' },
    { id: 'p5', platform: 'Instagram + Facebook + TikTok', date: '2026-11-02', title: 'Rezervări 2027 deschise!', status: 'planificat', content: 'Anunț early-booking + card vacanță + link rezervare' },
    { id: 'p6', platform: 'Instagram + Facebook', date: '2026-11-10', title: '„Voi ați zis" — carusel review-uri', status: 'planificat', content: '4–5 citate reale din Google reviews peste poze din grădină' },
  ],
  events: [
    { id: 's1', week: 'Săpt. 1 · 19–25 oct', theme: 'Toamna în Grădină', title: 'Postare foto toamnă', platform: 'Instagram + Facebook', date: '2026-10-21', status: 'planificat' },
    { id: 's2', week: 'Săpt. 1 · 19–25 oct', theme: 'Toamna în Grădină', title: 'Video „O zi de toamnă"', platform: 'TikTok + Reels', date: '2026-10-23', status: 'planificat' },
    { id: 's3', week: 'Săpt. 1 · 19–25 oct', theme: 'Toamna în Grădină', title: 'Carusel „Din culise"', platform: 'Instagram + Facebook', date: '2026-10-25', status: 'planificat' },
    { id: 's4', week: 'Săpt. 1 · 19–25 oct', theme: 'Toamna în Grădină', title: 'Blog: toamna la Făgăraș', platform: 'Blog', date: '2026-10-23', status: 'planificat' },
    { id: 's5', week: 'Săpt. 2 · 26 oct–1 nov', theme: 'Noaptea Ielelor', title: 'Carusel „Legenda ielelor"', platform: 'Instagram + Facebook', date: '2026-10-28', status: 'planificat' },
    { id: 's6', week: 'Săpt. 2 · 26 oct–1 nov', theme: 'Noaptea Ielelor', title: 'Video de semnătură', platform: 'TikTok + Reels', date: '2026-10-31', status: 'planificat' },
    { id: 's7', week: 'Săpt. 2 · 26 oct–1 nov', theme: 'Noaptea Ielelor', title: 'Blog: cine sunt ielele', platform: 'Blog', date: '2026-10-30', status: 'planificat' },
    { id: 's8', week: 'Săpt. 3 · 2–8 nov', theme: 'Rezervări 2027', title: 'Anunț early-booking', platform: 'Toate', date: '2026-11-02', status: 'planificat' },
    { id: 's9', week: 'Săpt. 3 · 2–8 nov', theme: 'Rezervări 2027', title: 'Carusel tipuri de cazare', platform: 'Instagram', date: '2026-11-04', status: 'planificat' },
    { id: 's10', week: 'Săpt. 3 · 2–8 nov', theme: 'Rezervări 2027', title: 'Video „Top 5 motive"', platform: 'TikTok + Reels', date: '2026-11-07', status: 'planificat' },
    { id: 's11', week: 'Săpt. 4 · 9–15 nov', theme: '„Voi ați zis"', title: 'Carusel review-uri', platform: 'Instagram + Facebook', date: '2026-11-10', status: 'planificat' },
    { id: 's12', week: 'Săpt. 4 · 9–15 nov', theme: '„Voi ați zis"', title: 'Video cu Furnica 🐶', platform: 'TikTok + Reels', date: '2026-11-12', status: 'planificat' },
  ],
  emails: { office: [], rezervari: [] },
  reservations: [
    { id: 'b1', guest: 'Oaspete Airbnb 1', unit: 'Glamping', checkin: '2026-09-19', checkout: '2026-09-20', source: 'Airbnb', amount: 1317.95, status: 'finalizata', note: 'Încasare Airbnb 16 sept' },
    { id: 'b2', guest: 'Oaspete Airbnb 2', unit: '—', checkin: '', checkout: '', source: 'Airbnb', amount: 389.20, status: 'finalizata', note: 'Recenzie 5★ pe 9 sept; încasare 389,20 lei' },
    { id: 'b3', guest: 'Oaspete Airbnb 3', unit: '—', checkin: '', checkout: '', source: 'Airbnb', amount: null, status: 'finalizata', note: 'Recenzie 5★ pe 4 sept' },
    { id: 'b4', guest: 'Oaspete necunoscut', unit: '—', checkin: '2026-10-04', checkout: '', source: 'Airbnb', amount: null, status: 'necesita-atentie', note: '⚠ Recenzie de 2 stele din 4 oct — FĂRĂ răspuns încă' },
    { id: 'b5', guest: '2 oaspeți', unit: '—', checkin: '', checkout: '', source: 'Airbnb', amount: null, status: 'necesita-atentie', note: '⚠ Recenziile tale către oaspeți sunt în așteptare (3 oct)' },
  ],
  emailConfig: { office: null, rezervari: null },
  lastFetch: { office: null, rezervari: null },
};

function loadDb() {
  if (!fs.existsSync(DB_FILE)) {
    fs.writeFileSync(DB_FILE, JSON.stringify(seed, null, 2));
  }
  return JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
}
function saveDb(db) {
  fs.writeFileSync(DB_FILE, JSON.stringify(db, null, 2));
}
let db = loadDb();
if (!db.reservations) { db.reservations = []; saveDb(db); }

// ---------------------------------------------------------------- Microsoft OAuth2
const OAUTH_REDIRECT = `http://localhost:${PORT}/api/oauth/callback`;
const OAUTH_SCOPE = 'https://outlook.office.com/IMAP.AccessAsUser.All offline_access';

async function tokenRequest(tenant, params) {
  const res = await fetch(`https://login.microsoftonline.com/${tenant}/oauth2/v2.0/token`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error_description || data.error || 'Token request failed');
  return data;
}

async function getValidAccessToken(mailbox) {
  const cfg = db.emailConfig[mailbox];
  if (!cfg) throw new Error('Căsuța nu este configurată.');
  if (cfg.mode !== 'oauth') return null;
  const expires = cfg.accessTokenExpiry || 0;
  if (Date.now() < expires - 60_000) return cfg.accessToken;
  const data = await tokenRequest(cfg.tenantId || 'common', {
    client_id: cfg.clientId,
    grant_type: 'refresh_token',
    refresh_token: cfg.refreshToken,
    scope: OAUTH_SCOPE,
  });
  cfg.accessToken = data.access_token;
  cfg.accessTokenExpiry = Date.now() + (data.expires_in || 3600) * 1000;
  if (data.refresh_token) cfg.refreshToken = data.refresh_token;
  saveDb(db);
  return cfg.accessToken;
}

// ---------------------------------------------------------------- local Outlook (COM, fără parolă)
async function fetchLocalOutlook(mailbox) {
  const cfg = db.emailConfig[mailbox];
  const script = path.join(__dirname, 'outlook-fetch.ps1');
  const match = cfg.user || `${mailbox}@gradina-ielelor.ro`;
  const { stdout } = await runCmd(
    PS_EXE,
    ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', script, '-Match', match],
    { timeout: 90_000, maxBuffer: 16 * 1024 * 1024, windowsHide: true }
  );
  const raw = JSON.parse(stdout || '[]');
  const arr = Array.isArray(raw) ? raw : [raw];
  const list = arr.filter(Boolean).map((m) => ({
    uid: m.entryId || m.date,
    fromName: m.fromName || '',
    from: m.from || '',
    to: match,
    subject: m.subject || '(fără subiect)',
    date: m.date || '',
    seen: !!m.seen,
    snippet: (m.snippet || '').replace(/https?:\/\/\S+/g, '[link]').trim(),
  }));
  list.sort((a, b) => new Date(b.date) - new Date(a.date));
  db.emails[mailbox] = list;
  db.lastFetch[mailbox] = new Date().toISOString();
  saveDb(db);
  return list;
}

// ---------------------------------------------------------------- IMAP fetch
function stripHtml(s) {
  return (s || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

// ---------------------------------------------------------------- clasificare automată: spam / reclame
// Domenii de la care NU vin niciodată reclame (clienți, parteneri, platforme)
const TRUSTED_DOMAINS = /(gradina-ielelor\.ro|audserv\.ro|btrl\.ro|airbnb\.com|5stardesk\.net|camping\.info|h2b\.ro|campercontact\.com)$/i;
// Domenii cunoscute de reclame / newslettere (se pot adăuga altele ușor)
const SPAM_DOMAINS = [
  'finestore.ro', 'cosuricorporate.com', 'mobilaabc.ro', 'dyson', 'licente24.com',
  'imogratis.eu', 'reduceriromanesti', 'koziol.de', 'fordbrasov.ro', 'sdsgroup.ro',
  'risco.ro', 'iso-romania.com', 'snapcert.ro', 'padidiver.com', 'apsap.ro',
  'newsletter-enecesar', 'parflex.ro', 'just-ideas.ro', 'carboysafety.ro', 'nacecogreen.ro',
  'mail.instagram.com', 'facebookmail.com', 'tiktok.com', 'youtube.com',
];
// Cuvinte din adresa expeditorului care indică aproape sigur reclamă
const SPAM_SENDER_RE = /^(newsletter|marketing|comenzi|oferte|reduceri|promo|promotii|deals|sales|catalog|shop|vlag|noutati|info)$/i;
// Cuvinte din subiect care indică reclamă / promoție
const SPAM_SUBJECT_RE = /\b(reducere|reduceri|cupon|voucher|\b\d{1,2}\s?%|black friday|precomand[aă]|ofert[aă] special[aă]|super ofert[aă]|doar azi|ultima zi|ultimele zile|discount|profit[aă]\b|economise[șs]te|gratuit|cadou| concurs|premii|promo[tț]ie)\b/i;
// Subiecte tip phishing (nu sunt reclame, dar sunt periculoase — le marcăm separat)
const PHISHING_SUBJECT_RE = /actualizare necesar|cont(ul)? (a fost|va fi) suspendat|verific[aă]ți contul|confirmați identitatea|dezactivare (a )?acces|expiration|expir[ăa] (azi|în)/i;

function classifyEmail(e) {
  const subj = (e.subject || '').trim();
  const from = (e.from || '').toLowerCase();
  const local = from.split('@')[0] || '';
  const domain = from.split('@')[1] || '';
  if (/^\[spam\]/i.test(subj)) return 'spam'; // deja marcat de Bitdefender
  if (TRUSTED_DOMAINS.test(domain)) {
    // de la parteneri de încredere: doar etichetăm notificările automate
    return /^(automated|discover|express|noreply|no-reply|follow-suggestions|posts-recaps?|notify|notifications)@/.test(from) ? 'notificare' : null;
  }
  if (SPAM_DOMAINS.some((d) => domain.includes(d))) return 'spam';
  if (SPAM_SENDER_RE.test(local)) return 'spam';
  if (SPAM_SUBJECT_RE.test(subj)) return 'spam';
  if (PHISHING_SUBJECT_RE.test(subj)) return 'suspicios';
  return null;
}
// etichetează lista și returnează [vizibile, spam]
function splitSpam(list) {
  const tagged = (list || []).map((e) => ({ ...e, category: e.category || classifyEmail(e) }));
  return [tagged.filter((e) => e.category !== 'spam'), tagged.filter((e) => e.category === 'spam')];
}

async function fetchMailbox(mailbox) {
  const cfg = db.emailConfig[mailbox];
  if (!cfg) throw new Error(`Căsuța ${mailbox} nu este configurată.`);
  if (cfg.mode === 'outlook-local') return fetchLocalOutlook(mailbox);
  let auth;
  if (cfg.mode === 'password') {
    auth = { user: cfg.user, pass: cfg.pass };
  } else {
    const token = await getValidAccessToken(mailbox);
    auth = { user: cfg.user, accessToken: token };
  }
  const client = new ImapFlow({
    host: cfg.imapHost || IMAP_HOST, port: 993, secure: true, auth,
    logger: false, socketTimeout: 30_000,
  });
  await client.connect();
  const lock = await client.getMailboxLock('INBOX');
  try {
    const uids = await client.search({ all: true });
    const last = uids.slice(-50);
    if (!last.length) { db.emails[mailbox] = []; }
    else {
      const list = [];
      const range = `${last[0]}:${last[last.length - 1]}`;
      for await (const msg of client.fetch(range, { envelope: true, flags: true, internalDate: true, bodyParts: true })) {
        const parts = msg.bodyParts ? Object.values(msg.bodyParts) : [];
        const raw = parts.map((p) => p.toString('utf8')).join(' ');
        const txt = stripHtml(raw).slice(0, 400);
        list.push({
          uid: msg.uid,
          fromName: msg.envelope?.from?.[0]?.name || msg.envelope?.from?.[0]?.address || '',
          from: msg.envelope?.from?.[0]?.address || '',
          to: (msg.envelope?.to || []).map((a) => a.address || '').join(', '),
          subject: msg.envelope?.subject || '(fără subiect)',
          date: msg.internalDate ? msg.internalDate.toISOString() : '',
          seen: !!msg.flags?.has('\\Seen'),
          snippet: txt,
        });
      }
      list.sort((a, b) => new Date(b.date) - new Date(a.date));
      db.emails[mailbox] = cfg.filterTo
        ? list.filter((e) => e.to.toLowerCase().includes(cfg.filterTo.toLowerCase()))
        : list;
    }
    db.lastFetch[mailbox] = new Date().toISOString();
    saveDb(db);
  } finally {
    lock.release();
    await client.logout();
  }
  return db.emails[mailbox];
}

// ---------------------------------------------------------------- API
const app = express();
app.use(cors());
app.use(express.json());

const ok = (res, data) => res.json({ ok: true, data });
const fail = (res, err, code = 400) => res.status(code).json({ ok: false, error: String(err?.message || err) });

app.get('/api/health', (req, res) => ok(res, { up: true, time: new Date().toISOString() }));

app.get('/api/overview', (req, res) => {
  const reviews = db.reviews;
  const avg = reviews.length ? (reviews.reduce((s, r) => s + r.rating, 0) / reviews.length) : 0;
  const unreadOffice = splitSpam(db.emails.office)[0].filter((e) => !e.seen).length;
  const unreadRez = splitSpam(db.emails.rezervari)[0].filter((e) => !e.seen).length;
  const planned = db.events.filter((e) => e.status !== 'publicat').length;
  ok(res, {
    avgRating: Math.round(avg * 10) / 10,
    reviewCount: reviews.length,
    newReviews: reviews.filter((r) => r.status === 'new').length,
    unreadEmails: unreadOffice + unreadRez,
    unreadOffice, unreadRez,
    emailConfigured: { office: !!db.emailConfig.office, rezervari: !!db.emailConfig.rezervari },
    lastFetch: db.lastFetch,
    plannedPosts: planned,
    eventsDone: db.events.filter((e) => e.status === 'publicat').length,
    eventsTotal: db.events.length,
  });
});

// reviews
app.get('/api/reviews', (req, res) => ok(res, db.reviews));
app.patch('/api/reviews/:id', (req, res) => {
  const r = db.reviews.find((x) => x.id === req.params.id);
  if (!r) return fail(res, 'Review negăsit', 404);
  Object.assign(r, req.body);
  saveDb(db);
  ok(res, r);
});

// posts
app.get('/api/posts', (req, res) => ok(res, db.posts));
app.post('/api/posts', (req, res) => {
  const p = { id: 'p' + Date.now(), status: 'planificat', ...req.body };
  db.posts.push(p); saveDb(db); ok(res, p);
});
app.patch('/api/posts/:id', (req, res) => {
  const p = db.posts.find((x) => x.id === req.params.id);
  if (!p) return fail(res, 'Postare negăsită', 404);
  Object.assign(p, req.body); saveDb(db); ok(res, p);
});

// calendar events
app.get('/api/events', (req, res) => ok(res, db.events));
app.patch('/api/events/:id', (req, res) => {
  const e = db.events.find((x) => x.id === req.params.id);
  if (!e) return fail(res, 'Element negăsit', 404);
  Object.assign(e, req.body); saveDb(db); ok(res, e);
});

// reservations
app.get('/api/reservations', (req, res) => ok(res, db.reservations));
app.post('/api/reservations', (req, res) => {
  const r = { id: 'b' + Date.now(), status: 'confirmata', note: '', amount: null, ...req.body };
  db.reservations.push(r); saveDb(db); ok(res, r);
});
app.patch('/api/reservations/:id', (req, res) => {
  const r = db.reservations.find((x) => x.id === req.params.id);
  if (!r) return fail(res, 'Rezervare negăsită', 404);
  Object.assign(r, req.body); saveDb(db); ok(res, r);
});

// email config
app.get('/api/email/config', (req, res) => {
  const strip = (c) => c ? { mode: c.mode, user: c.user, clientId: c.clientId, tenantId: c.tenantId, imapHost: c.imapHost, filterTo: c.filterTo, connected: !!c.refreshToken || c.mode === 'password' } : null;
  ok(res, { office: strip(db.emailConfig.office), rezervari: strip(db.emailConfig.rezervari) });
});
app.post('/api/email/config', (req, res) => {
  const { mailbox, ...cfg } = req.body;
  db.emailConfig[mailbox] = { ...(db.emailConfig[mailbox] || {}), ...cfg };
  saveDb(db); ok(res, { saved: true });
});

app.get('/api/email/oauth-url', (req, res) => {
  const mailbox = req.query.mailbox;
  const cfg = db.emailConfig[mailbox];
  if (!cfg?.clientId) return fail(res, 'Completează mai întâi Client ID în Setări.');
  const tenant = cfg.tenantId || 'common';
  const url = `https://login.microsoftonline.com/${tenant}/oauth2/v2.0/authorize?` + new URLSearchParams({
    client_id: cfg.clientId,
    response_type: 'code',
    redirect_uri: OAUTH_REDIRECT,
    response_mode: 'query',
    scope: OAUTH_SCOPE,
    state: mailbox,
  });
  ok(res, { url });
});

app.get('/api/email/consent-url', (req, res) => {
  const mailbox = req.query.mailbox;
  const cfg = db.emailConfig[mailbox];
  if (!cfg?.clientId) return fail(res, 'Completează mai întâi Client ID în Setări.');
  const tenant = cfg.tenantId || 'common';
  const url = `https://login.microsoftonline.com/${tenant}/adminconsent?` + new URLSearchParams({
    client_id: cfg.clientId,
    redirect_uri: OAUTH_REDIRECT,
    state: 'consent:' + mailbox,
  });
  ok(res, { url });
});

app.get('/api/oauth/callback', async (req, res) => {
  const { code, state, error, error_description } = req.query;
  if (error) return res.redirect(`${FRONTEND_SETTINGS}&oauth=error&msg=${encodeURIComponent(error_description || error)}`);
  const mailbox = String(state || '').replace('consent:', '');
  try {
    const cfg = db.emailConfig[mailbox];
    const data = await tokenRequest(cfg.tenantId || 'common', {
      client_id: cfg.clientId,
      grant_type: 'authorization_code',
      code,
      redirect_uri: OAUTH_REDIRECT,
      scope: OAUTH_SCOPE,
    });
    cfg.accessToken = data.access_token;
    cfg.accessTokenExpiry = Date.now() + (data.expires_in || 3600) * 1000;
    if (data.refresh_token) cfg.refreshToken = data.refresh_token;
    saveDb(db);
    res.redirect(`${FRONTEND_SETTINGS}&oauth=ok&mailbox=${mailbox}`);
  } catch (e) {
    res.redirect(`${FRONTEND_SETTINGS}&oauth=error&msg=${encodeURIComponent(e.message)}`);
  }
});

app.get('/api/email/status', async (req, res) => {
  const mailbox = req.query.mailbox;
  const cfg = db.emailConfig[mailbox];
  ok(res, {
    configured: !!cfg,
    mode: cfg?.mode || null,
    user: cfg?.user || null,
    connected: cfg ? (cfg.mode === 'outlook-local' ? true : cfg.mode === 'password' ? !!cfg.pass : !!cfg.refreshToken) : false,
    lastFetch: db.lastFetch[mailbox],
    count: db.emails[mailbox]?.length || 0,
    spamCount: splitSpam(db.emails[mailbox] || [])[1].length,
  });
});

app.post('/api/email/test', async (req, res) => {
  try {
    const mailbox = req.body.mailbox;
    await fetchMailbox(mailbox);
    ok(res, { connected: true, count: db.emails[mailbox].length });
  } catch (e) { fail(res, e); }
});

app.post('/api/email/fetch', async (req, res) => {
  try {
    const mailbox = req.body.mailbox;
    const emails = await fetchMailbox(mailbox);
    ok(res, { count: emails.length });
  } catch (e) { fail(res, e); }
});

app.get('/api/emails', (req, res) => {
  const mailbox = req.query.mailbox || 'office';
  const [visible, spam] = splitSpam(db.emails[mailbox] || []);
  const emails = req.query.includeSpam === '1' ? [...visible, ...spam] : visible;
  emails.sort((a, b) => new Date(b.date) - new Date(a.date));
  ok(res, { emails, spamCount: spam.length, lastFetch: db.lastFetch[mailbox] });
});

// background polling every 5 minutes for configured mailboxes
async function pollAll() {
  for (const mb of ['office', 'rezervari']) {
    const cfg = db.emailConfig[mb];
    if (!cfg) continue;
    try { await fetchMailbox(mb); console.log(`[poll] ${mb}: ${db.emails[mb].length} mesaje`); }
    catch (e) { console.log(`[poll] ${mb} eșuat: ${e.message}`); }
  }
}
setInterval(pollAll, 5 * 60 * 1000);

app.listen(PORT, () => console.log(`Gradina Ielelor HQ server → http://localhost:${PORT}`));
