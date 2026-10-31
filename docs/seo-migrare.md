# Lansarea site-ului nou fără pierderi în Google

**Data verificării:** 31.10.2026
**Cum am verificat:** din exterior, numai citire: harta site-ului pentru Google (sitemap), `robots.txt`, paginile publice și datele publice ale WordPress. Cereri rare (sub 2 pe secundă), fără formulare și fără autentificare.
**Fișier pregătit:** `gradina-ielelor-integration/includes/redirects.php`: redirecționările de la adresele vechi la cele noi. **Nu este activat încă.** Se activează după verificare (anexa D).

---

## 1. Pe scurt

### Ce am găsit
- **71 de adrese** pe site-ul de acum: **59** sunt în harta site-ului pentru Google. Alte **12** le-am găsit în meniu, în magazin și pe pagina Festival: 5 produse de rezervare, pagina Festival, formularul de cerere de ofertă, o pagină de test, o categorie de produse și 3 categorii de blog goale. Mai sunt și **2 PDF-uri**: Regulamentul intern și fișa tehnică Festival.
- Toate răspund normal, cu o excepție: cele **3 categorii de blog din hartă** (Aventuri, Noutăți, Timp liber) dau eroare „pagină negăsită” pentru Google (secțiunea 7).
- **34 de adrese** îi spun lui Google că adresa lor „oficială” e pe `dev24.gradina-ielelor.ro`, o copie de lucru veche care azi nici nu mai funcționează (secțiunea 2).

### Ce se întâmplă cu adresele vechi

| Ce facem | Câte | Pe înțeles |
|---|---:|---|
| **Rămân la fel** | 48 | Aceeași adresă. Unele primesc doar designul nou (pagina principală, Glamping, Camping). |
| **Redirecționare 301** | 8 | Cine intră pe adresa veche ajunge automat pe pagina nouă. Google mută acolo și „vechimea” paginii. |
| **Rămân, dar ascunse din Google (noindex)** | 10 | 8 sunt deja ascunse (coș, cont, finalizare, 4 produse, formularul de ofertă). Mai ascundem 2: Info Covid-19 și categoria de produse „Oricare”. |
| **Ștergere, după confirmarea ta** | 2 | Pagina de test „#demo_gallery” și produsul „Cort Campare Test”. |
| **Fără nicio acțiune** | 3 | Categorii de blog goale, invizibile oricum. |

Tabelul complet: secțiunea 3. Fiecare adresă, cu titlu, H1 și celelalte date: anexa B.

### Cele mai mari 5 riscuri la lansare și ce facem

1. **Pagini noi lipsă = vizitatori pe „pagină negăsită”.** Header-ul și footer-ul noi trimit la `/despre-noi/` și `/regulament-politici/`, care azi **nu există**. `/bungalow/` și `/rulote/` nu există nici ele.
   → Le publicăm înainte de lansare. Activăm apoi redirecționările și verificăm fiecare adresă veche cu tabelul din secțiunea 3.
2. **Titlurile vechi din Yoast rămân peste cele noi.** `/glamping/`, `/camping/` și pagina principală au deja titlu și descriere scrise în Yoast, iar pluginul nou le respectă. Textele vechi au greșeala „Sambata **se** Sus” și promit „Facilitati All Inclusive”.
   → Ștergi sau înlocuiești textele în Yoast (secțiunea 4.1).
3. **Adresa „dev24”.** 34 de adrese au adresa oficială greșită: paginile QR, rezervările, 3 articole și categoriile. Aceeași greșeală apare din nou dacă site-ul nou e construit pe o adresă de test și apoi mutat.
   → Re-salvare din WordPress, fără programator (secțiunea 2). După mutare, verificăm sursa paginilor.
4. **Pagina principală pierde cuvintele căutate.** Acum titlul mare (H1) spune „Glamping - Camping, Brasov, Romania”. În designul nou spune „Unde natura devine acasă.” Pagina principală este cea mai puternică pagină a site-ului în Google.
   → Păstrăm titlul Yoast al paginii principale la lansare. Adăugăm lângă titlul mare o frază cu „glamping, camping și bungalow sub Munții Făgăraș” (de stabilit cu cine lucrează la texte). Nu schimbăm totul deodată.
5. **Linkuri care dispar.** Header-ul și footer-ul noi nu mai trimit la Contact, Blog, Galerie, Info util (întrebări frecvente), Termeni, Confidențialitate, Cookies și la linkurile ANPC/SOL. Linkurile ANPC/SOL sunt obligatorii pentru vânzarea online. Fără linkuri, paginile acestea „se pierd” și pentru Google. Pe de altă parte, meniurile vechi trimit încă la adresele vechi.
   → Adăugăm în footer-ul nou linkurile legale, Contact și Blog. Actualizăm meniurile vechi spre adresele noi (secțiunea 5).

### Ce poți face singur, din WordPress
- Repari „dev24” (secțiunea 2, cam 30 de minute).
- Golești titlurile și descrierile vechi din Yoast pe paginile reutilizate (secțiunea 4.1).
- Treci pe „Ciornă” paginile vechi, după ce redirecționările merg (secțiunea 5).
- Retragi produsul de test, după ce verifici comenzile (secțiunea 3).
- Trimiți harta site-ului în Google Search Console după lansare (secțiunea 7).

### Ce am nevoie să confirmi
Întrebările sunt în secțiunea 8 (7 întrebări scurte).

> **De lămurit:** cererea de lansare spune că designul nou se pune pe WordPress-ul existent. `docs/decizii.md` (30.10.2026) spune că site-ul nou se face pe un WordPress nou, curat. Harta adreselor e aceeași în ambele cazuri. Ce mai trebuie în plus la un WordPress nou: secțiunea 6.

---

## 2. Problema „dev24”: adresa oficială greșită

### Pe înțeles
Fiecare pagină îi spune lui Google care e adresa ei „oficială” (*canonical*). La 34 de adrese, adresa oficială e scrisă greșit: `https://dev24.gradina-ielelor.ro/...` în loc de `https://gradina-ielelor.ro/...`. `dev24` a fost o copie de lucru. Azi trimite la google.ro și are certificat greșit, deci Google nu o poate deschide. Rezultatul: Google primește un semnal contradictoriu și poate trata pagina ca dublură sau o poate ignora.

**Unde apare:**
- **Info QR** și toate cele **17 pagini cu plante** (codurile QR tipărite duc aici);
- **Rezervări** (`/shop/`), **Contul meu** (și „Recuperare parolă”), **Rezervarea mea** (coșul), **Finalizare rezervare**;
- 3 articole: „Energia naturii…”, „Redefinește felul…”, „Reîncarcă-ți bateriile…”;
- 6 categorii de articole: Aventuri, Noutăți, Timp Liber, Plante din Grădina Ielelor, Uncategorized, Uncategorized @ro;
- 2 categorii de produse: Glamping și Rulote;
- pagina de test „#demo_gallery”;
- în plus, toate cele 5 pagini de produs arată „Rezervări” cu adresa dev24 în firul de navigare (*breadcrumb*) din datele pentru Google.

### De unde vine
**Nu vine din temă și nu e scris de mână în cod.** Vine din **Yoast SEO**, din „memoria” lui internă: un tabel în care Yoast ține adresa fiecărei pagini.

Site-ul a fost construit pe `dev24` și mutat apoi pe adresa reală, cel mai probabil în primăvara lui 2024. Yoast a păstrat adresa veche pentru paginile care **nu au mai fost salvate de atunci**.

Dovezile: toate paginile cu problema au ultima modificare până la 28.03.2024, iar cele salvate după aceea sunt corecte. Adresa veche apare și în firul de navigare, pe care Yoast îl ia din memoria lui, nu din câmpul de canonical completat manual. Detalii: anexa C.

Din afară nu pot exclude complet ca pe câteva pagini să fi fost completat de mână câmpul Yoast „URL canonic”. Pasul 5 de mai jos verifică asta.

