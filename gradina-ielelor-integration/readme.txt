=== Grădina Ielelor — Site + BRW ===
Contributors: gradina-ielelor
Tags: one-page, woocommerce, booking, ovabrw, seo
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

Template-uri server-side pentru pagina principală (hub de rezervare) și paginile dedicate ofertelor Grădina Ielelor, cu WooCommerce + OVABRW drept singur motor de rezervări.

== Description ==

Pagina principală devine pagina de rezervare: modulul real BRW sus, apoi cardurile celor cinci categorii — Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private. Fiecare categorie are pagina ei dedicată, deschisă în același tab, cu tranziție fluidă între pagini și preîncărcare la hover. Despre noi, Regulament & politici și Contact folosesc șablonul „Pagină simplă”, editabil din WordPress.

Paginile dedicate nu încarcă formularul BRW. Butoanele „Rezervă” duc înapoi la modulul de rezervări de pe pagina principală, cu oferta preselectată (/#rezervare-glamping).

Până la publicarea paginii dedicate a unei oferte, cardul ei duce la secțiunea detaliată de pe pagina principală; după publicare, secțiunea dispare de pe pagina principală.

Nu include React, nu creează un al doilea motor de rezervări și nu modifică produsele, comenzile sau plățile.

== Installation ==

1. Instalează și activează pluginul pe staging.
2. Păstrează WooCommerce și OVABRW active.
3. Verifică Setări → Grădina Ielelor: URL rezultate și slug-uri categorii.
4. Selectează template-ul „Grădina Ielelor — Pagina principală (rezervare)” pentru pagina principală.
5. Setează pagina drept pagină statică de pornire și testează fluxul BRW/WooCommerce cu plăți sandbox.
6. Pentru fiecare categorie (Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private): editează pagina ei și selectează template-ul „Grădina Ielelor — <categoria>”. URL-ul rămâne același.
7. Pentru Despre noi, Regulament & politici și Contact: template-ul „Grădina Ielelor — Pagină simplă”.
8. Verifică: butonul „Rezervă” de pe /glamping/ deschide pagina principală cu Glamping preselectat în modulul BRW.

Pașii completi (backup, staging, plata de test, lansare, revenire): docs/lansare.md din depozit.

== Frequently Asked Questions ==

= Folosește formularul React demonstrativ? =
Nu. Constanta GI_REACT_DEMO_ENABLED este false, bundle-ul React nu este inclus, iar template-ul execută exclusiv [ovabrw_search].

= Modifică celelalte pagini sau comenzile? =
Nu. Template-ul se aplică numai paginii selectate. Dezinstalarea șterge numai opțiunile acestui plugin.

== Changelog ==

= 1.2.0 =
* Pagini dedicate pentru toate cele cinci categorii: Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private (template comun, conținut din materialele proprietarului).
* Festivaluri & tabere (/festivaluri-si-tabere/) și Evenimente private (/evenimente-private/) înlocuiesc pagina „Festivaluri & evenimente”: calendarul 2027 e pe Festivaluri & tabere; „Cere ofertă” duce la /festival/, respectiv la Contact, cu telefonul la vedere. /evenimente/ duce la Festivaluri & tabere, iar pagina veche de evenimente la Evenimente private.
* Prima pagină = pagina de rezervare: 5 carduri, Festival (ofertă organizatori), Despre noi, Regulament, recenzii selectate (fără medie calculată), blog.
* Link „Înapoi la rezervare” la finalul fiecărei pagini dedicate; calendar 2027 pe pagina Festivaluri & tabere.
* Versiune nouă pentru reîmprospătarea CSS/JS în browsere și CDN.
* Pagini simple (Despre noi, Regulament & politici, Contact), editabile în WordPress; redirecționări 301 de la adresele vechi.
* Regulamentul la finalizarea rezervării: casetă de citit și bifă obligatorie (activă după derulare), cu dovada acceptării în comandă (data, versiunea, limba, codul textului); textul se arhivează. Regulamentul se consultă numai în pagină: fără selectare, copiere sau tipărire. Setări → Grădina Ielelor → „Regulamentul la plată”.
* „Review & blog” în meniu.
* Subsol cu linkurile legale: termeni, confidențialitate, cookies, politica de anulare, ANPC, SAL, SOL; Contact și Blog.
* Accesibilitate: focus vizibil, contrast, ținte de atingere de 44 px, galerie și meniu din tastatură (axe: 0 probleme).
* Viteză: fără fișierele WooCommerce pe paginile ofertelor; poze principale în 960/1280/1600 px, după ecran.
* SEO: titlul setat în Yoast / Rank Math are prioritate; un singur canonical fără plugin SEO.
* Redirecționările de la adresele vechi sunt temporare (302) până când bifezi „Redirecționări permanente (301)” în Setări → Grădina Ielelor (la D+7).
* Blocurile cu clasa gi-nota-editor nu ajung la vizitatori nicăieri (pagină, căutare, REST, rezumate).
* Festivaluri & tabere: festivaluri, tabere de copii, ieșiri cu echipa, retreaturi și boot camp pentru tineri. Evenimente private: nunți, botezuri, majorate și petreceri de familie, în aer liber, în pavilioane puse la dispoziție de noi sau aduse de client; sezon 1 mai – 1 octombrie.
* Plata la locație: prețul de pe site + 10% (rând separat în coș și în comandă), cu textul pentru vizitatori lângă prețuri și la rezervare. Se pornește din setări.
* Sezonul fiecărei oferte, editabil din setări: apare lângă prețuri și la întrebări.
* Prima pagină: în partea din dreapta a imaginii de sus, lacul cu munții (în locul vederii aeriene).
* Paleta „O zi senină la munte”: cer, lac, pajiște și soare în locul verdelui închis; toate butoanele de rezervare au culoarea soarelui. Contrastul măsurat (WCAG AA), accesibilitate 100.
* Pozele importante au Munții Făgăraș în spate (imaginile mari, cardurile, pozele de lângă text, finalul paginilor).
* Formularul de rezervare de pe prima pagină: câmpuri rotunjite pe un rând, buton mare „Caută disponibilitatea”, texte prietenoase („Alege cazarea”, „Alege data”).

= 1.1.0 =
* Pagină dedicată Glamping (template nou), cu ton prietenos și fără jargon tehnic pentru vizitatori.
* Pagina principală ca hub de rezervare, cu carduri pentru cele patru oferte.
* Butoanele de rezervare de pe paginile dedicate trimit la modulul BRW de pe pagina principală, cu oferta preselectată.
* Navigare fluidă: tranziții între pagini (View Transitions) și preîncărcare la hover (Speculation Rules, WordPress 6.8+), fără coș, checkout și cont.
* Header, footer și lightbox comune în templates/parts; SEO server-side pe fiecare template.

= 1.0.8 =
* Primul ecran mai compact, pentru vizibilitatea imediată a modulului de rezervare pe desktop și mobil.

= 1.0.7 =
* Redenumirea asseturilor lacului pentru eliminarea imaginii vechi din cache și încărcarea cadrului fără corpul de toaletă.

= 1.0.6 =
* Cropuri procesate separat pentru fiecare felie și centrare pe lac/munți, oaspeți/rulote și zona de lounge, cu poziționare distinctă pe mobil.

= 1.0.5 =
* Fundal hero împărțit în trei felii diagonale: lac, vedere aeriană cu oaspeți și rulote, și terasa de relaxare.

= 1.0.4 =
* Hero final cu fotografia preferată a lacului, fără corpul de toaletă, plus accente pentru chill/relaxare și festivaluri/evenimente.

= 1.0.3 =
* Hero cu lacul și munții în plan principal și o a doua fotografie Glamping, fără rulote.

= 1.0.2 =
* Compactarea formularului BRW și corectarea sortării pe ecrane mobile.

= 1.0.1 =
* Uniformizare vizuală pentru catalogul WooCommerce și formularele BRW, inclusiv markupul Woodmart.

= 1.0.0 =
* Prima versiune pentru integrare staging.
