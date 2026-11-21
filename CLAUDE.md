# Site Grădina Ielelor — reguli de lucru

Site-ul gradina-ielelor.ro (camping, glamping, bungalow și evenimente, Sâmbăta de Sus, Brașov).
Proprietar: Asociația Bioglamp. Comunicarea cu proprietarul se face în română, simplu, fără jargon.

## Arhitectură (decisă, nu se reia fără cererea proprietarului)
- **Lansare în 7 zile (decizia din 31.10.2026):** designul nou se aplică prin pluginul nostru pe WordPress-ul existent (întâi staging, apoi live), cu rezervările actuale (OVABRW + NETOPIA).
  WordPress-ul nou, curat (fără tema `giw` și fără Elementor) și trecerea pe 5starDesk vin după lansare. Nimic nu ajunge pe live fără aprobarea proprietarului.
- **Fără dependență de programatori:** tot ce se poate face e documentat pas cu pas pentru proprietar (`docs/lansare.md`, `docs/independenta.md`).
- WordPress + WooCommerce + OVABRW rămân motorul de rezervări. Rezervările, plățile și comenzile trec numai prin ele.
- Pluginul `gradina-ielelor-integration/` conține template-urile paginilor (hub și pagini dedicate).
- Pagina principală = hub de rezervare: modulul BRW sus, apoi cardurile celor patru oferte:
  **Glamping · Camping & rulote · Bungalow · Festivaluri & evenimente**.
- Fiecare ofertă are pagina ei dedicată, deschisă în același tab, cu tranziție fluidă.
  Paginile dedicate nu încarcă formularul BRW; butoanele „Rezervă” duc la `/#rezervare-<tip>` pe pagina principală, cu oferta preselectată.
- Până la publicarea paginii dedicate a unei oferte, secțiunea ei detaliată rămâne pe pagina principală.
- Stil: pagini suple și prietenoase, ton cald („tu”), fraze scurte, fără termeni tehnici vizibili vizitatorilor.
- **REGULA DE BAZĂ (proprietara, 15.11.2026):** tot ce primesc clienții și pot descărca sau printa (codul rezervării, vouchere, fișa de cazare, documente PDF, orice fișier nou) trebuie să poarte **sigla Grădina Ielelor și numele**. În cod: `gi_print_brand()`. Orice funcție nouă cu descărcare sau printare pentru clienți se verifică după regula aceasta.

## Surse de adevăr
- `docs/decizii.md`: deciziile proprietarului. Are prioritate față de orice document.
- `docs/prezentari.md`: textele extrase din cele patru prezentări (Tabere, Festivaluri, Festival familie, Boot camp) și fișa tehnică.
- Nu se inventează fapte: cifre, prețuri, capacități, dotări, distanțe, reguli. Ce nu e confirmat nu se publică; se notează în `docs/decizii.md` ca întrebare pentru proprietar.
- Tarifele de pe site sunt orientative; prețul final vine numai din BRW.

## Cum se lucrează (același model ca la SAGA GRAVITY)
1. Fiecare schimbare pe o ramură separată (ex. `pagina-camping`), niciodată direct pe `main`.
2. Se testează în WordPress local (`tools/local-wp/README.md`): `php -l` pe fiecare fișier PHP, `node --check` pe JS, capturi pe mobil și desktop, fără erori PHP din plugin și fără scroll orizontal.
3. Verificare de un agent separat (review), apoi prezentare proprietarului cu capturi.
4. Unire în `main` numai cu acordul proprietarului.
5. Staging înainte de live. Nimic nu se instalează pe site-ul live fără aprobare explicită. Pe staging: plăți sandbox, emailuri către clienți oprite, `noindex`.
6. Nu se scriu parole, chei API sau date personale în depozit.

## Livrare (arhivă, staging, live)
- Arhiva se face numai cu `tools/build-zip.sh` (local) sau cu workflow-ul „Build” din GitHub Actions. Nu se instalează niciodată o arhivă cu „INCOMPLET” în nume.
- La fiecare versiune nouă: `Version:` din antet și `GI_VERSION` egale (altfel arhiva nu se construiește), `Stable tag` și changelog în `readme.txt`; pozele noi sau schimbate se trec în `binary-manifest.tsv` cu mărimea exactă.
- Release: tag `v<versiune>` pe `main`, numai după aprobarea proprietarului; GitHub atașează arhiva și amprenta.
- Pașii proprietarului (backup, instalare, NETOPIA sandbox, lansare, revenire, urmărire): `docs/lansare.md`. Conturi, accese, secrete: `docs/independenta.md`.
- Nu se adaugă deploy automat pe live. `deploy-staging.yml` rămâne manual și oprit implicit.

## Stare la 21.11.2026
- Versiunea pluginului pe live și pe `main`: **1.8.0** (instalată pe live 16.11, verificată din afară; Release `v1.8.0` publicat pe GitHub, cu arhivă și amprentă): harta Grădinii cu „Ești aici”, panouri informative cu cod QR, scriere simplă, PDF la locuri, sigla pe plăcuțe; peste 1.7.0 (QR la rezervare, Recepție, fișa de cazare și registru; Zona echipei; vouchere și copaci cu cod QR). Tag-urile se scriu cu **v mic** (`v1.8.0`); eticheta se creează de proprietară din pagina GitHub (din sesiune nu se pot împinge etichete).
- Lansarea site-ului a avut loc 13.11. **D+7 (21.11):** contul vechi `a.min` închis (email al asociației, parolă nouă nepăstrată, profilul curățat, articolele mutate pe contul proprietarei); ștergerea lui, 28.11. Adresa de administrare WordPress: a asociației. Chei REST WooCommerce: niciuna. De făcut, pe rând: webhook-urile WooCommerce, proprietatea ID-urilor de urmărire (GTM, GA4, Meta, TikTok), paginile vechi `/info-qr/`, utilizatorii NETOPIA și FGO, licențele. Detalii în `docs/decizii.md`.
- Planul Grădinii: schița numerotată (32 de locuri, cu lacurile 31 și 32 din zonele inundabile din PUZ) a fost trimisă proprietarei; uneltele sunt în `tools/harta-plan/` (ramura `harta-plan-unelte`). Așteaptă numele locurilor, ce nu se arată vizitatorilor și poziția terasei, campingului și Wi-Fi-ului (nu sunt în PUZ; nu se inventează).
- Istoricul stării de la 30.10.2026, pentru context:
- Versiunea pluginului era 1.1.0 (ramura `main`).
- Gata: hub-ul de pe pagina principală și pagina dedicată Glamping.
- Următoarele: paginile Camping & rulote, Bungalow, Festivaluri & evenimente (același model ca Glamping), apoi pozele reale.
- Pozele: în `assets/images/` (WebP optimizat, fără metadate), preluate de pe site-ul live; sursa fiecăreia e în `binary-manifest.tsv`. Alte fotografii ale proprietarului: Google Drive, `kimi/gradina ielelor hub/lucru/01-poze-siglate`.
