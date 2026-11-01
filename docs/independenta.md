# Independența: site-ul fără programatori

Data: 31.10.2026. Scop: toate conturile afacerii sunt ale tale, orice schimbare la site se face prin Claude, cu aprobarea ta, și niciun fost programator nu mai are acces.

## 1. Regula de bază

- **Fiecare cont este pe numele asociației și se deschide cu login-ul tău.** Nu cu login-ul unui programator, al unei agenții sau al unei rude.
- **Tu ești administrator principal peste tot.** Ceilalți (Claude prin GitHub, un colaborator temporar) primesc cel mult accesul de care au nevoie, și numai cât au nevoie.
- **Contul Google al asociației este cheia de la multe uși** (Gmail, Drive, Search Console, Analytics, Profilul de companie). Îl protejezi primul: verificare în doi pași (2FA), telefonul și emailul de recuperare să fie **ale tale**, codurile de rezervă tipărite și puse bine.
- **Parolele stau într-un manager de parole** (ex. Bitwarden, 1Password sau Managerul de parole Google), nu pe hârtie, în chat, pe email sau în GitHub.

## 2. Cum scoți în siguranță accesul unui fost programator

Ordinea contează. Așa nu rămâi blocat pe dinafară și nu se strică nimic:

1. **Backup întâi.** Backup complet la site (`docs/lansare.md`, secțiunea 2), descărcat pe calculator și în Drive. Verifică dacă fișierul are o mărime rezonabilă.
2. **Confirmă că TU intri.** Într-o fereastră privată, intră cu contul tău și verifică dacă ești administrator (coloana „Cum verifici” din tabel). Pornește 2FA. Abia apoi scoți pe altcineva.
3. **Caută ce funcționează pe contul lor.** Unele servicii pot rula, fără să știi, pe contul programatorului: trimiterea emailurilor din WordPress (SMTP), Google Analytics, Tag Manager, chei Google Maps sau reCAPTCHA, licența OVABRW, conturi FTP, webhook-uri. Fă lista cu Claude **înainte** să ștergi ceva, și mută-le pe contul tău.
4. **Un serviciu pe rând.** După fiecare: deschizi site-ul, faci o căutare în modulul de rezervare și verifici dacă vin emailurile.
5. **Schimbă parolele pe care le știau** (cPanel, FTP, căsuțe de email, WordPress) și cheile pe care le-au avut.
6. **Notează în managerul de parole** ce ai schimbat și când.

## 3. Inventarul conturilor

„Tu” = asociația, cu login-ul tău. Unde scrie „Claude te ghidează”, deschide o sesiune Claude și cere pașii; Claude nu intră în aceste conturi.

