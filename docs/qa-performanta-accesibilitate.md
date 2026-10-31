# Viteză și accesibilitate: verificarea site-ului nou

Data: 31.10.2026. Ramura `site-nou`, pluginul 1.2.0. Verificare făcută de agentul QA (Claude), pe WordPress-ul local.

## 1. Pe scurt, pentru tine

**Ce am verificat.** Pagina principală și cele cinci pagini noi (Bungalow, Glamping, Camping, Rulote, Festivaluri & evenimente), pe telefon și pe calculator:
- cât de repede apare conținutul pe un telefon obișnuit, pe internet mobil lent;
- dacă pagina „sare” în timp ce se încarcă;
- dacă site-ul se poate folosi doar cu tastatura (fără mouse) și dacă se vede mereu unde ești;
- dacă textele se citesc ușor, inclusiv cele scrise peste fotografii;
- dacă butoanele sunt destul de mari pentru deget.

**Rezultatul, comparat cu site-ul vechi.**

| | Site-ul vechi (live) | Site-ul nou, acum | Site-ul nou, după cei 2 pași de mai jos |
|---|---|---|---|
| Scor viteză pe telefon (din 100) | 38 | 86–95 (prima pagină 95) | 98–99 (paginile ofertelor) |
| Până apare conținutul principal | 4,5 s | 2,86–4,20 s | ~2–2,5 s |
| Telefonul „blocat” de scripturi | 6,5 s | sub 0,1 s | sub 0,1 s |
| Pagina sare în timpul încărcării | — | deloc (0) | deloc (0) |
| Scor accesibilitate (din 100) | — | 100 pe toate paginile (înainte de reparații: 91–96) | 100 |

Pe scurt: paginile noi sunt de câteva ori mai ușoare decât cele vechi. Telefonul nu mai „îngheață”, iar pagina nu mai sare. Cel mai mult contează acum două lucruri. Primul: pozele mari din capul paginilor trebuie să aibă și o variantă mică pentru telefon. Al doilea: pe paginile ofertelor nu trebuie încărcate fișierele magazinului WooCommerce, care acolo nu sunt folosite.

**Ce am reparat:**
- Între ~900 și ~1000 px lățime (tabletă, laptop mic) meniul de sus ieșea din ecran și butonul „Rezervă” era tăiat. Acum încape.
- Cine folosește tastatura vede acum mereu unde se află: conturul e verde pe fundal deschis și piersică pe fundal închis. Înainte nu se vedea deloc în header și în subsol.
- Textele mici de pe fotografii (ex. „Acasă / Glamping”, „Sâmbăta de Sus · Brașov”, meniul) se citesc acum și pe pozele luminoase (cer, apă).
- Câteva texte gri sau maro deschis erau prea palide (subsol, prețurile de pe carduri, etichetele din calendar). Le-am închis puțin la culoare.
- Butoanele și linkurile de pe telefon au acum cel puțin 44 px: butonul de meniu, linkurile din subsol, „Festivaluri & petreceri”.
- Butonul fix „Rezervă cazare” de pe telefon nu mai acoperă formularul de rezervare de pe prima pagină. Nici elementul pe care ești cu tastatura nu mai rămâne sub el.
- Meniul de pe telefon se închide cu tasta Esc. Galeria foto ține tastatura în interiorul ei cât e deschisă.
- Butonul „Rezervă” de pe paginile ofertelor duce la formular, cu oferta aleasă o singură dată. La reîncărcare sau la „Înapoi” nu mai schimbă alegerea făcută între timp.
- Pe telefon, prima pagină se pregătește din timp, cât citești pagina ofertei. Așa, „Rezervă” se deschide aproape instant.
- Fotografia nu mai „zboară” spre un card care nu e pe ecran când treci de la o pagină la alta.
- Fotografiile de pe prima pagină care nu sunt cea principală se încarcă după ea.

**Ce rămâne de făcut (și cine):**

