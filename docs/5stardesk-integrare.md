# Integrarea cu 5starDesk: planul

Data: 31.10.2026. Decizia rămâne cea din 30.10: **rezervarea prin 5starDesk, plata doar pe site-ul nostru** (NETOPIA, inclusiv cardurile de vacanță). Nimic de aici nu se face pe site-ul live fără aprobarea ta; totul se încearcă întâi pe staging.

## Pe scurt
- **Până la lansare (7.11):** site-ul și 5starDesk își arată unul altuia **zilele ocupate**, prin linkuri de calendar (iCal). Scopul: fără rezervări duble pentru Glamping și Bungalow.
- **După lansare:** integrarea completă (disponibilitate și prețuri din 5starDesk, plata pe site, rezervarea marcată automat „plătită”). Depinde de ce oferă 5starDesk (API, webhook, link de plată). Întrebările sunt în `docs/plati-rezervari.md`.

## Etapa 1 — calendarele legate (înainte de lansare)

### Ce face
- O rezervare făcută pe Airbnb, Booking sau direct în 5starDesk închide zilele și pe site.
- O rezervare făcută pe site apare ca zile ocupate în 5starDesk (care le închide apoi pe Airbnb și Booking).

### Ce nu face
- Nu mută prețuri, nume de clienți sau plăți: doar zilele ocupate.
- Nu e instantaneu: calendarele se citesc periodic (de obicei la câteva ore, de verificat la fiecare parte). Cu 4 corturi de glamping și 4 bungalow-uri, între două citiri rămâne un risc mic de rezervare dublă.
- **Camping și rulote:** au multe locuri, cu preț pe persoană. Un calendar iCal închide o zi întreagă, deci nu e potrivit pentru ele. Dacă nu se vând și pe Airbnb sau Booking, nu e nevoie de legătură.

### Pașii (pe staging întâi)
Ce găsim deja: modulul de rezervări de pe site (BRW) are în **WooCommerce → Setări → Booking & Rental → Sync Order** o opțiune pentru **importul de linkuri de calendar** de pe alte platforme (Airbnb, Booking, Google Calendar) ([documentația BRW](https://ovatheme.gitbook.io/brw/basics/global-settings/sync-order)). Detaliile exacte le confirm când pot citi documentația.

1. **[TU]** În 5starDesk: pentru fiecare unitate (Glamping 1–4, Bungalow 1–4) găsește **linkul de export al calendarului (iCal / .ics)**. Trimite-mi o captură a ecranului (fără parole).
2. **[TU + CLAUDE]** În WordPress (staging): pui fiecare link în produsul BRW corespunzător. Îți scriu pașii exacți după captură.
3. **[TU]** În sens invers: dacă BRW dă un link de export pentru fiecare produs, îl pui în 5starDesk la unitatea corespunzătoare. Dacă nu dă, rezervările de pe site se trec în 5starDesk din emailul „Comandă nouă” (până la etapa 2).
4. **[TU + CLAUDE]** Test: o rezervare de test în 5starDesk → după sincronizare, zilele apar ocupate pe site; și invers.

## Etapa 2 — integrarea completă (după lansare)
Varianta aleasă depinde de răspunsurile 5starDesk (`docs/plati-rezervari.md`):
1. **Prin API:** site-ul citește disponibilitatea și prețurile din 5starDesk, încasează prin NETOPIA și creează rezervarea în 5starDesk ca „plătită”. Formularul rămâne în designul site-ului.
2. **Redirecționare:** clientul rezervă în motorul 5starDesk, apoi plătește pe pagina noastră, cu codul rezervării și suma.
3. **Link de plată în email:** varianta minimă; marcarea „plătită” se face de mână.

## De la tine
1. Ai primit răspuns de la 5starDesk la întrebări? Trimite-mi-l (sau redirecționează-l pe Gmail). Dacă nu, emailul e pregătit (întrebările din `docs/plati-rezervari.md`).
2. Captura cu linkurile de calendar (iCal) din 5starDesk, pentru fiecare unitate.
3. Ce se vinde azi pe Airbnb și pe Booking: numai Glamping, sau și Bungalow?
4. Accesul la documentații: în setările mediului de lucru (Network access) adaugă domeniile **5stardesk.com** și **ovatheme.gitbook.io**, ca să pot citi documentațiile 5starDesk și BRW.
