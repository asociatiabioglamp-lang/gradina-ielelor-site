# Lansarea site-ului nou, pas cu pas

Data: 31.10.2026. Versiunea care se lansează: **pluginul Grădina Ielelor 1.2.0** (sau 1.2.x, dacă apar corecturi; Claude îți spune exact numărul).

## 0. Pe scurt

**Ce înseamnă lansarea.** Nu mutăm site-ul și nu schimbăm WordPress-ul. Instalăm pluginul nostru pe site-ul existent și le spunem paginilor să folosească designul nou (le alegem „șablonul”). Rezervările, plățile, comenzile și produsele rămân exact cum sunt. Mutarea pe un WordPress curat și pe 5starDesk vine **după** lansare.

**Trei reguli:**
1. **Întâi staging, apoi live.** Nimic nu ajunge pe site-ul live dacă nu a mers pe staging.
2. **Backup înainte de orice schimbare.** Fără backup descărcat pe calculatorul tău, nu începi.
3. **Revenirea durează sub 5 minute** și o poți face singur (secțiunea 8). Dacă ceva arată greșit, revii întâi și întrebi apoi.

**Cine face ce.** Pe fiecare pas scrie cine îl face:
- **[TU]**: tu, proprietarul. Doar tu intri în cPanel, în WordPress (admin), în NETOPIA, în Google și în GitHub cu contul tău.
- **[CLAUDE]**: Claude. Scrie și testează codul, construiește arhiva, verifică paginile publice ale site-ului, scrie documentele și îți spune exact ce să apeși. Claude **nu** are acces la cPanel sau la WordPress-ul tău și **nu îți cere niciodată parole sau chei**.
- **[TU + CLAUDE]**: faci tu clicurile, Claude verifică rezultatul.

**Cuvinte folosite:** *staging* = copia de test a site-ului; *live* = site-ul public gradina-ielelor.ro; *arhivă* = fișierul `.zip` al pluginului; *șablon* = designul ales pentru o pagină. Restul, în glosarul din `docs/independenta.md`.

---

## 1. Calendarul D-7 → D0

Lansarea (D0) este **sâmbătă 7.11.2026, ora 10:00**. Lansarea nu atinge plățile, iar revenirea o faci singur în câteva minute, așa că o zi de weekend din extrasezon e în regulă. Dacă preferi o zi lucrătoare și D-2 a ieșit fără probleme, lansarea se poate face și vineri 6.11 dimineața.

| Ziua | Data | Ce se întâmplă | Timpul tău |
|---|---|---|---|
| D-7 | sâm 31.10 | Pregătire: accese, chei NETOPIA, backup | ~1 oră |
| D-6 | dum 1.11 | Textele paginilor noi; automatizarea în GitHub | ~45 min |
| D-5 | lun 2.11 | Instalare pe staging (Procedura A) | ~1 oră |
| D-4 | mar 3.11 | Plata de test NETOPIA pe staging (Procedura B) | ~45 min |
| D-3 | mie 4.11 | Verificarea ta completă, pe telefon și calculator | ~45 min |
| D-2 | joi 5.11 | Versiunea finală pe staging, repetiția revenirii, aprobarea | ~45 min |
| D-1 | vin 6.11 | Pregătirea live-ului: ciorne, verificări, decizia „Da / Nu” | ~45 min |
| D0 | sâm 7.11 | Lansarea pe live (Procedura C) și Search Console | ~1,5 ore |
| D+1 … D+7 | 8–14.11 | Urmărire zilnică (secțiunea 9) | 10 min/zi |

### D-7 — sâmbătă 31.10: pregătire
- **[CLAUDE]** Arhiva automată (`tools/build-zip.sh`), verificările din GitHub (`.github/workflows/build.yml`), documentele `docs/lansare.md` și `docs/independenta.md`. Arhiva de test 1.2.0, cu amprenta ei SHA-256.
- **[TU]** Citește secțiunile 0–3 din acest document (15 min).
- **[TU]** Verifică dacă intri, cu contul tău, în: zona de client Romarg, cPanel, WordPress admin pe staging și pe live, NETOPIA, GitHub (tabelul din `docs/independenta.md`, coloana „Cum verifici”) (20 min).
- **[TU]** NETOPIA: descarcă **ambele** fișiere de test (`.cer` și `.key`) de la **același punct de vânzare** (Procedura B, pasul B1). Le păstrezi pe calculator; nu le trimiți nimănui, nici lui Claude (10 min).
- **[TU]** Backup la staging și la live (secțiunea 2) (20 min).
- **[TU]** Search Console → **Performanță** → **Export**: ultimele 3 și ultimele 12 luni, **Pagini** și **Interogări**. Cu ele comparăm după lansare (`docs/seo-migrare.md`, secțiunea 5) (10 min).

