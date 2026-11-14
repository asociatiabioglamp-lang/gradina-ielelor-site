# Plan de vizibilitate: cum ajungem la mai mulți clienți

Întocmit 14.11.2026, la o zi după lansare. Rămâne în vigoare până la revizuirea din 15.12.2026.
Fiecare etapă spune **cine face** (Claude sau proprietara), **când** și **de ce contează**. Nu se promit cifre: Google are nevoie de săptămâni ca să ia în calcul paginile noi, iar pozițiile în căutări se mută în luni.

## Unde suntem acum (verificat 14.11.2026, din afară)

| Ce | Starea | Ce înseamnă |
|---|---|---|
| Paginile noi (Bungalow, Festivaluri, Evenimente private) | Indexabile, cu titlu și descriere bune; **încă nepreluate de căutare** | Normal la o zi după lansare. Le urmărim în Search Console. |
| Căutarea arată paginile **vechi** | `/tarife/`, `/cazare-bungalow/`, `/` cu texte și greșeli vechi („Sambata se Sus”, 26 sau 50 de locuri pentru rulote) | Redirecționările sunt temporare (302), deci Google ține paginile vechi. Trecem la 301 pe 20.11. |
| Adresa „dev24” | 34 de pagini (3 articole de blog, paginile QR, rezervările, categorii) spun că adresa lor oficială e o copie de lucru veche, care nu mai există | Google le poate ignora. **Se repară în pluginul 1.3.10.** |
| Harta site-ului | Conține 9 adrese vechi care doar redirecționează și 3 pagini de plată/cont | **Se repară în 1.3.10.** |
| Titlurile de la Glamping și Camping | Încă cele vechi din Yoast, cu greșeli | **Se repară în 1.3.10** (titlurile noi sunt deja scrise în plugin). |
| Date structurate (schema) | Doar „Organizație” și „Site”; nu spune că e un camping, nici telefon sau localitate | **Se adaugă în 1.3.10**, numai cu date deja publice pe site. |
| Platforme terțe | camping.info (20 de limbi, notă 4,7), campertunity, hotele.pl, Airbnb: apar în căutări înaintea site-ului propriu. Pe camping.info sezonul scrie „1 mai – 30 octombrie”. | Date vechi pe site-uri pe care nu le controlăm. De actualizat (etapa 2). |
| Concurența la „glamping Brașov” | Domină agregatoarele (Booking, Airbnb, Priceline) și proprietăți ca Dragonfly Gardens, Forest Glamping, CABANA 365, Glamping ZAZEN, Kama Retreat | Pe căutarea largă nu câștigăm repede. Câștigăm pe căutări precise și locale (etapa 3). |

**Limită:** nu pot vedea pozițiile din Google.ro (motorul meu de căutare e american, iar Search Console arată datele doar proprietarului). Pozițiile reale se citesc din Search Console → Performanță.

## Etapa 0 · Baza tehnică (Claude, săptămâna 14–20.11)
Pluginul **1.3.10**, testat local, verificat de un agent separat, instalat numai cu aprobarea proprietarei:
1. Adresa „dev24” înlocuită cu adresa reală în canonical, `og:url` și datele structurate.
2. Harta site-ului curățată (adrese redirecționate, coș, finalizare, cont).
3. Titlurile vechi cu greșeli de la Glamping și Camping înlocuite cu cele din plugin. Titlul paginii principale **nu se schimbă** acum (decizia din `docs/seo-migrare.md`: după 4–6 săptămâni, cu date din Search Console).
4. Date structurate de tip „Campground” (telefon, email, localitate, județ, țară), numai din ce e deja pe site.
5. Sezonul implicit din cod: „1 mai – 30 septembrie”.
6. **20.11:** proprietara bifează „Redirecționări permanente (301)” (Setări → Grădina Ielelor), ca Google să mute definitiv adresele vechi pe cele noi.

## Etapa 1 · Să te găsească cei care caută pe hartă (Google Business Profile)
Pentru „camping lângă mine”, „camping Sâmbăta de Sus”, „glamping Făgăraș”, Google arată întâi harta cu locuri. Un profil complet aduce mai mulți clienți decât orice articol.
- **Proprietara (o dată, 20 de minute):** deschide `business.google.com`, verifică dacă profilul „Grădina Ielelor” e revendicat de contul asociației. Dacă nu, îl revendică. Îmi scrie ce vede.
- **Completăm împreună:** categorii (Camping, Loc de glamping), program sezonier „1 mai – 30 septembrie”, linkul „Rezervări” spre `https://gradina-ielelor.ro/`, descriere, atribute (parcare, Wi-Fi, accept animale, dacă e adevărat), 15–20 de poze luminoase din cele noi.
- **Recenziile:** o carte cu cod QR la recepție și un mesaj după șederea fiecărui client, cu linkul de recenzie. Răspunzi la **fiecare** recenzie, cu căldură și în câteva fraze.
- **Ritm:** o postare pe săptămână în sezon (poză + o frază + link).

