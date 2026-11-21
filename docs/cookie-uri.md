# Cookie-uri și măsurare (pluginul 1.9.0)

Cerere de la proprietară (21.11.2026): „implementează să afișeze ok”. Site-ul măsura vizitele (Google Analytics, iar pe paginile vechi și Facebook și TikTok) fără să ceară acordul vizitatorului. De la 1.9.0 apare un banner, iar măsurarea pornește numai după acord.

**Atenție:** textele bannerului, pagina „Cookies” și durata alegerii (180 de zile) sunt făcute după practica obișnuită și nu sunt verificate de un jurist. Rămân de arătat unui jurist (secțiunea 5).

## 1. Ce vede vizitatorul
- **Prima vizită, pe orice pagină** (și pe cele vechi): un banner mic, jos. Trei butoane: **Accept toate**, **Refuz** (la fel de mari și de vizibile) și **Alege**.
- **Alege** deschide trei rânduri: *Necesare* (mereu active), *Statistici* (Google Analytics), *Marketing* (reclame; azi nu rulează nimic aici). Se salvează cu **Salvează alegerea**.
- Alegerea se păstrează **180 de zile** într-un cookie propriu, `gi_consent`. Apoi se întreabă din nou.
- **Se poate schimba oricând:** linkul „Setări cookie-uri” din subsol, butonul mic rotund de jos, din stânga (pe toate paginile, după ce s-a ales), sau `[gi_cookie_setari]` pus într-o pagină (de exemplu în „Cookies”).
- Dacă retrage un acord: cookie-urile de măsurare (`_ga`, `_gid`, `_fbp` etc.) se șterg, iar pagina se reîncarcă fără ele.
- **Zona echipei** (cu utilizator și parolă) nu are banner și nu măsoară nimic.

## 2. Ce se întâmplă în spate
- Din prima clipă, înainte de orice script de măsurare, pagina spune Google: „totul refuzat” (Google Consent Mode: `analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`, `personalization_storage`; doar `functionality_storage` și `security_storage` rămân acordate, ca la orice site). Alegerea salvată se aplică imediat ce pagina se încarcă.
- **Google Tag Manager nu se încarcă** până când vizitatorul nu acceptă statistici sau marketing. Containerul e cel al asociației (`GTM-5XNZX5QT`), setat în Setări → Grădina Ielelor → *Cookie-uri și măsurare*.
- Bannerul se afișează din JavaScript, nu din PHP: paginile pot fi păstrate în memoria serverului (LiteSpeed), iar HTML-ul e același pentru toți vizitatorii.
- **Orice cod de măsurare scris direct în pagină se scoate** (Tag Manager, gtag, pixel Facebook, pixel TikTok: scripturile, imaginile și iframe-urile lor), pentru că ar rula fără acord. Asta include codul modulului vechi GTM4WP: chiar dacă uiți să-l dezactivezi, el nu mai poate încărca Tag Manager înainte de acord. Tag Manager pornește numai din scriptul bannerului, după acord, și numai o singură dată (dacă un container cu același cod e deja pornit, nu se mai încarcă a doua oară).
- **Atribuirea comenzilor WooCommerce** („de unde a venit clientul”, cookie-urile `sbjs_*`) se pune la statistici: scripturile ei se scot din pagină și pornesc din banner numai după acord pentru statistici, cu aceleași date. Fără acord, comenzile nu mai păstrează sursa vizitatorului. Oprire: filtrul `gi_keep_order_attribution` (nu se recomandă).
- **Cookie-urile de măsurare ale unei categorii neacordate se șterg la prima pagină** (și cele rămase de la vizite mai vechi, de dinainte de 1.9.0): statistici = `_ga*`, `_gid`, `_gat*`, `__utm*`, `sbjs_*`; marketing = `_gcl_*`, `_gac_*`, `_fbp`, `_fbc`, `_ttp`, `_tt_*`, `ttclid`. Se șterge numai ce nu s-a acordat (cine retrage doar marketingul își păstrează `_ga`).
- **Memoria serverului (LiteSpeed):** bannerul și codul containerului stau în pagini păstrate de server. La fiecare schimbare reală a setărilor și la prima cerere după o actualizare a pluginului se cere ștergerea ei (`litespeed_purge_all`); dacă un cache nu o primește, se șterge de mână din administrare.
- **Plasa de siguranță:** scriptele cu ID-urile fostului administrator (pixel Facebook `371463157145157`, pixel TikTok `D7P8DJBC77U3CRQJR7P0`, Google Analytics `G-T0Y2TVCYBD`, Tag Manager `GTM-T5L2SC7K`) se scot din toate paginile, indiferent de acord. Vizitele nu trebuie să ajungă în conturi care nu sunt ale asociației. Lista se poate completa din cod, cu filtrul `gi_blocked_tracker_ids`. Când scriptele vechi se scot și din sursă (Module / temă / Elementor), plasa nu mai are ce face.