| Ce | Cine | De ce contează |
|---|---|---|
| Pe paginile ofertelor să nu se mai încarce fișierele magazinului (WooCommerce) și jQuery | Claude (codul pluginului, alt fișier decât ale mele; codul e gata, secțiunea 5) | Conținutul apare cu ~1 s mai repede pe telefon |
| Variante mici (640 și 960 px) pentru pozele mari din capul paginilor și pentru cardurile de pe prima pagină | Claude (agentul de poze) | Telefonul descarcă ~60–75 KB în loc de 160–190 KB pentru poza principală. Pagina le folosește singură când apar |
| **LoftLoader** (ecranul de încărcare): îl oprești | Tu, în WordPress → Module | Ascunde pagina până se încarcă tot și anulează câștigul de viteză |
| Cache de pagină **LiteSpeed Cache**: îl pornești pe live (fără coș, checkout, cont) | Tu, cu Claude | Azi serverul răspunde în ~3 s la prima pagină. Cu cache: sub 0,3 s |
| Măsurătoarea pe **staging**, înainte de lansare | Claude | Pe live rămân tema veche și modulele vechi. Trebuie văzut ce încarcă ele pe paginile noi |
| Modulele vechi pe care site-ul nou nu le mai folosește (Elementor, tema veche, LoftLoader): pe staging, verificat ce încarcă pe paginile noi | Tu + Claude, după lansare (mutarea pe WordPress curat) | Fiecare modul adaugă fișiere pe fiecare pagină |
| GTranslate: rămâne (vezi `docs/seo-migrare.md`), dar fără comutare automată | Tu | Adaugă un script mic. Nu blochează pagina, dar se verifică pe staging |
| Google Analytics apare de două ori pe site-ul vechi (același cod G-…) | Tu + Claude, pe staging | Vizitele se numără dublu și se încarcă un script în plus |

**Atenție la lansare.** Lansarea instalează pluginul pe WordPress-ul existent, care are încă tema veche și multe module: Elementor, Woodmart, LoftLoader, GTranslate, Google Tag Manager și altele. Prima pagină veche încarcă azi 102 scripturi și 69 de fișiere de stil. Măsurătorile din acest document sunt făcute pe un WordPress curat. Dacă modulele vechi își încarcă fișierele și pe paginile noi, câștigul scade. De aceea, înainte de lansare, Claude repetă aceeași măsurătoare pe staging (secțiunea 6).

---

## 2. Cum am măsurat (tehnic)

- **Lighthouse 12.8.2**, profil mobil implicit: Moto G Power emulat, 412 px, rețea 4G lentă simulată (150 ms RTT, 1,6 Mbps), CPU ×4. O rulare pe pagină. Chromium 153 (Playwright).
- **Două medii:**
  - **A. Direct pe WordPress Playground** (`127.0.0.1:9400`): PHP-WASM, fără compresie gzip. Timpul de răspuns al serverului e de 1,7–3 s, deci **nu e reprezentativ**. Scorul acestui mediu e tras în jos de server, nu de pagină.
  - **B. „Laborator front-end”** (`127.0.0.1:9401`): un proxy local (`scratchpad/qa/tools/proxy.js`, nu e în depozit). Servește același HTML din memorie (ca un cache de pagină), cu gzip și antete de cache pentru fișiere. Pozele sunt cele reale din `assets/images/`. Simulează un server live configurat corect. **Pe acest mediu se judecă paginile.**
- **axe-core 4.14** (reguli WCAG 2.0/2.1/2.2 A+AA + best practices) pe fiecare pagină la 390 px și 1440 px. Pe telefon încă două stări: meniul deschis și galeria deschisă.
- **Verificări manuale automatizate (Playwright):**
  - linkul „Sari la conținut”, meniul (Enter / Tab / Esc), întrebările frecvente (`details`: Enter / Space), galeria (focus, Tab blocat în dialog, Esc, întoarcerea focusului);
  - focusul vizibil pe fiecare element din ordinea Tab, cu contrastul conturului (inclusiv măsurat pe pixeli peste fotografii);
  - contrastul textului din hero măsurat pe pixelii reali ai fotografiei (percentila 5, fără umbra textului);
  - zonele de atingere sub 44 px;
  - `prefers-reduced-motion`, imaginile fără `width/height` și lazy-load;
  - butonul fix de rezervare (acoperă conținut sau focusul?), scroll orizontal la 360, 390, 901, 960, 1005, 1150 și 1440 px.
- **Tranziții și preîncărcare**, verificate prin evenimentele CDP `Preload.*` și prin `pageswap`/`pagereveal`. Prerender-ul nu se poate testa sub automatizare (Chrome îl oprește când DevTools e atașat), dar prefetch-ul și declanșarea regulilor da.
- **CSS nefolosit**: acoperirea CSS din Chrome, cumulată pe 390 + 1440 px, cu derulare, meniu și galerie deschise.

