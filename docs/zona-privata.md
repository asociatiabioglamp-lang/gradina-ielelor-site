# Zona privată „Grădina” (pluginul 1.4.0)

Aici faci lucrurile care nu se văd pe site: pagini pentru copaci și locuri, coduri QR de printat, vouchere (reduceri).
Intri în administrarea site-ului cu **utilizatorul și parola** contului tău. În meniul din stânga apare **„Grădina”**.

Cine ce vede (conturile WordPress existente):
- **Administrator** (tu): totul.
- **Manager de magazin**: copaci, coduri QR **și** vouchere (rolul WooCommerce de gestionare a magazinului).
- **Editor**: „Copaci și locuri” și „Coduri QR de printat”. **Nu** vede „Vouchere”.
- **Autor, colaborator, abonat, client**: nimic din zona Grădina. (Dacă ai conturi de acest fel, rămân fără acces.)

**Generează și printează codurile numai de pe site-ul real, gradina-ielelor.ro.** Pe o copie de test, codurile ar duce la adresa de test, care poate dispărea (pluginul te avertizează pe ecran când nu ești pe site-ul real).

## 1. Un copac (sau un loc, sau o plantă) cu cod QR

Pași, o dată pentru fiecare copac:
1. **Grădina → Copaci și locuri → Adaugă un loc.**
2. **Titlul**: numele locului, de ex. „Stejarul bătrân”.
3. **Textul mare** (editorul): ce vrei să afle vizitatorul. Povestea locului, ce îl face special, ce poate observa. Acesta e textul care explică codul QR.
4. **Rezumat** (mai jos): o frază care apare sub titlu. **Poza locului**: în dreapta, „Alege poza locului”.
5. **Despre acest loc**: alegi „Ce este?” (Copac, Plantă, Loc din Grădină, Altceva) și, dacă vrei, numele științific.
6. Apeși **Publică**. În dreapta apare **codul QR**.
7. **Printează acest cod** (în dreapta) sau, pentru mai multe deodată: **Grădina → Coduri QR de printat** → bifezi locurile → alegi mărimea → „Printează codurile alese”. Se deschide o pagină curată; apeși „Printează”, tai pe linia punctată.
8. Pui codul în teren, pe copac. Un cod de **8 cm** se citește bine de la circa un metru; pentru citire de la mai departe, printează-l de **10 cm**.

Ce vede vizitatorul: scanează codul cu telefonul și ajunge pe o pagină a site-ului, în designul Grădinii: titlul, poza, textul tău, apoi „Mai multe de văzut în Grădină” (celelalte locuri) și un buton de rezervare.

Bine de știut:
- **Textul îl poți schimba oricând**, fără să schimbi codul. Codul printat rămâne bun.
- **Nu schimba adresa paginii** după ce ai printat codul (adresa e sub titlu, „Permalink”). Dacă o schimbi totuși, WordPress duce singur adresa veche la cea nouă, dar verifică.
- **Nu opri pluginul și nu șterge un loc** al cărui cod e în teren: codul ar duce la „pagina nu există” (și la oprirea pluginului, „Revino” din ghidul de lansare, toate codurile din teren duc la „nu există”). Dacă nu mai vrei un loc, lasă-l publicat cu un text scurt.
- Codul se desenează chiar în browserul tău; nu se trimite nimic către alte site-uri. Adresa din cod are la sfârșit parametri (`utm_source=qr&utm_medium=print`): în Google Analytics apare sursa „qr / print”, ca să vezi câți vizitatori vin din teren.
- Poți **descărca codul ca imagine** (butonul „Descarcă (SVG)”), de exemplu pentru un grafician sau pentru o plăcuță.
- Textul se traduce în alte limbi la fel ca restul site-ului (steagul din colț).

## 2. Vouchere (reduceri valabile 18 luni)

**Grădina → Vouchere.** Completezi:
- **Tipul reducerii**: sumă fixă (lei) sau procent. (Cupoanele trebuie să fie pornite în WooCommerce → Setări → General; ecranul te avertizează dacă nu sunt.)
- **Valoarea**, **câte coduri** (1–100, toate cu aceeași valoare), **începutul codului** (de ex. GRADINA) și, dacă vrei, **pentru ce / pentru cine** (se vede numai în administrare; **nu scrie nume de persoane**).
- „Generează codurile”. Rezultă coduri ca `GRADINA-K7M2-Q9XP`, fiecare **valabil 18 luni de azi** și **o singură folosire**.

Lista de mai jos arată fiecare cod cu valoarea, câte ori a fost folosit, data de expirare și un **cod QR**. Le poți bifa și printa (cu codul QR). Voucherele sunt și cupoane WooCommerce: le vezi și le oprești și din **WooCommerce → Marketing → Cupoane**.

Cum se folosește un voucher: clientul îl scrie la plată („Ai un cod de reducere?”) **sau** scanează codul QR: ajunge pe site, codul se reține 30 de zile și se aplică singur în coș, când are o rezervare adăugată.

**De verificat o dată pe copia de test:** reducerea se aplică la o rezervare reală (cu formularul de rezervări, nu doar la un produs obișnuit). Pe un produs de probă merge; la rezervările BRW depinde de cum calculează modulul prețul. Dacă nu se aplică, spune-i lui Claude: se rezolvă în plugin.

## 3. Ce nu face încă (vine după)

- **Cod QR la fiecare rezervare finalizată** (pe pagina de mulțumire și în emailul de confirmare).
- **Aplicația de recepție pe telefon**: scanezi codul rezervării, fotografiezi actul de identitate, se generează fișa de cazare. Aici contează GDPR: ce păstrăm, cât timp, unde. De stabilit cu juristul înainte (docs/decizii.md).

## 4. Siguranță

- Accesul e numai cu utilizator și parolă. Recomandare: **autentificare în doi pași** pentru conturile care au acces (docs/securitate.md).
- Nu există nicio zonă nouă cu parole proprii: se folosesc conturile WordPress, deci aceleași protecții ca pentru administrarea site-ului.
- Nicio dată personală nu intră în coduri sau în vouchere.
- Pagina de printat se deschide numai cu cont autorizat și cu un cod de siguranță în adresă; nu e văzută de Google.

## 5. Dacă ceva nu merge

- Un cod QR nu se citește: apropie telefonul, verifică să nu fie ud sau murdar, printează-l mai mare (8 sau 10 cm). Codurile au corecție de erori de 25%: rezistă la mici murdării.
- Pagina unui loc dă „nu a fost găsită” imediat după instalarea pluginului: deschide **Setări → Permalinkuri** și apasă „Salvează modificările” (o dată). Pluginul face asta singur la prima rulare, dar poate dura un moment.
- Spune-i lui Claude ce vezi; poți trimite o captură.
