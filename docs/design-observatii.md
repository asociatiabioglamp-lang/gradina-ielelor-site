# Observații de design ale proprietarei (3.11.2026, după ce a văzut designul pe copia de test)

Sursa: mesajele ei din conversație, unele dictate. Ea aduce creația, eu o pun în practică. Lista e deschisă (urmează „gata lista”).

| # | Observație | Stare |
|---|---|---|
| 1 | **Pagina și pozele mai luminoase** | **Făcut în 1.3.3** (umbre mai ușoare, filtru ușor pe poze, `--veil` și `--photo-filter` în `site.css`) |
| 2 | **Scrisul mai mare** (text de bază, meniu, butoane, etichete) | **Făcut în 1.3.3** (scara `--fs-*`: text 19 px, etichete 15 px, meniu 14–15,5 px; hamburger sub 1300 px) |
| 3 | Titlul „Unde natura” de pe prima pagină nu se vedea (închis pe poza închisă; tema suprascria culoarea) | Rezolvat în `site.css` (commit 43043b8) |
| 4 | **Cerul luminos, vizibil în toate pozele și în toată povestea paginii** („impact psihologic”). Fundalul e prea întunecat și cerul se pierde | **Făcut în 1.3.3**: cerul și munții în stânga, în spatele textului; umbra e doar sub text |
| 5 | **Colajul din prima pagină:** poza de sus (cerul și munții) importantă și vizibilă; apoi lacul; a treia poză cu focul; în stânga, **a patra poză, de pe terasă, integrată în colaj** | **Făcut în 1.3.3**, cu o schimbare: cerul în stânga, iar în dreapta, pe diagonală, lacul, terasa (poza 26 din Drive, tăiată) și focul. Terasa nu e în stânga, ca textul să rămână citibil |
| 6 | O frază dictată neclară: „Poza de atât să fie unul arat” | De lămurit cu ea |
| 7 | **Scrisul mic poate fi prea mic; proporțiile între mărimile de scris** (ea nu știe dacă e bine; „dacă zici tu că e bine așa, mergi pe varianta cea mai bună”) | Decis de mine: scara tipografică se echilibrează și textul mic crește (în lucru la agentul de design) |
| 8 | **Bungalow:** textele din lista dictată (baie și duș propriu, calorifere, zonă privată cu acces la lac etc.) | Făcut în `offer-pages.php` (1.3.3) |
| 9 | **Bungalow: poze.** Poza de pe terasă e blurată; vrea una de pe terasă cu baldachinele de pe malul lacului (dedicate zonei de bungalow); interioare mai bune | **Făcut în 1.3.3** (poza 3 sus, 5 în poveste, 4/7/10/11 în galerie); lipsește o poză de pe terasă cu baldachine clare |
| 10 | **Glamping: poze greșite** (de festival, pe pământ). Trebuie cele 4 permanente, pe platformă, cu mobilier. 4 permanente; la cerere și la evenimente, până la 50 | **Făcut în 1.3.3** (poze 13, 14, 15). Rămâne: poza mare din capul paginii Glamping arată încă corturi pe pământ |
| 11 | La „Alege camping” (în modulul de rezervare), mesajul să fie mai mare | De lămurit ce mesaj anume |
| 12 | **Prima pagină (după 1.3.3):** stânga = cer 1/3, munți 1/3, corturi jos 1/3 („acum e doar vârful muntelui”); dreapta = **o singură** felie sus (lacul cu muntele, ziua, cer senin, cu SUP-uri și corturi) și focul jos; fără felia cu terasa | **Făcut** (poza 20 din Drive, tăiată fără persoana din prim-plan; stânga cu `object-position`) |
| 13 | **Poze „albăstrite”:** ea întreabă dacă are logică | Răspuns: nu e necesar. Umbrele erau albastru închis (culoarea brandului) și culorile erau intensificate cu 8%. **Schimbat:** umbre neutre (`--shade`), `--photo-filter: brightness(1.05) saturate(1)` |
| 14 | **Recenzii:** zeci, cu note de 4 și 5; media 4,8–4,9; pe criterii (curățenie, natură, pet friendly, eco-friendly, liniște, disponibilitatea gazdelor), în stele | **Așteaptă date reale** (recenzii și note); nu se inventează |
| 15 | **Glamping:** poza principală aleasă arată platforma udă, după ploaie; cortul „murdar”. Vrea glamping pe platformă de lemn, dar poze uscate | Agent de poze caută alte exterioare, uscate, cu soare |
| 16 | **Parteneri** (produse naturale, brânză de bivoliță, gastronomie locală, catering, pizzerii) cu reduceri pentru oaspeții Grădinii | Idee de discutat; după lansare |
| 17 | **Închiderile de pagină** („Gata de o noapte sub cerul Făgărașului?”) sunt înnorate: soare, cer senin | Agent de poze caută poze însorite |
| 18 | **Două adrese de email** la „scrie-ne”: rezervari@ și office@ | **Făcut** (întrebări frecvente și contact din subsol) |
| 19 | **Poze aeriene și aglomerate (domeniu de sus, rulote rând lângă rând): doar la festivaluri.** Scoase din Camping și din prima pagină | **Făcut** (rămân la Festivaluri & tabere) |
| 20 | **Camping:** și poze din zona 1 (acum în mare parte zona 3, lacul), vetre de foc, unde pui cortul, bucătăria | Agent de poze |
| 21 | **Festivaluri & tabere:** poze aeriene; poza „construit de la zero pentru grupuri mari” e prea risipită; bar/corturi după tipul evenimentului; vetrele cu oamenii în jurul focului, mare | Agent de poze |
| 22 | **Evenimente private:** poză aferentă fiecărui loc la care oferă acces (toalete/băi, vetre, bucătării etc.) | Agent de poze |
