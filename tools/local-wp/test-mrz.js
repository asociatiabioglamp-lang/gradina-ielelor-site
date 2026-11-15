// Test (fără browser, fără WordPress) pentru citirea zonei de jos a actului: gradina-ielelor-integration/assets/js/mrz.js
// Rulează: node tools/local-wp/test-mrz.js     Iese cu cod 1 dacă o verificare pică.
// Folosește exemplele oficiale din standardul ICAO 9303 (persoana de probă „Anna Maria Eriksson”, țara de probă „UTO”) și acte inventate, cu CNP calculat aici.
const path = require('path');
const M = require(path.join(__dirname, '..', '..', 'gradina-ielelor-integration', 'assets', 'js', 'mrz.js'));
let failures = 0;
function ok(condition, text) { console.log((condition ? '[OK]     ' : '[EROARE] ') + text); if (!condition) { failures++; } }
const TODAY = new Date(Date.UTC(2026, 10, 15));

// ---- exemplele ICAO 9303 ----
const td3 = M.parse('P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\nL898902C36UTO7408122F1204159ZE184226B<<<<<10', TODAY);
ok(!td3.error && td3.fmt === 'TD3' && td3.allOk, 'pașaport (TD3) din standard: toate cele 5 cifre de control se potrivesc');
ok(td3.sur === 'ERIKSSON' && td3.given === 'ANNA MARIA' && td3.docNoClean === 'L898902C3' && td3.nat === 'UTO' && td3.sexNorm === 'F' && td3.dobISO === '1974-08-12' && td3.expISO === '2012-04-15' && td3.docType === 'Pașaport', 'pașaport (TD3): nume, prenume, număr, țară, sex, data nașterii (1974: secolul trecut) și expirării');
const td1 = M.parse('I<UTOD231458907<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<<<', TODAY);
ok(!td1.error && td1.fmt === 'TD1' && td1.allOk && td1.sur === 'ERIKSSON' && td1.given === 'ANNA MARIA' && td1.docNoClean === 'D23145890' && td1.docType === 'Carte de identitate', 'carte de identitate (TD1) din standard: cifrele de control se potrivesc, nume și număr citite');
const td2 = M.parse('I<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<\nD231458907UTO7408122F1204159<<<<<<<6', TODAY);
ok(!td2.error && td2.fmt === 'TD2' && td2.allOk && td2.sur === 'ERIKSSON' && td2.docNoClean === 'D23145890', 'act cu 2 rânduri de 36 (TD2) din standard: cifrele de control se potrivesc');

// ---- greșeli de citire ----
const badDob = M.parse('P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\nL898902C36UTO7408123F1204159ZE184226B<<<<<10', TODAY);
ok(!badDob.allOk && badDob.checks[1].ok === false && badDob.checks[0].ok === true, 'o cifră greșită în data nașterii: cifra de control a datei nu se potrivește, cea a numărului da (și cea totală pică)');
const badDoc = M.parse('I<UTOD231458908<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<<<', TODAY);
ok(!badDoc.allOk && badDoc.checks[0].ok === false, 'o cifră greșită în numărul actului: se vede');
for (const [text, what] of [['', 'text gol'], ['ceva scris de mână', 'text oarecare'], ['I<UTOD231458907<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<<6', 'două rânduri de 30'], ['I<UTOD231458907<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<<<', 'un rând scurt'], ['I<UTOD231458907<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<ș<', 'semn în afara alfabetului']]) {
  const r = M.parse(text, TODAY);
  ok(typeof r.error === 'string' && r.error.length > 10 && !r.fmt, `intrare nepotrivită (${what}): mesaj, fără date`);
}
const lower = M.parse('p<utoeriksson<<anna<maria<<<<<<<<<<<<<<<<<<<\nl898902c36uto7408122f1204159ze184226b<<<<<10', TODAY);
ok(!lower.error && lower.allOk, 'literele mici (cum le scriu unele cititoare) se tratează ca litere mari');
const guillemet = M.parse('P«UTOERIKSSON««ANNA«MARIA«««««««««««««««««««\nL898902C36UTO7408122F1204159ZE184226B«««««10', TODAY);
ok(!guillemet.error && guillemet.allOk, 'semnele „«” citite în loc de „<” se corectează');
const one = M.parse('I<UTOD231458907<<<<<<<<<<<<<<<7408122F1204159UTO<<<<<<<<<<<6ERIKSSON<<ANNA<MARIA<<<<<<<<<<', TODAY);
ok(!one.error && one.fmt === 'TD1' && one.allOk, 'tot textul pe un singur rând (90 de caractere) se împarte în 3 rânduri');
const crlf = M.parse('P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\r\nL898902C36UTO7408122F1204159ZE184226B<<<<<10\r\n', TODAY);
ok(!crlf.error && crlf.allOk, 'sfârșit de rând Windows și rând gol la sfârșit: se citește');