### D-6 — duminică 1.11: textele și GitHub
- **[CLAUDE]** Termină paginile Despre noi, Regulament & politici și Contact (șablonul „Pagină simplă”), redirecționările de la adresele vechi (`docs/seo-migrare.md`) și textele lor (`docs/pagini-continut.md`). Testează totul într-un WordPress local și îți trimite capturi de pe telefon și de pe calculator.
- **[TU]** Citești textele din `docs/pagini-continut.md` și confirmi sau corectezi (30 min).
- **[TU]** Aprobi în GitHub cererea (pull request) cu automatizarea: butonul **Merge pull request** (5 min). De acum, fiecare schimbare produce singură o arhivă verificată.

### D-5 — luni 2.11: instalare pe staging
- **[CLAUDE]** Îți dă linkul exact spre arhivă (secțiunea 3), cu versiunea și amprenta ei.
- **[TU]** Procedura A pe staging (secțiunea 4) (1 oră). Îi trimiți lui Claude **doar adresa** staging-ului (fără parolă).
- **[CLAUDE]** Verifică paginile staging-ului din exterior (dacă adresa e accesibilă): pagini, poze, linkuri, butoane „Rezervă”, versiunea pluginului. Îți trimite lista de probleme, dacă există.

### D-4 — marți 3.11: plata de test
- **[TU]** Procedura B (secțiunea 5) (45 min). Dacă cheile tot nu se potrivesc, suni la NETOPIA (zi lucrătoare).
- **[CLAUDE]** Analizează rezultatul. Dacă e o problemă de cod, o repară, face o arhivă nouă, iar tu o reinstalezi pe staging.

### D-3 — miercuri 4.11: verificarea ta
- **[TU]** Treci prin lista de verificare (secțiunea 6) pe telefon și pe calculator. Scrii lui Claude tot ce vrei schimbat (45 min).
- **[CLAUDE]** Face corecturile, testează și construiește versiunea finală (1.2.x).

### D-2 — joi 5.11: versiunea finală, repetiția și aprobarea
- **[TU]** Instalezi versiunea finală pe staging (Procedura A, pașii A1–A2 și A8–A9).
- **[TU]** **Repetiția revenirii pe staging**: faci revenirea R1 (secțiunea 8) cu cronometrul pornit, verifici că a revenit designul vechi, apoi reactivezi pluginul. Ținta: sub 5 minute (15 min).
- **[TU]** Scrii lui Claude: „Aprob lansarea versiunii 1.2.x” și apeși **Merge pull request** în GitHub.
- **[CLAUDE]** Publică versiunea finală în GitHub (Release `v1.2.x`, cu arhiva și amprenta), notează aprobarea în `docs/decizii.md`.

### D-1 — vineri 6.11: pregătirea live-ului și decizia „Da / Nu”
- **[TU]** În WordPress-ul **live**, creezi paginile noi **ca ciorne** (invizibile pentru vizitatori), cu textele lor: Procedura C, pasul C3 (30 min).
- **[TU]** Verificări pe live (10 min):
  - **Module (Plugins):** WooCommerce, OVABRW și NETOPIA sunt active.
  - **WooCommerce → Comenzi (Orders):** ultima comandă plătită cu cardul a trecut singură în „În procesare” (Processing). Asta arată că plățile live funcționează azi; lansarea nu le atinge.
  - **Setări → Citire (Settings → Reading):** căsuța „Descurajează motoarele de căutare să indexeze acest site” **nu** este bifată.
- **[TU]** Din `docs/seo-migrare.md`, secțiunea 5, partea **Setări** (GTranslate, coș, cont), plus repararea „dev24” (secțiunea 2), dacă nu ai făcut-o deja (30 min).
- **[TU + CLAUDE]** **Decizia „Da / Nu”.** Lansăm în D0 numai dacă toate răspunsurile sunt „da”:
  1. Staging-ul arată și funcționează corect pe telefon și pe calculator (lista din secțiunea 6).
  2. Plata de test NETOPIA a trecut singură în „În procesare”, iar disponibilitatea a scăzut.
  3. Revenirea R1 a durat sub 5 minute la repetiție.
  4. Ai aprobat versiunea, iar arhiva finală e în GitHub (Release).
  5. Subsolul paginilor noi are linkurile legale: Termeni, Confidențialitate, Cookies, ANPC și SOL (obligatorii la vânzarea online). Claude confirmă.
  6. Ai 1,5 ore libere în D0 și încă 30 de minute după-amiază, pentru o a doua verificare.

  Dacă un răspuns e „nu”, lansarea se amână. Nu lansăm „pe jumătate”.

### D0 — sâmbătă 7.11, ora 10:00: lansarea
- **[TU]** Procedura C (secțiunea 7) (1 oră).
- **[CLAUDE]** În primele 15 minute după lansare verifică site-ul live din exterior (pagini, poze, linkuri, redirecționări, versiunea) și îți spune rezultatul.
- **[TU]** Search Console (pasul C10) (15 min).
- **[TU]** După-amiază: încă o trecere rapidă prin lista de verificare și prin comenzile zilei.