| Cont | Cine trebuie să-l dețină | Cum verifici că ești administrator | Cum scoți un fost programator (după backup) |
|---|---|---|---|
| **Contul Google al asociației** (Gmail, Drive, Search Console, Analytics, Profil) | Tu | myaccount.google.com → **Securitate**: 2FA pornit; telefonul și emailul de recuperare sunt ale tale; **Dispozitivele tale**: le recunoști pe toate | Dacă programatorul a știut parola: o schimbi, apoi **Securitate → Dispozitivele tale → Deconectează** de pe dispozitivele necunoscute. Verifici **Aplicații terțe cu acces la cont** și scoți ce nu recunoști |
| **Domeniul gradina-ielelor.ro și DNS** (Romarg) | Tu; titularul domeniului = SC AGRICOLA MEDICINALE 2002 SRL (firma proprietarei, aceeași care facturează prin FGO) | Verificat pe 1.11.2026 în zona de client Romarg: domeniul apare în lista ta, „Detinator drept folosinta” este firma proprietarei (nu programatorul), administrat de Romarg. Emailul de contact al domeniului e o adresă personală a proprietarei, unde vin avizele de reînnoire. Data expirării și reînnoirea automată sunt setate | Schimbi parola zonei de client; ștergi contactele sau utilizatorii suplimentari necunoscuți; faci o captură a înregistrărilor DNS (A, MX, TXT) ca să observi orice schimbare. Codul de autorizare (transfer) al domeniului îl ții în managerul de parole |
| **Hosting și cPanel** (Romarg, server-0387) | Tu (serviciul e în zona ta de client Romarg) | Intri în cPanel din zona de client. **Securitate → Autentificare în doi pași (Two-Factor Authentication)** pornită | Schimbi parola cPanel (din zona de client sau **Password & Security**). Apoi verifici și ștergi ce nu recunoști în: **Conturi FTP (FTP Accounts)**, **User Manager**, **SSH Access → Manage SSH Keys**, **Manage API Tokens**, **Cron Jobs**, **Forwarders**. **Nu șterge utilizatorii bazei de date** (MySQL): WordPress îi folosește |
| **WordPress admin** (live și staging) | Tu, cu utilizatorul și emailul tău | **Utilizatori (Users)** → filtrul **Administrator**: ești în listă. **Setări → Generale**: „Adresa de email a administrării” e a ta | **Utilizatori** → contul lor → **Șterge (Delete)** → alegi **„Atribuie tot conținutul lui: (tu)”** (altfel se șterg și paginile scrise de ei!) → confirmă. În profilul tău: **Parole aplicații (Application Passwords)** → revoci ce nu recunoști. Module necunoscute: **nu le dezactiva pe live**; întreabă-l întâi pe Claude |
| **WooCommerce** (în WordPress) | Tu | **WooCommerce → Setări → Emailuri**: „Comandă nouă” vine la adresele tale. Utilizatorii cu rol **Manager magazin (Shop manager)** sunt cunoscuți | **WooCommerce → Setări → Avansat → REST API**: revoci cheile necunoscute. **Webhooks**: ștergi ce trimite comenzi la adrese necunoscute (întâi verifici cu Claude că nu e o integrare folosită, ex. 5starDesk). Ștergi utilizatorii **Manager magazin** necunoscuți (ca la WordPress) |
| **FGO** (platforma de facturare; modulul „FGO Premium” din WordPress) | Tu, cu contul firmei sau al asociației care emite facturile | Intri în contul FGO cu utilizatorul tău; vezi seria de facturi și cheia API. În WordPress, modulul FGO are cheia ta | Cheia FGO se poate regenera din contul FGO; după regenerare o pui din nou în setările modulului. Fără FGO activ, clienții nu mai primesc facturile |
| **Licența OVABRW** (modulul de rezervări) | Tu: codul de cumpărare (purchase code) și contul de unde s-a cumpărat (de obicei Envato / CodeCanyon) | Ai în contul tău factura și codul de cumpărare. Dacă modulul are ecran de licență în WordPress, codul de acolo e al tău | Dacă licența e pe contul programatorului, nu se poate muta pe contul tău. Varianta sigură: cumperi o licență pe contul asociației la următoarea actualizare necesară. După lansare, oricum se trece pe 5starDesk |
| **NETOPIA** (contul de comerciant) | Tu; contractul și contul bancar de încasare sunt ale asociației | În admin.netopia-payments.com: intri cu utilizatorul tău, vezi punctul de vânzare al site-ului și contul IBAN al asociației. Lista de utilizatori a contului o vezi (dacă e disponibilă în meniu) | Ștergi sau dezactivezi utilizatorul lor; schimbi parola; pornești 2FA dacă există. Dacă au avut cheile (`.key`), întrebi NETOPIA dacă se pot regenera. Cheile noi le încarci tu în WordPress |
| **5starDesk** | Tu | În setările contului, lista utilizatorilor: ești administrator. Legăturile cu Airbnb și Booking sunt autorizate din conturile tale | Ștergi utilizatorii lor; schimbi parola; verifici dacă legăturile cu canalele funcționează după schimbare |
| **Profilul de companie Google** | Tu, ca **Proprietar principal (Primary owner)** | Pe Google, caută „Grădina Ielelor” logat cu contul tău → meniul profilului → **Setările profilului → Persoane și acces (People and access)**: apari ca Proprietar principal | Elimini persoanele lor din **Persoane și acces**. Dacă programatorul e proprietar principal, el îți transferă rolul; dacă nu răspunde, ceri acces prin formularul Google „Solicită acces” (Claude te ghidează) |
| **Google Search Console** | Tu, ca **Proprietar verificat** | search.google.com/search-console → **Setări → Utilizatori și permisiuni**: ești „Proprietar” | **Utilizatori și permisiuni** → ⋮ → **Elimină accesul**. Dacă sunt proprietari verificați: **Setări → Verificarea proprietății** → le elimini metoda de verificare (fișier pe site, etichetă sau înregistrare DNS), altfel se re-verifică singuri |
| **Google Analytics 4 și Google Tag Manager** | Tu, ca **Administrator** la nivel de cont | Analytics → **Administrare → Gestionarea accesului la cont**: ești Administrator. Tag Manager → **Administrare → Gestionarea utilizatorilor**: ești Administrator la cont și ai „Publicare” la container | Elimini utilizatorii lor la ambele niveluri (cont și proprietate / container). În Tag Manager te uiți la lista de etichete (tags) și întrebi pentru orice nu recunoști. Dacă nu poți deveni administrator: proprietate nouă în contul tău, iar Claude te ghidează să schimbi codul în WordPress |
| **Meta** (Facebook, Instagram, Meta Business) | Tu, cu **control total** în portofoliul de business | business.facebook.com → **Setări → Utilizatori → Persoane**: ai control total. Pagina și contul Instagram apar în portofoliul tău | Elimini persoanele lor din **Persoane** și agențiile din **Parteneri**; verifici accesul la Pagină, la contul Instagram și la Pixel / seturile de date |
| **Airbnb** | Tu, ca gazdă principală | Intri în contul de gazdă; emailul și telefonul sunt ale tale; 2FA pornit | La fiecare anunț: **Co-gazde (Co-hosts)** → elimini co-gazdele necunoscute. Verifici aplicațiile conectate (ex. 5starDesk) |
| **Booking.com** (Extranet) | Tu, ca titular principal al contului | Extranet → **Cont → Gestionarea utilizatorilor**: ești utilizatorul principal | Ștergi utilizatorii lor; schimbi parola; verifici furnizorul de conectivitate (5starDesk) |
| **h2b.ro** (contracte cu agenții) | Tu | Intri cu login-ul tău; datele firmei și emailul de contact sunt ale asociației | Ștergi utilizatorii suplimentari, dacă platforma are această opțiune; altfel schimbi parola și emailul contului |
| **Căsuțele de email** (rezervari@, office@) | Tu (sunt în cPanel) | cPanel → **Email Accounts**: le vezi pe amândouă. **Forwarders**: mesajele merg numai la Gmail-ul asociației | Ștergi redirecționările, filtrele și răspunsurile automate necunoscute. Schimbi parolele căsuțelor pe care le-au știut. **Atenție:** actualizezi parola nouă în același timp și acolo unde e folosită (telefon, Gmail, modulul de email din WordPress), altfel nu mai pleacă emailurile site-ului |
| **GitHub** (`asociatiabioglamp-lang`) | Tu | Deschizi depozitul `gradina-ielelor-site`: vezi fila **Settings** (o văd doar administratorii). Contul are 2FA pornit și codurile de recuperare puse bine | Depozit → **Settings → Collaborators**: elimini programatorii. **Deploy keys** și **Webhooks**: ștergi ce nu recunoști. Contul tău → **Settings → Applications**: revoci aplicațiile necunoscute (păstrezi Claude). Dacă au știut parola FTP de staging: o schimbi în cPanel și actualizezi secretul |
| **Google Drive** | Tu (contul asociației) | În Drive, la fișierele importante, coloana **Proprietar** = „eu” | Pe folderele partajate: **Distribuie** → elimini persoanele lor. Fișierele ale căror proprietari sunt ei: **Creează o copie** (copia e a ta) |
| **Claude** (claude.ai) | Tu | Intri cu contul tău; pagina privată „Grădina Ielelor Hub” e acolo; GitHub e conectat cu contul tău | Nu se aplică. Contul e numai al tău și nu se împarte |

