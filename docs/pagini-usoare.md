# Pagini ușoare (pluginul 1.10.0)

Cerere de la proprietară (21.11.2026): „da, începe cu paginile ușoare”, după măsurătoarea de pe live (`docs/viteza-2026-11-21.md`): primul conținut la ~6 s, pagina gata la 10–14 s, 125–145 de cereri pe pagină, inclusiv pe paginile noi.

## Ce se întâmplă acum, pe pagina Glamping de pe live
116 fișiere de stil și de script (plus imagini, fonturi). Din ele, ale paginii noastre sunt 4. Restul vin de la:

| De unde | Fișiere | Folosite de paginile noastre? |
|---|---|---|
| Tema veche `giw` (WoodMart, Bootstrap) | 24 de stil, 6 de script, 2 generate în „uploads” | nu (paginile noastre au propriul CSS și JS) |
| Elementor și Elementor Pro | 5 de stil, 4 + 3 de script, 4 generate în „uploads” | nu |
| `hoteller-elementor` | 7 de stil, 10 de script | nu |
| Formularul de rezervare (`ova-brw`) | 13 de stil, 9 de script | nu (paginile ofertelor nu au formularul; butoanele duc la prima pagină) |
| `advanced-coupons` | 2 de stil, 2 module JavaScript | nu |
| Fonturi Google ale altor module | 3 | nu (paginile noastre nu le folosesc) |
| WooCommerce (coș, plată, fragmente de coș) | 5 | nu pe paginile fără coș |
| jQuery, jQuery UI, masonry și alte fișiere WordPress, aduse de cele de mai sus | ~12 | numai cât cer cele rămase |

## Ce face versiunea 1.10.0 (`includes/class-gi-light.php`)
Pe paginile care se afișează cu un template al pluginului, înainte să se afișeze stilurile și scripturile, se scot din coadă cele de mai sus (după locul fișierului și după numele lui). Ce rămâne: CSS-ul și JS-ul nostru, Tag Manager (GTM4WP), traducerea (GTranslate), statisticile (WP Statistics), fișierele de atribuire a comenzilor (WooCommerce) și ce cer ele. Din HTML se scot și `<link rel="modulepreload">` și modulele JavaScript ale cuponelor.

**Nu se ating:** paginile WooCommerce (magazin, produs, coș, plată, cont), paginile vechi făcute cu Elementor, Zona echipei, administrarea, previzualizările.

**Excepții pe care le știe codul:** pagina cu formularul de rezervare (`[ovabrw_…]`, `[gradina_ielelor_booking]`) își păstrează BRW; pagina cu „woocommerce” în text își păstrează fișierele WooCommerce; prima pagină (formularul real) se atinge numai la nivelul 2.

## Nivelul din Setări → Grădina Ielelor → „Pagini ușoare”
- **0, oprit:** ca înainte (revenire imediată, fără să se mai instaleze nimic).
- **1, paginile noi fără prima pagină (implicit):** Glamping, Camping, Bungalow, Festivaluri, Evenimente, Despre noi, Contact, Regulament, Galerie, Harta, locurile din Grădină, pagina codului de rezervare.
- **2, și prima pagină:** se scot tema, Elementor, hoteller, cupoanele și fonturile; BRW (formularul) și WooCommerce rămân. Se alege numai după ce proprietara a văzut prima pagină pe capturi.

## Ce nu se poate testa local
Local nu există tema `giw`, Elementor, hoteller, BRW (plugin comercial) și cupoanele. Testul local (`tools/local-wp/test-1-10-0.js`) pune în pagini fișiere de probă cu **aceleași nume și aceleași locuri ca pe live** și verifică regulile. Pentru restul:
1. Regulile s-au aplicat pe **lista reală de fișiere de pe live** (din HTML-ul paginilor): Glamping 116 fișiere → 24 rămân înainte de dependențe.
2. S-a făcut o **simulare pe site-ul real, fără instalare**: pagina se încarcă de pe live cu cererile către fișierele scoase blocate (vezi mai jos). Se compară viteza și arătarea înainte și după.
3. După instalare, verificare din afară (număr de fișiere, capturi) și verificare vizuală de către proprietară, pe telefon.

## Revenire
Setări → Grădina Ielelor → Pagini ușoare → **Oprit** → Salvează. Sau instalezi din nou arhiva 1.9.0/1.8.0 din Release.

## Ce urmează (nu face parte din 1.10.0)
- Memorie de pagină (LiteSpeed Cache), după ce paginile sunt ușoare.
- Poze: 22 prea mari, 193 fără dimensiuni.
- Paginile vechi (blog, produse, plante): rămân grele, până la un „WordPress curat”.
