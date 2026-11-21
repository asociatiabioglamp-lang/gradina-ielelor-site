=== Grădina Ielelor — Site + BRW ===
Contributors: gradina-ielelor
Tags: one-page, woocommerce, booking, ovabrw, seo
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.10.0
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

= 1.10.0 =
* Pagini ușoare (viteză): pe paginile făcute cu template-urile pluginului (oferte, Despre noi, Contact, Galerie, Harta, locurile din Grădină) nu se mai încarcă fișierele temei vechi (`giw`, WoodMart, Bootstrap), ale Elementor și Elementor Pro, ale `hoteller-elementor`, ale cuponelor (`advanced-coupons`), cele trei fonturi Google ale altor module și, unde pagina nu are formularul de rezervare, fișierele BRW; nici fișierele WooCommerce de coș și plată. Măsurat pe live (21.11.2026): ~140 de cereri pe pagină, primul conținut la ~6 s. Setări → Grădina Ielelor → „Pagini ușoare”: 0 oprit, 1 fără prima pagină (implicit), 2 și prima pagină. Nu se ating: paginile WooCommerce, paginile vechi, Zona echipei.

= 1.8.0 =
* Harta și ghidul Grădinii: proprietara încarcă un plan al Grădinii (Zona echipei → Harta), iar la fiecare loc din „Copaci și locuri” apasă pe plan unde se află (poziția se păstrează în procente). Cine scanează codul QR al unui loc vede „Ești aici”, cu locul marcat pe plan, și un link spre pagina /in-gradina/harta/ (planul cu toate locurile, apăsabile, și ghidul pe grupe: informații, locuri și facilități, copaci și plante). Fără plan încărcat, nu apare nicio hartă; adresa „harta” e rezervată.
* Tipuri noi de locuri (pentru bucătărie, terasă, Wi-Fi, loc de joacă, băi, vatră, parcare, lac): fiecare are pagina și codul QR lui. Tipul „Informații” e pentru panouri ca „Ce poți vizita” sau „Parteneri”: textul se schimbă oricând, iar codul QR printat rămâne același, deci nu mai trebuie reprintate panouri și afișe.
* Scriere simplă pentru textul unui loc, fără HTML: „# ” titlu, „## ” subtitlu, „- ” listă, „1. ” listă numerotată, **îngroșat**, *înclinat*, [text](https://adresa) link (și tel: și mailto:), „> ” citat, „---” linie. Cinci butoane peste casetă scriu semnele. Etichetele HTML și „javascript:” nu se execută; textele cu HTML din editorul WordPress rămân neschimbate.
* Document PDF la un loc (de ex. fișa plantei): se încarcă din formularul locului (maximum 15 MB, verificat să fie PDF adevărat), cu bifa „are sigla și numele Grădinii Ielelor” (regula de bază); pe pagina publică apare butonul „Descarcă fișa (PDF)”. Se poate înlocui sau șterge (fișierul vechi se șterge).
* Regula de bază (sigla și numele Grădinii Ielelor pe tot ce descarcă sau printează clienții) se aplică și plăcuțelor cu cod QR de printat ale locurilor.