### Cum repari: varianta A, re-salvare (recomandată, cam 30 de minute)
Re-salvarea nu schimbă textul paginilor. Yoast își recalculează doar adresa.

0. Fă o copie de siguranță din hosting (fișiere și bază de date).
1. **Pagini → Toate paginile.** La fiecare pagină de mai jos: ține mouse-ul pe titlu → **Editare rapidă** → **Actualizează**. Nu schimba nimic altceva.
   Pagini: *Info QR*, cele 17 plante (Afin Românesc, Agrișul, Anason, Aronia, Busuioc, Cătina, Coacăz, Coriandru, Lavandă, Lonicera – Afin Siberian, Măceș, Menta, Murul, Mușețelul, Socul, Trandafirul de dulceață, Zmeur), *Rezervări*, *Contul Meu*, *Rezervarea Mea*, *Finalizare Rezervare*.
   Pagina *#demo_gallery* nu o re-salva, se șterge (secțiunea 3).
2. **Articole → Toate articolele:** la fel, pentru cele 3 articole de mai sus.
3. **Articole → Categorii:** **Editare rapidă** → **Actualizează categoria**, pentru cele 6 categorii.
4. **Produse → Categorii:** la fel, pentru *Glamping* și *Rulote*.
5. **Verifică:** deschide pagina într-o fereastră privată (nelogat). Clic dreapta → **Vezi sursa paginii** → Ctrl+F → `dev24`. Trebuie să apară **0 rezultate**.
   Dacă tot apare: deschide pagina în editor → caseta **Yoast SEO** → fila **Avansat** → câmpul **URL canonic**. Dacă acolo scrie o adresă cu dev24, șterge tot textul din câmp și apasă **Actualizează**.
6. Dacă e instalat pluginul de cache al serverului: **LiteSpeed Cache → Golește tot** (*Purge All*). Apoi verifică din nou o pagină QR și pagina Rezervări.

### Cum repari: varianta B, totul dintr-odată (dacă A nu ajunge)
1. Fă o copie de siguranță.
2. **Module → Adaugă modul** → instalezi pluginul oficial **Yoast Test Helper** (autor: Team Yoast).
3. **Unelte → Yoast Test Helper** → butonul **Reset indexables tables & migrations**.
4. **Yoast SEO → Unelte** → **Optimizează datele SEO** (*Start SEO data optimization*) și aștepți să termine.
5. Dezactivezi și ștergi Yoast Test Helper.

Titlurile și descrierile scrise de tine în Yoast **nu se pierd**: sunt salvate separat. Se reconstruiește doar memoria. E un plugin în plus pe site-ul live, temporar, deci are nevoie de aprobarea ta.

### După reparare
- **Search Console → Inspectare URL** pentru `/info-qr/`, 2–3 plante, `/shop/` → **Solicită indexarea**.
- **DNS:** subdomeniul `dev24` trimite azi la google.ro. Dacă nu îl mai folosește nimeni, ștergi înregistrarea `dev24` din panoul domeniului (Romarg) sau din cPanel → *Zone Editor*. Nu e urgent după ce adresele sunt reparate.
- **Se poate face acum, înainte de lansare?** Da. Paginile reparate rămân și pe site-ul nou, iar re-salvarea nu schimbă conținutul. Cere totuși aprobarea ta, pentru că atinge site-ul live.

---

## 3. Harta adreselor: vechi → nou

### 3.1 Redirecționări 301 (8). Sunt în `includes/redirects.php`

În prima săptămână după lansare sunt **temporare (302)**, ca revenirea la site-ul vechi să fie curată. La D+7, proprietarul bifează **Setări → Grădina Ielelor → Redirecționări permanente (301)** (`docs/lansare.md`, secțiunea 9). Verificările de mai jos așteaptă 302 până atunci, 301 după.

Fiecare redirecționare pornește singură **numai după ce pagina nouă e publicată**. Până atunci, adresa veche funcționează ca acum, fără „pagină negăsită”.

| Adresa veche | Ce era | Adresa nouă | Pornește când |
|---|---|---|---|
| `/cazare-bungalow/` | „Cazare Bungalow” din meniu | `/bungalow/` (pagină nouă) | e publicată pagina cu template-ul Bungalow |
| `/camping-cort/` | „Camping Cort” din meniu | `/camping/` | pagina `/camping/` primește template-ul Camping |
| `/camping-rulota/` | „Camping Rulotă” din meniu | `/camping/` | pagina `/camping/` primește template-ul Camping & rulote |
| `/rulote/` | nu există pe live (pagină din planul inițial) | `/camping/` | la fel |
| `/gradina-ielelor-totul-despre-ea/evenimente/` | „Evenimente” (nunți, botezuri, majorate, seminarii, teambuilding) | `/evenimente-private/` (pagină nouă) | e publicată pagina cu template-ul Evenimente private |
| `/evenimente/` | arată azi aceeași pagină veche „Evenimente” | `/festivaluri-si-tabere/` (pagină nouă) | e publicată pagina cu template-ul Festivaluri & tabere |
| `/gradina-ielelor-totul-despre-ea/` | „Despre Noi” din meniu | `/despre-noi/` (pagină nouă) | e publicată pagina `/despre-noi/` |
| `/tarife/` | „Tarife”, sezon 2026 | `/` (pagina principală) | hub-ul de rezervare e setat ca prima pagină |
| `/tarife-2026/` | dublura identică a paginii Tarife | `/` | la fel |
| `/acasa2023/` | pagina principală veche, cu „Sezon 2025” | `/` | la fel |

De ce merg Tarifele la pagina principală: prețurile tuturor ofertelor stau pe cardurile ei, iar rezervarea e sus. Fiecare pagină dedicată are și ea prețurile. Alternativa: o pagină nouă `/tarife/` cu toate prețurile (întrebarea 4).

Adresele redirecționate își păstrează parametrii de după „?”: `utm_…`, `fbclid`, `gclid` și `ref` pentru agenți. Asta înseamnă că sursa vizitei și comisionul agentului nu se pierd.

### 3.2 Rămân la aceeași adresă (48)

| Adresa | Ce se schimbă |
|---|---|
| `/` | Devine hub-ul de rezervare. Pune template-ul *Pagina principală (rezervare)* **pe pagina existentă „Acasă2025”**, ca să rămână titlul și descrierea din Yoast. |
| `/glamping/` | Pagina existentă „Cazare Glamping” primește template-ul *Glamping*. Adresa rămâne. |
| `/camping/` | Pagina existentă „Camping” primește template-ul *Camping*. Adresa rămâne. |
| `/festival/` | Neatinsă: e un fișier separat pe server, în afara WordPress. Rămâne indexată. |
| `/gradina-ielelor-totul-despre-ea/timp-liber/` | Rămâne: are text unic („De vizitat / De făcut”: Mănăstirea Brâncoveanu, Cetatea Făgărașului etc.). Opțional, după ce textul e mutat în *Despre noi*: 301 spre `/despre-noi/`. Linia e pregătită, comentată, în `redirects.php` (întrebarea 3). |
| `/galerie-foto/`, `/contact/`, `/blog-2/` | Rămân. |
| `/info-util/` | Rămâne: întrebări frecvente, cu date structurate FAQ. |
| `/info-util/termeni-si-conditii/`, `/info-util/politica-de-confidentialitate/`, `/info-util/politica-cookies/`, `/info-util/politica-de-anulare/` | Rămân: sunt legate din comenzi și din finalizarea rezervării. *Politica de anulare* trebuie actualizată cu regula voucherului (se schimbă textul, nu adresa). |
| `/info-qr/` și cele 17 pagini de plante | **Rămân obligatoriu**: codurile QR tipărite duc aici. Repari doar „dev24”. |
| cele 9 articole de blog | Rămân. 3 au „dev24” de reparat. „Energia naturii…” are 4 linkuri spre un site foarte vechi (`…/index.html`) care dau „pagină negăsită”: trebuie scoase din text. |
| `/./aventuri/`, `/./noutati/`, `/./timp-liber/` | Rămân. Azi Google le vede ca „pagină negăsită”: secțiunea 7, punctul 1. |
| `/shop/` (Rezervări) | Rămâne: aici apar rezultatele căutării de rezervare. Repari „dev24”. |
| `/product-category/glamping/`, `/bungalow/`, `/camping/`, `/rulote/` | Rămân: fac parte din motorul de rezervări. Repari „dev24” la Glamping și Rulote. Opțional, la 2–4 săptămâni după lansare: noindex, ca să nu concureze cu paginile dedicate (întrebarea 6). |