### D+1 … D+7: urmărire
Secțiunea 9: 10 minute pe zi.

---

## 2. Backup (înainte de orice schimbare)

Faci backup la site-ul pe care urmează să lucrezi: staging în D-5, live în D0 (și o dată în D-7, pentru liniște).

**[TU]** Backup complet cu Softaculous (fișiere + bază de date):
1. Intră în **cPanel**.
2. Caută **Softaculous Apps Installer** și deschide-l.
3. Sus, în dreapta, apasă pictograma **Instalări (Installations)**.
4. Găsește rândul site-ului (verifică adresa: staging sau gradina-ielelor.ro).
5. Pe acel rând, apasă pictograma **Backup** (o săgeată sau o dischetă).
6. Lasă bifate **Backup Directory** (fișierele) și **Backup Database** (baza de date). Apasă **Backup Installation**.
7. Așteaptă mesajul de reușită (câteva minute).
8. Deschide lista **Backups**, descarcă arhiva pe calculator, apoi pune o copie în Google Drive (un folder „Backup site”, cu data în nume).
9. Verifică dacă fișierul descărcat are o mărime rezonabilă (zeci sau sute de MB, nu câțiva KB).

**Bine de știut:**
- Dacă în cPanel ai și **JetBackup**, uită-te la data ultimei copii automate. Copia ta din Softaculous rămâne obligatorie.
- Backup-urile ocupă spațiu pe hosting. După ce le-ai descărcat, le poți șterge de pe server pe cele mai vechi de o lună.
- **Nu folosi niciodată „Push to Live” din Softaculous** de pe staging spre live. Ar înlocui baza de date a site-ului live cu cea de pe staging și s-ar pierde comenzile reale primite între timp.

---

## 3. De unde iei arhiva pluginului

Arhiva se construiește automat în GitHub, verificată (sintaxa PHP pe PHP 7.4 și 8.3, JavaScript, integritatea arhivei, toate pozele). Nu o mai face nimeni de mână.

**Varianta 1, pentru versiunea aprobată (Release):**
1. **[TU]** Deschide depozitul în GitHub: `asociatiabioglamp-lang/gradina-ielelor-site`.
2. În dreapta, **Releases** → versiunea anunțată de Claude (ex. `v1.2.0`).
3. La **Assets**, descarcă `gradina-ielelor-integration-1.2.0.zip`.

**Varianta 2, pentru o versiune de test (înainte de aprobare):**
1. **[TU]** În depozit, fila **Actions** → **Build** → rularea cu bifă verde de pe ramura indicată de Claude.
2. Jos, la **Artifacts**, descarcă `gradina-ielelor-integration-1.2.0.zip`.

**Atenție:**
- Arhiva se încarcă în WordPress **așa cum e**. Nu o dezarhiva. Dacă folosești Safari pe Mac, el dezarhivează singur fișierele; descarcă cu Chrome sau Firefox.
- O arhivă cu **„INCOMPLET”** în nume este doar pentru test. **Nu o instala** (îi lipsesc poze).
- **Amprenta (opțional, 1 minut):** pe Windows, în folderul Descărcări: clic dreapta → **Deschide în Terminal**, apoi scrie `Get-FileHash .\gradina-ielelor-integration-1.2.0.zip`. Primele și ultimele 6 caractere trebuie să fie aceleași cu ale amprentei SHA-256 din GitHub (Release sau rezumatul rulării). Dacă diferă, nu instala și spune-i lui Claude.

---

## 4. Procedura A — instalarea pe staging (D-5)

Timp: aproximativ o oră. Toți pașii sunt **[TU]**, în WordPress-ul de **staging**. Verifică adresa din bara browserului înainte de fiecare pas: trebuie să fie adresa staging-ului, nu gradina-ielelor.ro.

### A1. Backup
Secțiunea 2, pentru staging. Nu treci mai departe fără el.

### A2. Încarcă arhiva
1. **Module (Plugins)** → **Adaugă modul (Add New Plugin)** → sus, **Încarcă modul (Upload Plugin)**.
2. **Alege fișierul** → `gradina-ielelor-integration-1.2.0.zip` → **Instalează acum (Install Now)**.
3. Dacă pluginul există deja, WordPress îți arată versiunea veche și pe cea nouă. Apasă **Înlocuiește versiunea curentă cu cea încărcată (Replace current with uploaded)**.
4. Apasă **Activează modulul (Activate Plugin)** (dacă nu era deja activ).
5. Verifică în **Module**: „Grădina Ielelor — Site + BRW”, versiunea 1.2.0, activ.

Din acest moment, magazinul și paginile de produs primesc stilul nou. E normal. Paginile obișnuite nu se schimbă până nu le alegi șablonul.

### A3. Setările pluginului
**Setări → Grădina Ielelor**: verifică dacă „URL rezultate” arată spre pagina magazinului (nu spre blog) și dacă sunt completate slug-urile categoriilor. Dacă ai dubii, fă o captură și trimite-o lui Claude. Apasă **Salvează** numai dacă ai schimbat ceva.

