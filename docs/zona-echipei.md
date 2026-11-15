# Zona echipei (pluginul 1.5.0)

**Administrare** direct de pe site, fără să intri în administrarea WordPress. Utilizator + parolă.
Adresa: **gradina-ielelor.ro/zona-echipei/**. În subsolul fiecărei pagini a site-ului e o căsuță **„Administrare”** care duce acolo.
Pagina nu apare în Google și nu se păstrează în memoria browserului sau a serverului.

Ce e acum în Zonă: **Vouchere**, **Copaci și locuri** (cu cod QR) și **Echipă**.
Urmează, în aceeași Zonă, două dale noi: **QR la rezervare** și **Fișa de cazare** (cerute de proprietară, 15.11.2026; vezi „Ce urmează”).

## Intri
1. Apeși **Administrare** din subsolul site-ului (sau scrii adresa de mai sus).
2. Scrii utilizatorul și parola. Sunt **aceleași** cu cele cu care intri în administrarea WordPress (nu faci un cont nou).
3. Dacă ai uitat parola: **„Mi-am uitat parola”**, sub formular. Primești un email cu un link.
4. Ieși cu **„Ieși din cont”**, jos, pe prima pagină a Zonei.

Limitarea încercărilor repetate de parolă **nu** e în plugin (hotărâre din `docs/securitate.md`: se face din firewall-ul hostingului, ca să nu poată un atacator să blocheze pe toată lumea). Parolele generate pentru colegi sunt lungi (12 caractere, litere și cifre).

## Cine ce vede
| Cont | Vouchere | Copaci și locuri | Echipă | Administrarea WordPress |
|---|---|---|---|---|
| **Administrator** (tu) | toate | da | da | da |
| **Manager de magazin** | toate | da | nu | cum are acum |
| **Editor** | nu | da | nu | cum are acum |
| **Vânzări (Grădina)** — conturile colegilor | **numai ale lui**, în limite | nu | nu | **nu** (e trimis înapoi în Zonă) |

Colegii de la vânzări nu văd nimic din administrarea WordPress: nici articole, nici pagini, nici comenzi, nici alte conturi. Văd numai Zona echipei.

## Un coleg nou, de la vânzări
**Zona echipei → Echipă → Adaugă un coleg.** Scrii **numele** și **emailul**. Apeși „Creează contul”.
- Pe ecran apar o dată **adresa, utilizatorul și parola**, chiar în răspunsul la formular (**nu reîncărca pagina**). **Notează-le și transmite-le colegului**. Parola nu se păstrează nicăieri în site.
- **Nu se trimite niciun email** colegului. (Din motive de siguranță, parola nu pleacă prin email.)
- Colegul intră la Administrare, cu aceste date. Dacă o uită, tu îi faci alta: **„Parolă nouă”** (parola veche nu mai merge, iar sesiunile lui deschise se închid).
- Când pleacă din echipă: **„Scoate accesul”**. Contul rămâne, dar fără acces la nimic (se scoate numai rolul „Vânzări”; contul dispare din lista echipei).
- Dacă revine: la „Adaugă un coleg” scrii **același email**; contul se reactivează, cu o parolă nouă, arătată o singură dată.
- Din Zonă poți schimba parola și poți scoate accesul **numai conturilor de vânzări**, nu și conturilor tale sau ale altor roluri.

## Cât poate da un coleg de la vânzări (limite)
**Zona echipei → Echipă → Limite pentru vânzări.** Valorile de pornire sunt **provizorii** (le-am ales eu ca să nu pornească fără limite; schimbă-le după cum vrei):
- reducere maximă în procente, **pe cod**: **10%**
- reducere maximă în lei, **pe cod**: **100 lei**
- câte coduri deodată: **10**

Cu **0**, tipul respectiv e oprit (de ex. 0 la procente = colegul poate da numai sume fixe). Tu și managerii de magazin nu aveți limite.

**Ce înseamnă limita, exact:** se aplică **fiecărui cod** și fiecărei generări. Codurile generate de un coleg se pot folosi **doar câte unul la o rezervare** (nu se adună), deci la o rezervare reducerea maximă e cea de pe un cod. **Nu există o limită pe zi:** un coleg poate genera, în zile diferite sau una după alta, câte coduri vrea. Fiecare cod păstrează cine l-a generat (vezi „Toate voucherele”). Vrei și o limită pe zi? Spune-mi și o adaug.

## Vouchere
**Zona echipei → Vouchere.** Alegi tipul (sumă fixă sau procent), valoarea, câte coduri, începutul codului (de ex. GRADINA) și, dacă vrei, o notă („cadou de Crăciun”; **fără nume de persoane**).
- Valabile **18 luni**, o singură folosire, cod unic (de ex. GRADINA-K7M2-Q9XP), fiecare cu **cod QR**.
- Bifezi voucherele din listă, alegi mărimea și apeși **„Printează voucherele alese”**.
- Cine scanează codul ajunge pe site; codul se aplică singur la rezervare (la coș).
- Un coleg de la vânzări vede și printează **numai voucherele lui**.

### Prima încercare cu platforma de rezervări (înainte să dai vouchere clienților)
Voucherele sunt **cupoane WooCommerce obișnuite**, de două feluri: **sumă fixă** (în lei) sau **procent**. Sunt același sistem cu care lucrează și modulul de rezervări (BRW), dar eu **nu pot încerca aici** o rezervare reală cu BRW (modulul nu e disponibil în mediul meu de test). Faci o încercare, o dată, **pe copia de test (staging)**, nu pe site-ul real:
1. **Zona echipei → Vouchere:** generezi două coduri, unul de **10 lei** (sumă fixă) și unul de **10%** (procent), cu începutul „TEST”.
2. Pe site pornești o rezervare (de ex. Glamping), mergi la coș sau la plată și scrii codul la „Ai un cod de reducere?”.
3. Verifici: apare un rând de reducere, iar totalul scade cu 10 lei, respectiv cu 10%. Pe staging, plata (NETOPIA, mod de test) trebuie să ceară suma redusă.
4. Scanezi codul QR cu telefonul: se deschide site-ul și codul se aplică singur la coș.
5. Dacă ceva nu se potrivește (reducerea nu apare, suma de la plată nu e cea redusă, se aplică și la avans etc.), îmi spui exact ce vezi, cu o captură. Voucherele se pot vedea și în WooCommerce → Marketing → Cupoane.

## Copaci și locuri (cod QR)
**Zona echipei → Copaci și locuri → Adaugă un loc.** Scrii titlul, alegi ce este (copac, plantă, loc), numele științific (dacă vrei), o frază sub titlu, **povestea locului** și alegi **poza**. De pe telefon poți face poza chiar atunci (maximum 8 MB).
- Scrii și „ce se vede în poză” (o frază), pentru cei care nu văd imaginea.
- **Publică** = se vede vizitatorilor. Fără bifă, locul rămâne **ciornă** (nu se vede, nu se poate printa).
- După salvare apare **codul QR**: **„Printează acest cod”** sau **„Descarcă (SVG)”**. Pentru mai multe deodată: bifezi în listă și apeși „Printează codurile alese”.
- Adresa paginii **nu se schimbă** când modifici titlul sau textul, deci codul printat rămâne bun. Codul din teren **nu mai duce nicăieri** dacă scoți bifa „Publică” sau ștergi locul.
- Fotografiile făcute cu telefonul pot păstra în fișier locul unde au fost făcute (aici, chiar Grădina). Pe pagină se arată o variantă redimensionată.

Ghidul detaliat pentru codurile din teren (mărimi, unde le pui, statistici): `docs/zona-privata.md`. Aceleași funcții se găsesc și în administrarea WordPress, meniul **Grădina**.

## Ce urmează (în aceeași Zonă)
- **QR la rezervare**: un cod QR pentru fiecare rezervare finalizată (pagina de mulțumire, emailul de confirmare, contul clientului), fără date personale în cod.
- **Fișa de cazare**: la recepție, de pe telefon, scanezi codul rezervării și generezi fișa de cazare (obligație legală). Are nevoie de **modelul fișei pe care o folosiți acum** (proprietara îl trimite, 15.11) și de hotărâri despre datele personale (ce se păstrează, cât timp, cine are acces). **Nu** se face înainte să fie lămurite; textele legale se confirmă cu un jurist.
- Nu se pune nimic live fără aprobarea ta.

## Siguranță (pe scurt)
- Fiecare formular are un cod de siguranță care expiră; dacă lași pagina deschisă mult timp, apare „Pagina a expirat” și nu se salvează nimic. Încarci pagina și o iei de la capăt.
- Dacă un fișier nu e poză, primești un mesaj clar; restul se salvează. Dacă poza e prea mare pentru server, formularul **nu se trimite deloc** (și textul scris se pierde): primești un mesaj, alegi o poză mai mică și scrii din nou. Limita (cel mult 8 MB) depinde și de setările hostingului.
- Parolele noi se arată o singură dată, în răspunsul la formular; nu se trimit prin email și nu se păstrează în site.

## Dacă ceva nu merge
- **„Pagina nu a fost găsită” la /zona-echipei/** imediat după instalare: **Setări → Permalinkuri → Salvează modificările** (o dată). Pluginul face asta singur la prima rulare.
- **Revenire**: înainte să dezactivezi pluginul sau să reinstalezi o versiune fără Zonă, la **Echipă** apasă **„Scoate accesul”** pentru fiecare coleg. Altfel, rolul „Vânzări (Grădina)” rămâne în baza de date, iar fără Zonă colegii ar putea intra în administrarea WordPress ca simpli abonați (profil propriu, fără conținut). Rolul se șterge de tot numai la dezinstalare (vezi `docs/lansare.md`).
- Colegul spune că „e trimis în Zonă când vrea administrarea”: așa e făcut (nu are voie în administrare).
