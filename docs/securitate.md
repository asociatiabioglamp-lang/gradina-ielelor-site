# Securitate: plan în straturi și plan pentru cazul unui atac

Întocmit 14.11.2026, la cererea proprietarei: „pagina să aibă elemente de securitate; nimic să n-o blocheze în caz de atac”.
**Nimeni nu poate promite protecție 100%.** Ce putem face: mai multe straturi, fiecare opresc o parte din atacuri, și un plan clar pentru ziua în care ceva merge prost. Fiecare strat spune **cine îl face**.

## Starea de azi (verificat 14.11.2026, din afară, numai citire)

| Ce | Starea | Observație |
|---|---|---|
| Conexiune HTTPS | **Bine** (TLS 1.3) | |
| Fișiere sensibile (`.env`, `.git`, `wp-config` copii, `debug.log`) | **Bine**: blocate (403) | |
| XML-RPC | **Bine**: blocat de server (403) | Cale clasică de atac cu parole, închisă. |
| Listarea dosarelor (`/wp-content/uploads/`) | **Bine**: 404 | |
| Antete de protecție | **Lipsesc** (HSTS, anti-încadrare, Referrer, Permissions) | **Se repară în 1.3.10.** |
| Conturi vizibile public | **2** (`a.min`, contul proprietarei), prin `/wp-json/wp/v2/users` | Numele de utilizator ușurează ghicirea parolelor. **Se repară în 1.3.10.** |
| Versiunile din cod | Se văd: WordPress 7.1.2, WooCommerce 8.7.3, Elementor 3.20.2 | WordPress se ascunde în 1.3.10. Adevărata reparație: actualizarea. |
| **Aplicații vechi** | **WordPress 7.1.2 (două versiuni de securitate în urmă)**; Elementor 3.20.2 și Elementor Pro 3.20.1 au vulnerabilități cunoscute; WooCommerce 8.7.3 e din 2024; **PHP 8.1** nu mai primește corecturi de securitate; 16 module inactive încă în fișiere | **Cel mai mare risc.** Inventar complet și plan de actualizare: Strat 2. |
| Firewall / protecție la atac în fața site-ului | **Neconfirmat**: serverul e LiteSpeed, nu am văzut CDN sau firewall | Strat 3. |
| Limitarea încercărilor de parolă | **Neconfirmată** (nu fac teste de forță brută pe site-ul tău) | Strat 3. |
| Copii de siguranță | Făcute la mână (Softaculous), numai de bază de date | Strat 4. |

## Strat 0 · Dispozitivele și conturile tale (proprietara, cu Claude)
Cele mai multe site-uri sunt sparte prin parola furată de pe un calculator infectat, nu prin „spargerea” serverului. De aceea începem cu ce folosești tu.

**Bitdefender, pe toate dispozitivele cu care administrezi site-ul** (calculator, laptop, telefon):
- Protecția în timp real **pornită** și actualizarea **automată** (aplicația și semnăturile).
- **Scanare completă o dată pe săptămână**, plus una acum, înainte de orice schimbare de parole.
- **Protecția web și anti-phishing pornite**: majoritatea atacurilor încep cu un email fals „de la hosting”, „de la NETOPIA” sau „de la Google”.
- Pentru intrarea în conturile de bani (NETOPIA, banca): modul protejat al browserului din Bitdefender (Safepay), dacă pachetul îl include.
- Managerul de parole din Bitdefender (inclus în pachetele Total și Premium) poate păstra parolele lungi și cheile din secțiunea de mai jos.
- **Limită:** Bitdefender de pe calculatorul tău nu protejează serverul site-ului. Pentru server întrebăm hostingul ce scanare de fișiere (antivirus) rulează (vezi cererea din Strat 3).

**Aplicațiile de pe calculator și telefon** se actualizează la fel de important ca pluginurile: sistemul de operare (Windows/macOS/Android/iOS), **browserul** (Chrome/Edge: reporniți-l când cere), Bitdefender, programele de email. Actualizările automate pornite peste tot.

**Contul Google al asociației este cheia tuturor conturilor** (hosting, WordPress, NETOPIA, Search Console, GitHub: toate se resetează prin acel email). Se securizează primul:
1. **Cheie de acces (passkey)** sau **verificare în doi pași** pe contul Google.
2. Telefon și email de recuperare puse la zi; coduri de rezervă tipărite, păstrate într-un loc sigur.
3. Google → Securitate → **Verificarea securității**: dispozitivele necunoscute scoase, aplicațiile terțe cu acces verificate.
4. Aceeași protecție pe **GitHub** (contul `asociatiabioglamp-lang`) și pe **hosting**.

