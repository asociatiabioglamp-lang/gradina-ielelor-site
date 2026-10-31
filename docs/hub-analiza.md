# Grădina Ielelor Hub — ce este, de ce s-a oprit și cum îl repornim

Analiză făcută pe 31.10.2026, pe baza fișierelor din Google Drive. Nu am pornit aplicația, nu am intrat în căsuțele de e-mail și nu am modificat nimic în Drive.

Orele sunt date în ora României (până pe 25.10 ora de vară, UTC+3; după aceea UTC+2).

## Pe scurt

- „Hub-ul” este o mică aplicație care rulează **numai pe PC-ul tău**, în folderul `D:\gradina ielelor hub`. Se pornește din scurtătura „Gradina Ielelor HQ” de pe Desktop, care deschide o fereastră neagră și apoi pagina `http://localhost:7100` în browser.
- Are 7 taburi: Panou, E-mail-uri, Rezervări, Review-uri, Postări, Calendar și Setări.
- **Doar e-mailurile se actualizează singure.** Le citește direct din programul Outlook de pe PC, la fiecare 5 minute, dar numai cât timp fereastra neagră este deschisă.
- **Recenziile, rezervările și calendarul de postări au fost introduse o singură dată, de asistentul AI (Kimi), pe 18–19.10.2026.** De atunci nu se mai actualizează singure, pentru că aplicația nu este legată de Google, Airbnb, 5starDesk sau site.
- De ce pare „oprită la 18.10”: aplicația sincronizează doar cât timp e deschisă. În plus, o copie veche din `Documente` (salvată automat în Drive) a rămas la 18.10, pentru că în noaptea de 18 spre 19.10 aplicația a fost mutată pe D:. Copia de pe D: arată că aplicația a mai citit e-mailuri și pe 30.10, seara.
- Aplicația are **probleme serioase de siguranță**: nu cere parolă, poate fi citită din rețeaua locală (de exemplu de pe Wi-Fi-ul oaspeților) și de orice pagină web deschisă pe PC. E-mailurile și rezervările stau într-un fișier necriptat, care a ajuns și în Google Drive și în OneDrive. Fișierul pare să conțină și o parolă de aplicație Gmail.
- Codul se poate reconstrui: `npm install` și `npm run build` au reușit aici fără erori.
- Ai două variante: (a) o repornim pe PC după câteva reparații de siguranță, sau (b) o refacem ca pagină găzduită, cu login, legată direct de Microsoft 365, WooCommerce și Google.

## 1. Unde am găsit aplicația și ce versiune am copiat

| Copie | Unde este în Drive | Ultima modificare | Observații |
|---|---|---|---|
| **Versiunea nouă (copiată)** | Drive-ul meu › `kimi` › `gradina ielelor hub` (id `1qAFRsqHCnWG8y5ggS-EfVRzFud58ot_T`) | cod: 19.10, 11:51; date: 31.10, 00:43 | Este copia folderului `D:\gradina ielelor hub`, încărcată în Drive în noaptea de 30 spre 31.10. |
| Versiune veche | Computere › My laptop › Documente › kimi › tasks › 2026-10-18 › 20-58-03-d515abcb › `gradina-hq` (id `1wRBa4YtE-NwiWltKqrUid0Xz7_JwGfeE`) | 18.10, 23:38 | Este copia de lucru a asistentului Kimi. Nu are modificările din 19.10, de exemplu filtrul de spam. |
| Fișierele lansatorului | același folder Kimi › `gradina-ielelor` (`LaunchHQ.cs`, `compile.bat`, `make-shortcut.ps1`, `test-exe.ps1`) | 19.10, 01:21–01:23 | Din ele se construiește programul `GradinaIelelor-HQ.exe` și scurtătura de pe Desktop. |

Folderul `gradina ielelor hub` din Drive mai conține și alte lucrări, care nu fac parte din hub: `site/` (o machetă de site din 24.10), `06-site/` (audit și backup-uri ale site-ului) și `lucru/` (poze, postări, prezentări, contacte, documente de administrare).

## 2. Ce arată aplicația

Este o singură pagină, cu fundal verde-închis, accente aurii și o lună în logo („GRĂDINA IELELOR · HQ · Sâmbăta de Sus, Brașov · sezon 2027”). Sus are șapte taburi:

1. **Panou**: patru casete cu nota Google, numărul de e-mailuri necitite, progresul calendarului de postări și numărul de recenzii fără răspuns. Mai jos arată starea conectării celor două căsuțe și o listă fixă „Acum în Grădină”.
2. **E-mail-uri**: comuți între `office@gradina-ielelor.ro` și `rezervari@gradina-ielelor.ro` și vezi ultimele 50 de mesaje din Inbox: expeditor, subiect, primele rânduri, citit/necitit. Reclamele sunt ascunse automat, iar mesajele de tip phishing sunt marcate „suspect”. Butonul „Preia acum” face sincronizarea imediat.
3. **Rezervări**: listă cu oaspete, unitate, perioadă, sursă (Airbnb, 5starDesk, Site propriu, Booking.com, Telefon), sumă și stare, plus totaluri. Rezervările se adaugă de mână, cu un formular.
4. **Review-uri**: 8 recenzii Google, cu filtru „fără răspuns / cu răspuns” și un buton „Sugestie de răspuns”, care completează un text-șablon.
5. **Postări**: lista postărilor planificate pentru Instagram, Facebook, TikTok și blog, cu buton „Publicat”.
6. **Calendar**: planul de conținut „Luna 1 (19 oct – 15 nov)”, pe săptămâni, cu bife.
7. **Setări**: alegi cum se citesc e-mailurile: „Outlook local — fără parolă” (recomandat), „OAuth2 Microsoft” sau „Gmail / IMAP cu parolă”. Tot aici sunt instrucțiunile pentru fiecare variantă.

## 3. De unde vin datele

| Ce | Sursa | Cum ajunge în hub |
|---|---|---|
| E-mailuri office@ și rezervari@ | Programul **Outlook de pe PC** (modul „outlook-local”), prin scriptul `server/outlook-fetch.ps1` | **Automat, cu codul aplicației**, la fiecare 5 minute, **doar cât timp e deschisă fereastra aplicației**. Variantele de rezervă, adică IMAP la `outlook.office365.com` sau `imap.gmail.com` (port 993) și OAuth2 Microsoft (`login.microsoftonline.com`), există în cod, dar fișierul de date nu arată că OAuth ar fi fost folosit. |
| Recenzii Google | Copiate o dată de asistentul AI, scrise direct în cod (8 recenzii, iunie–august 2026) | **Introduse manual.** Nu există legătură cu Google. Răspunsurile scrise în hub se salvează doar local și **nu ajung pe Google**. |
| Recenzii Airbnb | Notificările Airbnb din căsuța rezervari@ | **Citite o singură dată, cu un script rulat de asistent** (`server/find-review.ps1`, care caută „recenzie de 2/3 stele”). Rezultatul a fost scris de mână în notele rezervărilor. Media „4,3 ★” este scrisă fix în pagină. |
| Rezervări Airbnb | Extrase o dată din e-mailurile Airbnb | **Introduse manual** de asistent (5 înregistrări, septembrie–octombrie). |
| Rezervări 5starDesk, Booking, site (WooCommerce) | — | **Nu sunt conectate.** Pagina o spune direct: „rezervările din 5starDesk nu ajung automat aici — se adaugă manual”. |
| Postări și calendar | Planul de marketing scris de asistent | **Introduse manual**, apoi bifate de mână. |

Toate datele stau într-un singur fișier: `server/data/db.json`.

## 4. Cum era pornită

- **Pornire obișnuită:** scurtătura de pe Desktop „Gradina Ielelor HQ”, sau `LANSEAZA-APLICATIA.bat` din folder. Amândouă pornesc `GradinaIelelor-HQ.exe`, un mic program care:
  1. folosește Node.js-ul adus în folder (`.tools\node\node.exe`, versiunea 24, fără instalare);
  2. pornește serverul de date `server\index.js` pe **portul 8787**;
  3. pornește interfața (Vite, mod de dezvoltare) pe **portul 7100**;
  4. după 9 secunde deschide `http://localhost:7100` în browser;
  5. dacă închizi fereastra neagră sau apeși Ctrl+C, totul se oprește.