## 3. Scoruri înainte / după (Lighthouse mobil)

„Înainte” = codul de la începutul verificării, cu pozele reale. „După” = codul cu reparațiile mele. P = performanță, A = accesibilitate. Best Practices: 100 peste tot, înainte și după.

| Pagina | Mediu | P înainte → după | A înainte → după | LCP înainte → după | FCP după | TBT înainte → după | CLS | Octeți după |
|---|---|---|---|---|---|---|---|---|
| `/` | B. laborator (server corect) | 91 → **95** | 91 → **100** | 3,38 s → 2,86 s | 1,51 s | 76 → 0 ms | 0 | 949 KB |
| `/bungalow/` | B. laborator (server corect) | 95 → **94** | 96 → **100** | 2,86 s → 3,08 s | 1,55 s | 14 → 29 ms | 0 | 390 KB |
| `/glamping/` | B. laborator (server corect) | 90 → **90** | 96 → **100** | 3,53 s → 3,61 s | 1,55 s | 64 → 36 ms | 0 | 549 KB |
| `/camping/` | B. laborator (server corect) | 85 → **86** | 96 → **100** | 4,06 s → 4,20 s | 1,50 s | 124 → 18 ms | 0 | 756 KB |
| `/rulote/` | B. laborator (server corect) | 90 → **88** | 96 → **100** | 3,53 s → 3,90 s | 1,55 s | 36 → 41 ms | 0 | 638 KB |
| `/evenimente/` | B. laborator (server corect) | 88 → **90** | 95 → **100** | 3,64 s → 3,61 s | 1,51 s | 129 → 74 ms | 0 | 519 KB |
| `/` | A. Playground direct | 74 → **74** | 91 → **100** | 5,00 s → 4,81 s | 3,01 s | 7 → 2 ms | 0 | 1258 KB |
| `/bungalow/` | A. Playground direct | 81 → **79** | 96 → **100** | 3,86 s → 4,07 s | 3,07 s | 9 → 0 ms | 0 | 754 KB |
| `/glamping/` | A. Playground direct | 75 → **74** | 96 → **100** | 4,82 s → 5,11 s | 2,97 s | 6 → 0 ms | 0 | 913 KB |
| `/camping/` | A. Playground direct | 72 → **72** | 96 → **100** | 5,50 s → 5,72 s | 3,03 s | 0 → 18 ms | 0 | 1121 KB |
| `/rulote/` | A. Playground direct | 74 → **78** | 96 → **100** | 4,97 s → 4,08 s | 3,06 s | 20 → 19 ms | 0 | 1003 KB |
| `/evenimente/` | A. Playground direct | 74 → **73** | 95 → **100** | 5,12 s → 5,26 s | 2,97 s | 0 → 2 ms | 0 | 834 KB |

**Experimentul „ce-ar fi dacă”** (laborator B, aceleași pagini, cu cei doi pași din secțiunea 5 simulați de proxy: fără CSS/JS WooCommerce și jQuery pe paginile ofertelor + variante 640/960 px pentru poza din hero):

| Pagina | P după (acum) → experiment | LCP după → experiment | FCP după → experiment | Octeți după → experiment |
|---|---|---|---|---|
| `/bungalow/` | 94 → **99** | 3,08 s → **1,95 s** | 1,55 s → 0,90 s | 390 → 273 KB |
| `/glamping/` | 90 → **98** | 3,61 s → **2,33 s** | 1,55 s → 1,05 s | 549 → 382 KB |
| `/camping/` | 86 → **98** | 4,20 s → **2,48 s** | 1,50 s → 1,06 s | 756 → 591 KB |
| `/rulote/` | 88 → **98** | 3,90 s → **2,40 s** | 1,55 s → 1,05 s | 638 → 477 KB |
| `/evenimente/` | 90 → **98** | 3,61 s → **2,33 s** | 1,51 s → 1,05 s | 519 → 345 KB |

Notă: diferențele de ±2–4 puncte între două rulări sunt normale (o singură rulare pe pagină). În timpul verificării, alți agenți au schimbat conținutul (ex. Rulote are o poză nouă în galerie, iar pozele au fost re-comprimate), deci octeții „înainte” și „după” nu sunt identici. Pe paginile ofertelor, LCP-ul e dominat de două lucruri pe care nu le pot schimba din fișierele mele. Primul: 8 fișiere care blochează afișarea (4 CSS WooCommerce, `jquery.min.js`, `jquery-migrate.min.js`, `site.css` și `pages.css`). Al doilea: poza de 1600 px. De aceea „după” iese aproape egal cu „înainte” la performanță. Câștigul se vede în experiment.