= 1.7.0 =
* Această versiune cuprinde tot ce s-a făcut după 1.5.1: codul QR la rezervare și Recepția (descrise la „1.6.0”, mai jos, care nu s-a lansat separat) și fișa de cazare cu registrul (descrise aici).
* Zona echipei nu se traduce automat (meta notranslate, clasa notranslate pe pagină) și nu mai are steagul de limbă: numele, telefoanele, CNP-urile turiștilor nu trebuie să ajungă la un traducător extern. Căutarea din Registru și descărcarea CSV se fac prin formular (POST), nu prin adresă, ca ce cauți să nu ajungă în jurnalele serverului. Seria fișelor nu se refolosește după o ștergere; aceeași fișă (același act pe aceeași rezervare) nu se poate înscrie de două ori nici dacă două tablete trimit deodată; textul despre datele personale se verifică la trimitere (dacă s-a schimbat cât se completa, fișa nu se înscrie cu un text nevăzut). Semnătura goală e respinsă și în browser, și pe server (când PHP-ul are GD).
* Fișa de cazare (Zona echipei → Recepție → „Fișa de cazare”): pentru fiecare adult al rezervării (copiii nu au fișă). Datele turistului se iau din zona de jos a actului de identitate (citită în browser: cifrele de control ICAO 9303, valabilitatea, CNP-ul numai dacă are cifra de control corectă și se potrivește cu data nașterii, sexul și secolul), din rezervare (domiciliu, telefon, sosire, plecare) sau se scriu de mână. Actul nu se copiază și textul brut al zonei de jos nu se trimite și nu se păstrează. Include biblioteca proprie assets/js/mrz.js (fără serviciu extern).
* Fișa e în română și engleză, cu serie (prefix-an-număr), semnătura turistului și a recepționerului (desenate pe ecran, păstrate ca imagini PNG), confirmarea că turistul a fost informat despre prelucrarea datelor (textul se schimbă din Setări; provizoriu până îl dă juristul), datele unității (păstrate pe fiecare fișă) și tipar pe A4 (o pagină).
* Registrul fișelor (Zona echipei → Registrul fișelor): numai administratorul. Căutare, deschidere, descărcare CSV (Excel, cu protecție la formule), ștergere, termen de păstrare ales de proprietară (nu se șterge nimic singur) și Setări (unitate, operator, CUI, clasificare, prefixul seriei, textul GDPR). Recepția doar înscrie fișe și vede fișa abia înscrisă (o oră).
* Istoric → Fișe: cine a înscris, a șters sau a descărcat, fără nume, CNP, acte sau telefoane. Datele stau într-un tabel al site-ului (gi_fise), necriptat (deci și în copiile de siguranță ale bazei de date), care rămâne și dacă pluginul e șters; fișele se șterg din registru.

= 1.6.0 =
* Cod QR la fiecare rezervare finalizată (comandă în procesare, finalizată sau cu plata la locație): cod scurt XXXX-XXXX (fără 0, O, 1, I, L, ușor de dictat), arătat pe pagina de mulțumire, în Contul meu, în emailul de confirmare și pe pagina /cod-rezervare/<cod>/ (cod QR mare, de arătat pe telefon sau de printat; nu apare în Google). Codul QR conține numai GI1.<cod>.<sumă de control>: fără nume, telefon sau email.
* Zona echipei → Recepție: personalul scanează codul QR al turistului cu camera telefonului (în browser, fără aplicație; imaginea nu pleacă de pe telefon) sau scrie codul și vede rezervarea: titularul, telefonul, sosirea, plecarea, nopțile, adulții, copiii, starea plății și toate câmpurile din comandă. Suma de control prinde codurile citite greșit. Camera e permisă numai pe această pagină (antetul de securitate o ținea oprită pe tot site-ul). Include biblioteca „jsQR” (Apache-2.0) în assets/js/vendor/, încărcată numai când se pornește camera.
* Rol nou „Recepție (Grădina)”: vede numai Recepția. Se creează de proprietară din Echipă (alegere „Vânzări” sau „Recepție”), cu aceleași reguli ca la vânzări: utilizator și parolă alese de proprietară, parola o schimbă numai ea, „Mi-am uitat parola” răspunde ca pentru un cont inexistent, fără acces în administrarea WordPress.
* Rezervările făcute înainte de 1.6.0 (fără cod) se găsesc la Recepție după numărul comenzii și numele de familie (ambele). Rezervările cu plata la locație/livrare apar „de încasat la recepție”, nu „plătită”. Pagina de mulțumire a unei plăți încă neconfirmate de bancă explică când apare codul.
* Istoric: căutările de la Recepție apar („A căutat rezervarea XXXX-XXXX”), fără nume sau telefon. Drepturi noi: gi_receptie (administrator, manager de magazin, conturile „Recepție”) și gi_fise_vezi (numai administrator: istoricul căutărilor de la Recepție și, în versiunea următoare, registrul fișelor).
* Fișa de cazare (scanarea actului) urmează în versiunea următoare.