### A4. Notează cum e acum (pentru revenire)
Pe site-ul live, verificat la 31.10.2026, toate paginile de mai jos folosesc **„Șablon implicit” (Default template)**, cu conținut Elementor. Pe staging (o copie a live-ului) ar trebui să fie la fel. Verifică la două pagini (Acasă și Glamping): **Pagini (Pages)** → treci cu mouse-ul peste titlu → **Editare rapidă (Quick Edit)** → câmpul **Șablon (Template)**. Dacă scrie altceva decât „Șablon implicit”, notează și spune-i lui Claude.

### A5. Paginile: ce faci la fiecare

| Adresa nouă | Pe live azi (31.10) | Ce faci | Șablonul ales |
|---|---|---|---|
| `/` (pagina principală) | există: pagina „Acasă2025” | schimbi șablonul | **Grădina Ielelor — Pagina principală (rezervare)** |
| `/glamping/` | există: pagina „Cazare Glamping” | schimbi șablonul | **Grădina Ielelor — Glamping** |
| `/camping/` | există: pagina „Camping” | schimbi șablonul | **Grădina Ielelor — Camping** |
| `/bungalow/` | nu există | pagină nouă „Bungalow” | **Grădina Ielelor — Bungalow** |
| `/rulote/` | nu există | pagină nouă „Rulote” | **Grădina Ielelor — Rulote** |
| `/evenimente/` | pagina veche e la `/gradina-ielelor-totul-despre-ea/evenimente/` | pagină **nouă** „Festivaluri & evenimente”, fără părinte. Pe cea veche **nu o atingi**: adresa ei va duce singură spre pagina nouă | **Grădina Ielelor — Festivaluri & evenimente** |
| `/despre-noi/` | nu există | pagină nouă „Despre noi” | **Grădina Ielelor — Pagină simplă** |
| `/regulament-politici/` | nu există | pagină nouă „Regulament & politici” | **Grădina Ielelor — Pagină simplă** |
| `/contact/` | există: pagina „Contact” (nu crea alta: ar primi adresa `/contact-2/`) | schimbi șablonul | **Grădina Ielelor — Pagină simplă** |
| `/festival/` | există | **nu o atingi** | — |

Dacă `docs/seo-migrare.md` sau Claude îți spun altceva pentru o pagină, urmează-i pe ei. Acel document e mai nou.

**Pagină nouă (Bungalow, Rulote, Festivaluri & evenimente, Despre noi, Regulament & politici):**
1. **Pagini → Adaugă pagină (Add New Page)**.
2. Titlul exact din tabel. Pentru Despre noi, Regulament & politici și Contact, lipește textul din `docs/pagini-continut.md` (Claude ți-l dă gata de lipit). La paginile ofertelor, conținutul vine din plugin; lași pagina goală.
3. Apasă **Salvează ciorna (Save draft)**, nu Publică. Închide editorul.
4. În lista **Pagini**: treci cu mouse-ul peste pagina nouă → **Editare rapidă (Quick Edit)**:
   - **Descriptor (Slug):** exact ca în tabel, fără bare: `bungalow`, `rulote`, `evenimente`, `despre-noi`, `regulament-politici`;
   - **Părinte (Parent):** fără părinte (pagină principală, „Main Page”);
   - **Șablon (Template):** cel din tabel;
   - **Stare (Status):** lași **Ciornă (Draft)** deocamdată.
5. **Actualizează (Update)**.
6. Dacă după salvare descriptorul apare cu „-2” (ex. `evenimente-2`), **oprește-te** și spune-i lui Claude. Înseamnă că adresa e deja ocupată.

**Pagină existentă (Acasă, Glamping, Camping, Contact):**
1. **Pagini** → treci cu mouse-ul peste titlu → **Editare rapidă (Quick Edit)**.
2. **Șablon (Template)** → cel din tabel → **Actualizează (Update)**.
3. Conținutul vechi (Elementor) nu se șterge; rămâne salvat, pentru revenire.
4. Doar la **Glamping** și **Camping**: titlul și descrierea vechi din Yoast (cu greșeala „Sambata se Sus”) se șterg sau se înlocuiesc, după `docs/seo-migrare.md`, secțiunea 4.1. La pagina principală, titlul din Yoast rămâne deocamdată.

**Ordinea:** întâi paginile ofertelor și Despre noi / Regulament / Contact, **la final pagina principală**.

### A6. Publică paginile noi
**Pagini** → la fiecare pagină nouă: **Editare rapidă** → **Stare: Publicat (Published)** → **Actualizează**.