**Reguli simple:** nu intri în administrare de pe calculatoare publice sau Wi-Fi public fără VPN; nu deschizi linkuri din emailuri care cer parola; nu instalezi „crack-uri” sau pluginuri din surse necunoscute.

## Strat 1 · Pluginul (Claude) — versiunea 1.3.10
Testat local (42 de verificări, inclusiv roluri editor și client WooCommerce), verificat de un agent separat (fără probleme blocante; problemele minore au fost reparate), instalat numai cu aprobarea proprietarei.
- **Antete de protecție** pe paginile publice: site-ul nu poate fi încadrat în alt site (anti-„clickjacking”), adresele paginilor nu se scurg către alte site-uri, camera și microfonul sunt oprite, iar vizitatorii care revin sunt duși mereu pe HTTPS (HSTS, 30 de zile la început, fără subdomenii, **numai pe gradina-ielelor.ro**, nu și pe copia de test).
- **Conturile se văd mult mai greu din afară:** lista de utilizatori (REST), paginile de autor, oEmbed, harta „users” din sitemap și numele autorului din fluxul RSS. Administratorii, editorii și autorii văd în continuare ce le trebuie. Numele afișat al autorului pe articole rămâne cel ales de tine.
- **Autentificare greșită și „am uitat parola”:** același răspuns, fie că numele există, fie că nu (WordPress și WooCommerce).
- **Versiunea WordPress ascunsă** din cod (parțial: adresele CSS/JS păstrează numărul versiunii, iar Elementor își pune propria etichetă; protecția reală e actualizarea). Editorul de fișiere din administrare oprit: cine ajunge într-un cont nu poate scrie cod PHP de acolo. Pingback-ul oprit.
- **Nu atinge** paginile, rezervările, plățile și confirmările NETOPIA.
- **Revenire:** dezactivezi pluginul (mai puțin de 5 minute). Antetele, HSTS-ul și redirecționarea paginilor de autor au și filtre de oprire (`gi_security_*`).

## Strat 2 · Conturi și actualizări (proprietara, cu Claude)
**Conturi**
1. Rămâne **un singur cont de administrator**, al proprietarei. Contul `a.min` se închide pe 21.11 și se șterge pe 28.11 (planul existent din `docs/lansare.md`).
2. **Parolă lungă și unică** (frază de 4–5 cuvinte), într-un manager de parole, nu în email.
3. **Autentificare în doi pași** pentru administratori (cod din telefon). Se instalează un plugin dedicat, de exemplu „WP 2FA”, după testare pe copia de test.
4. Conturile clienților (create la plată) nu au drepturi de administrare.

**Inventarul aplicațiilor de pe site** (versiunile se văd public în adresele fișierelor; verificat 14.11.2026) și ce se știe despre ele:

| Aplicație | Versiune pe live | Ce am găsit | Prioritate |
|---|---|---|---|
| **WordPress (nucleu)** | **7.1.2** | **Două versiuni de securitate în urmă:** 7.1.3 (6.10.2026, 7 corecturi de securitate) și 7.1.4 (28.10.2026, aproximativ 22 de vulnerabilități corectate, printre ele una în procesul de actualizare). Actualizările automate de securitate nu au pornit. Notă: în versiunea anterioară a acestui document scria, greșit, că WordPress e la zi. | **URGENT** (mic, cu backup) |
| Elementor Pro | 3.20.1 | Două vulnerabilități cunoscute (XSS stocat, CVE-2024-2121 și CVE-2024-1364), corectate în 3.20.2. Cere un cont cu drept de editare, deci risc mic cât timp conturile sunt doar ale proprietarei. | **Mare** |
| Elementor (gratuit) | 3.20.2 | Vulnerabilitate cunoscută (XSS stocat, CVE-2024-2117), corectată în 3.20.3. Același tip și aceeași condiție. | **Mare** |
| WooCommerce | 8.7.3 | Nu am găsit o vulnerabilitate care să vizeze exact această versiune. Dar e din 2024 și nu mai primește corecturi; versiunile noi le acoperă. Contează pentru plăți. | **Mare** (cu test, vezi mai jos) |
| PHP (hosting) | 8.1.34 | PHP 8.1 nu mai primește corecturi de securitate (a ieșit din suport la sfârșitul lui 2025). Se trece pe 8.3 din cPanel, după test pe copia de test. | **Mare** |
| Advanced Coupons | 4.7.6 | Vulnerabilitățile găsite (CVE-2026-39508 și CVE-2026-31919) vizează versiuni până la 4.7.1.1; 4.7.6 e peste ele. | Mic (cât timp îl folosim) |
| OVABRW (rezervările) | 2.0.0 | Nu am găsit vulnerabilități publice pentru această versiune. Folderul pluginului se numește `s4p_ova-brw20`, nu cel obișnuit, deci **actualizările automate nu se aplică**. De lămurit de unde a fost instalat și cum se actualizează (licență, furnizor). | **De lămurit** |
| Tema `giw` | 7.4.3 | Versiune din 2024. Actualizarea cere licența temei. | Mediu |
| NETOPIA, GTranslate 5.0.1, Yoast 28.6, WP Mail SMTP 4.10.0, GTM4WP 2.0.5, Meta Pixel 5.2.2, WP Statistics 14.16.15 | vezi `docs/stare-live-2026-10-31.md` | Neverificate acum pentru vulnerabilități; se verifică la fiecare ciclu de actualizare. | Mediu |
| 16 module inactive (WP Rocket, Really Simple SSL, Contact Form 7 etc.) | inactive | **Un modul inactiv rămâne în fișiere și codul lui poate fi încă apelat direct.** Se șterg cele nefolosite. | Mediu |