= 1.5.1 =
* Zona echipei → Vouchere: ștergerea voucherelor gata create („Șterge voucherele alese”), numai pentru administrator și managerul de magazin, numai pentru cele nefolosite (cele folosite rămân ca dovadă). Se pun în Coșul WooCommerce: un cupon din coș nu se mai poate folosi, iar un administrator îl poate readuce 30 de zile.
* Istoric de creare (Zona echipei → Istoric): cine a generat sau a șters vouchere, a adăugat sau a modificat locuri, a creat conturi, a dat parole noi, a scos accesul sau a schimbat limitele, cu data și ora. Rămâne și după ștergere. Parolele nu apar niciodată. Se păstrează într-un tabel al site-ului (gi_istoric), care rămâne și dacă pluginul e șters.
* Echipă: utilizatorul și parola colegilor le alege proprietara (sau lasă câmpurile goale și se generează), cu butonul „Generează”; „Parolă nouă” tot ea o dă. Parolele alese trebuie să fie lungi (12 caractere) și greu de ghicit. Colegii de la vânzări **nu își pot schimba parola**: „Mi-am uitat parola” răspunde ca pentru un cont inexistent, iar din profil, Contul meu sau REST schimbarea e refuzată cu un mesaj.
* Fiecare voucher din listă arată când a fost creat.

= 1.5.0 =
* „Zona echipei” (adresa /zona-echipei/): administrare direct de pe site, fără administrarea WordPress, cu utilizator și parolă. În subsolul tuturor paginilor apare căsuța „Administrare”. Pagina nu apare în Google și nu se păstrează în memorii intermediare. Ghid: docs/zona-echipei.md.
* Rol nou „Vânzări (Grădina)”: colegii de la vânzări văd numai Zona (vouchere), nu intră în administrarea WordPress (sunt trimiși înapoi în Zonă), nu pot modifica conținutul. Conturile se fac din Zonă → Echipă: fără email, parola se arată o singură dată, „Parolă nouă” închide sesiunile, „Scoate accesul”.
* Vouchere în Zonă: sumă fixă sau procent, 1–100 deodată, 18 luni, o folosire, cod QR de printat. Colegii de la vânzări au limite pe cod și pe generare (provizorii: 10% / 100 lei / 10 coduri, schimbabile de proprietară), codurile lor nu se combină cu alte coduri (unul singur la o rezervare), și văd numai voucherele lor; fiecare voucher păstrează cine l-a generat.
* Copaci și locuri în Zonă: titlu, tip, poveste, poză făcută chiar de pe telefon (JPG, PNG, WebP, maximum 8 MB), text alternativ, ciornă/publicat, cod QR de printat sau descărcat.
* Drepturi noi pe roluri: administratorul (toate), managerul de magazin (zonă, vouchere, copaci), editorul (zonă, copaci). Se șterg la dezinstalare, împreună cu rolul „Vânzări (Grădina)”.

= 1.4.0 =
* Zona privată „Grădina” în administrare (meniul din stânga, cu utilizator și parolă): vezi docs/zona-privata.md.
* „Copaci și locuri”: pentru fiecare copac (sau plantă, sau loc) creezi o pagină publică (titlu, poză, text, rezumat, tip, nume științific) cu adresă stabilă /in-gradina/<nume>/ și cu cod QR. Codul se desenează în browser, fără serviciu extern, se poate printa (pagină curată, mărimi de 4–10 cm, mai multe deodată) sau descărca (SVG). Vizitatorul care scanează codul ajunge pe pagina locului, cu „Mai multe de văzut în Grădină”.
* „Vouchere”: generezi coduri de reducere (sumă fixă sau procent, 1–100 deodată), valabile 18 luni, o singură folosire, fiecare cu cod QR de printat. Sunt cupoane WooCommerce obișnuite. Cine scanează codul îl are reținut 30 de zile și se aplică singur în coș.
* Accesul: administratorii, editorii și managerii de magazin văd „Copaci și locuri” și „Coduri QR de printat” (autorii, colaboratorii și clienții nu); doar administratorii și managerii de magazin văd „Vouchere”. Pe un site care nu e cel real (copie de test), ecranele avertizează că codurile ar duce la adresa de test.
* Adresa din cod are parametri utm (utm_source=qr&utm_medium=print): în Google Analytics apare sursa „qr / print”, chiar și după ce se schimbă adresa unei pagini. Voucherul scanat se aplică numai dacă e generat din zona Grădina și se șterge din memoria browserului după încercare.
* Pagina „Galerie foto” primește singură șablonul galeriei cu 15 poze, o singură dată, la prima rulare (nu mai trebuie apăsat pasul 2 din „Pregătește paginile”). Dacă pagina are deja un șablon ales de mână, nu se atinge. „Revino” o readuce și nu se mai reaplică.
* Pagini cu designul vechi, pe telefon și tabletă: steagul de limbă e puțin mai mic și nu mai atinge titlul paginii de coș.
* Include biblioteca „qrcode-generator” (Kazuhiko Arase, licență MIT), în assets/js/vendor/.

