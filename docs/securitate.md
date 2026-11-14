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
| **Pluginuri vechi** | **WooCommerce 8.7.3 și Elementor 3.20.2 sunt din 2024** | **Cel mai mare risc.** Strat 2, mai jos. |
| Firewall / protecție la atac în fața site-ului | **Neconfirmat**: serverul e LiteSpeed, nu am văzut CDN sau firewall | Strat 3. |
| Limitarea încercărilor de parolă | **Neconfirmată** (nu fac teste de forță brută pe site-ul tău) | Strat 3. |
| Copii de siguranță | Făcute la mână (Softaculous), numai de bază de date | Strat 4. |

## Strat 1 · Pluginul (Claude) — versiunea 1.3.10
Testat local (24 de verificări), verificat de un agent separat, instalat numai cu aprobarea proprietarei.
- **Antete de protecție** pe paginile publice: site-ul nu poate fi încadrat în alt site (anti-„clickjacking”), adresele paginilor nu se scurg către alte site-uri, camera, microfonul și locația sunt oprite, iar vizitatorii care revin sunt duși mereu pe HTTPS (HSTS, 30 de zile la început, fără subdomenii).
- **Conturile nu se mai văd din afară:** lista de utilizatori (REST), paginile de autor și oEmbed. Administratorii văd în continuare tot.
- **Autentificare greșită:** același mesaj, „Datele de autentificare nu sunt corecte”, fie că numele nu există, fie că parola e greșită (WordPress și WooCommerce).
- **Versiunea WordPress ascunsă** din cod. Editorul de fișiere din administrare oprit: cine ajunge într-un cont nu poate scrie cod PHP de acolo.
- **Nu atinge** paginile, rezervările, plățile și confirmările NETOPIA.
- **Revenire:** dezactivezi pluginul (mai puțin de 5 minute). Fiecare măsură are și un filtru de oprire (`gi_security_*`).

## Strat 2 · Conturi și actualizări (proprietara, cu Claude)
**Conturi**
1. Rămâne **un singur cont de administrator**, al proprietarei. Contul `a.min` se închide pe 21.11 și se șterge pe 28.11 (planul existent din `docs/lansare.md`).
2. **Parolă lungă și unică** (frază de 4–5 cuvinte), într-un manager de parole, nu în email.
3. **Autentificare în doi pași** pentru administratori (cod din telefon). Se instalează un plugin dedicat, de exemplu „WP 2FA”, după testare pe copia de test.
4. Conturile clienților (create la plată) nu au drepturi de administrare.

**Actualizări** (cel mai important lucru din această listă)
WooCommerce 8.7.3 și Elementor 3.20.2 sunt mai vechi de doi ani. Pluginurile vechi sunt calea cea mai frecventă prin care sunt sparte site-urile. Dar OVABRW (rezervările) și NETOPIA depind de WooCommerce, deci **nu actualizăm direct pe live**:
1. Backup complet (fișiere și baza de date) înainte de orice.
2. Actualizare pe **copia de test** (`asociatia-ropam.ro/gi-test`), nu pe live.
3. Test complet pe copie: rezervare, calendar, plată sandbox, email, regulament la plată.
4. Verificăm compatibilitatea OVABRW 2.0.0 și NETOPIA 1.4.4 cu noul WooCommerce în notele lor de versiune. Dacă nu sunt compatibile, actualizăm întâi pe acelea, sau întrebăm furnizorii.
5. Pe live, într-o zi cu puține rezervări, cu backup făcut, cu revenirea pregătită.
6. Ordinea: WordPress (e la zi) → Elementor → WooCommerce → pluginurile mici.

Pluginuri nefolosite: se dezactivează și se șterg (de ex. produsul de test, „Advanced Coupons” dacă nu se folosește, plata iPay BT oprită).

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

## Dacă se întâmplă un atac: primele 60 de minute

**Semne:** site-ul e foarte lent sau căzut; pagini cu text străin sau reclame; avertisment roșu în Chrome; un cont de administrator pe care nu-l cunoști; plugin nou necunoscut; emailuri de la hosting sau Google despre „conținut compromis”; comenzi sau plăți ciudate.

**În ordine, fără panică:**
1. **Nu șterge nimic.** Notează ora și ce ai văzut (captură de ecran).
2. **Scrie hostingului** (Romarg) imediat: „site-ul pare atacat, vă rugăm să verificați serverul”. Cere-le jurnalele.
3. **Schimbă parolele**, de pe un calculator curat: administrare WordPress, hosting (cPanel/Softaculous), email, contul NETOPIA, contul Google Search Console.
4. **Revenirea rapidă:** dacă problema e de la pluginul nostru, dezactivezi pluginul (R1). Dacă e de altundeva, ceri hostingului să pună site-ul în mentenanță.
5. **Restaurezi dintr-o copie curată**, de dinainte de incident, apoi schimbi din nou parolele.
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
| Noiembrie | Backup zilnic automat, monitorizare, 2FA | Proprietara, cu ghidare |
| Decembrie | Actualizări Elementor și WooCommerce, întâi pe copia de test | Claude + proprietara |
| Ianuarie | Decizia pentru Cloudflare | Proprietara |
