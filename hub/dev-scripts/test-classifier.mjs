import fs from 'fs';
const src = fs.readFileSync('D:/gradina ielelor hub/server/index.js', 'utf8');
const start = src.indexOf('const TRUSTED_DOMAINS');
const end = src.indexOf('// etichetează lista');
const code = src.slice(start, end) + '\nreturn classifyEmail;';
const classifyEmail = new Function(code)();
const db = JSON.parse(fs.readFileSync('D:/gradina ielelor hub/server/data/db.json', 'utf8'));
for (const mb of ['office', 'rezervari']) {
  console.log('='.repeat(15), mb);
  let n = { spam: 0, suspicios: 0, notificare: 0, ok: 0 };
  for (const e of db.emails[mb]) {
    const c = classifyEmail(e);
    n[c || 'ok']++;
    if (c === 'spam' || c === 'suspicios') console.log(`  [${c}] ${(e.from||'').padEnd(42)} | ${(e.subject||'').slice(0,65)}`);
  }
  console.log('Rezumat:', JSON.stringify(n));
}