### A7. Pagina principală statică
1. **Setări → Citire (Settings → Reading)**.
2. **Pagina ta principală afișează (Your homepage displays):** **O pagină statică (A static page)**.
3. **Pagina principală (Homepage):** pagina căreia i-ai pus șablonul „Pagina principală (rezervare)” (aceeași pagină ca până acum).
4. **Pagina articolelor (Posts page):** nu o schimba.
5. Doar pe **staging**: bifa „Descurajează motoarele de căutare să indexeze acest site” rămâne **bifată**.
6. **Salvează modificările (Save Changes)**.
7. **Setări → Legături permanente (Permalinks)** → apasă **Salvează modificările** fără să schimbi nimic. Asta reîmprospătează adresele paginilor noi.

### A8. Golește cache-ul
- Dacă în meniul din stânga ai **LiteSpeed Cache**: **LiteSpeed Cache → Toolbox → Purge All (Curăță tot)**. Sau, în bara neagră de sus, pictograma LiteSpeed → **Purge All**.
- Dacă ai alt modul de cache (WP Rocket, W3 Total Cache, Autoptimize): butonul lui de golire a cache-ului.
- Dacă nu ai niciun modul de cache, sari peste acest pas.
- Apoi deschide site-ul într-o **fereastră privată (incognito)**, ca să vezi ce vede un vizitator.

### A9. Verifică
- **[TU]** Lista din secțiunea 6, pe telefon și pe calculator.
- **[CLAUDE]** Verificarea automată a paginilor publice (`tools/validare.py`), dacă adresa staging-ului e accesibilă din cloud. Îți spune rezultatul.

---

## 5. Procedura B — plata de test NETOPIA pe staging (D-4)

**De ce:** pe staging, NETOPIA acceptă plata de test, dar comanda rămâne „Plată în așteptare”. Confirmarea trimisă de NETOPIA (IPN) nu poate fi citită, pentru că certificatul `.cer` și cheia `.key` încărcate **nu sunt pereche** (vin de la puncte de vânzare sau medii diferite). Nu e o problemă de cod.

### B1. Descarcă perechea corectă (D-7)
1. **[TU]** Intră în contul NETOPIA (admin.netopia-payments.com).
2. Mergi la **Puncte de vânzare (Point of sale)** și alege punctul de vânzare al site-ului gradina-ielelor.ro.
3. La setările tehnice sau de securitate ale **acelui** punct de vânzare (denumirea exactă poate diferi), descarcă cele două fișiere **de test (sandbox)**:
   - certificatul public, `.cer`;
   - cheia privată, `.key`.
4. **Cum recunoști perechea:** de obicei, numele ambelor fișiere încep cu `sandbox.` și conțin **același cod** de forma `XXXX-XXXX-XXXX-XXXX-XXXX`. Același cod este și **semnătura** punctului de vânzare (Signature / Seller Account ID) din setările NETOPIA din WordPress.
5. Păstrează fișierele pe calculator. Nu le trimiți pe email, în chat sau în GitHub. Dacă nu găsești fișierele, sună la suportul NETOPIA și cere „certificatul și cheia privată sandbox pentru punctul de vânzare X”.

### B2. Încarcă cheile în WordPress-ul de staging
1. **[TU]** **WooCommerce → Setări → Plăți (Settings → Payments)** → **NETOPIA Payments** → **Gestionează (Manage)**.
2. Verifică: modul **test / sandbox** este pornit.
3. Verifică **semnătura**: același cod ca în numele fișierelor.
4. La câmpurile pentru **sandbox**, încarcă certificatul `.cer` și cheia `.key` descărcate acum (le înlocuiești pe cele vechi).
5. **Salvează modificările**.
6. **Nu schimba nimic la cheile live.** Ele sunt pentru site-ul public și funcționează separat.

### B3. Pregătește emailurile pentru test
Pe staging, emailurile către clienți sunt oprite, ca oaspeții reali (care există în copia bazei de date) să nu primească mesaje de test.
1. **[TU]** **WooCommerce → Setări → Emailuri (Emails)**: notează ce e pornit și ce e oprit.
2. Pe durata testului, pornește numai **„Comandă în procesare” (Processing order)**. Pentru test folosești **adresa ta de email**.
3. Verifică: **„Comandă nouă” (New order)** merge la adresa ta (rezervari@ sau office@).

### B4. Fă rezervarea de test
1. **[TU]** Într-o fereastră privată, deschide pagina principală a staging-ului.
2. În modulul de rezervare: Glamping, o perioadă liberă (ex. o noapte peste 2–3 săptămâni). Notează câte unități sunt libere (ex. „4”).
3. Continuă până la plată. Completează datele cu **numele și emailul tău**. Alege **NETOPIA**.
4. Pe pagina NETOPIA (sandbox), plătește cu **cardul de test** din documentația NETOPIA pentru sandbox. Exemplul des folosit este 9900 0048 1022 5098, CVV 111, orice dată de expirare din viitor. Dacă nu merge, cere cardul de test la NETOPIA.
5. Așteaptă să revii pe site, pe pagina de mulțumire.