## 4. Probleme găsite și reparate

| # | Problemă | Criteriu | Pagini | Reparație | Fișier |
|---|---|---|---|---|---|
| 1 | Scroll orizontal 901–1005 px și „Rezervă” tăiat (până la 105 px în afara ecranului) | WCAG 1.4.10, fără scroll orizontal | dedicate | Meniul mare (13,5 px) și butonul mare doar la ≥1151 px; între 901 și 1150 rămân dimensiunile compacte din site.css. Verificat la 901/960/1005/1100/1150/1151/1180/1240: 0 px | `pages.css` |
| 2 | Focus invizibil în header și subsol (contur verde pe fundal închis) | 2.4.7, 1.4.11 | dedicate | Sistem comun `--gi-focus` pe `.gi-site`: verde pe fundal deschis; piersică în skip-link, header, hero, subsol, banda de prețuri, închidere; verde închis în meniul mobil și pe butonul fix | `pages.css` |
| 3 | Focus slab pe prima pagină (piersică pe hârtie/alb ≈ 1,75:1); câmpurile BRW cu umbră de 12% | 2.4.7, 1.4.11 | / | Același sistem + contur verde plin pe câmpurile BRW; cardurile cu contur verde | `pages.css`, `home.css` |
| 4 | Contrast insuficient: supratitluri moss (3,7–4,0:1), prețul de pe carduri (4,43:1), subsolul (3,75:1 la 10 px), etichetele calendarului (4,36:1), numărătorul din galerie (4,36:1), pagina curentă în meniul mobil (1,75:1) | 1.4.3 | toate | Nuanțe mai închise (`#55704c`, `#985c33`), subsolul alb 72% la 12 px; pagina curentă în meniul mobil verde și subliniată | `pages.css`, `home.css` |
| 5 | Text pe fotografie sub prag: „Acasă / …” până la 1,9:1, „Sâmbăta de Sus” 2,6:1, meniul 2,6–4,4:1, „devine acasă.” 2,2:1 pe telefon, titlul de la Festivaluri 2,7:1 | 1.4.3 | toate | O umbră care urmează blocul de text din hero (nu un procent fix din poză); header puțin mai dens; pagina curentă în meniu albă cu subliniere piersică (nu doar culoare, 1.4.1); umbra din hero-ul primei pagini urcă mai sus. Măsurat după: toate ≥4,5:1 (text mic) și ≥3:1 (titluri) | `pages.css`, `home.css` |
| 6 | Pagina curentă marcată doar prin culoare | 1.4.1 | toate | Subliniere în meniu (desktop + mobil) | `pages.css` |
| 7 | Zone de atingere sub 44 px: butonul de meniu (41 px), linkurile din subsol (18 px), linkurile legale (32 px), „Festivaluri & petreceri” (24 px) | 2.5.8 (24 px) / țintă 44 px | toate | `min-width/min-height: 44px`; pentru linkul din hero, zonă extinsă cu `::after`, fără să mute sublinierea | `pages.css`, `home.css` |
| 8 | Butonul fix „Rezervă” acoperea elementul primit cu Tab (formularul, cardurile, subsolul) și formularul de pe prima pagină | 2.4.11 (WCAG 2.2) | / și dedicate | `scroll-padding-bottom: 84px` pe telefon; pe prima pagină butonul se ascunde cât sunt pe ecran hero-ul, formularul sau subsolul (ca pe paginile dedicate) | `pages.css`, `home.css`, `site.js` |
| 9 | Butonul fix în afara oricărui reper (landmark) | best practice `region` | toate | `<nav aria-label="Rezervare rapidă">` | `parts/footer.php` |
| 10 | Rândul de recenzii derulabil pe telefon, dar fără element focusabil | `scrollable-region-focusable` | dedicate | `role="group" aria-label="Recenzii" tabindex="0"` | `offer.php` |
| 11 | `aria-label` pe `div` fără rol (hero-ul primei pagini) | `aria-prohibited-attr` | / | `role="group"` | `one-page.php` |
| 12 | Alt dublat pe cardurile ofertelor (linkul are deja numele ofertei) | 1.1.1 / redundanță | / | `alt=""` | `one-page.php` |
| 13 | Câmpurile BRW fără nume accesibil (etichete fără `for`) | 1.3.1, 4.1.2 (`select-name`, critic) | / | `site.js` leagă eticheta vizibilă de câmp doar dacă pluginul nu a făcut-o. Nu schimbă valori sau trimiterea | `site.js` |
| 14 | Meniul mobil nu se închidea cu Esc | 2.1.1 (bună practică) | toate | Esc închide și întoarce focusul pe buton | `site.js` |
| 15 | Galeria: restul paginii rămânea accesibil cititoarelor de ecran | 2.4.3 / dialog modal | dedicate | `inert` pe `.site-shell` cât e deschisă | `site.js` |
| 16 | `#rezervare-<tip>` reaplica oferta și derula la fiecare reîncărcare sau „Înapoi” | comportament | / | După aplicare: `history.replaceState(..., '#rezervare')`. În prerender se curăță la `prerenderingchange` | `site.js` |
| 17 | Fotografia zbura spre un card din afara ecranului (/glamping/ → /, / → oferta din meniu) | View Transitions | / și dedicate | `pageswap` pe prima pagină: cardurile din afara ecranului pierd `view-transition-name`; `pagereveal` la sosire (sigur la paginile preîncărcate); pe paginile dedicate, la navigarea nouă (nu „Înapoi”) hero-ul pierde numele. Verificat: push → `none`, traverse → păstrat, card vizibil → păstrat | `site.js`, `pages.js` |
| 18 | Preîncărcarea „la hover” nu pornește pe telefon (nu există hover); verificat: nimic nu se preîncarcă înainte de atingere | Speculation Rules | dedicate | Regulă suplimentară `prefetch` „immediate” numai pentru `[data-gi-book]` (un singur document HTML, fără scripturi, deci fără statistici umflate); doar unde WordPress are regulile active (nu pentru utilizatorii autentificați). Verificat: prefetch în ~0,2 s, folosit la atingere | `parts/footer.php` |
| 19 | Pozele secundare din hero-ul primei pagini concurau cu poza principală (LCP) | LCP | / | `fetchpriority="low" decoding="async"` pe cele două felii mici | `one-page.php` |
| 20 | Hero-ul paginilor dedicate fără `srcset` | LCP, octeți | dedicate | Ajutor `$gi_srcset` în `offer.php`: adaugă `srcset/sizes` din variantele existente pe disc (`nume-640/960/1280/1600.webp`, cu lățimea reală citită din fișier). Azi se aplică pozelor de închidere de la Bungalow și Rulote. Hero-ul îl primește automat când apar variantele | `offer.php` |
| 21 | `aria-current` lipsea pe Despre noi / Regulament și în subsol | 4.1.2 / orientare | toate | Comparare cu adresa paginii afișate | `parts/header.php`, `parts/footer.php` |