### 3.3 Rămân, dar ascunse din Google: noindex (10)

| Adresa | Situația | Ce faci |
|---|---|---|
| `/rezervarea-mea/` (coș), `/finalizare-rezervare/`, `/contul-meu/` | Sunt deja noindex (WooCommerce), dar apar în harta site-ului. | În editorul fiecăreia: Yoast → **Avansat** → „Permiți motoarelor de căutare să afișeze această pagină…?” → **Nu**. Așa ies din hartă. |
| `/produs/glamping/`, `/produs/bungalow/`, `/produs/campare-cort/`, `/produs/campare-rulota/` | Sunt deja noindex, din setările Yoast. | Nimic. **Nu** se redirecționează: sunt motorul de rezervări. |
| `/cotatie/` | Formularul de cerere de ofertă, legat din `/festival/`. Deja noindex. | Nimic. |
| `/info-util/info-covid-19/` | Informații depășite. | Noindex acum. Sau ștergere și 301 spre `/info-util/`, linie pregătită în `redirects.php` (întrebarea 2). |
| `/product-category/oricare/` | Categorie goală „Oricare”, probabil opțiunea „oricare” din căutare. | Noindex: în **Produse → Categorii → Oricare**, Yoast → **Nu**. |

### 3.4 Ștergere, după confirmarea ta (2)

| Adresa | Ce e | Pași |
|---|---|---|
| `/demo_gallery/` | Pagină de test „#demo_gallery”, protejată cu parolă, cu „dev24”. Nu e în hartă. | Pagini → *#demo_gallery* → **Mută la gunoi**. Nu trebuie redirecționare. |
| `/produs/cort-campare-test/` | Produsul de test „Cort Campare Test”, vizibil în catalog, la 80 lei/zi. | 1. **WooCommerce → Comenzi**: caută „Cort Campare Test”. Trebuie să nu existe rezervări.<br>2. **Produse** → produsul → **Ciornă** (sau **Privat**).<br>3. După o lună fără probleme, îl ștergi. |

### 3.5 Fără acțiune (3)
Categoriile de blog goale *Plante din Grădina Ielelor*, *Uncategorized* și *Uncategorized @ro*. Nu sunt în hartă și nu au articole.

### 3.6 Adrese noi care trebuie să existe la lansare

| Adresa | Azi pe live | Cine o face |
|---|---|---|
| `/bungalow/` | „pagină negăsită” (404) | pagină nouă cu template-ul Bungalow |
| `/festivaluri-si-tabere/`, `/evenimente-private/` | „pagină negăsită” (404) | pagini noi cu template-urile Festivaluri & tabere și Evenimente private. `/evenimente/` (azi vechea pagină Evenimente) duce singură la Festivaluri & tabere |
| `/despre-noi/`, `/regulament-politici/` | „pagină negăsită” (404) | pagini noi, cu template-ul *Pagină simplă*. **Sunt legate din header și footer pe toate paginile noi** |

Două atenționări:
- **Contact:** pune template-ul *Pagină simplă* **pe pagina existentă** `/contact/`. Nu crea o pagină nouă: ar primi adresa `/contact-2/`.
- **Despre noi:** fă o pagină nouă `/despre-noi/`. **Nu redenumi** pagina veche „Grădina Ielelor. Totul despre ea”: s-ar schimba și adresele paginilor ei (Timp liber, Evenimente), care ar da 404.

### 3.7 Ce NU redirecționăm, intenționat
Produsele, coșul, finalizarea rezervării, contul, magazinul și categoriile de produse (motorul de rezervări), paginile QR, paginile legale și `/festival/`.

### 3.8 Linkuri din afara site-ului
Linkurile de pe alte site-uri (Facebook, camping.info, Booking, agenții) le vede numai Search Console, la care nu am acces. În prima săptămână după lansare, raportul **Pagini → Negăsită (404)** arată dacă vin vizitatori pe alte adrese vechi. Le adăugăm atunci în hartă.

---

## 4. Titluri și descrieri (ce apare în Google)

### 4.1 Atenție: pe paginile reutilizate, Yoast are prioritate
Pluginul nou are titluri și descrieri gata scrise pentru fiecare pagină dedicată. **Dacă pagina are deja titlu sau descriere în Yoast, se folosește textul din Yoast.**

- **Pagini noi** (`/bungalow/`, `/festivaluri-si-tabere/`, `/evenimente-private/`): se folosesc automat textele din plugin. Nu ai nimic de făcut.
- **Pagini reutilizate** (`/glamping/`, `/camping/`): păstrează azi textele vechi:
  - „Glamping in Romania - Gradina Ielelor - Glamping Sambata **se** Sus”;
  - „Camping in Romania - … Camping Sambata **se** Sus”, cu descrierea „Facilitati **All Inclusive**”.

  Ce faci la fiecare: editorul paginii → caseta **Yoast SEO** → **șterge** câmpurile „Titlu SEO” și „Meta descriere”. Varianta cealaltă: completezi caseta **„Grădina Ielelor — SEO server-side”**, care are prioritate față de Yoast, cu textele din tabelul de mai jos.
- **Pagina principală:** păstrează la lansare titlul Yoast de acum („Locatie Glamping Romania - Camping, Brasov, Gradina Ielelor”). Îl schimbăm după 4–6 săptămâni, cu datele din Search Console.

### 4.2 Recomandări pe pagină
Am comparat titlurile din `includes/offer-pages.php` (cheile `seo`) cu ce caută oamenii. Am propus schimbări numai unde ajută. Faptele rămân cele din pagină.

| Pagina | Titlu acum în plugin | **Propunere titlu** | Descriere | De ce |
|---|---|---|---|---|
| `/glamping/` | Glamping Sâmbăta de Sus, Brașov \| Grădina Ielelor | **Glamping Brașov, sub Munții Făgăraș \| Grădina Ielelor** (53) | **Nouă:** „Corturi de glamping cu pat adevărat, pe platformă de lemn, în Sâmbăta de Sus, la poalele Munților Făgăraș. Două lacuri, dușuri calde non-stop. Rezervă online.” (158) | Oamenii caută „glamping brasov” și „glamping fagaras”, nu numele satului. Satul rămâne în descriere. Descrierea de acum („Descoperă… Vezi facilitățile…”) nu spune nimic concret; cea nouă spune ce primești. |
| `/bungalow/` | Bungalow Sâmbăta de Sus, Brașov \| Grădina Ielelor | **Bungalow pe malul lacului, Brașov \| Grădina Ielelor** (51) | **Rămâne** cea din plugin (patru bungalow-uri, baie proprie, încălzire, malul lacului). | Lacul e diferența față de alte bungalow-uri. Răspunde la „bungalow lac brasov”. |
| `/camping/` | Camping Sâmbăta de Sus, Brașov \| Grădina Ielelor | **Camping cu cortul și rulota, sub Munții Făgăraș \| Grădina Ielelor** (66) | **Actualizată 31.10:** pagina unește campingul cu cortul și rulotele: „Camping cu cortul, rulota sau autorulota la Sâmbăta de Sus (Brașov): două lacuri, dușuri calde non-stop, bucătării comune, vetre de foc. La 20 min de Transfăgărășan.” | Acoperă „camping Făgăraș”, „camping rulote”, „camping autorulote” |
| `/rulote/` | — | — | **Fără pagină proprie (31.10):** rulotele sunt pe `/camping/`; `/rulote/` și `/camping-rulota/` redirecționează acolo. | — |
| `/festivaluri-si-tabere/` | – (pagină nouă) | **Festivaluri și tabere sub Munții Făgăraș \| Grădina Ielelor** (58) | **Rămâne** cea din plugin (aviz ISU 5.000, scenă permanentă, două lacuri, cazare, tabere de copii). | Cine caută „locatie festival brasov” e organizator. De aici ajunge, prin „Cere ofertă”, la `/festival/`, cu fișa tehnică. |
| `/evenimente-private/` | – (pagină nouă) | **Evenimente private și ieșiri cu echipa \| Grădina Ielelor** (56) | **Rămâne** cea din plugin. | Preia căutările paginii vechi „Evenimente” (nunți, majorate, teambuilding), care duce acum aici. |
| `/` (Yoast) | – | La lansare **rămâne** titlul de acum. Mai târziu: **Glamping, camping și bungalow Brașov \| Grădina Ielelor** (54) | Mai târziu: „Glamping, camping, rulote și bungalow-uri la poalele Munților Făgăraș, în Sâmbăta de Sus, Brașov. Două lacuri, dușuri calde non-stop. Rezervă online.” (149) | Pagina principală e cea mai puternică. O schimbăm separat de lansare, ca să știm ce efect a avut. |
| `/festival/` (fișier separat) | „Locație privată pentru festivaluri — 9 ha, aviz ISU 5.000 persoane, Brașov \| Grădina Ielelor — Camping and Nature Resort” (120) | **Locație privată pentru festivaluri, Brașov \| Grădina Ielelor** (60) | Rămâne. | Titlul de acum e tăiat în Google la jumătate. Se schimbă în fișierul de pe server (FTP sau cPanel), nu din WordPress. Opțional. |