## 3. Instalarea pe live (proprietara, pas cu pas; numai după „da” al ei)
1. **Copie de siguranță** (Softaculous → Backup), ca la fiecare versiune.
2. Încarcă arhiva `gradina-ielelor-integration-1.9.0.zip` (WordPress → Module → Adaugă modul → Încarcă modul). Dacă dă „403 Forbidden”, vezi alternativa prin cPanel din `docs/lansare.md`.
3. **Setări → Grădina Ielelor → Cookie-uri și măsurare:** trebuie să scrie codul `GTM-5XNZX5QT`. Dacă apare „✗ încă activ” la GTM4WP, **dezactivează GTM4WP** (Module → Module instalate → GTM4WP → Dezactivează; nu-l șterge încă). Codul lui din pagină se scoate oricum de banner, dar nu mai are rost să rămână.
4. **Verificare într-o fereastră privată:** deschide `gradina-ielelor.ro`. Trebuie să apară bannerul. Apasă **Accept toate**, apoi în Google Analytics → Rapoarte → Timp real trebuie să apară vizita ta. După **Refuz** nu trebuie să apară nimic.
5. **Claude verifică din afară** (numai citire): codul Tag Manager nu apare în HTML-ul paginilor, apare bannerul, codurile vechi sunt scoase.
6. **Revenire (dacă ceva nu merge):** Setări → Grădina Ielelor → debifează „Afișează bannerul de cookie-uri” (bannerul dispare, Tag Manager nu se mai încarcă de aici) și reactivează GTM4WP; sau instalează din nou arhiva 1.8.0 din Release-ul `v1.8.0`.

Important: **după 1.9.0, Analytics măsoară numai vizitatorii care acceptă.** Numerele vor fi mai mici decât înainte. Așa e corect.

## 4. Inventarul cookie-urilor (de confirmat)
Lista de mai jos e făcută din ce se știe din cod și din paginile publice; **trebuie verificată** în browser (F12 → Application → Cookies) pe site-ul live și completată, înainte de a o da juristului.

| Cookie | Rol | Categorie |
|---|---|---|
| `gi_consent` | alegerea ta de aici (180 de zile) | necesar |
| `woocommerce_*`, `wp_woocommerce_session_*` | coșul și rezervarea | necesar |
| `wordpress_*`, `wordpress_logged_in_*` | autentificarea (clienți și echipă) | necesar |
| `googtrans` (GTranslate) | limba aleasă | necesar / preferințe (de hotărât) |
| `sbjs_*` (WooCommerce, atribuirea comenzilor) | de unde a venit clientul (sursă, campanie, pagina de intrare) | statistici (numai după acord) |
| `_ga`, `_ga_*` (prin Tag Manager, după acord) | statistici | statistici |
| `_fbp`, `_ttp` și altele (numai dacă se adaugă în Tag Manager) | reclame | marketing |

## 5. Ce trebuie văzut de un jurist
1. Textele bannerului și ale rândurilor din „Alege”.
2. **Pagina „Cookies”** (`info-util/politica-cookies`): e un text general, mai vechi, care vorbește de personalizarea reclamelor și de „partenerii de rețele sociale și de publicitate”. Trebuie pusă de acord cu ce folosește site-ul acum (Google Analytics, după acord).
3. Durata de 180 de zile și faptul că după „Refuz” nu se mai întreabă 180 de zile.
4. Transferul datelor către Google (Analytics) și textul de informare (politica de confidențialitate).
5. Modul de măsurare: Tag Manager nu se încarcă deloc înainte de acord („varianta strictă”).
6. **GTranslate** (comutatorul de limbă) încarcă un script de la un server al lui (`cdn.gtranslate.net`) și poate pune cookie-ul `googtrans` înainte de orice alegere. Nu e blocat de banner. De hotărât: necesar (funcție cerută de vizitator) sau nu, și ce se spune în politică.
7. **Safari** limitează la 7 zile cookie-urile scrise din JavaScript: acolo alegerea nu ține 180 de zile, ci cel mult 7.

## 6. Pentru cine modifică codul
- Fișiere: `includes/class-gi-consent.php`, `assets/js/consent.js`, `assets/css/consent.css`; link în `templates/parts/footer.php`; opțiunile `consent_enabled` și `gtm_id` în `includes/template-tags.php` și `includes/class-gi-settings.php`.
- Când se schimbă categoriile sau sensul lor: crește `GI_Consent::VERSION`; toți vizitatorii sunt întrebați din nou.
- Test: `tools/local-wp/test-1-9-0.js` (cu `tools/local-wp/mu-plugins/gi-test-1-9-0.php`).
- Cererile către Google, Facebook și TikTok nu se pot testa din mediul de lucru (rețeaua e blocată): testul interceptează cererile și verifică că NU pleacă înainte de acord și că pleacă după. Comportamentul real al containerului se verifică în Tag Manager → Previzualizați.