Verificate și **în regulă**:
- linkul „Sari la conținut” e primul la Tab, vizibil, și duce în `main`;
- `details`: Enter și Space funcționează;
- galeria: focus pe „Închide”, Tab nu iese din dialog (0/8), Esc închide, focusul revine pe poză;
- `prefers-reduced-motion`: fără animații, fără tranziții de pagină, fără derulare lină;
- CLS 0 pe toate paginile: toate pozele au `width`/`height`, iar pozele de sub ecran sunt `loading="lazy"`;
- o singură poză cu `fetchpriority="high"` pe pagină, cea din hero.

## 5. Ce rămâne (tehnic)

### 5.1 Fișiere WooCommerce pe paginile ofertelor (`class-gi-plugin.php`, alt agent)
Pe paginile dedicate se încarcă, fără să fie folosite:
- CSS WooCommerce 0–4% folosit: `woocommerce.css` 77 KB, `woocommerce-layout.css` 18 KB, `woocommerce-smallscreen.css` 7 KB, `woocommerce-blocktheme.css` 9 KB, `wc-blocks.css` 13 KB; ≈124 KB brut, ≈18 KB gzip;
- `jquery.min.js` + `jquery-migrate.min.js` în `<head>` (101 KB brut, 35 KB gzip), plus `add-to-cart`, `woocommerce`, `js.cookie`, `blockUI`.

Toate blochează prima afișare. Propunere, de testat pe staging:

```php
// În GI_Plugin::init():
add_action('wp_enqueue_scripts', array(__CLASS__, 'trim_dedicated_assets'), 999);
add_action('wp_footer', array(__CLASS__, 'trim_dedicated_assets'), 1); // wc-blocks-style se adaugă târziu

public static function trim_dedicated_assets() {
    if (!gi_is_dedicated_page()) {
        return; // Pe prima pagină rămân: formularul BRW are nevoie de jQuery și de stilurile lui.
    }
    foreach (array('woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general', 'woocommerce-blocktheme', 'wc-blocks-style') as $handle) {
        wp_dequeue_style($handle);
    }
    foreach (array('wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'js-cookie', 'jquery-blockui') as $handle) {
        wp_dequeue_script($handle);
    }
    // NU se scot sourcebuster-js și wc-order-attribution: rețin sursa comenzii (Google, Facebook, UTM)
    // când vizitatorul intră direct pe o pagină de ofertă. Nu depind de jQuery (verificat în WC 8.7).
}
```

Pe live, cu tema veche activă, lista e mult mai lungă: `bootstrap`, `woodmart-style`, `wd-*`, `elementor-*`, `hoteller-elementor*`, `loftloader-lite-animation`, `ovabrw-*` pe paginile fără formular. Pe staging trebuie făcută lista reală a ce se încarcă pe paginile noi. Apoi se scoate tot ce nu e `gi-*`, WooCommerce-ul necesar pe prima pagină (BRW), atribuirea comenzii și Tag Manager / consimțământul cookie.

Efect măsurat în laborator: vezi experimentul din secțiunea 3 (P 98–99, LCP ~2,0–2,5 s pe paginile ofertelor).

### 5.2 Imagini (agentul de poze)
- **Hero-urile paginilor dedicate** sunt doar la 1600 px (`super-glamping`, `bungalow-detail`, `camping-hero`, `rulote-hero`, `evenimente-hero`; 100–190 KB). Pe un telefon ajung ~640–960 px. Variantele `-640.webp` și `-960.webp` (în experiment: 22–38 KB și 46–76 KB) se folosesc automat: `offer.php` le detectează și scrie `srcset`. Trebuie trecute și în `binary-manifest.tsv`.
- **Cardurile de pe prima pagină**: 5 poze de 960–1600 px, ≈640 KB, afișate la ~140 px (telefon) sau ~250 px (desktop). Sunt lazy, dar pe telefon intră în fereastra de preîncărcare și concurează cu poza principală pe rețele lente. Nevoie: `-480.webp`. Apoi `srcset` în `one-page.php`, cu imaginea din `gi_offer_cards()`.
- **Galeriile** sunt randate de `gi_render_gallery()` din `template-tags.php`, unde nu am drept să scriu. Se pot aplica aceleași variante și același `srcset`.
- `home-hero-lake-centered-mobile-960.webp` are de fapt 816 px lățime, iar `srcset`-ul din `one-page.php` declară `960w`. Diferența e mică (o mărire de ~15%): se corectează fie fișierul, fie descriptorul.
- **Logo-ul** din header (`logo-gradina-ielelor-white.png`, 27 KB, 450 px) se afișează la ~125–142 px și se încarcă înaintea pozei principale. O variantă SVG sau un WebP de ~300 px ar avea 3–6 KB. `gi_brand()` e în `template-tags.php`.

### 5.3 Alte lucruri în fișiere care nu sunt ale mele
- `template-tags.php`, `gi_render_gallery()`: `<div class="home-photo-gallery" aria-label=…>` are nevoie de `role="group"` (axe: `aria-prohibited-attr`, „incomplete” pe toate paginile dedicate).
- `site.css` (comun, nu l-am rescris): pe paginile dedicate se folosește doar 34–37% din el (≈16 KB brut / ≈3,7 KB gzip nefolosiți); pe prima pagină 44%. Gzip-ul îl face mic (5,8 KB), așa că nu merită împărțit acum. `pages.css` (7 KB gzip) se încarcă și pe prima pagină, unde se folosește puțin: tranzițiile, focusul și contrastul comun.
- Formularul BRW real (OVABRW) **nu e instalat local**: am testat pe o imitație. Pe staging trebuie verificate:
  - etichetele câmpurilor (`site.js` le leagă dacă lipsesc);
  - focusul vizibil;
  - butonul „Caută” (≥44 px);
  - greutatea JS/CSS adusă de OVABRW pe prima pagină: pe live, `ovabrw-*` înseamnă 15+ fișiere CSS, fonturi Google și Fancybox.