Între paranteze: numărul de caractere. Ținta: titlu sub 60, descriere 120–160.

**Limita acestor recomandări:** nu am acces la Search Console și nici la volumele de căutare din Google România. Recomandările se bazează pe:
- forma obișnuită a căutărilor în română;
- cuvintele pe care site-ul le țintește deja, de exemplu „Glamping Brasov” și „Camping Transilvania” din datele structurate ale paginii principale;
- felul în care platformele de rezervare își numesc categoriile („glamping în Brașov”, „bungalouri în regiunea Brașov”).

După 4–6 săptămâni de la lansare, verificăm în Search Console → **Performanță → Interogări** și ajustăm.

### 4.3 Restul paginilor
- **Titlul site-ului:** „Gradina Ielelor - Camping and Glamping in Romania” se adaugă la toate titlurile și le face prea lungi. Exemplu: „Contact - Gradina Ielelor - Camping and Glamping in Romania”. După lansare: **Setări → Generale → Titlul site-ului** → „Grădina Ielelor”. Risc mic, efect pe toate paginile.
- **Descrieri lipsă:** 56 de adrese nu au descriere (anexa B). Prioritare: Contact, Galerie foto, Info util, Blog, Evenimente/Timp liber (dacă rămâne) și articolele „8 trasee montane…” și „Grădina Ielelor, cel mai mare spațiu de campare…”. Paginile legale și QR nu sunt prioritare.
- **H1 lipsă:** paginile comerciale vechi nu au H1. Paginile noi au câte unul, deci problema dispare acolo. Rămâne la Contact, Galerie foto, Info util, Info QR și la pagina Timp liber.

---

## 5. Lista de verificare înainte de lansare

**Pregătire**
- [ ] Copie de siguranță completă (fișiere și bază de date), din hosting.
- [ ] Din **Search Console → Performanță**, exportă (butonul Export) ultimele 3 și ultimele 12 luni: **Pagini** și **Interogări**. Cu ele comparăm după lansare.
- [ ] Repari „dev24” (secțiunea 2). Poate fi făcut de acum.

**Paginile**
- [ ] Publicate: `/bungalow/`, `/festivaluri-si-tabere/`, `/evenimente-private/`, `/despre-noi/`, `/regulament-politici/`.
- [ ] Template-uri puse pe paginile existente: `/glamping/` (Glamping), `/camping/` (Camping), pagina principală „Acasă2025” (Pagina principală).
- [ ] Yoast pe `/glamping/` și `/camping/`: titlu și descriere vechi șterse sau înlocuite (secțiunea 4.1).
- [ ] Adăugată lângă titlul mare al paginii principale o frază cu cuvintele căutate (riscul 4).
- [ ] În footer-ul nou: Termeni și condiții, Politica de confidențialitate, Politica cookies, Politica de anulare (sau `/regulament-politici/`), ANPC, SOL, Contact, Blog. ANPC și SOL sunt **obligatorii legal**.

**Redirecționările**
- [ ] Activat `redirects.php` (anexa D) și testat **pe staging**.
- [ ] Fiecare adresă din 3.1 duce dintr-un singur salt (301) la pagina corectă.
- [ ] După ce merg: paginile vechi `/cazare-bungalow/`, `/camping-cort/`, `/camping-rulota/`, `/tarife/`, `/tarife-2026/`, `/acasa2023/` și `…/evenimente/` trec pe **Ciornă**. Ies din harta site-ului, iar redirecționarea merge mai departe.
- [ ] `/gradina-ielelor-totul-despre-ea/` rămâne publicată, pentru că e „părintele” paginii Timp liber. Doar Yoast → **Avansat** → **Nu** (iese din hartă).
- [ ] Meniurile vechi (header și footer Elementor, pe paginile care rămân pe tema veche) trimit direct la adresele noi, nu prin redirecționare.

**Setări**
- [ ] **Setări → Citire:** „Descurajează motoarele de căutare să indexeze acest site” este **nebifat** pe live. Pe staging trebuie să fie bifat. E cea mai frecventă greșeală la lansări.
- [ ] Coș, finalizare și cont: Yoast → **Nu** (secțiunea 3.3).
- [ ] Produsul de test retras (secțiunea 3.4).
- [ ] GTranslate: dezactivată comutarea automată după limba browserului (anexa F). Verificat că steagul plutitor nu acoperă header-ul nou.
- [ ] Google Tag Manager (`GTM-T5L2SC7K`) apare și pe paginile noi (sursa paginii → caută „GTM-”).
- [ ] Folderele `/festival/` și `/cotatie/` de pe server nu se ating și nu se șterg.

---

## 6. Dacă se alege WordPress nou, curat (decizia din 30.10.2026)
Harta din secțiunea 3 rămâne aceeași. În plus:
- **Toate cele 48 de adrese care rămân trebuie recreate cu exact aceeași adresă:** paginile QR, paginile legale, articolele, Galerie, Info util, Contact, Blog, categoriile. Structura linkurilor permanente rămâne `/%postname%/`. Orice adresă uitată devine „pagină negăsită”. Codurile QR tipărite nu se pot schimba.
- **Fișierele separate** se copiază la fel: `/festival/` (cu `img/` și `fisa-tehnica.pdf`) și `/cotatie/` (cu `cotatie.php`). La fel PDF-ul Regulamentului: `/wp-content/uploads/2022/06/ROI-GRADINA-IELELOR-ver-10.06.2022.pdf`. Dacă nu se poate, îi facem redirecționare spre noua adresă.
- **Pozele din articole** (`/wp-content/uploads/…`) se mută cu aceleași adrese.
- **Yoast:** aceleași setări (produsele noindex), titlurile și descrierile paginilor păstrate, numele site-ului.
- **Aceeași capcană ca „dev24”:** dacă site-ul se construiește pe o adresă de test și apoi se mută, după mutare caută adresa de test în sursa paginilor. Dacă apare, resetezi memoria Yoast (varianta B din secțiunea 2).
- Search Console nu se pierde: verificarea e făcută prin DNS, deci rămâne valabilă.

---

## 7. Lista de verificare după lansare