### B5. Cum arată reușita
Toate patru trebuie să fie adevărate:
1. **Comanda trece singură în „În procesare” (Processing)** în 1–2 minute, fără să o schimbi tu. Vezi la **WooCommerce → Comenzi**. În notele comenzii (dreapta) apare mesajul de confirmare de la NETOPIA.
2. **Disponibilitatea scade:** o nouă căutare pe aceleași date arată cu o unitate mai puțin (ex. 4 → 3).
3. **Emailurile pleacă:** primești „Comandă nouă” (ca administrator) și „Comanda ta este în procesare” (ca și client).
4. **În NETOPIA** (contul de test), tranzacția apare ca plătită.

**Anularea de test:** la comanda de test, schimbă starea în **Anulată (Cancelled)** → **Actualizează**. Disponibilitatea trebuie să revină (3 → 4). Nu testa returnarea banilor: politica e voucher, nu bani înapoi.

**La final:** oprește la loc emailul „Comandă în procesare” (B3). Șterge comanda de test sau las-o anulată.

### B6. Dacă nu reușește
- **Comanda rămâne „Plată în așteptare” după 5 minute:**
  1. Verifică din nou B1: aceeași pereche, același cod, aceeași semnătură.
  2. Este staging-ul **protejat cu parolă** (cPanel → Directory Privacy)? NETOPIA nu poate trimite confirmarea la o adresă cu parolă. Scoate parola pe durata testului.
  3. Are adresa staging-ului un **certificat HTTPS valid** (lacăt în browser, fără avertisment)? Altfel, NETOPIA poate refuza trimiterea.
  4. Fă o captură cu notele comenzii și trimite-o lui Claude **(fără chei sau parole în captură)**.
- **Plata e refuzată pe pagina NETOPIA:** cardul de test e greșit sau punctul de vânzare nu are sandbox activ. Sună la NETOPIA.
- **Dacă testul de pe staging nu se poate repara până în D-1:** plățile live funcționează azi independent de designul nou. **[TU + CLAUDE]** decideți atunci dacă lansați designul (fără să atingeți plățile) și reparați staging-ul după.

---

## 6. Lista de verificare (staging și live)

Fă-o pe **telefon** și pe **calculator**, într-o fereastră privată.

**Pagina principală**
- [ ] Se deschide cu designul nou. Modulul de rezervare se vede sus, fără să derulezi mult.
- [ ] O căutare cu date reale arată rezultate și prețuri.
- [ ] Cardurile Bungalow · Glamping · Camping · Rulote · Festivaluri & evenimente deschid fiecare pagina lor, în același tab.
- [ ] Linkurile Despre noi, Regulament & politici, Contact și Festival se deschid (nu „Pagina nu a fost găsită”).
- [ ] Recenziile și blogul apar.

**Paginile dedicate (fiecare din cele cinci)**
- [ ] Pozele se încarcă. Textele și prețurile (orientative) sunt corecte.
- [ ] Butonul „Rezervă” duce la pagina principală, cu oferta deja aleasă în modulul de rezervare.
- [ ] La final, „Înapoi la rezervare” funcționează.
- [ ] Pe telefon, pagina nu se mișcă stânga-dreapta (fără derulare orizontală). Butoanele nu sunt tăiate.

**Rezervarea până la plată**
- [ ] Rezultate → produs → coș → finalizarea comenzii → NETOPIA apare ca metodă de plată. (Pe live te oprești aici, fără să plătești; secțiunea 7.)

**Adresele vechi**
- [ ] Câteva adrese vechi duc spre paginile noi, de exemplu `/cazare-bungalow/` → `/bungalow/` (lista completă: `docs/seo-migrare.md`).

**Restul**
- [ ] Magazinul și paginile de produs se deschid.
- [ ] Telefonul și emailul din subsol sunt corecte.
- [ ] **[CLAUDE]** Verificarea automată: zero erori, versiunea corectă a pluginului.

---

## 7. Procedura C — lansarea pe live (D0)

Pașii sunt aceiași ca pe staging, cu trei diferențe: **plăți reale**, **emailuri către clienți pornite** și **site vizibil în Google**. Verifică adresa din bara browserului: **gradina-ielelor.ro**.

### C1. (D0, 10:00) Backup
Secțiunea 2, pentru **live**. Descarcă-l pe calculator. Fără el, nu continui.

### C2. Încarcă arhiva finală
Pasul A2, cu arhiva din **Release** (secțiunea 3, varianta 1). Apoi A3, setările pluginului.

### C3. Paginile noi (pregătite din D-1 ca ciorne)
Pasul A5 pentru paginile noi: titlu, text, descriptor, fără părinte, **Stare: Ciornă**. În D-1 nu poți alege încă șablonul, pentru că pluginul nu e instalat pe live. Îl alegi acum, după C2: **Editare rapidă → Șablon**.

### C4. Paginile existente
Pasul A5 pentru Glamping, Camping, Contact, apoi, **ultima, pagina principală**.

### C5. Publică paginile noi
Pasul A6.

