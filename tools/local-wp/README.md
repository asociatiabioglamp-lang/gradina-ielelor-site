# WordPress local pentru teste

Rulează pluginul într-un WordPress real (aceeași versiune ca site-ul live: 7.1.2), cu WooCommerce, fără să atingă staging-ul sau site-ul live.

## Ce trebuie
- Node.js 22+ și Python 3 cu Pillow (pentru capturi și imagini provizorii).
- WordPress 7.1.2: `git clone --depth 1 --branch 7.1.2 https://github.com/WordPress/WordPress.git wordpress`
- WooCommerce 8.7 (cel mai apropiat de 8.7.3 de pe live): arhiva `woocommerce.zip` din release-ul 8.7.0 de pe GitHub, dezarhivată în `wordpress/wp-content/plugins/`.
- Pluginul: copiază `gradina-ielelor-integration/` în `wordpress/wp-content/plugins/`.
- `mu-plugins/` de aici în `wordpress/wp-content/mu-plugins/`.

## Pornire
```
npx @wp-playground/cli@3.1.61 server --port 9400 --php 8.3 --mount-before-install ./wordpress:/wordpress --wordpress-install-mode install-from-existing-files-if-needed --blueprint blueprint.json --login
```
Apoi deschide http://127.0.0.1:9400/ (pagina principală) și http://127.0.0.1:9400/glamping/.

## mu-plugins (numai local, niciodată pe staging sau live)
- `gi-local-setup.php`: activează WooCommerce și pluginul, creează paginile Acasă (hub) și Glamping cu template-urile lor.
- `gi-fake-brw.php`: **imitație** a formularului `[ovabrw_search]`, pentru că OVABRW e plugin comercial și nu e disponibil local. Doar pentru testarea preselectării categoriei.
- `0-gi-debug.php`: scrie avertismentele PHP în `wp-content/gi-php-errors.log`.
- `gi-test-stiluri-straine.php`: cu `?gi_test=stiluri` în adresă, pagina primește stilurile temei vechi, ale Elementor și ale „hoteller-elementor”, în ordinea de pe live, plus steagul de limbă și bara de jos a temei. Fără ele, o pagină poate arăta bine local și stricat pe live (`docs/audit-2026-11-14.md`, „Stiluri străine”).

## Imagini
Pozele reale nu sunt în depozit. `binary-manifest.tsv` din rădăcina proiectului listează fișierele din `assets/images/` (cu ID-urile lor din Google Drive). Local se pot folosi imagini provizorii cu aceleași nume.

## Capturi
```
node tools/shot.js http://127.0.0.1:9400/glamping/ capturi/glamping
python3 tools/slice.py capturi/glamping
```
Produc capturi pe mobil (390 px) și desktop (1440 px) și verifică să nu existe scroll orizontal.

## Testul uneltei „Pregătește paginile” (pluginul 1.3.0)
`test-pregatire-pagini.js` verifică de la un capăt la altul cele patru butoane și „Revino” (creare ciorne, șabloane, text Contact, publicare, idempotență, nonce). Are nevoie de un mu-plugin de test, numai local, `gi-test-reset.php`, care readuce site-ul la starea de pe live (fără paginile noi, șabloane implicite, Contact cu text vechi) la deschiderea adresei `/?gi_reset=1` de un administrator. Rulează cu `node tools/local-wp/test-pregatire-pagini.js`.

## Testul pentru telefon și stiluri străine (pluginul 1.3.15)
`test-1-3-15.js` deschide prima pagină, cele cinci oferte și paginile Contact, Despre noi, Regulament & politici pe șase ecrane (telefon îngust, telefon, telefon mare, telefon ținut pe lat, tabletă, calculator), **cu stilurile străine simulate** (`gi-test-stiluri-straine.php`). Verifică: cele trei poze de la intrare pe telefon (și pe un browser mai vechi), niciun titlu sub antet, steagul de limbă liber, bara temei vechi ascunsă, butonul „Rezervă” descoperit, linkurile albe peste fotografii, subsolul, fără derulare pe orizontală.
```
NODE_PATH=$(npm root -g) node tools/local-wp/test-1-3-15.js
```
Pozițiile din antetul temei vechi (produse, blog, coș) nu se pot verifica local, pentru că tema nu e în depozit: se verifică pe paginile reale, numai citire, cu stilurile noi aplicate în browserul de test.