## Etapa 2 · Liste și platforme: aceleași date peste tot
Aceleași nume, adresă, telefon, sezon și link pe fiecare listă. Fiecare listă bună aduce clienți și un link către site.
- **camping.info** (cea mai puternică): sezonul din „01.05 – 30.10” în „01.05 – 30.09”, link către site, poze noi.
- **Booking.com, Airbnb, hotele.pl, campertunity, park4night, PiNCAMP, campercontact, campsaround:** verificăm fiecare, corectăm datele vechi, punem linkul către site.
- **Pentru festivaluri și tabere:** zilesinopti.ro (calendar de evenimente), openvan.camp (festivaluri pentru rulote), organizatori de festivaluri din zonă.
- Rezervările din platforme intră în același calendar abia după trecerea pe 5starDesk (post-lansare), deci fiecare platformă nouă se adaugă **după** ce există sincronizarea de calendar, ca să nu avem rezervări duble.

## Etapa 3 · Conținut pe site: căutările pe care le putem câștiga
Căutările largi au concurență puternică. Pe cele **precise și locale** putem fi pe primele locuri.
- Paginile existente acoperă deja intențiile: glamping, camping și rulote, bungalow, festivaluri și tabere, evenimente private.
- **Pagini noi, în ordine:** „Cum ajungi” (hartă, indicații, distanțe verificate); „De făcut în zonă” (din pagina Timp liber, curățată); întrebări frecvente cu date structurate pe Glamping și Camping (răspunsurile vin din prezentări și de la proprietară); „Calendar 2027” când sunt datele confirmate.
- **Blog:** 1–2 articole pe lună, fiecare pe o căutare reală: „camping cu copii lângă Făgăraș”, „weekend în Sâmbăta de Sus”, „cum alegi între glamping și bungalow”, „locație pentru un festival mic în natură”. Cu poze proprii și link spre rezervare.
- **Recenzii** reale pe pagini (trei avem azi), cu permisiunea autorilor.
- Descrierile lipsă (56 de adrese) și H1-urile lipsă de pe paginile vechi: vezi `docs/seo-migrare.md`, secțiunea 4.3.

## Etapa 4 · Canale în afara Google
- **Instagram** `gradina_ielelor` (conectat la instrumentele mele de măsurare): reels scurte cu muntele, lacul, apusul; o postare pe săptămână; link în biografie către site.
- **Facebook și TikTok:** aceleași materiale.
- **Lista de email a clienților din anii trecuți:** un mesaj la deschiderea sezonului 2027 (cu link și o ofertă). Numai cu acordul lor (GDPR).
- **Parteneri:** organizatori de festivaluri, asociații de turism din Brașov și Făgăraș, presa locală (eveniment de deschidere „Open Party”).
- **Publicitate (opțional, din primăvară):** Google Ads pe „camping Făgăraș” și „glamping Brașov”, cu buget mic și urmărire pe rezervări. Se decide după ce vedem datele din Search Console.

## Etapa 5 · Să rezerve cei care ajung
Vizibilitatea nu ajută dacă vizitatorul pleacă înainte să rezerve.
- **Pagina de plată:** etichetele sunt în engleză („Billing details”, „Place order”) și contul se creează obligatoriu cu parolă. Fiecare pas în plus pierde clienți. **Propunere:** traducere în română și plată fără cont (decizia o ia proprietara). Intră în 1.3.11.
- **Viteza pe telefon:** măsurăm și reparăm ce încetinește (greutatea paginilor, imagini).
- **Încredere:** prețuri clare (orientative pe site, finale din calendar), politica de anulare vizibilă, număr de telefon la un click.
- **Mesaje către clienți** (vezi etapa 6, „Să ajungă la clienți”).

## Etapa 6 · Totul să ajungă la clienți
- **Emailul de confirmare trebuie să ajungă în inbox, nu în spam.** Se verifică cu o rezervare reală de test (pasul de mai jos) și cu „Afișează originalul” din Gmail, care arată SPF, DKIM și DMARC: toate trebuie să spună **PASS**. Dacă nu, rezolvăm la furnizorul de email al domeniului (Romarg).
- **Mesaj înainte de sosire** (cu indicații, ora de sosire, regulile pe scurt): o idee pentru 1.3.11.
- **Mesaj după plecare**, cu linkul de recenzie Google.
- **Telefon și email** vizibile pe fiecare pagină (sunt în footer).

## Cum măsurăm (10 minute pe săptămână)
Search Console → **Performanță**: afișări, clicuri, cele mai frecvente interogări, pagini. Notăm în fiecare luni: numărul de afișări și de clicuri, cea mai bună interogare, o pagină nouă care apare. Comparăm lună de lună.
Indicatori, în ordinea importanței: (1) rezervări venite din căutări, (2) clicuri din Google, (3) afișări, (4) numărul și nota recenziilor Google.

## Calendar
| Când | Ce |
|---|---|
| 14–20.11 | Test complet pe live; pluginul 1.3.10; profilul Google Business verificat |
| 20.11 | Redirecționări 301; închiderea contului a.min |
| 21–30.11 | camping.info și celelalte liste actualizate; primul articol de blog; întrebări frecvente |
| Decembrie | 1.3.11 (plată în română, mesaje către clienți); lista de email; „Calendar 2027” |
| Ianuarie | Prima analiză în Search Console; decizia pentru titlul paginii principale |
| Martie – aprilie | Campania de deschidere a sezonului; decizia pentru publicitate |
| Înainte de octombrie 2027 | „Maximum year” în BRW la 2028 și închiderile pentru anul următor |
