# Zona echipei (pluginul 1.6.0)

**Administrare** direct de pe site, fără să intri în administrarea WordPress. Utilizator + parolă.
Adresa: **gradina-ielelor.ro/zona-echipei/**. În subsolul fiecărei pagini a site-ului e o căsuță **„Administrare”** care duce acolo.
Pagina nu apare în Google și nu se păstrează în memoria browserului sau a serverului.

Ce e acum în Zonă: **Vouchere**, **Copaci și locuri** (cu cod QR), **Recepție** (rezervări cu cod QR), **Echipă** și **Istoric**.
Urmează **Fișa de cazare** (cerută de proprietară, 15.11.2026; vezi „Ce urmează”).

## Intri
1. Apeși **Administrare** din subsolul site-ului (sau scrii adresa de mai sus).
2. Scrii utilizatorul și parola. Sunt **aceleași** cu cele cu care intri în administrarea WordPress (nu faci un cont nou).
3. Dacă ai uitat parola: **„Mi-am uitat parola”**, sub formular. Primești un email cu un link. (Asta e pentru contul tău. **Colegii de la vânzări** nu pot schimba parola: ei o cer de la tine.)
4. Ieși cu **„Ieși din cont”**, jos, pe prima pagină a Zonei.

Limitarea încercărilor repetate de parolă **nu** e în plugin (hotărâre din `docs/securitate.md`: se face din firewall-ul hostingului, ca să nu poată un atacator să blocheze pe toată lumea). Parolele generate pentru colegi sunt lungi (12 caractere, litere și cifre).

## Cine ce vede
| Cont | Vouchere | Copaci și locuri | Echipă | Administrarea WordPress |
|---|---|---|---|---|
| **Administrator** (tu) | toate | da | da | da |
| **Manager de magazin** | toate | da | nu | cum are acum |
| **Editor** | nu | da | nu | cum are acum |
| **Vânzări (Grădina)** — conturile colegilor | **numai ale lui**, în limite | nu | nu | **nu** (e trimis înapoi în Zonă) |
| **Recepție (Grădina)** — conturile colegilor de la recepție | nu | nu | nu | **nu** (e trimis înapoi în Zonă) |

La **Recepție** (căutarea rezervărilor) au acces: administratorul, managerul de magazin și conturile „Recepție (Grădina)”. **Istoricul căutărilor** de la Recepție îl vede numai administratorul.

Colegii de la vânzări nu văd nimic din administrarea WordPress: nici articole, nici pagini, nici comenzi, nici alte conturi. Văd numai Zona echipei.

## Cine creează conturile și cine ține parolele
- **Conturile le creezi tu**, ca administrator, din **Echipă → Adaugă un coleg**. **Din Zonă** nu poate crea conturi nimeni altcineva: nici managerul de magazin, nici colegii de la vânzări. (Administratorii WordPress pot, ca oricând, din administrarea WordPress.)
- **Utilizatorul și parola le alegi tu.** Poți lăsa câmpurile goale (utilizatorul se face din email, parola se generează) sau poți apăsa „Generează” pentru o parolă aleatoare. Parola are cel puțin 12 caractere și nu poate fi ușor de ghicit (fără numele Grădinii, „parola”, utilizatorul sau emailul, fără numai cifre). Pentru una bună, apasă „Generează”.
- **Parola o schimbi numai tu**, din **Echipă → Parolă nouă** (scrii una sau lași câmpul gol și se generează). **Colegul nu și-o poate schimba**: nici din Zonă, nici prin „Mi-am uitat parola” (la conturile de vânzări nu merge: răspunsul e ca pentru un cont inexistent și nu se trimite niciun email, ca să nu se poată afla cine e coleg de la vânzări), nici din profil sau Contul meu.
- După ce o schimbi, parola veche nu mai merge, iar sesiunile deschise ale colegului se închid.
- Parola **nu se păstrează în clar**: după ce ai creat contul sau ai dat „Parolă nouă”, ți se arată o singură dată, ca să i-o transmiți; apoi n-o mai vede nimeni. Dacă o pierzi, dai din nou „Parolă nouă”.
- Tot ce faci cu conturile (creat, parolă nouă, acces scos) rămâne în **Istoric**, fără parole.

## Un coleg nou, de la vânzări
**Zona echipei → Echipă → Adaugă un coleg.** Scrii **numele**, **emailul**, **utilizatorul** și **parola** (sau lași ultimele două goale). Apeși „Creează contul”.
- Pe ecran apar o dată **adresa, utilizatorul și parola**, chiar în răspunsul la formular (**nu reîncărca pagina**). **Notează-le și transmite-le colegului**. Parola nu se păstrează nicăieri în site. Dacă ai reîncărcat sau ai pierdut pagina, apeși „Parolă nouă” și iei alta.
- **Nu se trimite niciun email** colegului. (Din motive de siguranță, parola nu pleacă prin email.)
- Colegul intră la Administrare, cu aceste date. Dacă o uită, tu îi faci alta: **„Parolă nouă”** (parola veche nu mai merge, iar sesiunile lui deschise se închid).
- Când pleacă din echipă: **„Scoate accesul”**. Contul rămâne, dar fără acces la nimic (se scoate numai rolul „Vânzări”; contul dispare din lista echipei).
- Dacă revine: la „Adaugă un coleg” scrii **același email**; contul se reactivează, cu o parolă nouă, arătată o singură dată.
- Dacă colegul are deja un **cont de client** pe site (a rezervat și el): la „Adaugă un coleg”, cu același email, primește rolul de vânzări **și își păstrează parola de până acum**. Conturile cu alte roluri (editor, manager etc.) nu se schimbă de aici.
- Din Zonă poți schimba parola și poți scoate accesul **numai conturilor de vânzări**, nu și conturilor tale sau ale altor roluri.

## Cât poate da un coleg de la vânzări (limite)
**Zona echipei → Echipă → Limite pentru vânzări.** Valorile de pornire sunt **provizorii** (le-am ales eu ca să nu pornească fără limite; schimbă-le după cum vrei):
- reducere maximă în procente, **pe cod**: **10%**
- reducere maximă în lei, **pe cod**: **100 lei**
- câte coduri deodată: **10**

Cu **0**, tipul respectiv e oprit (de ex. 0 la procente = colegul poate da numai sume fixe). Tu și managerii de magazin nu aveți limite.

**Ce înseamnă limita, exact:** se aplică **fiecărui cod** și fiecărei generări. Codurile generate de un coleg **nu se combină cu alte coduri**: la o rezervare se aplică unul singur (dacă un client are în coș alt cod, cel nou îl înlocuiește). Deci la o rezervare reducerea maximă e cea de pe un cod. **Nu există o limită pe zi:** un coleg poate genera, în zile diferite sau una după alta, câte coduri vrea. Fiecare cod păstrează cine l-a generat (vezi „Toate voucherele”). Vrei și o limită pe zi? Spune-mi și o adaug.

## Vouchere
**Zona echipei → Vouchere.** Alegi tipul (sumă fixă sau procent), valoarea, câte coduri, începutul codului (de ex. GRADINA) și, dacă vrei, o notă („cadou de Crăciun”; **fără nume de persoane**).
- Valabile **18 luni**, o singură folosire, **în întregime** (nu se poate folosi parțial; dacă rezervarea e mai mică decât valoarea codului, **diferența se pierde**: nu se împarte și nu rămâne pe cod), cod unic (de ex. GRADINA-K7M2-Q9XP), fiecare cu **cod QR**.
- Bifezi voucherele din listă, alegi mărimea și apeși **„Printează voucherele alese”**.
- Cine scanează codul ajunge pe site; codul se aplică singur la rezervare (la coș).
- Un coleg de la vânzări vede și printează **numai voucherele lui**.

### Ștergerea unui voucher gata creat
În lista de vouchere bifezi unul sau mai multe și apeși **„Șterge voucherele alese”** (apare confirmarea). Numai administratorul și managerii de magazin pot șterge; colegii de la vânzări nu.
- Se șterg numai voucherele **nefolosite**. Unul folosit rămâne în listă (e dovada reducerii de pe comandă).
- Voucherul șters **nu se mai poate folosi** (nici cu codul scris, nici cu QR-ul printat).
- Nu dispare definitiv: merge în **Coșul WooCommerce** (WooCommerce → Marketing → Cupoane → Coș) și un administrator îl poate readuce în 30 de zile (dacă hostingul a dezactivat coșul, ștergerea e definitivă; ecranul te avertizează). Un voucher readus din coș rămâne **oprit** până îl publici din nou.
- Ștergerea rămâne în **Istoric** (cine, când, ce cod).

### Istoric de creare
**Zona echipei → Istoric:** cine a generat vouchere (cu codurile), cine a șters vouchere, cine a adăugat sau a modificat locuri, cine a creat conturi, a dat parole noi, a scos accesul sau a schimbat limitele, cu data și ora. Poți filtra pe Vouchere, Locuri sau Conturi. Rămâne și după ce voucherul sau locul e șters. **Parolele nu apar niciodată.**
- Fiecare vede doar ce are voie să administreze: voucherele (administrator, manager de magazin), locurile (cine administrează copacii), conturile (numai administratorul).
- Istoricul începe de la instalarea acestei versiuni: ce s-a creat înainte nu apare aici (voucherele mai vechi se văd în lista de vouchere, cu cine le-a generat).
- Se păstrează într-un tabel al site-ului (`gi_istoric`), care rămâne chiar dacă pluginul e șters. Conține numele și utilizatorii colegilor, notele de la vouchere și titlurile locurilor, nu și parole. Orele se arată în ora site-ului. Nu are ștergere automată: dacă vrei să-l golești sau să-l ștergi, se face din baza de date (spune-mi).
- Notele de la vouchere au cel mult 200 de caractere.

### Prima încercare cu platforma de rezervări (înainte să dai vouchere clienților)
Voucherele sunt **cupoane WooCommerce obișnuite**, de două feluri: **sumă fixă** (în lei) sau **procent**. Sunt același sistem cu care lucrează și modulul de rezervări (BRW), dar eu **nu pot încerca aici** o rezervare reală cu BRW (modulul nu e disponibil în mediul meu de test). Faci o încercare, o dată, **pe copia de test (staging)**, nu pe site-ul real:
1. **Zona echipei → Vouchere:** generezi două coduri, unul de **10 lei** (sumă fixă) și unul de **10%** (procent), cu începutul „TEST”.
2. Pe site pornești o rezervare (de ex. Glamping), mergi la coș sau la plată și scrii codul la „Ai un cod de reducere?”.
3. Verifici: apare un rând de reducere, iar totalul scade cu 10 lei, respectiv cu 10%. Pe staging, plata (NETOPIA, mod de test) trebuie să ceară suma redusă.
4. Scanezi codul QR cu telefonul: se deschide site-ul și codul se aplică singur la coș.
5. Dacă ceva nu se potrivește (reducerea nu apare, suma de la plată nu e cea redusă, se aplică și la avans etc.), îmi spui exact ce vezi, cu o captură. Voucherele se pot vedea și în WooCommerce → Marketing → Cupoane.

## Recepție: rezervări cu cod QR
**Ce primește turistul.** După ce rezervarea e finalizată (plătită cu cardul sau cu plata la locație), pe pagina de mulțumire apare **„Codul tău de rezervare”**: un cod scurt (ex. `7K4M-Q2XP`) și un cod QR. Același cod e în Contul meu, în emailul de confirmare (scris și cu link) și pe pagina `/cod-rezervare/<cod>/`, cu codul QR mare, de arătat pe telefon sau de printat. Codul QR conține **numai** numărul rezervării (`GI1.7K4M-Q2XP.<sumă de control>`), fără nume, telefon sau email, iar pagina nu apare în Google.

**Ce faci la recepție.** Zona echipei → **Recepție**:
1. Apeși **„Scanează cu camera”**, accepți camera (prima dată telefonul întreabă) și îndrepți telefonul spre codul turistului. Când îl citește, caută singur rezervarea. Merge și cu codul scris de mână în câmpul de dedesubt (cu sau fără cratimă, cu litere mari sau mici).
2. Vezi: titularul, telefonul, sosirea, plecarea, nopțile, adulții, copiii, starea plății (**plătită** / **neplătită încă**, la plata la locație) și toate câmpurile din comandă. Dacă rezervarea e anulată sau nefinalizată, apare un avertisment roșu.
3. Dacă scrie „suma de control nu se potrivește”, codul a fost citit incomplet: scanează din nou.

**Rezervări făcute înainte de codul QR (fără cod).** În Recepție, a doua casetă: scrii **numărul comenzii** (din emailul de confirmare) și **numele de familie** al titularului. Ambele trebuie să se potrivească (diacriticele și literele mari nu contează). Numărul singur nu ajunge: recepția nu poate răsfoi comenzile.

**Plata la locație.** Dacă turistul a ales „plata la locație / la livrare”, Recepția scrie **„de încasat la recepție”** și suma, chiar dacă WooCommerce numește comanda „În procesare”. „Plătită” apare numai la plăți încasate. Pagina de mulțumire a unei rezervări cu cardul, încă neconfirmate de bancă (câteva minute), spune turistului că „Codul apare aici și în email imediat ce banca confirmă plata”.

**Cum merge pe un telefon simplu.** Se deschide în browserul telefonului (Chrome, Safari, Samsung Internet), pe `https`, fără aplicație din magazin. Camera cere permisiunea o dată. Dacă telefonul nu are cameră sau o refuzi, scrii codul. Imaginea de la cameră nu pleacă de pe telefon.

**Prima încercare (pe copia de test, înainte de live).** Pe copia de test, cu plata în regim de probă (sandbox):
1. Faci o rezervare de probă până la pagina de mulțumire. Verifici că apare **„Codul tău de rezervare”** (cod + cod QR). Pe copia de test emailurile către clienți sunt oprite, deci codul din email îl verifici în comanda din administrare (se vede „Cod rezervare (QR)”).
2. Deschizi pagina de mulțumire pe calculator, iar de pe **telefonul de la recepție** intri în Zona echipei → **Recepție** → **Scanează cu camera** și îndrepți telefonul spre codul de pe ecran. Trebuie să apară rezervarea, cu datele corecte (sosire, plecare, adulți, copii, plătită sau de încasat).
3. Încerci și cu **codul scris**, și cu o rezervare cu **plata la locație**.
4. Dacă telefonul nu pornește camera, îmi spui ce telefon e (iPhone sau Android) și ce browser; atunci scrii codul și mergem mai departe.
Ce nu pot verifica eu din calculator: camera unui telefon real (am încercat cu o cameră simulată) și datele exacte pe care le pune modulul de rezervări (sosire, plecare, adulți, copii) pe o rezervare adevărată. Dacă apare „nu apar în comandă” la adulți sau copii, îmi spui cum se numesc câmpurile în comandă și le adaug.

**Cont pentru recepție.** Echipă → Adaugă un coleg → rolul **„Recepție”**. Colegul vede numai Recepția: nu vede vouchere, copaci, echipă sau istoric și nu intră în administrarea WordPress. Utilizatorul și parola le alegi tu, iar parola o schimbi numai tu (ca la vânzări).

**Istoric.** Fiecare căutare apare în Istoric → **Recepție** („A căutat rezervarea …”), cu cine a căutat, fără numele sau telefonul turistului. O singură linie pe rezervare și pe persoană într-un minut; la „Tot” nu apar (sunt multe, zilnice).

**Ce nu face (încă).** Nu creează fișa de cazare și nu citește actul de identitate: asta e etapa următoare (vezi „Ce urmează”). Datele de sosire, plecare, adulți și copii se citesc din câmpurile pe care le pune modulul de rezervări în comandă; dacă un câmp lipsește din comandă, Recepția scrie „nu apar în comandă”, nu inventează.

## Copaci și locuri (cod QR)
**Zona echipei → Copaci și locuri → Adaugă un loc.** Scrii titlul, alegi ce este (copac, plantă, loc), numele științific (dacă vrei), o frază sub titlu, **povestea locului** și alegi **poza**. De pe telefon poți face poza chiar atunci (maximum 8 MB).
- Scrii și „ce se vede în poză” (o frază), pentru cei care nu văd imaginea.
- **Publică** = se vede vizitatorilor. Fără bifă, locul rămâne **ciornă** (nu se vede, nu se poate printa).
- După salvare apare **codul QR**: **„Printează acest cod”** sau **„Descarcă (SVG)”**. Pentru mai multe deodată: bifezi în listă și apeși „Printează codurile alese”.
- Adresa paginii **nu se schimbă** când modifici titlul sau textul, deci codul printat rămâne bun. Codul din teren **nu mai duce nicăieri** dacă scoți bifa „Publică” sau ștergi locul.
- Fotografiile făcute cu telefonul pot păstra în fișier locul unde au fost făcute (aici, chiar Grădina). Pe pagină se arată o variantă redimensionată.

Ghidul detaliat pentru codurile din teren (mărimi, unde le pui, statistici): `docs/zona-privata.md`. Aceleași funcții se găsesc și în administrarea WordPress, meniul **Grădina**.

## Ce urmează (în aceeași Zonă)
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