// ---- acte românești inventate, cu CNP calculat aici ----
function cnpWith(s, ymd6, rest5) { // cifra de control cu ponderile 279146358279
  const base = String(s) + ymd6 + rest5; const w = '279146358279'; let sum = 0;
  for (let i = 0; i < 12; i++) sum += +base[i] * +w[i];
  let c = sum % 11; if (c === 10) c = 1; return base + c;
}
function build(o) { // TD1 cu CNP în datele opționale (câmpul 1 și 2)
  const pad = (s, n) => (s + '<'.repeat(n)).slice(0, n);
  const chk = (s) => M.check(s);
  const l1 = pad('IDROU', 5) + pad(o.doc, 9) + chk(pad(o.doc, 9)) + pad(o.opt1 || '', 15);
  const l2a = o.dob + chk(o.dob) + o.sex + o.exp + chk(o.exp) + pad('ROU', 3) + pad(o.opt2 || '', 11);
  const comp = chk(l1.slice(5, 30) + l2a.slice(0, 7) + l2a.slice(8, 15) + l2a.slice(18, 29));
  const names = pad(o.sur + '<<' + o.given.replace(/ /g, '<'), 30);
  return [l1, l2a + comp, names].join('\n');
}
const cnpF = cnpWith(2, '900314', '08001');
ok(M.cnpOk(cnpF) && cnpF.length === 13, 'CNP de probă calculat (' + cnpF + ') trece de validare');
const ro = M.parse(build({ doc: 'XY123456', dob: '900314', sex: 'F', exp: '300101', sur: 'POPESCU', given: 'MARIA ELENA', opt1: cnpF.slice(0, 13) }), TODAY);
ok(!ro.error && ro.allOk && ro.cnp === cnpF && ro.dobISO === '1990-03-14' && ro.country === 'România' && ro.expISO === '2030-01-01', 'carte de identitate românească de probă: CNP găsit în datele opționale, data nașterii 1990, expirare 2030, țara „România”');
const roSplit = M.parse(build({ doc: 'XY123456', dob: '900314', sex: 'F', exp: '300101', sur: 'POPESCU', given: 'MARIA', opt1: cnpF.slice(0, 7) + '<<<<<<<<', opt2: cnpF.slice(7) }), TODAY);
ok(!roSplit.error && roSplit.cnp === cnpF, 'CNP împărțit între cele două câmpuri opționale (cu „<” între ele): se găsește, pentru că trece de toate verificările (cifra de control, data nașterii, sexul, secolul)');
const roNoise = M.parse(build({ doc: 'XY123456', dob: '900314', sex: 'F', exp: '300101', sur: 'POPESCU', given: 'MARIA', opt1: '1234567890123' }), TODAY);
ok(roNoise.cnp === '', '13 cifre oarecare în câmpul opțional (fără legătură cu actul): nu se iau drept CNP');
const roWrongDob = M.parse(build({ doc: 'XY123456', dob: '900315', sex: 'F', exp: '300101', sur: 'POPESCU', given: 'MARIA', opt1: cnpF }), TODAY);
ok(roWrongDob.cnp === '', 'CNP care nu începe cu data nașterii din act: ignorat');
const roWrongSex = M.parse(build({ doc: 'XY123456', dob: '900314', sex: 'M', exp: '300101', sur: 'POPESCU', given: 'MARIA', opt1: cnpF }), TODAY);
ok(roWrongSex.cnp === '', 'CNP cu sexul din alt fel decât în act (2 = femeie, act: M): ignorat');
const roBadCheck = M.parse(build({ doc: 'XY123456', dob: '900314', sex: 'F', exp: '300101', sur: 'POPESCU', given: 'MARIA', opt1: cnpF.slice(0, 12) + String((+cnpF[12] + 1) % 10) }), TODAY);
ok(roBadCheck.cnp === '', 'CNP cu cifra de control greșită: ignorat');
const born2005 = M.parse(build({ doc: 'XY123456', dob: '050612', sex: 'M', exp: '300101', sur: 'IONESCU', given: 'ION', opt1: cnpWith(5, '050612', '12345') }), TODAY);
ok(born2005.dobISO === '2005-06-12' && born2005.cnp.startsWith('5050612'), 'născut în 2005: secolul 2000, CNP care începe cu 5');
const bornFuture = M.parse(build({ doc: 'XY123456', dob: '270101', sex: 'M', exp: '300101', sur: 'IONESCU', given: 'ION' }), TODAY);
ok(bornFuture.dobISO === '1927-01-01', 'an din două cifre peste anul de azi (27 > 26): secolul trecut (1927)');

