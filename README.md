# gradina-ielelor-site

Site-ul Grădina Ielelor: pluginul WordPress care aplică noul design, documentele proiectului și uneltele de test.

| Folder | Conținut |
|---|---|
| `gradina-ielelor-integration/` | Pluginul WordPress (se arhivează ca `.zip` și se instalează din Module → Adaugă modul). |
| `docs/` | Deciziile proprietarului și textele extrase din prezentări. |
| `tools/` | WordPress local pentru teste și scripturi de capturi de ecran. |
| `binary-manifest.tsv` | Lista imaginilor pluginului, cu sursa fiecăreia de pe site-ul live, dimensiunea și tipul. |

Reguli de lucru: `CLAUDE.md`. Lansare, staging, live și revenire: `docs/lansare.md`. Conturi, accese și schimbările fără programatori: `docs/independenta.md`.

## Arhivă instalabilă
Din rădăcina depozitului:
```
tools/build-zip.sh
```
Produce `dist/gradina-ielelor-integration-<versiune>.zip` (versiunea din antetul pluginului), fără fișierele de dezvoltare, verificată cu `php -l`, `node --check`, `unzip -t` și cu pozele din `binary-manifest.tsv`; afișează amprenta SHA-256.
GitHub Actions (`.github/workflows/build.yml`) face același lucru la fiecare schimbare (PHP 7.4 și 8.3); la un tag `v<versiune>` atașează arhiva unui Release.
`.github/workflows/deploy-staging.yml`: încărcare opțională pe staging prin FTPS, manuală și oprită implicit. Pentru live nu există deploy automat.
