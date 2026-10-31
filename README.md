# gradina-ielelor-site

Site-ul Grădina Ielelor: pluginul WordPress care aplică noul design, documentele proiectului și uneltele de test.

| Folder | Conținut |
|---|---|
| `gradina-ielelor-integration/` | Pluginul WordPress (se arhivează ca `.zip` și se instalează din Module → Adaugă modul). |
| `docs/` | Deciziile proprietarului și textele extrase din prezentări. |
| `tools/` | WordPress local pentru teste și scripturi de capturi de ecran. |
| `binary-manifest.tsv` | Lista imaginilor pluginului, cu sursa fiecăreia de pe site-ul live, dimensiunea și tipul. |

Reguli de lucru: `CLAUDE.md`. Instalare pe staging: `gradina-ielelor-integration/readme.txt`.

## Arhivă instalabilă
Din rădăcina depozitului:
```
zip -r gradina-ielelor-integration.zip gradina-ielelor-integration -x '*.DS_Store'
```