### C6. Pagina principală și vizibilitatea în Google
Pasul A7. Pe live, bifa „Descurajează motoarele de căutare” trebuie să fie **nebifată**.

### C7. Golește cache-ul
Pasul A8.

### C8. Verifică
- **[TU]** Lista din secțiunea 6, pe telefon și pe calculator.
- **[TU]** Rezervarea pe live: mergi până la pagina de plată NETOPIA, **fără să plătești**. Opțional, dacă vrei siguranță deplină: o rezervare reală cu cardul tău, la cea mai mică sumă posibilă. Verifici B5 (comanda în „În procesare”, disponibilitatea, emailurile), apoi anulezi comanda și returnezi suma din contul NETOPIA.
- **[CLAUDE]** Verificarea automată a site-ului live (pagini, poze, linkuri, redirecționări, versiunea pluginului) și raportul către tine.

### C9. Dacă ceva nu e în regulă
Revenirea din secțiunea 8. Întâi revii, apoi analizăm.

### C10. Search Console (în aceeași zi)
1. **[TU]** Deschide search.google.com/search-console și alege proprietatea gradina-ielelor.ro.
2. **Sitemapuri (Sitemaps)** → adaugă `sitemap_index.xml` → **Trimite (Submit)**.
3. **Inspectarea adresei URL (URL inspection)**: pe rând, pentru `https://gradina-ielelor.ro/`, `/bungalow/`, `/glamping/`, `/camping/`, `/rulote/`, `/evenimente/`, `/despre-noi/`, apasă **Solicită indexarea (Request indexing)**. Google permite doar câteva cereri pe zi; continuă a doua zi dacă e nevoie.
4. **Google Analytics → Rapoarte → În timp real (Realtime)**: deschide site-ul pe telefon și verifică dacă vizita apare.
5. **Profilul de companie Google:** linkul site-ului rămâne `https://gradina-ielelor.ro/`. Nu trebuie schimbat nimic.
6. Restul verificărilor din ziua lansării: `docs/seo-migrare.md`, secțiunea 7.

---

## 8. Revenirea în sub 5 minute

Alegi **R1** dacă e urgent (ceva important nu merge) și **R2** dacă vrei să revii doar la o pagină. Ambele sunt **[TU]**, în WordPress admin, pe site-ul unde a apărut problema.

### R1. Oprește pluginul (1–2 minute, revine tot designul vechi)
1. **Module (Plugins)** → la „Grădina Ielelor — Site + BRW” → **Dezactivează (Deactivate)**.
2. Golește cache-ul (pasul A8) și verifică pagina principală într-o fereastră privată: trebuie să apară designul vechi.

Ce se întâmplă: paginile existente revin singure la designul vechi (conținutul Elementor e neatins), iar redirecționările se opresc. Dacă ai trecut deja pe Ciornă paginile vechi (secțiunea 9, D+2), le **publici din nou**, ca adresele vechi să nu dea „pagină negăsită”. Paginile **noi** (Bungalow, Rulote, Festivaluri & evenimente, Despre noi, Regulament & politici) rămân goale. Treci-le pe **Ciornă** când ai timp (Editare rapidă → Stare: Ciornă). Nu e urgent, pentru că meniul vechi nu duce la ele.

Ca să revii la designul nou: **Activează** pluginul. Paginile își păstrează șabloanele alese.

### R2. O singură pagină înapoi (1 minut pe pagină)
**Pagini** → **Editare rapidă** pe pagina respectivă → **Șablon: Șablon implicit (Default template)** → **Actualizează**. Golește cache-ul. La o pagină nouă: **Stare: Ciornă**.

### R3. Versiunea anterioară a pluginului (5 minute)
Dacă o versiune nouă (ex. 1.2.1) strică ceva ce mergea în 1.2.0: descarcă 1.2.0 din **Releases** și instaleaz-o ca la pasul A2, cu **Înlocuiește versiunea curentă cu cea încărcată**.

### R4. Restaurarea backup-ului (ultima soluție)
Softaculous → **Instalări** → rândul site-ului → **Backups** → **Restore**. **Atenție:** pe live, restaurarea șterge comenzile și rezervările primite după backup. Folosește-o numai dacă R1 nu ajută, și numai după ce ai notat comenzile noi.

---

## 9. Urmărirea în primele 7 zile (D+1 … D+7)

**[TU]** Zilnic, aproximativ 10 minute:
1. **Comenzi:** **WooCommerce → Comenzi**. Nicio comandă plătită nu trebuie să rămână „Plată în așteptare” mai mult de o oră. Dacă rămâne, compar-o cu NETOPIA (pasul 2) și spune-i lui Claude.
2. **NETOPIA:** plățile din ziua precedentă corespund comenzilor „În procesare” din WooCommerce.
3. **Fără rezervări duble:** noile rezervări de pe site apar în calendarele celelalte (5starDesk, Airbnb, Booking, h2b.ro), iar zilele ocupate acolo apar ocupate pe site.
4. **Emailuri:** pentru fiecare comandă ai primit „Comandă nouă”.
5. **Site-ul pe telefon:** pagina principală și o pagină dedicată se deschid normal.

