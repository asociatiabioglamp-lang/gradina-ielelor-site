# Site Grădina Ielelor — reguli de lucru

Site-ul gradina-ielelor.ro (camping, glamping, bungalow și evenimente, Sâmbăta de Sus, Brașov).
Proprietar: Asociația Bioglamp. Comunicarea cu proprietarul se face în română, simplu, fără jargon.

## Arhitectură (decisă, nu se reia fără cererea proprietarului)
- **Site nou, curat (decizia din 30.10.2026):** WordPress nou, fără tema `giw` și fără Elementor, construit separat de site-ul live.
  Site-ul live rămâne neatins până când proprietarul verifică și aprobă site-ul nou; abia atunci îl înlocuiește.
- WordPress + WooCommerce + OVABRW rămân motorul de rezervări. Rezervările, plățile și comenzile trec numai prin ele.
- Pluginul `gradina-ielelor-integration/` conține template-urile paginilor (hub și pagini dedicate).
- Pagina principală = hub de rezervare: modulul BRW sus, apoi cardurile celor patru oferte:
  **Glamping · Camping & rulote · Bungalow · Festivaluri & evenimente**.
- Fiecare ofertă are pagina ei dedicată, deschisă în același tab, cu tranziție fluidă.
  Paginile dedicate nu încarcă formularul BRW; butoanele „Rezervă” duc la `/#rezervare-<tip>` pe pagina principală, cu oferta preselectată.
- Până la publicarea paginii dedicate a unei oferte, secțiunea ei detaliată rămâne pe pagina principală.
- Stil: pagini suple și prietenoase, ton cald („tu”), fraze scurte, fără termeni tehnici vizibili vizitatorilor.

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

## Stare la 30.10.2026
- Versiunea pluginului: 1.1.0 (ramura `main`).
- Gata: hub-ul de pe pagina principală și pagina dedicată Glamping.
- Următoarele: paginile Camping & rulote, Bungalow, Festivaluri & evenimente (același model ca Glamping), apoi pozele reale.
- Pozele: în `assets/images/` (WebP optimizat, fără metadate), preluate de pe site-ul live; sursa fiecăreia e în `binary-manifest.tsv`. Alte fotografii ale proprietarului: Google Drive, `kimi/gradina ielelor hub/lucru/01-poze-siglate`.