- **Comenzi pentru programator** (în folderul aplicației): `npm install`, `npm run dev` (interfața pe 7100; pornește singur și serverul pe 8787), `npm run server` (doar serverul), `npm run build` (creează `dist/`; nu e folosit de lansator).
- **Nu există pornire automată:** nicio sarcină programată în Windows și niciun serviciu.
- **Setările** nu sunt într-un fișier `.env`, ci tot în `server/data/db.json`, la cheia `emailConfig`. Pentru fiecare căsuță se pot salva: `mode`, `user`, `pass`, `clientId`, `tenantId`, `imapHost`, `filterTo`, `accessToken`, `accessTokenExpiry`, `refreshToken`.
- **Secrete posibile** (dau doar numele, nu valorile): `pass` (parolă IMAP sau parolă de aplicație Gmail), `accessToken` și `refreshToken` (pentru OAuth Microsoft). Modul „outlook-local” nu are nevoie de nicio parolă.
- **Condiții pe PC:** Windows, programul Outlook clasic instalat și deschis, cu ambele conturi, și PowerShell din `C:\Windows\System32\WindowsPowerShell\v1.0\`.

## 5. De ce pare că s-a oprit pe 18.10.2026

Indiciile din fișiere:

1. **Aplicația lucrează doar „la cerere”.** E-mailurile se citesc numai cât timp fereastra neagră e deschisă. Nimic nu o pornește automat la deschiderea PC-ului. Dacă fereastra nu e deschisă, nu se adună nimic nou.
2. **S-a schimbat locul aplicației.** Pe 18.10 asistentul a construit-o în `C:\Users\<utilizator>\OneDrive\Documente\kimi\tasks\...\gradina-hq`. În noaptea de 18 spre 19.10 (01:19–01:23) a mutat-o în `D:\gradina ielelor hub`, cu `compile.bat` și `make-shortcut.ps1`, care scriu exact această cale. Copia din Documente, salvată automat în Drive („My laptop”), a rămas înghețată la **18.10, 23:38**. Cine se uită acolo vede date vechi.
3. **Exporturile din `.tmp` sunt instantanee făcute o singură dată**, nu date vii. `emails_rez.json` și `emails_office.json` (cu `lastFetch` 18.10) și `report.json` au fost făcute de asistent pe 18.10, între 23:33 și 23:42, ca să analizeze căsuțele.
4. **Recenziile și rezervările nu s-au actualizat niciodată singure** (vezi secțiunea 3). De aceea ele „stau” la datele din septembrie–octombrie, chiar dacă aplicația rulează.
5. **Pe D: aplicația a mai funcționat după 18.10.** Pe 19.10, în jurul orei 11:50, asistentul a testat serverul pornit din `D:\gradina ielelor hub` (`dev-scripts/check-spam.ps1`, `dev-scripts/test-classifier.mjs`). Apoi fișierul de date de pe D: a fost scris ultima dată pe **31.10, la 00:43**, și conține o marcă de timp din seara de 30.10. Asta arată că aplicația a fost pornită atunci și a citit e-mailuri, probabil chiar înainte de copierea în Drive. Nu am deschis fișierul; am căutat în el doar data și câteva cuvinte-cheie.
6. **Nu am găsit un token expirat.** În fișierul de date nu apare `refreshToken`, deci varianta OAuth nu a fost folosită. Modul folosit este „outlook-local”, care nu expiră.

**Concluzie:** aplicația nu e „stricată”; ea nu rulează decât dacă o pornești. Restul datelor (recenzii, rezervări) n-au fost niciodată automate. Un risc real pentru viitor: Microsoft înlocuiește Outlook clasic cu „noul Outlook”, iar acesta nu permite citirea mesajelor de către alte programe. Atunci modul „outlook-local” se va opri.

## 6. Defecte și riscuri

### Siguranță (cele mai importante)

1. **Fără parolă și deschisă în rețea.** Serverul de pe portul 8787 ascultă pe toate plăcile de rețea (`app.listen(8787)`, fără `127.0.0.1`) și nu cere autentificare. Orice dispozitiv din aceeași rețea, inclusiv de pe un Wi-Fi pentru oaspeți dacă e aceeași rețea, poate citi e-mailurile și rezervările (`/api/emails`, `/api/reservations`) și poate schimba setările.
2. **Orice site deschis în browser poate citi datele.** Serverul permite cereri de pe orice site (`app.use(cors())` fără restricții). O pagină web rău intenționată, deschisă pe PC cât timp hub-ul rulează, poate citi e-mailurile. Poate și schimba setarea „server de e-mail” (`POST /api/email/config`) către un server străin; la următoarea sincronizare, aplicația i-ar trimite acelui server parola salvată.
3. **Secrete în fișier simplu.** `server/data/db.json` păstrează necriptat parola (`pass`) și tokenurile, când sunt folosite. Căutarea în Drive arată că fișierul conține setările `imapHost`/`filterTo`, valoarea `imap.gmail.com` și cuvântul `"pass"`. Este deci **probabil** salvată o parolă de aplicație Gmail. Același lucru apare și în copia veche din Documente.
4. **Date personale necriptate.** `db.json` conține expeditori, adrese, subiecte și primele 300–400 de caractere din e-mailuri, plus nume de oaspeți și sume. Folderul `.tmp/` are exporturi cu e-mailurile rezervari@ și office@ și o listă de subiecte cu prenume de oaspeți. Codul original (`server/index.js`) avea scrise nume reale de oaspeți Airbnb, cu sumele încasate, și numele autorilor recenziilor. În copia noastră le-am înlocuit cu „Oaspete Airbnb 1–3” și „Recenzent Google 1–8”.
5. **Datele au ajuns la terți.**
   - În cloud: copia din `Documente` este în **OneDrive** și în backup-ul **Google Drive** („My laptop”). Copia de pe D: a fost încărcată în **Google Drive** pe 30–31.10, cu tot cu `db.json` și `.tmp`. Mesajul din subsolul aplicației („datele stau local, pe PC-ul tău”) nu mai e adevărat.
   - La asistentul AI: în timpul lucrului, **asistentul Kimi** a citit e-mailurile prin aplicație (exporturile din `.tmp`, scriptul care listează expeditorii și subiectele). Acest conținut a trecut prin serviciul Kimi.
   - Aceste transferuri trebuie trecute în evidențele GDPR sau oprite.
6. **Conectarea Microsoft (OAuth) nu verifică cererea de întoarcere** (parametrul `state`). Riscul e mic, pentru că totul e local, dar e o slăbiciune.

### Funcționare

7. Sincronizarea merge doar cât timp e deschisă fereastra aplicației. Nu există pornire automată.
8. Aplicația depinde de Outlook clasic pe Windows (prin COM și PowerShell). Nu merge cu „noul Outlook”. Outlook poate afișa și ferestre de avertizare care blochează citirea.
9. Se citesc doar **ultimele 50 de mesaje** și **doar din Inbox**. Mesajele mutate în subfoldere nu mai apar, de exemplu cele mutate de alt script în „Raspunsuri campanie 2027”.
10. Dacă Outlook nu găsește căsuța (de exemplu, dacă s-a schimbat numele contului), lista de e-mailuri se golește **fără niciun mesaj de eroare**, iar „ultima sincronizare” se actualizează ca și cum totul ar fi în regulă.
11. Serverul pornește de două ori: o dată din lansator, o dată din Vite. A doua pornire eșuează cu „port ocupat”, iar mesajul apare în fereastra neagră.
12. Versiunea „construită” (`dist/`) nu e folosită de nimic. Lansatorul rulează modul de dezvoltare, care nu e gândit pentru folosire zilnică. Folderul `dist/` din Drive e din 18.10 și nu are modificările din 19.10.
13. „Rating Google 5,0” din Panou e calculat doar din cele 8 recenzii copiate de mână, deci e înșelător. Media „4,3 ★” și avertismentul despre recenzia de 2 stele sunt scrise fix în cod.
14. Rezervări: nu se pot edita sau șterge din pagină, iar totalul încasărilor adună și rezervările anulate.
15. Calitatea codului:
    - 11 erori de „lint” (verificarea automată a stilului codului); nu blochează construirea;
    - 53 de componente de interfață din șablon, nefolosite, plus `Home.tsx` și `App.css` rămase din șablon;
    - numele proiectului e încă „my-app”;
    - nu există teste;
    - dacă `db.json` se strică, serverul nu mai pornește, pentru că nu există tratare de erori la citire.
16. Aplicația merge doar pe Windows: căile sunt scrise fix (`D:\gradina ielelor hub`, `C:\Windows\...\powershell.exe`), iar `node_modules` conține componente compilate pentru Windows.

## 7. Ce trebuie ca să o repornim

### (a) Din nou pe PC-ul tău

Pași:
1. Verifici că există folderul `D:\gradina ielelor hub`, cu `node_modules` și `.tools\node` (sunt și în copia din Drive).
2. Deschizi Outlook clasic, cu conturile office@ și rezervari@.
3. Pornești „Gradina Ielelor HQ” de pe Desktop, aștepți circa 10 secunde și lași fereastra neagră deschisă (o poți minimiza).
4. În **Setări**, pentru fiecare căsuță: „Outlook local” → Salvează → Testează conexiunea.
5. Înainte de folosirea zilnică, facem reparațiile de siguranță în cod:
   - serverul ascultă doar pe `127.0.0.1`;
   - scoatem accesul deschis pentru alte site-uri (CORS);
   - un singur start al serverului;
   - ștergem parola salvată din `db.json`;
   - mutăm sau ștergem `.tmp/` și oprim urcarea în cloud a folderului `server/data`.
6. La cerere: pornire automată la deschiderea PC-ului, printr-o sarcină programată în Windows.

Ce ai de făcut sau de confirmat, fără să trimiți parole în chat:
- dacă folosești Outlook clasic sau „noul Outlook”;
- dacă ai creat o adresă Gmail cu „parolă de aplicație” pentru hub. Dacă da, o revoci chiar tu din contul Google: Securitate → Parole de aplicații;
- dacă ștergi copiile cu date din Drive: `kimi/gradina ielelor hub/server/data/db.json`, `kimi/gradina ielelor hub/.tmp/` și copia veche din „My laptop”;
- acces la PC: fie urmezi tu pașii, fie conectezi PC-ul printr-o sesiune la distanță.

### (b) Ca pagină găzduită, care nu depinde de PC

1. **E-mail:** citire prin **Microsoft Graph** din căsuțele office@ și rezervari@. Căsuțele par să fie pe Microsoft 365; trebuie confirmat. Se face o „înregistrare de aplicație” în Microsoft Entra, cu dreptul *Mail.Read* limitat doar la aceste două căsuțe. Cheia secretă stă în setările de secrete ale serverului, nu în fișiere.
2. **Rezervări:**
   - din **WooCommerce + OVABRW**, prin API, cu o cheie doar de citire (rezervările de pe site);
   - **Airbnb**: din notificările din rezervari@ (prin Graph) sau din calendarul iCal, care are doar datele, fără nume;
   - **5starDesk**: de verificat cu ei dacă au API sau export;
   - **Booking**: prin channel manager sau prin export.
3. **Recenzii:** prin **Google Business Profile API**, cu cerere de acces aprobată de Google pentru profilul firmei. Până atunci, recenziile se introduc manual.
4. **Găzduire:** un server mic în UE, cu bază de date, HTTPS și **login obligatoriu** (cont Microsoft/Google al firmei sau cod pe telefon). Altă variantă: o pagină de administrare în WordPress-ul nou, protejată de login-ul WordPress, care citește direct comenzile WooCommerce.
5. **Protecția datelor:** se păstrează doar ce e necesar (expeditor, subiect, dată, nu tot e-mailul), cu ștergere automată după un termen (de exemplu 90 de zile), fără trimiterea datelor la servicii AI fără contract.

Ce ai de furnizat, fără parole în chat; codurile le introduci tu direct în setările serverului:
- acces de administrator Microsoft 365, sau persoana care îl are, pentru înregistrarea aplicației și acordul de citire;
- calitatea de proprietar sau manager pe profilul Google al firmei;
- acces de administrator WooCommerce, pentru cheia API doar de citire;
- contul 5starDesk, ca să verificăm ce export oferă;
- alegerea găzduirii și a unui subdomeniu (de exemplu `hub.gradina-ielelor.ro`), cu acces la DNS.

## 8. Ce am copiat în `hub/` și ce nu

Am copiat 41 de fișiere. 40 sunt identice cu originalul (am verificat dimensiunea față de Drive). Unul, `server/index.js`, l-am modificat intenționat (vezi mai jos). Toate stau în `/home/user/gradina-ielelor-site/hub/`.

- **Rădăcina** (15): `package.json`, `package-lock.json`, `vite.config.ts`, `tsconfig.json`, `tsconfig.app.json`, `tsconfig.node.json`, `components.json`, `eslint.config.js`, `postcss.config.js`, `tailwind.config.js`, `index.html`, `.gitignore`, `README.md`, `template-info.md`, `LANSEAZA-APLICATIA.bat`.
- **`server/`** (3): `index.js` (**modificat**: numele oaspeților și ale recenzenților au fost înlocuite cu „Oaspete Airbnb N” / „Recenzent Google N”, iar un prenume a fost scos dintr-un răspuns; 23 407 octeți față de 23 365 în original), `outlook-fetch.ps1`, `find-review.ps1`.
- **`src/`** (17): `App.tsx`, `main.tsx`, `types.ts`, `index.css`, `App.css`, `lib/ui.ts`, `lib/api.ts`, `lib/utils.ts`, `hooks/use-mobile.ts`, `pages/Home.tsx` și cele 7 secțiuni din `sections/` (Dashboard, Emails, Reservations, Reviews, Posts, Calendar, Settings).
- **`launcher/`** (4, din folderul Kimi `gradina-ielelor`): `LaunchHQ.cs` (sursa lansatorului `.exe`), `compile.bat`, `make-shortcut.ps1`, `test-exe.ps1`.
- **`dev-scripts/`** (2, din folderul Kimi): `check-spam.ps1`, `test-classifier.mjs`.

Ce **nu** am copiat:

| Fișier sau folder | Motiv |
|---|---|
| `server/data/db.json` (55 669 octeți, 31.10, 00:43); copia veche `gradina-hq/server/data/db.json` (67 898 octeți, 18.10, 23:38) | **Secrete și date personale.** Câmpuri: `reviews`, `posts`, `events`, `emails.office/rezervari[]` (`uid, fromName, from, to, subject, date, seen, snippet`), `reservations[]` (`guest, unit, checkin, checkout, source, amount, status, note`), `emailConfig` (vezi secțiunea 4), `lastFetch`. |
| `.tmp/emails_rez.json` (23 353 octeți), `.tmp/emails_office.json` (26 783 octeți), ambele din 18.10, 23:33 | **Exporturi de e-mailuri.** Câmpuri: `emails[]` (`uid, fromName, from, to, subject, date, seen, snippet`), `spamCount`, `lastFetch` (18.10). |
| `.tmp/report.json` (2 582 octeți, 18.10, 23:42) | **Date personale.** Inventarul folderelor Outlook rezervari@ (`store, folders[{path, items, hits[]}]`); `hits` conține subiecte Airbnb cu prenume de oaspeți. |
| `.tmp/review.json`, `.tmp/review2.json` | Fișiere goale (0 octeți). |
| `src/components/ui/*` (53 de fișiere, circa 176 kB) | Componente generice din șablon, **nefolosite de aplicație** (nu le importă nimeni). Se pot regenera cu `npx shadcn add`. Fără ele, construirea merge. |
| `GradinaIelelor-HQ.exe` (7 680 octeți) | Program compilat; am copiat sursa lui (`launcher/LaunchHQ.cs`). |
| `.tools/node/` (Node.js 24 portabil, `node.exe` de 94 MB), `node_modules/`, `dist/` | Programe și rezultate de construire, care se pot reface. |
| `launcher`: `check-env.ps1`; scripturile Kimi din `Documente/kimi/workspace` (`outlook-scan.ps1`, `draft-glampinghub.ps1` etc.) | Nu țin de hub: verificări ale mediului Kimi și alte sarcini de e-mail (ciorne către platforme, mutare de răspunsuri). |

---

## Anexă tehnică

**Stack:** React 19, TypeScript 5.9, Vite 7, Tailwind 3, lucide-react; server Express 5 (ESM) cu `imapflow` și `cors`. Proiectul pornește de la un șablon Kimi cu shadcn/ui; pluginul de dezvoltare `kimi-plugin-inspect-react` este pe npm public.

**API** (`server/index.js`, port 8787; Vite face proxy pentru `/api` de pe portul 7100):
- `GET /api/health`, `GET /api/overview`;
- `GET /api/reviews`, `PATCH /api/reviews/:id`;
- `GET/POST /api/posts`, `PATCH /api/posts/:id`;
- `GET /api/events`, `PATCH /api/events/:id`;
- `GET/POST /api/reservations`, `PATCH /api/reservations/:id`;
- `GET/POST /api/email/config`, `GET /api/email/oauth-url`, `GET /api/email/consent-url`, `GET /api/oauth/callback` (redirect `http://localhost:8787/api/oauth/callback`);
- `GET /api/email/status`, `POST /api/email/test`, `POST /api/email/fetch`, `GET /api/emails?mailbox=office|rezervari&includeSpam=1`;
- `setInterval(pollAll, 5 min)`.

**Moduri e-mail** (`emailConfig.<căsuță>.mode`):
- `outlook-local`: PowerShell și `Outlook.Application` (COM), primele 50 de mesaje din Inbox, sortate descrescător; magazinul Outlook se alege după numele afișat, care trebuie să conțină adresa;
- `password`: IMAP 993 cu utilizator și parolă (`imapHost`, implicit `outlook.office365.com`; opțional `imap.gmail.com` și `filterTo`);
- `oauth`: Microsoft identity v2, scope `https://outlook.office.com/IMAP.AccessAsUser.All offline_access`, client public, tokenuri în `db.json`.

**Clasificare spam:**
- domenii de încredere: gradina-ielelor.ro, audserv.ro, btrl.ro, airbnb.com, 5stardesk.net, camping.info, h2b.ro, campercontact.com;
- listă de domenii de reclame;
- expresii regulate pe expeditor și subiect;
- `[SPAM]` (marcaj Bitdefender) înseamnă spam;
- subiecte de tip phishing primesc eticheta „suspicios”.

**Verificări făcute aici** (Node 22.22, npm 10.9; fără niciun contact cu căsuțele sau cu conturi externe):
- `npm install`: OK, 568 de pachete;
- `npm run build` (`tsc -b && vite build`, Vite 7.3.0): **OK**, rezultat `dist/index.html` 0,42 kB, CSS 15 kB, JS 293 kB (88,7 kB gzip). CSS-ul e mai mic decât în `dist/` original (83 kB), pentru că lipsesc componentele de șablon nefolosite;
- `node --check server/index.js`: OK;
- `npx eslint .`: 11 erori și 1 avertisment (`no-explicit-any`, `set-state-in-effect`, `exhaustive-deps`), care nu blochează construirea.

**Cronologie** (ora României):
- 18.10, 20:58: începe sarcina Kimi;
- 21:15: proiectul creat din șablon;
- 23:24: apare `outlook-fetch.ps1` (trecerea la Outlook local);
- 23:33–23:42: exporturile `.tmp`;
- 23:36: `dist/`;
- 19.10, 01:19–01:23: Node portabil, `GradinaIelelor-HQ.exe`, scurtătura de pe Desktop, mutarea pe D:;
- 19.10, 11:48–11:53: filtrul de spam și testele;
- 31.10, 00:29–01:39: copierea folderului în Drive (`db.json` modificat la 00:43).

**ID-uri Drive utile:**
- rădăcina nouă: `1qAFRsqHCnWG8y5ggS-EfVRzFud58ot_T`;
- `server/`: `1JkF1jP0Bam52X8fODK5GFLyPbIKT8vFe`;
- `src/`: `19hsxiJTRmCaRuBcHox6HTXpbn0mQ247I`;
- `.tmp/`: `1r9ynq-RbiWMWOllBUG041Ao-q5O3Ukl5`;
- copia veche `gradina-hq`: `1wRBa4YtE-NwiWltKqrUid0Xz7_JwGfeE`;
- folderul sarcinii Kimi: `12ltTMBXa7u9br9dDs4h51nw6SdDHrC5D`.