**[CLAUDE]** Zilnic, la cererea ta (sau programat), verificarea automată a site-ului live (doar citire): pagini, poze, linkuri, redirecționări, versiunea. Îți trimite rezumatul.

**[TU]** În **D+2**, dacă totul a mers 48 de ore: treci pe **Ciornă** paginile vechi care au acum redirecționare (`/cazare-bungalow/`, `/camping-cort/`, `/camping-rulota/`, `/tarife/`, `/tarife-2026/`, `/acasa2023/`, `/gradina-ielelor-totul-despre-ea/evenimente/`), după `docs/seo-migrare.md`, secțiunea 5. Nu mai devreme: până atunci, revenirea R1 rămâne de un singur clic.

**[TU]** În D+2 și D+7, în **Search Console**:
- **Indexare → Pagini (Pages):** numărul de pagini „Negăsită (404)” nu crește. Trimite-i lui Claude lista adreselor noi cu 404.
- **Performanță:** clicurile din Google, comparate cu săptămâna de dinainte.

**[TU]** În D+7, în **Google Analytics**: vizitele și rezervările, comparate cu săptămâna de dinainte.

**Când revii imediat (R1):** plățile nu se mai confirmă; modulul de rezervare nu apare sau nu caută; pagina principală dă eroare; mai mulți oaspeți îți spun că nu pot rezerva.

**În D+7:** **[TU + CLAUDE]** Scurt bilanț. Claude îl notează în `docs/decizii.md` și propune pașii următori (WordPress curat, 5starDesk).

---

## 10. Deploy automat pe staging (opțional, oprit implicit)

În loc să încarci arhiva în WordPress (pasul A2), GitHub poate trimite singur pluginul pe **staging**, prin FTPS. Nu e necesar pentru lansare. **Pentru live nu există și nu va exista** un asemenea automatism: live se face numai manual, cu acordul tău.

**[TU]** Ca să-l pornești (o singură dată, aproximativ 20 de minute):
1. **cPanel → Conturi FTP (FTP Accounts)** → creează un cont nou, de ex. `deploy-staging`:
   - **Director (Directory):** folderul pluginului de pe **staging**, de forma `.../wp-content/plugins/gradina-ielelor-integration`;
   - o parolă lungă, generată de cPanel. Notează-o în managerul de parole.
   Contul acesta poate scrie **numai** în folderul pluginului de pe staging. Nu poate atinge site-ul live.
2. GitHub → depozitul → **Settings → Secrets and variables → Actions**:
   - fila **Secrets** → **New repository secret**, de patru ori:
     - `STAGING_FTP_HOST`: serverul FTP, de ex. `server-0387.whmpanels.com`;
     - `STAGING_FTP_USER`: utilizatorul complet al contului de la pasul 1, așa cum apare în cPanel;
     - `STAGING_FTP_PASSWORD`: parola lui;
     - `STAGING_PLUGIN_DIR`: `./` (contul de la pasul 1 vede direct folderul pluginului);
   - fila **Variables** → **New repository variable**: `STAGING_DEPLOY_ENABLED` = `true`.
3. **Actions → Deploy staging (opțional) → Run workflow**: alegi ramura indicată de Claude. Lași bifat **„Doar simulare”** și apeși **Run workflow**. Rezultatul arată ce s-ar încărca, fără să schimbe nimic.
4. Dacă simularea e verde, rulezi din nou cu **„Doar simulare” debifat**. Apoi golești cache-ul pe staging (A8) și verifici (A9).

Dacă apare o eroare de „certificate”, folosește ca server numele serverului (`server-0387.whmpanels.com`), nu `ftp.gradina-ielelor.ro`. **Oprire oricând:** schimbi variabila `STAGING_DEPLOY_ENABLED` în `false` sau ștergi contul FTP din cPanel.

---

## 11. Rezumat: ce poți face numai tu

- Backup-urile (cPanel / Softaculous) și descărcarea lor.
- Instalarea arhivei și alegerea șabloanelor în WordPress (staging și live).
- Cheile NETOPIA: descărcarea și încărcarea lor în WordPress.
- Plata de test și verificarea comenzilor, emailurilor și disponibilității.
- Aprobarea: „Aprob lansarea versiunii X” și **Merge pull request** în GitHub.
- Decizia „Da / Nu” din D-1 și revenirea, dacă e nevoie.
- Search Console, Google Analytics și profilul de companie Google.
- Opțional: contul FTP și secretele din GitHub pentru deploy-ul automat pe staging.

**Claude face:** codul, testele, arhiva verificată, Release-urile din GitHub, verificarea automată a paginilor publice, documentele și instrucțiunile pas cu pas. Îți spune mereu ce versiune instalezi și ce amprentă are.