= 1.3.16 =
* Galerie foto: pagina nouă, cu 15 fotografii reprezentative (locul, glamping, camping și rulote, bungalow, evenimente), în locul celor 98 de poze și al celor circa 13 MB de acum. Miniaturile sunt mici (în total circa 0,5 MB); la apăsare, fotografia se deschide mare, cu săgeți și cu legendă (miniatura apare imediat, iar fotografia mare o înlocuiește când s-a încărcat).
* Pagina „Galerie foto” primește noul design prin „Pregătește paginile” → pasul 2 (sau alegând șablonul „Grădina Ielelor — Galerie foto” în pagină). Conținutul vechi rămâne în pagină, neatins, și revine cu „Revino”.
* Subsol: link nou „Galerie foto”.
* Tastatură: conturul de focalizare se vede acum pe tot site-ul (o regulă a modulului de rezervări îl oprea pe toate paginile). Cine navighează cu Tab vede unde se află.
* Legendele pozelor spun numai ce se vede în ele.
* Paginile produselor: „Nu avem dispobibilitate în intervalul” devine „disponibilitate” (textul vine din modulul de rezervări și nu trecea prin traducerile corectate de plugin).

= 1.3.15 =
* Telefon, prima pagină: se văd toate cele trei poze de la intrare (înainte se vedea numai prima). Poza mare rămâne sus, cu textul; lacul și focul apar dedesubt, tăiate pe diagonală ca pe calculator. Pe telefoanele obișnuite încap pe primul ecran; pe cele cu ecran scund, pozele de jos se văd parțial și apar întregi la prima derulare.
* Telefon și tabletă: bara de jos a temei vechi („Contul meu / Coș / Meniu”) nu mai acoperă butonul „Rezervă” pe paginile noi.
* Paginile Contact, Despre noi și Regulament & politici: titlul paginii nu mai stă sub siglă (se întâmpla și pe telefon, și pe calculator).
* Paginile ofertelor (mai ales Festivaluri & tabere pe telefon și toate, pe telefonul ținut pe lat): titlul nu mai intră sub antet.
* Linkurile de peste fotografii („Acasă”, „Vezi pozele”, „Înapoi la rezervare”) sunt din nou albe; culoarea verde a temei vechi le făcea greu de citit.
* Steagul de limbă nu mai stă peste siglă, peste meniu și peste text: pe telefon stă în antet, între siglă și meniu; pe calculator stă sub antet, în dreapta; pe paginile cu designul vechi (produse, blog, coș), pe telefon și tabletă, stă sub butonul „Meniu”. Nu mai însoțește derularea.
* La trecerea cu mouse-ul (sau la atingere), textul butoanelor și al linkurilor nu se mai face roșu, iar butonul de meniu nu se mai face gri (veneau de la tema veche). Butonul „Rezervă” din antet se deschide la culoare, ca celelalte butoane portocalii.
* Paginile cu designul vechi: steagul de limbă nu mai stă peste meniul lateral, coș sau fereastra de autentificare când acestea se deschid.
* Articole și pagini „Info QR”: au fost scoase patru linkuri comerciale fără legătură cu Grădina (salubritatetargoviste.ro, cloud9estate.ro, elianadesign.ro, jorjette.ro). Dispare numai linkul; cuvintele rămân în text.
* Subsol: linkuri noi „Contul meu” și „Coș” (bara de jos a temei vechi, care le conținea, nu mai apare pe paginile noi, nici pe telefon, nici pe tabletă).
* Subsol: linkul „Litigii online (SOL)” e scos, pentru că platforma europeană s-a închis la 20.07.2025. Rămân ANPC și ANPC — SAL.
* Meniurile vechi (de pe blog, produse, coș) duc direct la paginile noi, nu prin redirecționare.
* Plată: și textul din câmpul „Bloc, scară, apartament” e în română.
* Formularul de rezervare: mesajele mici de lângă câmpuri sunt în română („Completează acest câmp.” în loc de „This field is required.”), iar „dispobibilitate” devine „disponibilitate”, dacă textul vine dintr-o traducere.

