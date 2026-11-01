# Modulul de ofertare (cereri de ofertă) — cum funcționează și cum e legat de site (31.10.2026)

Surse: pachetul din Drive `FESTIVAL OFERTARE` (`CITESTE-MA-INTAI.md`, `ORDINEA-DE-URCARE.txt`, `cotatie/`, `festival/`), sursa paginii `/cotatie/` trimisă de proprietară și fișierul `cotatie.php` citit din Drive. Verificat 31.10.2026. Nu face parte din plugin și nu e pagină WordPress: sunt fișiere pe hosting, în `public_html/festival/` și `public_html/cotatie/`.

## Ce face
1. Organizatorul ajunge pe site (pagina nouă Festivaluri & tabere sau Evenimente private) și apasă „Cere ofertă”.
2. Se deschide configuratorul `/cotatie/?tip=<tip>`, direct la pasul 2, cu tipul deja ales: `festival`, `nunta`, `tabara`, `camping`, `retreat`. Are trei pași (tip, detalii, contact), apoi trimite cererea.
3. `cotatie.php` primește cererea, o **scrie în `cereri.csv`** (în folderul `cotatie`) și **trimite email la `rezervari@gradina-ielelor.ro`** (expeditor `site@gradina-ielelor.ro`). Dacă emailul nu pleacă, cererea rămâne în `cereri.csv`.
4. `Generator-oferte.html` (instrument intern, pe calculatorul proprietarei, nu pe server) construiește oferta în PDF pe baza cererii.

## Unde e legat din site (plugin `1.3.0`)
- Festivaluri & tabere: butonul mare „Cere ofertă” → `?tip=festival`; „Pagina pentru organizatori” → `/festival/`; pe cardurile Festivaluri, Tabere, Echipe și retreaturi, Boot camp: câte un buton „Cere ofertă pentru …” → `festival`, `tabara`, `retreat`, `retreat`.
- Evenimente private (nunți, botezuri, majorate): „Cere ofertă” → `?tip=nunta`; „Sau scrie-ne” → Contact.
- Tipul `camping` rămâne fără legătură din site: campingul se rezervă direct, prin modulul de rezervare.

## Ce am verificat în cod (`cotatie.php`)
- **Bun:** datele sunt curățate de etichete și de rânduri noi (fără injecție în antetele emailului); subiectul e codat; există un câmp-capcană anti-spam; cererea se salvează chiar dacă emailul eșuează; `.htaccess` blochează descărcarea lui `cereri.csv` (`Require all denied`).
- **Verificat de proprietară pe 1.11.2026:** `gradina-ielelor.ro/cotatie/cereri.csv` dă **eroare 403**, deci fișierul nu se poate descărca (în fereastră privată). De repetat după orice schimbare în folderul `cotatie`. Fișierul conține nume, telefoane și emailuri.
- **De îmbunătățit după lansare, nu înainte:**
  1. Valorile care încep cu `=`, `+`, `-` sau `@` pot fi interpretate ca formule când se deschide `cereri.csv` în Excel: se prefixează cu un apostrof.
  2. Fără protecție împotriva spamului în afara câmpului-capcană (de exemplu o limită de cereri pe oră de la aceeași adresă).
  3. Acordul „Sunt de acord să fiu contactat…” nu trimite la Politica de confidențialitate; textul „nu dăm datele nimănui” trebuie confirmat de avocat (datele ajung și la contabilitate, la facturare etc.).
  4. Perioada de păstrare a lui `cereri.csv` (de exemplu 24 de luni, apoi ștergere) trebuie stabilită și trecută în politica de confidențialitate.
  5. Trimiterea prin `mail()` poate ajunge în spam; testul de mai jos arată cum stă.
- **Texte vechi în configurator** (de aliniat cu deciziile, după lansare): „Bucătărie licențiată” (decis: autorizată), „Rulotă, cu racorduri” (racordurile sunt neconfirmate, `docs/decizii.md`, întrebarea 4), „Domeniul … 1.000 de locuri de parcare” (în acord cu ~1.000 de mașini).

## Test înainte de lansare (D-4)
1. Din site, apeși „Cere ofertă pentru festival”, parcurgi cei trei pași și trimiți o cerere de probă; apoi încă una, pe „Nuntă”, din Evenimente private.
2. Emailurile trebuie să ajungă în Gmail (redirecționare de la `rezervari@`); se verifică și Spam.
3. `cereri.csv` apare în `public_html/cotatie` (cPanel → File Manager) cu două rânduri; fișierul nu se descarcă de pe internet.
4. La plata de test pe staging, modulul rămâne neatins: folderele `cotatie` și `festival` sunt copiate cu site-ul, dar cererile de probă de pe staging ajung tot la `rezervari@`.