## 4. Cum se fac schimbările de acum înainte

Nu mai e nevoie de programatori. Fiecare schimbare trece prin aceiași pași:

1. **[TU] Ceri, în cuvintele tale.** De exemplu: „Schimbă prețul de weekend la Glamping în 250 lei” sau „Adaugă o poză nouă pe pagina Bungalow”. Poți cere în două locuri:
   - **în cloud:** claude.ai/code → depozitul `asociatiabioglamp-lang/gradina-ielelor-site`. Merge de oriunde, și de pe telefon;
   - **pe calculatorul tău:** Claude Code în folderul `D:\proiect site gi`. Aici Claude poate porni un WordPress de test pe calculator și poate citi fișierele tale (ex. `D:\kimi`).
2. **[CLAUDE] Face schimbarea pe o ramură separată.** Nimic nu se atinge pe `main`, pe staging sau pe live.
3. **[CLAUDE] Testează:** sintaxa (PHP și JavaScript), un WordPress local, capturi pe telefon și pe calculator, verificarea automată a paginilor și arhiva (`tools/build-zip.sh`). Crește versiunea pluginului și notează schimbarea.
4. **[CLAUDE] Deschide o cerere de aprobare (pull request) în GitHub**, cu explicația în română și capturile. GitHub verifică singur codul și construiește arhiva (bifă verde).
5. **[TU] Aprobi:** te uiți la capturi (eventual pe staging) și apeși **Merge pull request**. Sau îi scrii lui Claude „aprob, unește”. Fără bifă verde nu se aprobă.
6. **[CLAUDE] Publică versiunea** (Release în GitHub, cu arhiva și amprenta ei) și notează decizia în `docs/decizii.md`.
7. **[TU] Instalezi pe staging** (arhiva în WordPress, sau deploy-ul automat pe staging, dacă l-ai pornit), verifici, apoi **instalezi pe live, cu backup**. Pașii sunt în `docs/lansare.md`. Pe live nu se instalează niciodată automat.