// ---- corecturi din verificarea independentă ----
const old25 = M.parse(build({ doc: 'XY123456', dob: '250101', sex: 'F', exp: '300101', sur: 'VECHE', given: 'ANA' }), TODAY);
ok(old25.dobISO === '1925-01-01', 'an din două cifre care ar da o persoană sub 18 ani (25 → 2025 = 1 an): fișa e pentru adulți, deci 1925');
const adult08 = M.parse(build({ doc: 'XY123456', dob: '080101', sex: 'F', exp: '300101', sur: 'TANARA', given: 'ANA' }), TODAY);
ok(adult08.dobISO === '2008-01-01' || adult08.dobISO === '1908-01-01', 'an 08 la 17 ani și 10 luni (față de 15.11.2026): decis după regula adulților (1908), omul vede data și o corectează');
const adult05 = M.parse(build({ doc: 'XY123456', dob: '081115', sex: 'F', exp: '300101', sur: 'MAJORA', given: 'ANA' }), TODAY);
ok(adult05.dobISO === '2008-11-15', 'născută la 15.11.2008, azi 15.11.2026 → exact 18 ani: 2008');
const leap = M.parse(build({ doc: 'XY123456', dob: '000229', sex: 'M', exp: '300101', sur: 'BISECT', given: 'ION' }), TODAY);
ok(leap.dobISO === '2000-02-29', '29 februarie 2000 (an bisect): data se citește');
const deu = M.parse('P<D<<MUELLER<<HANS<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\nC01X00T478D<<8505201M3206019<<<<<<<<<<<<<<<0', TODAY);
ok(M.countryName('D<<') === 'Germania' && M.countryName('ROU') === 'România', 'țară cu „<” de umplutură (D<<): „Germania”, fără „<”');
const longDoc = M.parse('I<UTOD' + '<'.repeat(24) + '\n7408122F1204159UTO<<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<<<', TODAY);
ok(!longDoc.error && longDoc.checks[0].ok === true && longDoc.checks[0].note === true, 'număr de act cu „<” în loc de cifră de control (act cu serie lungă): nu se declară alterat, doar se cere verificarea la vedere');
ok(!M.cnpOk('0900314080015') && !M.cnpMatches('0900314080015', '1990-03-14', 'F'), 'CNP care începe cu 0 nu e valabil');

// ---- CNP scris de mână ----
ok(M.cnpMatches(cnpF, '1990-03-14', 'F') && !M.cnpMatches(cnpF, '1990-03-14', 'M') && !M.cnpMatches(cnpF, '1991-03-14', 'F'), 'CNP: se potrivește cu data și sexul; altfel nu');
ok(!M.cnpMatches(cnpWith(1, '900314', '08001'), '1890-03-14', 'M') && M.cnpMatches(cnpWith(3, '900314', '08001'), '1890-03-14', 'M'), 'CNP: secolul se verifică (1 = 1900–1999, 3 = 1800–1899)');
ok(M.cnpMatches(cnpWith(7, '900314', '08001'), '1990-03-14', 'M') && M.cnpMatches(cnpWith(9, '900314', '08001'), '1990-03-14', 'F'), 'CNP de rezident străin (7) sau de străin (9): fără regula secolului');
ok(!M.cnpOk('') && !M.cnpOk('123') && !M.cnpOk('abcdefghijklm') && !M.cnpOk('2900314080018'.slice(0, 12) + 'x'), 'CNP: text gol, prea scurt sau cu litere nu trece');
ok(M.countryName('ROU') === 'România' && M.countryName('XYZ') === 'XYZ' && M.countryName('D') === 'Germania', 'țări: nume în română pentru cele cunoscute, codul pentru restul');

console.log(failures ? `\n${failures} verificări au picat.` : '\nToate verificările au trecut.');
process.exit(failures ? 1 : 0);