= 1.3.14 =
* Tariful la camping corectat în pagini, în card și în întrebările frecvente: 80 lei pe noapte pentru locul de cort sau de rulotă, plus 30 lei pe noapte pentru fiecare adult (ca în calendarul de rezervări; exemplu: doi adulți, o noapte, 140 lei). Până acum paginile spuneau doar „80 lei”.
* Sezonul implicit din cod: „1 mai – 30 septembrie” (valorile salvate în Setări → Grădina Ielelor nu se schimbă).
* Coșul, finalizarea rezervării, pagina de mulțumire și contul clientului, în limba română (înainte erau în engleză). Textele se schimbă numai pe paginile publice, numai când nu există deja o traducere, și nu în emailuri.
* La Cort și Rulotă: dacă nu alegi numărul de adulți și apeși „Adaugă în coș”, apare acum un mesaj vizibil (înainte nu se întâmpla nimic).
* SEO: adresa de lucru veche „dev24” din Yoast (canonical, og:url, date structurate) e înlocuită cu adresa reală; harta site-ului nu mai conține paginile vechi redirecționate, coșul, finalizarea și contul; titlurile vechi cu greșeli de la Glamping și Camping („Sambata se Sus”) sunt înlocuite cu cele din plugin; numele site-ului din titluri devine „Grădina Ielelor”; datele structurate spun că e un camping, cu telefon, email și localitate.
* Nu schimbă rezervările, prețurile din calendar, plățile și confirmările NETOPIA.

= 1.3.10 =
* Securitate: antete de protecție pe paginile publice (fără încadrare în alte site-uri, fără scurgeri de adresă, HTTPS obligatoriu pentru cine revine, fără acces la cameră, microfon și locație).
* Securitate: vizitatorii nu mai pot afla numele conturilor din administrare (lista de utilizatori din REST, paginile de autor, oEmbed). Administratorii văd în continuare tot.
* Securitate: autentificarea greșită dă același mesaj, indiferent dacă numele există sau parola e greșită (WordPress și WooCommerce).
* Securitate: versiunea WordPress nu mai apare în cod; editorul de fișiere din administrare și XML-RPC sunt oprite.
* Nu schimbă paginile, rezervările, plățile și confirmările NETOPIA.

= 1.3.9 =
* Rezervarea nu se pierde între pagini: un vizitator cu rezervarea în coș vede „Continuă rezervarea” (buton plutitor pe desktop, bara de jos și meniul pe telefon) spre coș, în loc să fie dus mereu la un formular nou.
* Regulamentul deschis din pagina de plată: și butonul „Rezervă” din antet, meniul și bara de jos duc înapoi la plată, nu la o rezervare nouă.
* Linkurile spre pagini încă nepublicate (ciorne) nu mai dau 404 vizitatorilor.
* Linkurile din textele paginilor simple (Despre noi, Contact) cu rădăcina site-ului merg și într-un subdosar (copia de test).
* Pagina Camping: butonul din antet și bara de jos deschid formularul fără tip (cort sau rulotă). Alegerea tipului din adresă (?tip=) nu mai suprascrie alegerea vizitatorului.

= 1.3.8 =
* La plată, linkul „Deschide regulamentul într-o pagină nouă” duce acum la o pagină cu butonul „Înapoi la finalizarea rezervării” (aceeași rezervare), nu la „Înapoi la rezervare” (o rezervare nouă). Lângă link scrie că rezervarea rămâne în fila de plată.

= 1.3.7 =
* Prima pagină: calendarul pentru alegerea datelor nu mai este tăiat la marginea cardului de rezervare (se vedea doar primul rând de zile, deci nu se puteau alege date).

= 1.3.6 =
* Poze luminoase, cu soare și cer albastru, în loc de cele cu nori dominanți sau de amurg întunecat: Camping (poza de început, poza din text, închiderea, galeria), Evenimente private (poza de început, galeria, poza din text), Festivaluri (poze aeriene și corturi de glamping), Bungalow (galeria) și spațiile comune.
* Camping: poza de început are două poze mixate (corturi sub sălcii și lacul cu SUP-uri), ca în prima pagină; pe telefon se vede prima.
* Pozele cu nori rămân doar ca poze mici, nu ca poză mare. Prima poză din galerie și poza de la închidere sunt cu soare pe toate paginile.
* Umbrele de pe poza de început și de la închidere sunt mai ușoare, ca pozele să rămână luminoase.
* Fără pozele nefolosite în arhivă (arhiva e cu aproape 5 MB mai mică).

