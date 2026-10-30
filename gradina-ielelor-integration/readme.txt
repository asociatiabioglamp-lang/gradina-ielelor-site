=== Grădina Ielelor — One Page + BRW ===
Contributors: gradina-ielelor
Tags: one-page, woocommerce, booking, ovabrw, seo
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later

Template server-side pentru pagina principală Grădina Ielelor, cu WooCommerce + OVABRW drept singur motor de rezervări.

== Description ==

Aplică designul prototipului unei singure pagini WordPress printr-un template furnizat de plugin. Include modulul real BRW, prezentări compacte și galerii pentru Camping, Glamping, Bungalow și Evenimente, CTA-uri către rezervare, SEO server-side și logo în header/footer.

Nu include React, nu creează un al doilea motor de rezervări și nu modifică produsele, comenzile sau plățile.

== Installation ==

1. Instalează și activează pluginul pe staging.
2. Păstrează WooCommerce și OVABRW active.
3. Verifică Setări → Grădina Ielelor: URL rezultate și slug-uri categorii.
4. Selectează template-ul „Grădina Ielelor — One Page + BRW” pentru pagina principală.
5. Setează pagina drept pagină statică de pornire și testează fluxul BRW/WooCommerce cu plăți sandbox.

== Frequently Asked Questions ==

= Folosește formularul React demonstrativ? =
Nu. Constanta GI_REACT_DEMO_ENABLED este false, bundle-ul React nu este inclus, iar template-ul execută exclusiv [ovabrw_search].

= Modifică celelalte pagini sau comenzile? =
Nu. Template-ul se aplică numai paginii selectate. Dezinstalarea șterge numai opțiunile acestui plugin.

== Changelog ==

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
