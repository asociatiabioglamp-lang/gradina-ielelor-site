/*
 * Citirea zonei de jos a actului de identitate (MRZ, „rândurile cu <<<”): cărți de identitate (TD1, 3 rânduri × 30), cărți și vize mici (TD2, 2 × 36),
 * pașapoarte (TD3, 2 × 44). Verifică cifrele de control (ponderi 7-3-1, standardul ICAO 9303), data nașterii și expirării, și caută CNP-ul în câmpul
 * opțional, numai dacă are cifra de control corectă și începe cu data nașterii din act. Nu trimite nimic nicăieri: lucrează în browser.
 *
 * Folosire în pagină:  var r = GIMrz.parse(text);   r.error (text) sau r.fields + r.checks.   Același fișier rulează în Node (teste).
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) { module.exports = factory(); } else { root.GIMrz = factory(); }
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';
  var W = [7, 3, 1];

  function cv(ch) {
    if (ch === '<') return 0;
    if (ch >= '0' && ch <= '9') return ch.charCodeAt(0) - 48;
    if (ch >= 'A' && ch <= 'Z') return ch.charCodeAt(0) - 55;
    return NaN;
  }
  function check(str) {
    var s = 0, v;
    for (var i = 0; i < str.length; i++) {
      v = cv(str.charAt(i));
      if (isNaN(v)) return NaN;
      s += v * W[i % 3];
    }
    return s % 10;
  }
  function ymd(s) { return /^\d{6}$/.test(s) ? { yy: +s.slice(0, 2), mm: +s.slice(2, 4), dd: +s.slice(4, 6) } : null; }
  function isoDate(y, m, d) {
    var dt = new Date(Date.UTC(y, m - 1, d));
    if (dt.getUTCFullYear() !== y || dt.getUTCMonth() !== m - 1 || dt.getUTCDate() !== d) return '';
    return dt.toISOString().slice(0, 10);
  }
  // Anul are două cifre. Fișa e numai pentru adulți: dacă anul 20xx ar da o persoană sub 18 ani (sau din viitor), data nașterii e din secolul trecut.
  // La expirare, mereu 20xx.
  function dobISO(s, today) {
    var p = ymd(s); if (!p) return '';
    var t = today || new Date();
    var modern = isoDate(2000 + p.yy, p.mm, p.dd);
    var old = isoDate(1900 + p.yy, p.mm, p.dd);
    if (!modern) return old;
    var age = t.getUTCFullYear() - (2000 + p.yy) - ((t.getUTCMonth() + 1 < p.mm || (t.getUTCMonth() + 1 === p.mm && t.getUTCDate() < p.dd)) ? 1 : 0);
    return age < 18 && old ? old : modern;
  }
  function expISO(s) { var p = ymd(s); return p ? isoDate(2000 + p.yy, p.mm, p.dd) : ''; }
  function clean(s) { return s.replace(/</g, ' ').replace(/\s+/g, ' ').trim(); }
  function nameParts(field) {
    var i = field.indexOf('<<');
    var sur = i < 0 ? field : field.slice(0, i);
    var giv = i < 0 ? '' : field.slice(i + 2);
    return { sur: clean(sur), given: clean(giv) };
  }

  /* Cetățenia: codul din act (3 litere, ICAO), cu numele în română pentru cele mai întâlnite; restul rămân cu codul. */
  var COUNTRIES = {
    ROU: 'România', ROM: 'România', D: 'Germania', DEU: 'Germania', HUN: 'Ungaria', GBR: 'Regatul Unit', FRA: 'Franța', ITA: 'Italia', NLD: 'Țările de Jos',
    POL: 'Polonia', CZE: 'Cehia', SVK: 'Slovacia', BGR: 'Bulgaria', MDA: 'Republica Moldova', UKR: 'Ucraina', USA: 'Statele Unite', AUT: 'Austria', ESP: 'Spania',
    PRT: 'Portugalia', BEL: 'Belgia', CHE: 'Elveția', SWE: 'Suedia', DNK: 'Danemarca', NOR: 'Norvegia', FIN: 'Finlanda', IRL: 'Irlanda', GRC: 'Grecia',
    HRV: 'Croația', SVN: 'Slovenia', SRB: 'Serbia', TUR: 'Turcia', LTU: 'Lituania', LVA: 'Letonia', EST: 'Estonia', ISR: 'Israel', CAN: 'Canada'
  };
  function countryName(code) { code = String(code).replace(/</g, ''); return COUNTRIES[code] || code; } // „D<<” (Germania) are „<” de umplutură

  function parse(raw, today) {
    var text = String(raw == null ? '' : raw).toUpperCase().replace(/[«‹]/g, '<');
    var lines = text.split(/\r?\n/).map(function (l) { return l.replace(/\s+/g, ''); }).filter(Boolean);
    if (lines.length === 1) { // cititoarele de documente scriu uneori tot textul pe un singur rând
      var n = lines[0].length, k = n === 90 ? 3 : n === 72 || n === 88 ? 2 : 0;
      if (k) { var sz = n / k; var one = lines[0]; lines = []; for (var j = 0; j < k; j++) lines.push(one.slice(j * sz, (j + 1) * sz)); }
    }
    var len = lines.length ? lines[0].length : 0;
    var fmt = lines.length === 3 && len === 30 ? 'TD1' : lines.length === 2 && len === 36 ? 'TD2' : lines.length === 2 && len === 44 ? 'TD3' : '';
    if (!fmt || lines.some(function (l) { return l.length !== len; }) || lines.some(function (l) { return /[^A-Z0-9<]/.test(l); })) {
      return { error: 'Nu recunosc rândurile. Trebuie 3 rânduri de 30 de caractere, 2 de 36 sau 2 de 44, numai litere mari, cifre și „<”. Verifică și citește din nou.' };
    }
    var r = { fmt: fmt, checks: [] }, l1 = lines[0], l2 = lines[1], names, comp, opt;
    r.type = l1.slice(0, 2); r.issuer = l1.slice(2, 5);
    if (fmt === 'TD1') {
      r.docNo = l1.slice(5, 14); r.cdDoc = l1.charAt(14); opt = l1.slice(15, 30);
      r.dob = l2.slice(0, 6); r.cdDob = l2.charAt(6); r.sex = l2.charAt(7); r.exp = l2.slice(8, 14); r.cdExp = l2.charAt(14); r.nat = l2.slice(15, 18);
      comp = [l1.slice(5, 30) + l2.slice(0, 7) + l2.slice(8, 15) + l2.slice(18, 29), l2.charAt(29)];
      names = lines[2]; r.optRaw = opt + l2.slice(18, 29);
    } else if (fmt === 'TD2') {
      names = l1.slice(5); r.docNo = l2.slice(0, 9); r.cdDoc = l2.charAt(9); r.nat = l2.slice(10, 13); r.dob = l2.slice(13, 19); r.cdDob = l2.charAt(19); r.sex = l2.charAt(20);
      r.exp = l2.slice(21, 27); r.cdExp = l2.charAt(27); r.optRaw = l2.slice(28, 35);
      comp = [l2.slice(0, 10) + l2.slice(13, 20) + l2.slice(21, 28) + l2.slice(28, 35), l2.charAt(35)];
    } else {
      names = l1.slice(5); r.docNo = l2.slice(0, 9); r.cdDoc = l2.charAt(9); r.nat = l2.slice(10, 13); r.dob = l2.slice(13, 19); r.cdDob = l2.charAt(19); r.sex = l2.charAt(20);
      r.exp = l2.slice(21, 27); r.cdExp = l2.charAt(27); opt = l2.slice(28, 42); r.optRaw = opt;
      comp = [l2.slice(0, 10) + l2.slice(13, 20) + l2.slice(21, 43), l2.charAt(43)];
      var cdOpt = l2.charAt(42);
      var okOpt = /^<*$/.test(opt) ? (cdOpt === '<' || cdOpt === '0') : String(check(opt)) === cdOpt;
      r.checks.push({ ok: okOpt, text: 'Cifra de control a datelor opționale' });
    }
    var np = nameParts(names);
    r.sur = np.sur; r.given = np.given;
    r.docNoClean = r.docNo.replace(/</g, '');
    // Un număr de act mai lung de 9 caractere are „<” în loc de cifra de control (restul stă în câmpul opțional): nu se poate verifica, nu se declară alterat.
    var longDoc = r.cdDoc === '<';
    r.checks.unshift(
      longDoc ? { ok: true, note: true, text: 'Seria actului are mai mult de 9 caractere: cifra ei de control nu se verifică. Verifică seria cu ochiul liber.' }
              : { ok: String(check(r.docNo)) === r.cdDoc, text: 'Cifra de control a seriei și numărului actului' },
      { ok: String(check(r.dob)) === r.cdDob, text: 'Cifra de control a datei nașterii' },
      { ok: String(check(r.exp)) === r.cdExp, text: 'Cifra de control a datei expirării' },
      { ok: String(check(comp[0])) === comp[1], text: 'Cifra de control totală' }
    );
    r.dobISO = dobISO(r.dob, today);
    r.expISO = expISO(r.exp);
    r.sexNorm = r.sex === 'F' || r.sex === 'M' ? r.sex : '';
    r.allOk = r.checks.every(function (c) { return c.ok; });
    r.cnp = findCnp(r);
    r.docType = r.type.charAt(0) === 'P' ? 'Pașaport' : r.type.charAt(0) === 'I' ? 'Carte de identitate' : r.type.charAt(0) === 'A' || r.type.charAt(0) === 'C' ? 'Act de identitate' : 'Act de identitate';
    r.country = countryName(r.nat);
    return r;
  }

  /* ---------- CNP (numai români): 13 cifre, cifra de control cu ponderile 279146358279, începe cu data nașterii din act ---------- */
  function cnpOk(c) {
    if (!/^[1-9]\d{12}$/.test(c)) return false; // prima cifră (sexul și secolul) e 1–9
    var w = '279146358279', s = 0;
    for (var i = 0; i < 12; i++) s += +c.charAt(i) * +w.charAt(i);
    var r = s % 11;
    if (r === 10) r = 1;
    return r === +c.charAt(12);
  }
  function yymmdd(iso) { return iso.slice(2, 4) + iso.slice(5, 7) + iso.slice(8, 10); }
  // Potrivirea CNP cu datele din act: cifra de control, data nașterii (aammzz), sexul și secolul (S: 1/2 = 1900–1999, 3/4 = 1800–1899, 5/6 = 2000–2099;
  // 7/8 = rezidenți străini, 9 = străini: fără regulă pentru secol; impar = bărbat, par = femeie).
  function cnpMatches(cnp, dobIso, sex) {
    if (!cnpOk(cnp)) return false;
    var s = +cnp.charAt(0);
    if (dobIso && cnp.slice(1, 7) !== yymmdd(dobIso)) return false;
    if (sex && s >= 1 && s <= 8 && (s % 2 === 1) !== (sex === 'M')) return false;
    if (dobIso && s >= 1 && s <= 6) {
      var y = +dobIso.slice(0, 4), century = s <= 2 ? 1900 : s <= 4 ? 1800 : 2000;
      if (y < century || y > century + 99) return false;
    }
    return true;
  }
  // CNP-ul din datele opționale ale actului, numai dacă se potrivește cu restul actului.
  function findCnp(r) {
    var found = (String(r.optRaw || '').replace(/</g, '').match(/\d{13}/g) || []).filter(function (c) { return cnpMatches(c, r.dobISO, r.sexNorm); });
    return found.length ? found[0] : '';
  }

  return { parse: parse, check: check, cnpOk: cnpOk, cnpMatches: cnpMatches, findCnp: findCnp, countryName: countryName };
}));
