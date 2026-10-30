# Plăți și rezervări pentru site-ul nou: ce verificăm

Data: 30.10.2026. Nimic nu se schimbă pe site-ul live pe baza acestui document.

## Ce există azi
- **Site (staging):** WooCommerce + modulul de rezervări OVABRW + NETOPIA Payments 1.4.4 în mod de test.
- **5starDesk:** cont activ (emailuri pe rezervari@gradina-ielelor.ro). Sincronizarea site ↔ 5starDesk prin calendar iCal era pe lista testelor obligatorii.
- **Alte canale:** Airbnb (rezervări Glamping), h2b.ro (contracte cu agenții de turism), Campercontact.

## Varianta A — WooCommerce + BRW + NETOPIA (pe site)
**Stare pe staging (din VALIDATION.md):**
- Căutarea, prețurile, patul suplimentar și disponibilitatea funcționează (ex. Glamping 4 → 3 → 4 unități la confirmare și anulare).
- NETOPIA acceptă plata de test, dar comanda rămâne „Pending payment”: confirmarea de la NETOPIA (IPN) nu poate fi decriptată.
- Cauza găsită: certificatul public și cheia privată de test **nu formează o pereche** (`pair_match=no`). Nu e o problemă de cod.

**De verificat:**
1. Din contul NETOPIA, același punct de vânzare (POS), se descarcă **ambele** fișiere de test: certificatul `.cer` și cheia privată `.key`. Proprietarul le încarcă direct în WordPress, nu în chat.
2. Plată de test nouă: comanda trebuie să treacă singură în „Processing”.
3. Anulare/rambursare de test și emailurile către client.
4. Sincronizarea cu 5starDesk (iCal): cât durează până apare o rezervare de pe site în 5starDesk și invers. Cu 4 corturi și 4 bungalow-uri, o întârziere înseamnă risc de dublă rezervare.

**Avantaje:** design complet controlat în site; vouchere WooCommerce (pluginul `gradina-ielelor-vouchers`); camping cu adulți/copii și pat suplimentar deja configurate în BRW.
**Dezavantaje:** multe piese de întreținut (WooCommerce, BRW cu licență, NETOPIA, chei); sincronizarea iCal nu e instantanee.

## Varianta B — 5starDesk ca motor de rezervări pe site
**Ce spune producătorul (de confirmat la demo):**
- Un singur sistem: PMS, channel manager, booking engine, e-Factură ([5starDesk](https://www.5stardesk.com/)).
- Channel manager cu 30+ platforme, inclusiv Booking.com, Airbnb, Expedia ([Lighthouse Marketplace](https://marketplace.mylighthouse.com/partner/5stardesk)).
- Plăți online în booking engine: Viva Wallet, Revolut, PlatiOnline; avans adăugat automat la rezervare ([5starDesk](https://www.5stardesk.com/)). NETOPIA nu apare în lista găsită.
- Preț pe cameră/unitate pe lună ([Lighthouse Marketplace](https://marketplace.mylighthouse.com/partner/5stardesk)).

**De verificat cu 5starDesk:**
1. Booking engine-ul e inclus în abonamentul actual? Cât costă în plus?
2. Poate vinde **locuri de camping (cort/rulotă) cu preț pe persoană**, Glamping cu pat suplimentar și bungalow-uri?
3. Ce procesatori de plată acceptă? NETOPIA? Carduri de vacanță (Edenred, Pluxee, Up)? Avans configurabil?
4. Cum se pune pe site: widget, iframe sau link? Se poate deschide direct pe o categorie (ex. Glamping), ca butoanele „Rezervă” de pe paginile dedicate?
5. Disponibilitate în timp real pentru Airbnb, Booking și agențiile din h2b.ro.
6. Emailuri de confirmare și facturi automate.

**Avantaje:** un singur calendar pentru toate canalele, fără dublă rezervare; site-ul nou poate renunța la WooCommerce, BRW și NETOPIA, deci e mai simplu și mai rapid.
**Dezavantaje:** designul formularului îl dă 5starDesk; probabil alt contract de procesare a plăților; voucherele WooCommerce nu se mai aplică.

## Decizia proprietarului (30.10.2026)
**Rezervarea prin 5starDesk, plata doar pe site-ul nostru** (procesatorul nostru, ex. NETOPIA).
- 5starDesk ține calendarul și disponibilitatea pentru toate canalele: site, Airbnb, agenții (h2b.ro), Booking.
- Banii intră prin contul de plăți al Grădinii, pe gradina-ielelor.ro, nu prin procesatorii 5starDesk.
- Consecință probabilă: site-ul nou nu mai are nevoie de modulul OVABRW. WooCommerce rămâne doar pentru plată (avans/integral) și vouchere.

### Cum se poate lega rezervarea de plată (de confirmat cu 5starDesk)
1. **Prin API (cel mai bun):** site-ul nostru citește disponibilitatea și prețurile din 5starDesk, încasează prin NETOPIA, apoi creează rezervarea în 5starDesk ca „plătită”. Formularul e complet în designul nostru.
2. **Redirecționare după rezervare:** clientul rezervă în booking engine-ul 5starDesk (fără plată acolo), apoi e trimis automat pe pagina noastră de plată, cu codul rezervării și suma. După plată, rezervarea se marchează „plătită” automat (API/webhook) sau manual.
3. **Link de plată în email:** 5starDesk trimite confirmarea cu un link spre pagina noastră de plată (cod rezervare + sumă). Marcarea ca plătită în 5starDesk se face manual. E soluția minimă, fără integrare tehnică.

### Întrebări pentru 5starDesk
1. Aveți **API** pentru site-ul propriu: disponibilitate, prețuri, creare rezervare, marcare „plătită”? Există documentație?
2. Booking engine-ul poate funcționa **fără plată online** la voi, cu redirecționare spre pagina noastră de plată, cu codul și suma în link?
3. Aveți **webhook** sau notificare când se creează, se modifică sau se anulează o rezervare?
4. Puteți vinde **locuri de camping (cort/rulotă) cu preț pe persoană**, Glamping cu pat suplimentar și bungalow-uri?
5. Booking engine-ul se poate deschide direct pe o categorie (ex. Glamping) printr-un link?
6. Emailurile de confirmare pot include un **link de plată** personalizat spre site-ul nostru?

## Recomandare inițială (înainte de decizie)
Înainte de a construi site-ul nou, două verificări în paralel:
1. **5starDesk:** demo cu cele 6 întrebări de mai sus.
2. **NETOPIA:** perechea corectă de chei de test și o plată de test reușită pe staging.

Dacă 5starDesk răspunde „da” la întrebările 2, 4 și 5, varianta B face site-ul nou mai simplu și elimină riscul de dublă rezervare. Altfel, rămâne varianta A, cu NETOPIA reparat și sincronizarea iCal testată.