= 1.3.5 =
* Glamping: numai cele 4 glamping-uri permanente, pe platforme de lemn. Pozele cu corturi pe pământ și cele cu foișorul/umbrela albă au fost scoase de pe pagina Glamping, de pe cardul din prima pagină și de la închidere; ele rămân doar la festivaluri și evenimente.
* Poză nouă de început la Glamping (corturi pe platforme, cer senin), poză nouă cu cortul pe platformă, cu masă și scaune la intrare.
* Secțiune nouă „Spațiile comune” pe toate paginile de ofertă: băi, bucătării, vetre de foc, loc de joacă, terase, ponton și lac. Sunt ale tuturor oaspeților, inclusiv ai celor de la bungalow.
* Galeriile specifice (Bungalow, Camping & rulote, Festivaluri, Evenimente private) păstrează numai pozele locului; grilă fără goluri la 5, 8 sau 11 poze.
* Subsol: ambele adrese de email (rezervari@ și office@).

= 1.3.4 =
* Prima pagină: stânga = cer 1/3, munți, corturi jos; dreapta = o singură felie cu lacul, muntele și corturile, ziua, cer senin, și focul jos (fără felia cu terasa).
* Culori mai naturale: umbrele peste poze sunt neutre (nu albastre), iar pozele nu mai sunt intensificate.
* Poze noi din Drive: Camping (zona 1 și zona lacului, vetre, bucătărie, chiuvete), Glamping (exterior însorit, fără platforma udă), Festivaluri & tabere (vetrele cu oamenii, poze aeriene, tabără ordonată), Evenimente private (câte o poză pentru vetre, bucătărie, băi, toalete); închideri de pagină însorite.
* Fără poze aeriene și aglomerate pe Camping și pe prima pagină (rămân doar la festivaluri).
* Două adrese de email (rezervari@ și office@) la „Nu găsești răspunsul?” și în contactul din subsol.

= 1.3.3 =
* Design: pagini mai luminoase (umbre mai ușoare, doar în spatele textului) și scris mai mare, cu scară tipografică în variabile CSS (`--fs-*`); meniul trece în hamburger sub 1300 px.
* Prima pagină: colaj nou, cu cerul, munții și corturile în stânga (în spatele textului) și, pe diagonală, în dreapta: lacul, terasa din lemn și focul. Pe telefon rămâne doar cerul cu munții.
* Poze noi alese de proprietară (Drive): Bungalow (cer senin, malul lacului cu baldachin, dormitor, zonă de relaxare, baie) și Glamping (cele 4 glamping-uri permanente, pe platformă, exterior și interior), în locul pozelor de festival.
* Bungalow: baie și duș propriu, calorifere, zonă privată cu acces la lac, bucătării dotate, iluminat pe perimetru, Wi-Fi gratuit inclusiv în zona bungalow-urilor. Glamping: 4 permanente pe platformă; la cerere și la evenimente, până la 50.
* Titlurile primesc culoarea explicit, ca tema să nu o poată suprascrie (titlul din prima pagină era invizibil pe poză).

= 1.3.2 =
* „Pregătește paginile”: o pagină veche din coșul de gunoi (ex. un „Despre noi” șters demult) rămâne acolo, neatinsă, iar pagina nouă se creează alături.

= 1.3.1 =
* „Pregătește paginile”, corecturi din verificarea independentă: adresă ocupată (de alt fișier sau pagină) oprește crearea și publicarea; blocare la dublă apăsare; pagini din coș recunoscute; „Revino” păstrează copia textului nou de la Contact și nu șterge nimic dacă salvarea eșuează; pasul „Contact” se desface singur dacă eșuează.

= 1.3.0 =
* Unealta „Pregătește paginile” (Setări → Grădina Ielelor): creează paginile care lipsesc ca ciorne, pune șabloanele pe paginile existente, pune textul nou la Contact (cel vechi se păstrează) și publică paginile create; „Revino” desface totul, fără să șteargă nimic. Textele paginilor simple vin din content/pagini/.

= 1.2.1 =
* Camping & rulote: textele despre rulote corectate după răspunsurile proprietarei: fără racorduri și fără curent la locul rulotei; curent pentru telefon și laptop în spațiile comune; stație de golire doar în zona 1; Wi-Fi în zona teraselor și în zonele marcate.

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