**Bine de știut:**
- **O schimbare = o cerere.** Mai multe schimbări mici se verifică mai ușor decât una mare.
- **Dacă ceva iese prost:** revenirea din `docs/lansare.md`, secțiunea 8. Apoi îi spui lui Claude ce s-a întâmplat.
- **O a doua părere:** pentru schimbări importante (plăți, rezervări), cere „o verificare independentă” într-o sesiune Claude separată, înainte să aprobi.
- **Tot contextul e în depozit:** `CLAUDE.md` (regulile), `docs/decizii.md` (deciziile tale), `docs/lansare.md` și acest document. Orice sesiune Claude nouă pornește de la ele. Nu depinzi de memoria cuiva.
- **Costuri GitHub:** contul gratuit include 2.000 de minute de verificări pe lună pentru depozite private. O verificare completă durează câteva minute, deci ajunge pentru sute de schimbări pe lună. Arhivele de test se șterg singure după 30 de zile; cele din Releases rămân.
- **Dacă vreodată ai nevoie de un om:** îi dai acces numai la GitHub (colaborator) și la staging, niciodată la conturile principale, și îl scoți la final (secțiunea 2).

## 5. Unde stau secretele

| Ce | Unde stă | Unde NU stă niciodată |
|---|---|---|
| Parolele tale (cPanel, WordPress, NETOPIA, Google, Romarg etc.) | Managerul de parole | Depozitul GitHub, chatul cu Claude, emailuri, documente partajate |
| Cheile NETOPIA (`.cer`, `.key`) | În WordPress (le încarci tu, în setările NETOPIA) și pe calculatorul tău | Depozitul GitHub (arhiva refuză singură astfel de fișiere), chat, email |
| Parola bazei de date, cheile WordPress | Pe hosting (`wp-config.php`), unde le-a pus instalarea | Oriunde altundeva |
| Datele pentru deploy-ul pe staging (`STAGING_FTP_HOST`, `STAGING_FTP_USER`, `STAGING_FTP_PASSWORD`, `STAGING_PLUGIN_DIR`) | **GitHub → Settings → Secrets and variables → Actions**. Odată salvate, nici tu, nici Claude nu le mai poate citi; se pot doar înlocui | Fișiere din depozit, chat |
| Parolele căsuțelor de email | cPanel, managerul de parole, aplicațiile de email | Chat, documente |