**În ziua lansării**
- [ ] Toate adresele din 3.1 → 301 spre pagina corectă (comenzile din anexa H sau click pe fiecare).
- [ ] Paginile noi răspund normal. În sursa lor nu apar `noindex`, „dev24” sau adresa de staging.
- [ ] `https://gradina-ielelor.ro/robots.txt` nu blochează nimic. Hartă: `https://gradina-ielelor.ro/sitemap_index.xml`.
- [ ] Harta conține paginile noi și **nu** conține paginile trecute pe Ciornă.
- [ ] Cache-ul golit (**LiteSpeed Cache → Golește tot**, dacă pluginul e instalat).
- [ ] **Search Console → Sitemaps:** retrimite `sitemap_index.xml`.
- [ ] **Search Console → Inspectare URL** → **Solicită indexarea** pentru `/`, `/bungalow/`, `/glamping/`, `/camping/`, `/festivaluri-si-tabere/`, `/evenimente-private/`, `/despre-noi/`.
- [ ] Testul de rezervare complet: căutare din pagina principală → produs → coș → finalizare (în modul de test).

**În prima săptămână**
- [ ] Search Console → **Pagini → Negăsită (404)**: orice adresă veche nouă apărută → o adăugăm în `redirects.php`.
- [ ] **Pagină cu redirecționare:** e normal să apară aici adresele vechi.
- [ ] **Dublură, Google a ales alt canonical:** nu trebuie să apară paginile noi.
- [ ] [Testul pentru rezultate îmbogățite](https://search.google.com/test/rich-results) pe `/`, `/info-util/` și un articol: datele structurate se citesc fără erori (anexa E).
- [ ] Actualizat linkul site-ului în **Google Business Profile**, pe Facebook, Booking, Airbnb, camping.info și la agenți, dacă trimiteau la adrese vechi (de exemplu `/cazare-bungalow/`).

**În primele 2 luni**
- [ ] Comparație cu exportul de dinainte: clicurile adreselor vechi trebuie să se mute pe cele noi. O scădere de 2–4 săptămâni e normală la o reproiectare; după 6–8 săptămâni nivelul trebuie să revină.
- [ ] Redirecționările rămân **cel puțin un an**. Ideal, pentru totdeauna: nu costă nimic.
- [ ] Apoi: titlul paginii principale (4.2), titlul site-ului (4.3), descrierile lipsă și decizia pentru categoriile de produse.

---

## 8. Întrebări pentru proprietar
1. Confirmi ștergerea paginii **#demo_gallery** și retragerea produsului **Cort Campare Test**, după ce verifici că nu are comenzi?
2. **Info Covid-19**: o ascundem din Google (noindex) sau o ștergem și redirecționăm spre Info util?
3. **Timp liber** („De vizitat / De făcut”): rămâne pagină separată, sau mutăm textul în *Despre noi* și redirecționăm?
4. **Tarife**: e în regulă să ducă la pagina principală, unde sunt prețurile pe carduri? Sau vrei o pagină nouă `/tarife/` cu toate prețurile?
5. **GTranslate** (traducerea automată): rămâne pe site-ul nou? Recomandarea mea: da, dar fără comutare automată după limba browserului.
6. **Categoriile de produse** (`/product-category/…`): le ascundem din Google la 2–4 săptămâni după lansare, ca să nu concureze cu paginile dedicate?
7. Repar „dev24” acum, pe live (re-salvare, fără schimbări de conținut), sau aștept lansarea?

---
---

# Anexa tehnică

## A. Metodă și limite
- **Surse:**
  - `robots.txt` (Yoast, nu blochează nimic);
  - `sitemap_index.xml` → `post-sitemap.xml` (10), `page-sitemap.xml` (42), `category-sitemap.xml` (3), `product_cat-sitemap.xml` (4), în total 59;
  - REST API: `/wp-json/wp/v2/pages` (44), `/posts` (9), `/product` (5), `/product_cat` (5), `/categories` (6), cu `_fields=…,yoast_head_json`;
  - HTML-ul fiecărei adrese (GET, fără redirecționări urmate automat): `<title>`, `<h1>`, `rel=canonical`, `meta robots`, antetul `X-Robots-Tag`, `meta description`, `hreflang`, tipurile JSON-LD;
  - meniul extras din HTML-ul paginii principale;
  - linkurile interne din toate paginile.
- **Platformă observată:** WordPress 7.1.2, WooCommerce 8.7.3, Elementor 3.20.2, Yoast SEO 28.6, GTranslate 5.0.1 (versiunea gratuită), LiteSpeed. Tema veche: `giw`.
- **Limite:**
  - fără acces la Search Console (indexare reală, backlinks, interogări), la administrarea WordPress (câmpurile Yoast, setări) sau la DNS;
  - căutarea web la care am acces nu redă rezultatele Google România, deci recomandările de titluri sunt calitative (secțiunea 4.2);
  - inventarul anterior („inventar-gradina-ielelor.md”, 24.10.2026) a numărat 76 de adrese testate. Diferența față de cele 71 de aici: variante de adrese (cu sau fără `/`, `www`, endpoint-uri de cont), nu pagini distincte.

## B. Inventar complet (71 de adrese)
Legendă:
- **Canonical:** „proprie” = arată spre ea însăși (corect); „dev24 ✗” = greșit; „lipsă” = nu are; „→ /x/” = arată spre altă pagină.
- **Indexabilă:** „neclar” = Google poate ignora pagina din cauza canonical-ului greșit.
- În paranteză la H1 apare numărul de H1-uri în plus.

#### Pagina principală și paginile de cazare

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 1 | `/` | da | Locatie Glamping Romania - Camping, Brasov, Gradina Ielelor | Glamping - Camping, Brasov, Romania | proprie | index | da | da |
| 2 | `/glamping/` | da | Glamping in Romania - Gradina Ielelor - Glamping Sambata se Sus | **lipsă** | proprie | index | da | da |
| 3 | `/camping/` | da | Camping in Romania - Gradina Ielelor - Camping Sambata se Sus | **lipsă** | proprie | index | da | da |
| 4 | `/camping-cort/` | da | Camping in Romania - Gradina Ielelor - Camping Sambata se Sus | **lipsă** | proprie | index | da | da |
| 5 | `/camping-rulota/` | da | Camping in Romania - Gradina Ielelor - Camping Sambata se Sus | **lipsă** | proprie | index | da | da |
| 6 | `/cazare-bungalow/` | da | Cazare Bungalow in Romania - Gradina Ielelor - Cazare Sambata se Sus | **lipsă** | proprie | index | da | da |
| 7 | `/tarife/` | da | Tarife 2026 Glamping si Camping. Sezon Nou. Rezerva din timp! | **lipsă** | proprie | index | da | da |
| 8 | `/tarife-2026/` | da | Tarife 2026 Glamping si Camping. Sezon Nou. Rezerva din timp! | **lipsă** | proprie | index | da | da |
| 9 | `/acasa2023/` | da | Locatie Glamping - Camping, Brasov, Romania Gradina Ielelor | Glamping - Camping, Brasov, Romania | proprie | index | da | da |

#### Despre, evenimente, galerie, informații

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 10 | `/gradina-ielelor-totul-despre-ea/` | da | Grădina Ielelor. Totul despre ea. - Gradina Ielelor - Camping and Glamping… | **lipsă** | proprie | index | da | da |
| 11 | `/gradina-ielelor-totul-despre-ea/evenimente/` | da | Evenimente - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 12 | `/gradina-ielelor-totul-despre-ea/timp-liber/` | da | Timp Liber - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 13 | `/galerie-foto/` | da | Galerie Foto - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 14 | `/info-util/` | da | Info Util - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 15 | `/contact/` | da | Contact - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 16 | `/blog-2/` | da | Blog - Gradina Ielelor - Camping and Glamping in Romania | Blog | proprie | index | **lipsă** | da |
| 17 | `/demo_gallery/` | nu | #demo_gallery - Gradina Ielelor - Camping and Glamping in Romania | Protejat: #demo_gallery | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |

#### Pagini legale

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 18 | `/info-util/termeni-si-conditii/` | da | Termeni și condiții - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 19 | `/info-util/politica-de-confidentialitate/` | da | Politica de confidențialitate - Gradina Ielelor - Camping and Glamping in … | Politica de confidențialitate | proprie | index | **lipsă** | da |
| 20 | `/info-util/politica-cookies/` | da | Politica Cookies - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | proprie | index | **lipsă** | da |
| 21 | `/info-util/politica-de-anulare/` | da | Politica de Anulare - Gradina Ielelor - Camping and Glamping in Romania | Politica de Anulare | proprie | index | **lipsă** | da |
| 22 | `/info-util/info-covid-19/` | da | Info Covid-19 - Gradina Ielelor - Camping and Glamping in Romania | Info Covid-19 | proprie | index | **lipsă** | da |

#### Pagini QR (plante)

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 23 | `/info-qr/` | da | Info QR - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 24 | `/info-qr/afin-romanesc/` | da | Afin Românesc - Gradina Ielelor - Camping and Glamping in Romania | Afin Românesc | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 25 | `/info-qr/agrisul/` | da | Agrișul - Gradina Ielelor - Camping and Glamping in Romania | Agrișul (+1) | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 26 | `/info-qr/anason/` | da | Anason - Gradina Ielelor - Camping and Glamping in Romania | Anason | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 27 | `/info-qr/aronia/` | da | Aronia - Gradina Ielelor - Camping and Glamping in Romania | Aronia | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 28 | `/info-qr/busuioc/` | da | Busuioc - Gradina Ielelor - Camping and Glamping in Romania | Busuioc | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 29 | `/info-qr/catina/` | da | Cătina - Gradina Ielelor - Camping and Glamping in Romania | Cătina | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 30 | `/info-qr/coacaz/` | da | Coacăz - Gradina Ielelor - Camping and Glamping in Romania | Coacăz | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 31 | `/info-qr/coriandru/` | da | Coriandru - Gradina Ielelor - Camping and Glamping in Romania | Coriandru | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 32 | `/info-qr/lavanda/` | da | Lavandă - Gradina Ielelor - Camping and Glamping in Romania | Lavandă | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 33 | `/info-qr/lonicera-afin-siberian/` | da | Lonicera - Afin Siberian - Gradina Ielelor - Camping and Glamping in Roman… | Lonicera – Afin Siberian | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 34 | `/info-qr/maces/` | da | Măceș - Gradina Ielelor - Camping and Glamping in Romania | Măceș | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 35 | `/info-qr/menta/` | da | Menta - Gradina Ielelor - Camping and Glamping in Romania | Menta | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 36 | `/info-qr/murul/` | da | Murul - Gradina Ielelor - Camping and Glamping in Romania | Murul | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 37 | `/info-qr/musetelul/` | da | Mușețelul - Gradina Ielelor - Camping and Glamping in Romania | Mușețelul | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 38 | `/info-qr/soc/` | da | Socul - Gradina Ielelor - Camping and Glamping in Romania | Socul (+1) | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 39 | `/info-qr/trandafirul-de-dulceata/` | da | Trandafirul de dulceață - Gradina Ielelor - Camping and Glamping in Romania | Trandafirul de dulceață | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 40 | `/info-qr/zmeur/` | da | Zmeur - Gradina Ielelor - Camping and Glamping in Romania | Zmeur | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |

#### Articole de blog

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 41 | `/energia-naturii-efectele-benefice-asupra-mintii-si-corpului-in-perioada-de-camping/` | da | Energia naturii. Efectele benefice asupra minții și corpului în perioada d… | Energia naturii. Efectele benefice asupra minții … | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 42 | `/redefineste-felul-in-care-experimentezi-natura-eco-friendly-modern/` | da | Redefinește felul în care experimentezi natura: eco friendly & modern - Gr… | Redefinește felul în care experimentezi natura: e… | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 43 | `/reincarca-ti-bateriile-in-cel-mai-nou-loc-de-glamping-din-romania/` | da | Reîncarcă-ți bateriile în cel mai nou loc de glamping din Romania - Gradin… | Reîncarcă-ți bateriile în cel mai nou loc de glam… | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 44 | `/glamping-romania-o-experienta-de-neuitat-la-gradina-ielelor-relaxare-in-natura-la-poalele-muntilor-fagaras/` | da | Glamping România: O Experiență de Neuitat la Grădina Ielelor \| Relaxare în… | Glamping România: O Experiență de Neuitat la Grăd… | proprie | index | da | da |
| 45 | `/camping-in-transilvania-la-munte-o-experienta-autentica-in-gradina-ielelor-relaxare-aventura-si-natura-la-poalele-fagarasului/` | da | Camping în Transilvania la Munte: O Experiență Autentică în Grădina Ielelo… | Camping în Transilvania la Munte: O Experiență Au… | proprie | index | da | da |
| 46 | `/camping-2025-cazare-camping-la-gradina-ielelor-o-oaza-de-relaxare-pentru-corturi-si-rulote/` | da | Camping 2025, cazare camping la Grădina Ielelor: O Oază de Relaxare pentru… | Camping 2025, cazare camping la Grădina Ielelor: … | proprie | index | da | da |
| 47 | `/locatie-perfecta-de-campare-intre-brasov-si-sibiu/` | da | Locație perfectă de campare între Brașov și Sibiu - Gradina Ielelor - Camp… | Locație perfectă de campare între Brașov și Sibiu (+2) | proprie | index | da | da |
| 48 | `/8-trasee-montane-care-au-ca-punct-de-plecare-gradina-ielelor/` | da | 8 trasee montane care au ca punct de plecare Gradina Ielelor - Gradina Iel… | 8 trasee montane care au ca punct de plecare Grad… | proprie | index | **lipsă** | da |
| 49 | `/gradina-ielelor-cel-mai-mare-spatiu-de-campare-din-romania/` | da | GRADINA IELELOR, cel mai mare spatiu de campare din Romania - Gradina Iele… | GRADINA IELELOR, cel mai mare spatiu de campare d… | proprie | index | **lipsă** | da |

#### Categorii de articole

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 50 | `/./aventuri/` | da | Aventuri - Gradina Ielelor - Camping and Glamping in Romania | Aventuri | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |
| 51 | `/./noutati/` | da | Noutăți - Gradina Ielelor - Camping and Glamping in Romania | Noutăți | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |
| 52 | `/./timp-liber/` | da | Timp Liber - Gradina Ielelor - Camping and Glamping in Romania | Timp Liber | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |
| 53 | `/./plante-din-gradina-ielelor/` | nu | Plante din Grădina Ielelor - Gradina Ielelor - Camping and Glamping in Rom… | Plante din Grădina Ielelor (+1) | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |
| 54 | `/./uncategorized/` | nu | Uncategorized - Gradina Ielelor - Camping and Glamping in Romania | Uncategorized (+1) | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |
| 55 | `/./uncategorized-ro/` | nu | Uncategorized @ro - Gradina Ielelor - Camping and Glamping in Romania | Uncategorized @ro (+1) | **dev24** ✗ | index | **lipsă** | nu: 404 pentru Google (baza „.”) |

#### WooCommerce și rezervări

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 56 | `/shop/` | da | Rezervări - Gradina Ielelor - Camping and Glamping in Romania | Rezervări | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 57 | `/rezervarea-mea/` | da | Rezervarea Mea - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | **dev24** ✗ | noindex | **lipsă** | nu (noindex) |
| 58 | `/finalizare-rezervare/` | da | – (coș gol) | – | **dev24** ✗ (în Yoast) | – | – | nu (302 → `/rezervarea-mea/` când coșul e gol) |
| 59 | `/contul-meu/` | da | Contul Meu - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | **dev24** ✗ | noindex | **lipsă** | nu (noindex) |
| 60 | `/produs/glamping/` | nu | Glamping - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | lipsă | noindex | **lipsă** | nu (noindex) |
| 61 | `/produs/bungalow/` | nu | Bungalow - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | lipsă | noindex | **lipsă** | nu (noindex) |
| 62 | `/produs/campare-cort/` | nu | Campare Cort - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | lipsă | noindex | da | nu (noindex) |
| 63 | `/produs/campare-rulota/` | nu | Campare Rulotă - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | lipsă | noindex | da | nu (noindex) |
| 64 | `/produs/cort-campare-test/` | nu | Cort Campare Test - Gradina Ielelor - Camping and Glamping in Romania | **lipsă** | lipsă | noindex | **lipsă** | nu (noindex) |
| 65 | `/product-category/glamping/` | da | Glamping - Gradina Ielelor - Camping and Glamping in Romania | Glamping (+1) | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 66 | `/product-category/bungalow/` | da | Bungalow - Gradina Ielelor - Camping and Glamping in Romania | Bungalow | proprie | index | **lipsă** | da |
| 67 | `/product-category/camping/` | da | Camping Cort - Gradina Ielelor - Camping and Glamping in Romania | Camping Cort | proprie | index | **lipsă** | da |
| 68 | `/product-category/rulote/` | da | Rulote - Gradina Ielelor - Camping and Glamping in Romania | Rulote (+1) | **dev24** ✗ | index | **lipsă** | neclar (canonical greșit) |
| 69 | `/product-category/oricare/` | nu | Oricare - Gradina Ielelor - Camping and Glamping in Romania | Oricare (+1) | proprie | index | **lipsă** | da |

#### Pagini statice (în afara WordPress)

| # | Adresa | În sitemap | Titlu (title) | H1 | Canonical | Robots | Meta descriere | Indexabilă |
|---|---|---|---|---|---|---|---|---|
| 70 | `/festival/` | nu | Locație privată pentru festivaluri — 9 ha, aviz ISU 5.000 persoane, Brașov… | Locație privată pentru festivaluri | proprie | implicit (index) | da | da |
| 71 | `/cotatie/` (formular cerere ofertă, legat din `/festival/`) | nu | Cerere de ofertă — Grădina Ielelor, Camping and Nature Resort | Cerere de ofertă | proprie | noindex | da | nu (noindex, intenționat) |

**Variante de adrese verificate în plus** (nu sunt pagini distincte):

| Adresa | Răspuns |
|---|---|
| `/festival` | 301 → `/festival/` |
| `www.gradina-ielelor.ro/...` | 301 → fără `www` |
| `/evenimente/` | 200: vechea pagină Evenimente, canonical spre `/gradina-ielelor-totul-despre-ea/evenimente/` |
| `/timp-liber/` | 200: vechea pagină Timp liber, canonical spre `/gradina-ielelor-totul-despre-ea/timp-liber/` |
| `/contul-meu/lost-password/` | 200, noindex, canonical dev24 |
| `/author/a-min/` | 301 → `/` |
| `/en/` | 301 → un articol de blog: WordPress „ghicește” după începutul adresei |
| `/despre-noi/`, `/regulament-politici/`, `/bungalow/`, `/rulote/`, `/cart/`, `/checkout/`, `/aventuri/`, `/camping/index.html`, `/evenimente-corporate/index.html`, `/index.html` | 404 |

**PDF-uri:**
- `/wp-content/uploads/2022/06/ROI-GRADINA-IELELOR-ver-10.06.2022.pdf` (Regulament intern, legat din meniu): 200;
- `/festival/fisa-tehnica.pdf`: 200.

## C. „dev24”: dovezi tehnice
1. **Yoast emite valoarea, nu tema.** `yoast_head_json.canonical` din REST API are aceeași adresă dev24 ca HTML-ul, la toate cele 26 de pagini și articole afectate. Tot dev24 apar și `og:url` și `WebPage.url`/`@id` din schema Yoast.
2. **Nu e o setare globală.** 27 de pagini și articole au canonical corect, deci adresa site-ului (Setări → Generale) e corectă.
3. **Corelare cu data ultimei modificări:**
   - toate obiectele afectate au `modified` ≤ 28.03.2024 (de exemplu Rezervări 28.03.2024, Contul meu 28.03.2024, plantele QR 13.05.2021, articolele 2021–2022);
   - obiectele salvate după aceea sunt corecte (Blog 01.04.2024, Contact 2025, paginile de cazare 2026);
   - două excepții vechi sunt corecte: *Politica de anulare* (2023) și copiii paginii *Totul despre ea*. Părinții lor au fost salvați în 2024–2025, iar Yoast actualizează adresa copiilor când salvează părintele.
4. **Firul de navigare (BreadcrumbList).**
   - În paginile plantelor, elementul-părinte „Info QR” are `item: https://dev24…/info-qr/`.
   - În toate paginile de produs, elementul „Rezervări” are `item: https://dev24…/shop/`.
   - Yoast ia adresele strămoșilor din tabelul lui de *indexables* (coloana `permalink`), nu din câmpul manual „URL canonic”. Deci adresa e învechită în *indexables*, în urma mutării de pe dev24 (căutare-înlocuire care nu a acoperit tabelul `wp_yoast_indexable` sau a fost făcută înaintea lui).
5. **Sitemap-ul e corect.** Pentru aceleași pagini, sitemap-ul Yoast are adresele corecte: le ia din adresele WordPress (`get_permalink()`), nu din coloana `permalink` a *indexables*. Asta confirmă că WordPress însuși știe adresa corectă.
6. **Reparare:**
   - salvarea unui obiect (inclusiv „Editare rapidă”) declanșează `wp_insert_post` sau `edited_term`, iar Yoast reconstruiește *indexable*-ul cu adresa curentă;
   - resetarea completă (Yoast Test Helper → *Reset indexables tables & migrations*, apoi *SEO data optimization*) reconstruiește tot;
   - pe paginile pluginului nou, `GI_SEO::canonical()` întoarce oricum `get_permalink()` dacă nu există canonical manual, deci paginile noi nu sunt afectate.
7. **Pe live, dev24** = CNAME/alias spre google.ro, cu certificat nepotrivit (conform inventarului din 24.10.2026). Google nu poate accesa adresele canonice indicate.

## D. Modulul `includes/redirects.php`
- **Activare** (după verificare): în `gradina-ielelor-integration.php`, după `require_once GI_PATH . 'includes/pages.php';`, se adaugă:
  ```php
  require_once GI_PATH . 'includes/redirects.php';
  ```
- **Conținut:**
  - `gi_redirect_map()`: cele 8 adrese din 3.1, plus 2 linii opționale comentate (Timp liber, Info Covid-19). Filtrul `gi_redirect_map` permite extinderea fără editarea fișierului.
  - Handler pe `template_redirect`, cu prioritatea 1, adică înaintea `redirect_canonical` din WordPress. Rezultatul e un singur salt, inclusiv pentru `/camping-cort` fără `/` final sau cu majuscule.
- **Ținta trebuie să existe:**
  - oferte → `gi_offer_has_page()` / `gi_offer_url()` (după template, nu după slug);
  - `/` → hub-ul (`GI_TEMPLATE_SLUG`) publicat **și** setat ca pagină de start;
  - alte pagini → `get_page_by_path()` cu `post_status = publish`.
- **Nu redirecționează:**
  - cererile non-GET/HEAD;
  - `is_admin()`, AJAX, cron, REST;
  - previzualizările (`is_preview()`, Personalizare, `?preview`, `?elementor-preview` etc. pentru utilizatorii cu `edit_pages`);
  - WooCommerce: `is_woocommerce()`, `is_cart()`, `is_checkout()`, `is_account_page()`, `is_wc_endpoint_url()`, `wc-ajax`, `add-to-cart` etc.;
  - parametrii BRW observați pe live: `ovabrw_*`, `cat`, `pickup_date`, `dropoff_date`, `order`, `orderby`, plus `tip` (preselectarea din plugin) și `product_cat`.
- **Siguranță:**
  - protecție la buclă: dacă template-ul nou e pus chiar pe pagina veche, nu se redirecționează;
  - query string-ul rămas (`utm_*`, `fbclid`, `gclid`, `ref`) se adaugă la țintă;
  - `wp_safe_redirect()` permite numai domeniul propriu;
  - antetul `X-Redirect-By: Gradina Ielelor` ajută la diagnostic.
- **Verificat:**
  - `php -l` fără erori. Fără funcții PHP 8: `str_contains`, `match` și altele nu sunt folosite. Compatibil PHP 7.4.
  - Test cu WordPress simulat, 22 de scenarii: toate cele 8 redirecționări; așteptarea până la publicare; majuscule și fără `/`; păstrarea `utm`/`ref`; BRW, `tip`, previzualizare admin, WooCommerce și POST neatinse; bucla; WordPress în subfolder.
- **După activare pe staging:** comenzile din anexa H. Notă: serverul LiteSpeed poate ține în cache vechea pagină 200, deci după activare golește cache-ul (*Purge All*).

## E. Date structurate existente
- **Din Yoast, pe toate paginile WordPress:**
  - `WebSite` (cu `SearchAction`, `alternateName` „Glamping si Camping in inima Romaniei”);
  - `Organization` „Gradina Ielelor” (logo `logo_linii_negre_450.png`, `sameAs` Facebook);
  - `WebPage`, `BreadcrumbList`, `ImageObject`;
  - `Article` pe articole, `CollectionPage` pe categorii.

  Rămân și pe paginile noi: template-urile apelează `wp_head()`.
- **Adăugate manual în pagini:**
  - **`ItemList` pe pagina principală (din conținutul Elementor):** 4 elemente cu nume nepotrivite: `/glamping/` „Glamping Brasov”, `/camping/` „Camping Transilvania”, `/tarife/` „Camping Rulote”, `/galerie-foto/` „Camping Corturi”. Dispare când hub-ul înlocuiește conținutul Elementor. E în regulă: nu aduce rezultate îmbogățite pentru acest tip de site. **Nu se recreează cu adresele vechi.**
  - **`FAQPage` pe `/info-util/`:** rămâne, pagina se păstrează. Google arată întrebări frecvente în rezultate doar pentru site-uri guvernamentale și de sănătate, deci efectul e mic, dar nu strică.
- **Lipsesc:**
  - `Campground`/`LodgingBusiness` (nume, adresă, telefon, coordonate);
  - `Event` pentru evenimentele 2027 confirmate.

  Recomandat după lansare, numai cu date confirmate de proprietar: adresa exactă, datele evenimentelor. Recenziile Google nu se marchează ca `AggregateRating` pe propriul site (regulile Google pentru recenzii „despre sine”).
- `/festival/` și `/cotatie/` nu au JSON-LD.

## F. GTranslate și hreflang
- **Configurația live:**
  - `default_language: ro`, limbi `en, fr, de, it, pl, ro`;
  - **`url_structure: none`**: traducerea se face numai în browserul vizitatorului, cu Google Translate. Nu există adrese `/en/`, `/de/`…, deci nu există `hreflang`;
  - `detect_browser_language: 1`;
  - steag plutitor (`float.js`), cu CSS propriu (`top:65px`; conține greșeala de scriere `!impoartant`).
- **Impact SEO la lansare:**
  - nu există adrese traduse de păstrat sau de redirecționat;
  - Google indexează doar româna;
  - nu e nevoie de `hreflang`.
- **Riscuri:**
  1. **Comutarea automată după limba browserului.** Robotul Google randează paginile cu un browser în engleză, deci există riscul să vadă versiunea tradusă automat.
     → Verifică în Search Console: Inspectare URL → *Testează adresa URL publicată* → *Vezi pagina testată* (captură/HTML). Recomandare: comutarea automată oprită.
  2. **Steagul plutitor apare și pe paginile noi** (template-urile apelează `wp_footer()`). Poate acoperi header-ul nou → de verificat vizual.
- **Decizia proprietarului (31.10.2026):** site-ul se traduce automat în cel puțin 6 limbi: engleză, franceză, germană, olandeză, cehă, poloneză, maghiară, spaniolă, norvegiană, daneză. Comutarea automată după limba browserului rămâne pornită; riscul 1 se verifică în Search Console la D+2 (`docs/lansare.md`, secțiunea 9).
- **Dacă se vor pagini traduse indexate:** e nevoie de GTranslate plătit, cu subdirectoare sau subdomenii (adaugă `hreflang` automat), și de traduceri revizuite. E proiect separat, nu pentru lansare.

## G. Alte probleme găsite (în afara redirecționărilor)
1. **Baza categoriilor = „.”.** Adresele categoriilor de blog sunt `https://gradina-ielelor.ro/./aventuri/`.
   - Browserele și Google elimină „./” și cer `/aventuri/`, care dă **404**. Testat: 404 normalizat, 200 numai cu calea exactă. Deci cele 3 categorii din sitemap sunt 404 pentru Google.
   - Reparare: **Setări → Legături permanente → Bază categorii**: șterge „.” și salvează. Adresele devin `/category/aventuri/`.
   - Nu pierdem nimic, pentru că azi nu funcționează. Linkurile din tema veche se actualizează singure. Se poate face oricând.
2. **Linkuri moarte în articole:**
   - „Energia naturii…” → `/2019/10/17/8-trasee-montane-…/index.html`, `/camping/index.html`, `/evenimente-corporate/index.html`, `/index.html` (404, de la un site mai vechi);
   - „Reîncarcă-ți bateriile…” → `/aventuri/8-trasee-montane-…/` (301 → articol, se poate corecta direct);
   - „Termeni și condiții” → `www.gradina-ielelor.ro` (301, de corectat la ocazie).
3. **H1 multiple:** articolul „Locație perfectă de campare…” (3 × H1), plantele Agrișul și Socul (2), categoriile de produse Glamping, Rulote, Oricare (H1 dublat de temă).
4. **Yoast trimite în sitemap pagini noindex** (coș, finalizare, cont). Vezi 3.3.
5. **Primul click pe site setează cookie-ul Meta Pixel (`_fbp`) înainte de acord**, observat pe răspunsul sitemap-ului. Nu e SEO, dar e de verificat la consimțământ (inventarul din 24.10.2026).
6. `/evenimente/` și `/timp-liber/` răspund azi 200 cu paginile-copil vechi, cu canonical corect spre adresa lungă. Nu e o problemă. După publicarea paginii Festivaluri & tabere, adresa scurtă `/evenimente/` duce acolo (redirecționare).

## H. Comenzi de verificare (staging sau live, după activare)
```sh
# Redirecționări: așteptat 301 și ținta corectă, dintr-un singur salt
for u in cazare-bungalow camping-cort camping-rulota rulote evenimente tarife tarife-2026 acasa2023 \
         gradina-ielelor-totul-despre-ea gradina-ielelor-totul-despre-ea/evenimente; do
  curl -s -o /dev/null -w "%{http_code} /$u/ -> %{redirect_url}\n" "https://gradina-ielelor.ro/$u/"
done

# Paginile noi și cele păstrate: așteptat 200
for u in "" bungalow glamping camping festivaluri-si-tabere evenimente-private despre-noi regulament-politici festival \
         info-qr info-qr/menta contact blog-2 galerie-foto info-util shop; do
  curl -s -o /dev/null -w "%{http_code} /$u\n" "https://gradina-ielelor.ro/$u/"
done

# Fără dev24 și fără noindex accidental
for u in "" glamping info-qr info-qr/menta shop; do
  printf '/%s dev24=%s ' "$u" "$(curl -s "https://gradina-ielelor.ro/$u/" | grep -c dev24)"
  curl -s "https://gradina-ielelor.ro/$u/" | grep -o '<meta name="robots"[^>]*>' | head -1
done

# Căutarea BRW nu se redirecționează (așteptat: NU 301; 200 dacă pagina veche e încă publicată, 404 dacă e ciornă)
curl -s -o /dev/null -w "%{http_code}\n" "https://gradina-ielelor.ro/tarife/?ovabrw_search=search_item&cat=glamping"
```
