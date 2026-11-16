# Planul Grădinii (schiță numerotată → plan final)

Uneltele cu care s-a făcut planul simplificat al Grădinii din planul PUZ trimis de proprietară. Nu fac parte din plugin și nu se instalează pe site.

**Pe scurt:** `make_schita.py` desenează schița cu numere pe fiecare clădire și zonă; proprietara spune ce e fiecare număr; `make_plan_final.py` face din asta planul final, cu sigla și numele Grădinii.

## Ce trebuie să existe
- Python 3 cu `opencv-python-headless`, `numpy` și `Pillow`.
- Imaginea planului PUZ (extrasă din `GI_SCHITA.pdf`, pagina 1, ca PNG): **nu e în depozit**. Se indică prin variabila `GI_PUZ_IMG` (implicit `img-000.png`, lângă script).
- Sigla se ia singură din `gradina-ielelor-integration/assets/logo-gradina-ielelor.png` (sau din `GI_LOGO`).

## Pașii
1. `GI_PUZ_IMG=/cale/plan-puz.png python3 make_schita.py` → `schita-numerotata.png` și `schita-date.json` (numărul, tipul și poziția fiecărui loc), **lângă script**; nu le adăuga în depozit.
2. Proprietara dă numele numerelor. Se face un fișier `nume.json` după modelul din `nume-exemplu.json`:
   - cheia = numărul din schiță; `nume` = denumirea pentru vizitatori; `grup` (opțional) = culoarea și titlul din legendă; `"arata": false` = locul rămâne pe plan estompat, fără pin și fără rând în legendă (ex. stația de epurare);
   - `"_grupuri"` (opțional) = ordinea grupurilor în legendă;
   - un loc fără nume nu se afișează, iar planul de tipar are mențiunea „CIORNĂ”.
3. `GI_PUZ_IMG=/cale/plan-puz.png python3 make_plan_final.py nume.json /cale/iesire` →
   - `plan-site.png`: planul fără numere, cu sigla și numele în colț. Se încarcă în **Zona echipei → Harta → Planul**; pinii îi pune site-ul din poziții;
   - `plan-tipar.png` și `plan-tipar.pdf`: planul cu numere și legendă, cu sigla și numele (regula de bază: tot ce descarcă clienții poartă sigla și numele);
   - `locuri.csv`: numele și poziția în procente (din `plan-site.png`) pentru fiecare loc afișat.

## De știut
- Poziția pinilor e centrul fiecărui loc, în procente din `plan-site.png`; planul nu trebuie decupat altfel după generare, ca pozițiile să rămână valabile.
- Lacurile, terasa, campingul, Wi-Fi-ul și alte locuri care nu apar în PUZ nu se desenează de unelte; se adaugă numai cu poziția dată de proprietară.
- Nu se pun în depozit planul PUZ, schițele și planurile generate; ele sunt ale proprietarei și se păstrează în Google Drive.