**Reguli:**
- **Claude nu are nevoie de parolele tale.** Dacă o sesiune îți cere o parolă sau o cheie, refuzi.
- **Dacă ai lipit din greșeală o parolă sau o cheie în chat, email sau GitHub:** o schimbi imediat în serviciul respectiv. Ștergerea mesajului nu ajunge.

## 6. Glosar

- **Artefact (artifact):** fișierul produs de o verificare automată în GitHub (aici, arhiva pluginului). Se descarcă din pagina rulării.
- **Amprentă (SHA-256):** un șir de 64 de caractere calculat din arhivă. Aceeași amprentă înseamnă exact același fișier.
- **Arhivă (zip):** fișierul `.zip` al pluginului, care se încarcă în WordPress.
- **Backup:** copia completă a site-ului (fișiere + bază de date), din care se poate reface.
- **Cache:** copii salvate ale paginilor, pentru viteză. După o schimbare se golește, ca vizitatorii să vadă varianta nouă.
- **cPanel:** panoul de control al hostingului (fișiere, emailuri, backup, FTP).
- **Deploy:** trimiterea automată a pluginului pe server.
- **DNS:** „cartea de telefon” a domeniului: spune unde e site-ul și unde vin emailurile.
- **FTP / FTPS:** modul prin care se copiază fișiere pe server. FTPS este varianta criptată.
- **GitHub:** locul unde stau codul și documentele site-ului, cu istoricul tuturor schimbărilor.
- **IPN:** mesajul automat trimis de NETOPIA către site după plată. Datorită lui, comanda trece singură în „În procesare”.
- **Live:** site-ul public, gradina-ielelor.ro.
- **Merge (unire):** aprobarea finală a unei schimbări în GitHub.
- **Modul (plugin):** o extensie a WordPress-ului. Al nostru: „Grădina Ielelor — Site + BRW”.
- **POS (punct de vânzare):** configurarea site-ului în contul NETOPIA, cu semnătura și cheile lui.
- **Pull request (cerere de aprobare):** propunerea de schimbare făcută de Claude, cu explicații și capturi, pe care o aprobi sau o refuzi.
- **Ramură (branch):** o copie de lucru a codului, separată de varianta aprobată (`main`).
- **Release:** o versiune aprobată, publicată în GitHub cu arhiva ei.
- **Revenire (rollback):** întoarcerea rapidă la varianta de dinainte.
- **Sandbox:** modul de test NETOPIA, cu carduri de test și fără bani reali.
- **Secret (GitHub):** o parolă salvată criptat în GitHub, folosită doar de verificările automate.
- **Șablon (template):** designul ales pentru o pagină WordPress.
- **Softaculous:** instrumentul din cPanel pentru backup și restaurarea WordPress-ului.
- **Staging:** copia de test a site-ului, unde se încearcă totul înainte de live.
- **Tag:** o etichetă pe o versiune a codului (ex. `v1.2.0`). Din ea se face Release-ul.
- **2FA (verificare în doi pași):** pe lângă parolă, un cod de pe telefon. Oprește pe oricine are doar parola.
- **Workflow (GitHub Actions):** o verificare sau o acțiune automată din GitHub (aici: „Build” și „Deploy staging”).