### 5.4 Pe serverul live (proprietar)
- **Cache de pagină**:
  - la verificarea din 31.10, prima pagină live a răspuns în **3,1 s**, fără antet `x-litespeed-cache` (cache-ul de pagină nu e activ sau nu e folosit);
  - serverul e LiteSpeed, deci **LiteSpeed Cache** e potrivit; excluderi: coș, checkout, contul meu, `?wc-ajax`;
  - fiecare răspuns trimite `Set-Cookie: _fbp=…` (pixelul Facebook pus de pe server). Un răspuns cu cookie de obicei nu se pune în cache: de verificat setarea modulului Facebook/Meta.
- **Compresia** e activă pe live (Brotli) și HTTP/3 e anunțat (`alt-svc: h3`). Bine.
- **LoftLoader**: ecranul de încărcare ascunde pagina până la `window.load`, deci LCP-ul devine practic timpul de încărcare completă. Se oprește (dezactivare sau „doar pe nicio pagină”).
- **GTranslate**: rămâne (decizie SEO), fără comutare automată. Scriptul `float.js` nu blochează afișarea. De verificat pe staging că steagul plutitor nu acoperă butonul fix „Rezervă” de pe telefon.
- **Google Analytics de două ori** (`gtag/js?id=G-T0Y2TVCYBD` apare de 2 ori pe prima pagină live, plus GTM4WP): vizite duble și un script în plus. Rămâne un singur mod de încărcare.
- **CDN de imagini**: opțional, nu e necesar la lansare. Pozele sunt deja WebP și mici; cu variantele de la 5.2 și cache-ul LiteSpeed (sau QUIC.cloud) e suficient.
- **Elementor, Woodmart, hoteller-elementor, Advanced Coupons**: paginile noi nu le folosesc. Până la mutarea pe WordPress curat, ce încarcă ele pe paginile noi se scoate prin lista de la 5.1.

## 6. Riscuri principale (în ordine)

1. **Modulele și tema veche de pe live** își încarcă fișierele și pe paginile noi. Pe live: 102 scripturi, 69 de stiluri, LoftLoader și Elementor. Totul s-a măsurat aici pe un WordPress curat. **Măsurătoarea trebuie repetată pe staging înainte de D0.** LoftLoader trebuie oprit, iar lista de excluderi (5.1) trebuie aplicată.
2. **CSS-ul WooCommerce și jQuery blochează afișarea** pe paginile ofertelor: ≈1 s în plus la LCP pe telefon. Codul e gata (5.1). Trebuie doar aplicat în `class-gi-plugin.php` și testat.
3. **Pozele din hero de 1600 px pe telefon** (100–190 KB) și cardurile de pe prima pagină (≈640 KB pentru miniaturi). `srcset`-ul se activează singur când apar fișierele `-640/-960` (și `-480` pentru carduri).
4. **Serverul live răspunde lent** (3,1 s, fără cache de pagină, cu `Set-Cookie` pe fiecare răspuns). Fără cache, orice pagină pornește cu 3 s întârziere, oricât de ușoară ar fi.
5. **Formularul BRW real** (OVABRW) n-a putut fi testat local: greutatea lui pe prima pagină, accesibilitatea câmpurilor și a calendarului de date, mărimea butonului. Se verifică pe staging, cu aceleași scripturi (axe + Lighthouse).

## 7. Cum se repetă verificarea

Scripturile sunt în directorul de lucru al sesiunii (`scratchpad/qa/tools/`), nu în depozit:
- `proxy.js`: laboratorul B, cu `?__exp` pentru experiment;
- `lh.sh`: Lighthouse pe cele 6 pagini;
- `axe.js`: axe la 390 și 1440 px, cu meniul și galeria deschise;
- `manual.js`: tastatură, focus, contrast pe fotografii, zone de atingere, mișcare redusă, butonul fix, scroll orizontal;
- `focusring.js`: contrastul conturului de focus măsurat pe pixeli;
- `cov.js`: CSS nefolosit;
- `spec.js`: preîncărcarea.

Pe staging se rulează aceleași comenzi cu adresa staging-ului în loc de `127.0.0.1`. Validarea standard rămâne `python3 tools/validare.py`: la 31.10, **0 erori**.