Surse consultate: [WordPress.org: versiunea 7.1.3](https://wordpress.org/news/?p=21753), [WordPress.org: versiunea 7.1.4](https://wordpress.org/support/wordpress-version/version-7-1-4/), [Patchstack: 7.1.4](https://patchstack.com/articles/wordpress-7-1-4-security-release/), [Wordfence: Elementor Pro ≤ 3.20.1](https://www.wordfence.com/threat-intel/vulnerabilities/wordpress-plugins/elementor-pro/elementor-website-builder-pro-3201-authenticated-contributor-stored-cross-site-scripting), [Patchstack: Elementor ≤ 3.20.2](https://patchstack.com/database/wordpress/plugin/elementor/vulnerability/wordpress-elementor-plugin-3-20-2-auth-stored-cross-site-scripting-vulnerability), [Patchstack: Advanced Coupons ≤ 4.7.1](https://patchstack.com/database/Wordpress/Plugin/advanced-coupons-for-woocommerce-free/vulnerability/wordpress-advanced-coupons-for-woocommerce-coupons-plugin-4-7-1-broken-access-control-vulnerability). Pentru OVABRW și NETOPIA nu am găsit surse publice; nu înseamnă că nu există.

**Cum actualizăm, în siguranță** (OVABRW și NETOPIA depind de WooCommerce, deci **nu actualizăm direct pe live**):
1. Backup complet (fișiere și baza de date) înainte de orice.
2. Actualizare pe **copia de test** (`asociatia-ropam.ro/gi-test`), nu pe live.
3. Test complet pe copie: rezervare, calendar, plată sandbox, email, regulament la plată.
4. Verificăm compatibilitatea OVABRW 2.0.0 și NETOPIA 1.4.4 cu noul WooCommerce în notele lor de versiune, sau întrebăm furnizorii. Dacă nu sunt compatibile, actualizăm întâi pe acelea.
5. Pe live, într-o zi cu puține rezervări, cu backup făcut, cu revenirea pregătită.
6. Ordinea: **WordPress 7.1.4 (urgent, întâi pe copia de test, apoi pe live)** → PHP 8.3 (cPanel, testat) → Elementor Pro și Elementor → WooCommerce → tema → pluginurile mici. Și copia de test se actualizează: e publică și conține date reale.
7. **Ritm după aceea:** o zi fixă pe lună (prima marți) pentru actualizări; actualizările de securitate mici, în aceeași săptămână. Claude verifică lista și cere aprobarea.
8. **Actualizările automate** pornite numai pentru pluginurile mici și sigure (WordPress, Yoast, GTM4WP). **Nu** pornim actualizări automate pentru WooCommerce, OVABRW, NETOPIA și Elementor: o actualizare automată proastă poate opri rezervările.

Aplicațiile nefolosite se șterg (nu doar se dezactivează): cele 16 module inactive, produsul de test, plata iPay BT oprită, fișierul `advanced-cache.php` rămas de la WP Rocket.

## Strat 3 · La marginea site-ului: firewall și protecție la atac (hosting)
Aici nu pot lucra eu: se face la hosting sau în DNS. Două variante:
- **A. Firewall-ul hostingului (cea mai simplă, fără DNS).** Trimitem hostingului cererea de mai jos.
- **B. Cloudflare gratuit în fața site-ului.** Absoarbe atacurile de trafic și poate servi paginile chiar dacă serverul e căzut. Cere schimbarea serverelor de nume ale domeniului: **după prima lună**, cu plan de revenire, pentru că poate afecta confirmările NETOPIA și emailurile.

**Regula de aur, ca „niciun alt sistem automat” să nu blocheze site-ul:**
- Firewall-ul trebuie să lase să treacă **NETOPIA** (adresa de confirmare a plății și serverele lor), **Google** (Googlebot) și **clienții**.
- Nu pornim reguli agresive fără test: „blochează boții”, „blochează țări”, „modul sub atac” permanent.
- După **orice** schimbare de firewall: o plată de test, apoi o verificare în Search Console (Inspectare URL, test live). Dacă ceva pică, anulăm regula.
- Limitarea încercărilor de parolă la autentificare: din firewall (ideal) sau dintr-un plugin dedicat. Nu o punem în pluginul nostru: dacă în fața site-ului apare Cloudflare, toți vizitatorii ar părea că vin de la aceeași adresă și un atacator i-ar bloca pe toți.

**Cerere gata de trimis hostingului (Romarg):**
> Bună ziua. Pentru site-ul gradina-ielelor.ro (WordPress, LiteSpeed) vă rugăm să ne confirmați: (1) ce firewall/WAF (ModSecurity, Imunify360 sau alt sistem) este activ pe contul nostru; (2) dacă încercările repetate de autentificare la wp-login.php sunt limitate automat; (3) ce protecție aveți împotriva atacurilor de trafic (DDoS); (4) dacă puteți programa copii de siguranță zilnice complete, cu păstrare 7 zile; (5) că nu blocați confirmările de plată trimise de NETOPIA și accesul Googlebot. Mulțumim.

## Strat 4 · Copii de siguranță și revenire
- **Zilnic, automat**, complet (fișiere și bază de date), păstrate 7 zile (Softaculous sau hosting). Azi sunt făcute la mână.
- **Înainte de orice actualizare:** copie nouă.
- **Test de restaurare, o dată pe lună**, pe copia de test. O copie care nu s-a încercat nu e o copie.
- **O copie și în afara serverului** (Google Drive), o dată pe săptămână.
- **Revenirea la designul vechi (R1)** rămâne: dezactivezi pluginul, mai puțin de 5 minute.

## Strat 5 · Supraveghere
- **Alertă dacă site-ul cade:** un serviciu gratuit de monitorizare (de exemplu UptimeRobot), cu alertă pe telefon la 5 minute.
- **Search Console → Securitate și acțiuni manuale:** alertele ajung pe email.
- **Google Safe Browsing:** o dată pe lună, deschizi `transparencyreport.google.com/safe-browsing/search?url=gradina-ielelor.ro`. Trebuie să scrie „Nu s-au găsit probleme”.
- **Lunar:** lista conturilor de administrator (trebuie să fie doar al proprietarei) și lista pluginurilor (nimic nou necunoscut).

## Chei publice complicate și parole: unde le folosim
Cheile publice (perechi de chei: una publică, una privată care nu pleacă niciodată de la tine) sunt mai greu de spart decât orice parolă. Unde se poate, le preferăm:

| Unde | Ce folosim | Cine |
|---|---|---|
| Contul Google, GitHub | **Chei de acces (passkeys)**: telefonul sau calculatorul semnează cu o cheie privată, nu mai circulă nicio parolă. Parola rămâne ca rezervă, cu verificare în doi pași. | Proprietara |
| Administrarea WordPress | Autentificare în doi pași cu **passkey sau cheie de securitate** (WebAuthn), cu cod din aplicație ca rezervă. Plugin dedicat, testat întâi pe copia de test. | Claude propune, proprietara aprobă |
| Acces la hosting prin fișiere (SFTP/SSH) | **Chei SSH** (Ed25519 sau RSA de minimum 4096 biți), cu parola de acces oprită, dacă hostingul permite (cPanel → Acces SSH). Cheia privată rămâne pe calculatorul tău, protejată cu o frază lungă. | Proprietara, cu ghidare |
| NETOPIA | Perechea de chei (certificat public + cheie privată, RSA 2048 sau mai mult). **Cheia privată nu se pune niciodată în depozit, în chat sau în email.** Se notează data expirării certificatului în calendar: dacă expiră, plățile se opresc. | Proprietara |
| Emailul site-ului | **DKIM** (cheie publică de 2048 de biți în DNS) + SPF + DMARC: emailurile de confirmare ajung la clienți, iar altcineva nu poate trimite emailuri în numele domeniului. Se verifică la hosting și în Gmail („Afișează originalul”: toate trei trebuie să spună PASS). | Hosting + Claude verifică |
| Cheile secrete WordPress (cele 8 chei din `wp-config.php`) | Șiruri lungi, aleatorii, unice. **Se schimbă după orice incident** (toți utilizatorii sunt deconectați). Se generează de pe `api.wordpress.org/secret-key/1.1/salt/`. | Claude ghidează |
| Arhiva pluginului (release) | Amprenta SHA-256 e deja publicată. Opțional, mai târziu: **semnătură digitală** a arhivei, cu cheie publică publicată, ca să verifici că arhiva vine de la noi. | Claude, după decizie |
| Parolele rămase | Fraze lungi (4–5 cuvinte), **unice pe fiecare cont**, într-un manager de parole (Bitdefender sau altul). Niciodată în chat, email sau depozit. | Proprietara |

## Dacă se întâmplă un atac: primele 60 de minute

**Semne:** site-ul e foarte lent sau căzut; pagini cu text străin sau reclame; avertisment roșu în Chrome; un cont de administrator pe care nu-l cunoști; plugin nou necunoscut; emailuri de la hosting sau Google despre „conținut compromis”; comenzi sau plăți ciudate.

**În ordine, fără panică:**
1. **Nu șterge nimic.** Notează ora și ce ai văzut (captură de ecran).
2. **Scrie hostingului** (Romarg) imediat: „site-ul pare atacat, vă rugăm să verificați serverul”. Cere-le jurnalele.
3. **Scanează cu Bitdefender calculatorul și telefonul tale** (scanare completă). Abia apoi **schimbă parolele**, de pe un dispozitiv curat: administrare WordPress, hosting (cPanel/Softaculous), email, contul NETOPIA, Google, GitHub. Dacă parola a fost furată de pe un dispozitiv infectat, parolele noi ar fi furate din nou.
4. **Revenirea rapidă:** dacă problema e de la pluginul nostru, dezactivezi pluginul (R1). Dacă e de altundeva, ceri hostingului să pună site-ul în mentenanță.
5. **Restaurezi dintr-o copie curată**, de dinainte de incident, apoi schimbi din nou parolele și **cheile secrete WordPress** din `wp-config.php`. Verifici și aplicațiile: toate pluginurile la zi.
6. **Verifici conturile de administrator și pluginurile.** Ștergi tot ce nu cunoști.
7. **Anunți NETOPIA** dacă ai văzut plăți sau comenzi neobișnuite, și verifici tranzacțiile din ultimele zile.
8. **Search Console:** dacă Google a marcat site-ul, ceri o reverificare după reparare.
9. **Date personale:** dacă datele clienților au putut fi văzute de altcineva, legea (GDPR) cere anunțarea autorității (ANSPDCP) în cel mult 72 de ore. Cere-mi ajutorul și pentru textul anunțului.

**Pe cine suni:** hostingul (primul), NETOPIA (pentru plăți), Claude (analiza și repararea pluginului).

## Lista pe 30 de zile
| Când | Ce | Cine |
|---|---|---|
| 14–20.11 | Pluginul 1.3.10 testat și verificat | Claude |
| După aprobare | Instalare 1.3.10, verificare din afară | Proprietara + Claude |
| 15–20.11 | Cererea către hosting trimisă | Proprietara |
| 21.11 | Contul `a.min` închis | Proprietara |
| 28.11 | Contul `a.min` șters | Proprietara |
| 15–20.11 | **Strat 0:** scanare completă Bitdefender pe dispozitive, actualizări pornite, contul Google cu passkey sau doi pași | Proprietara |
| Noiembrie | Backup zilnic automat, monitorizare, 2FA pe WordPress | Proprietara, cu ghidare |
| Noiembrie | Aflăm de unde vine și cum se actualizează OVABRW (`s4p_ova-brw20`) | Proprietara, cu Claude |
| Decembrie | PHP 8.3, Elementor Pro, Elementor, WooCommerce: întâi pe copia de test, apoi pe live; ștergerea celor 16 module inactive | Claude + proprietara |
| Lunar, prima marți | Zi fixă de actualizări (aplicații, pluginuri, Bitdefender, sistem) | Claude propune, proprietara aprobă |
| Ianuarie | Decizia pentru Cloudflare | Proprietara |
