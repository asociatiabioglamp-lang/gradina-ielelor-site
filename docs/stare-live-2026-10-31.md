# Cum arată site-ul live, văzut din administrare (31.10.2026)

Surse: capturile trimise de proprietară din WordPress (live, `gradina-ielelor.ro`), Softaculous și phpMyAdmin. Fără parole, chei sau date personale ale clienților.

## Raportul WooCommerce (Stare → Get system report, 31.10.2026, 20:41 UTC)
- **Server:** LiteSpeed, PHP 8.1.34, MariaDB 10.6.14, memorie 2 GB, încărcare până la 1 GB, baza de date 150 MB. Comenzile sunt stocate ca articole WordPress (nu HPOS).
- **Paginile WooCommerce:** Magazin `/shop/` (#47); Coș `/rezervarea-mea/` (#7); **Finalizare `/finalizare-rezervare/` (#4440)**; Contul meu `/contul-meu/` (#9); **Termeni și condiții `/info-util/termeni-si-conditii/` (#3047), setată în WooCommerce**, deci la plată apare deja bifa WooCommerce pentru termeni, lângă caseta noastră cu regulamentul.
- **Tema activă:** `giw`, o copie personalizată a WoodMart 7.4.3 (nu un copil). Suprascrie șabloanele WooCommerce pentru coș (`cart/cart.php`), **finalizare (`checkout/form-checkout.php`)** și mulțumire (`checkout/thankyou.php`), plus pagini de produs. **Nu** suprascrie `checkout/payment.php` și `checkout/review-order.php`: caseta cu regulamentul se leagă de șablonul standard al plății. Suprascrierea `form-checkout.php` arată că finalizarea e cea **clasică** (cu shortcode), nu cea cu blocuri; se confirmă pe staging.
- **Cache:** niciun modul de cache activ (WP Rocket e inactiv, dar a rămas fișierul `advanced-cache.php`); serverul e LiteSpeed.
- **Module inactive notabile (16):** WP Rocket, Really Simple SSL, WPS Hide Login, Yoast SEO Premium, Contact Form 7, MC4WP (Mailchimp), PW WooCommerce Affiliates Pro (afiliați), YITH Coupon Email, două versiuni vechi de BRW (1.2.1 și 1.4.9), „BRW iCal Sync”, trei module MotoPress Hotel Booking (resturi: 11 rezervări vechi).
- **Conținut:** 83 de pagini, 8 produse, 12 articole, 1.099 de comenzi, 59 de cupoane, 784 de fișiere media, 46 de șabloane Elementor. Există tabele pentru formulare Elementor (`wp_e_submissions`).

## Platforma
- **Hosting:** Romarg, cPanel (utilizator `r63031grad`), instalarea Softaculous `26_57643`, calea `/home3/r63031grad/public_html`, instalată pe 12.09.2019. WordPress **7.1.2** (7.1.4 disponibil, neactualizat), WooCommerce **8.7.3**, limita de memorie 2 GB, cron-ul funcționează, depanarea oprită, limba `ro_RO`.
- **Staging:** instalare separată, creată pe 28.10.2026, la o adresă temporară `…cpanel.site`, WordPress 7.1.2. Conturile din ea sunt cele de la data clonării (vezi `docs/lansare.md`, A0).
- **Tema:** WoodMart (cu „Woodmart Core” și „Hoteller Theme Elements”); tema copil veche `giw`. Constructor de pagini: Elementor 3.20.2 și Elementor Pro 3.20.1.
- **Indexare:** „Search Engine Visibility” în Softaculous e **Enabled**: motoarele de căutare văd site-ul.
- **Actualizări automate:** oprite la toate cele 42 de module. 12 actualizări disponibile, **neaplicate**: nu se aplică înainte de lansare.

## Rezervări, plăți, facturi
- **Rezervări:** BRW – Booking & Rental 2.0.0 (Ovatheme) și „BRW iCal Fix” 1.3.0 (fișier propriu, cu avertisment „are nevoie de ova-brw”; vezi întrebarea 16 din `docs/decizii.md`). Meniu „BRW iCal Diagnostic” în WooCommerce.
- **Plăți:** NETOPIA 1.4.4, **singura metodă de plată cu cardul** (decizia proprietarului). Modulul iPay BT (Banca Transilvania) 2.0.4 e instalat, dar nefolosit.
- **Facturi:** modulul FGO Premium 0.6.0.1; seria facturilor `AGR…` (de exemplu AGR0589), emise de AGRICOLA MEDICINALE 2002 SRL. Pe lista comenzilor apar acțiunile „Anulează”, „Șterge”, „Stornează” pentru fiecare factură: nu se apasă. „Facturare – Persoana Fizica sau Juridica” 1.3.1 adaugă câmpurile de facturare la plată.
- **Comenzi:** 1.099 în total (877 finalizate, 186 anulate, 34 eșuate, 1 în așteptare, 1 rambursată). Ultima comandă: 19.09.2026. Comenzile sunt stocate ca articole WordPress (tabelele noi de comenzi nu sunt pornite). Lista are coloanele Deposit, Remaining, Deposit status, Insurance: rezervările pot avea avans și rest.
- **Pagina de finalizare:** nu s-a stabilit încă dacă e cea clasică (`[woocommerce_checkout]`) sau cu blocuri. Caseta cu regulamentul funcționează numai cu cea clasică.

## Alte module active
GTranslate 5.0.1, Yoast SEO 28.6, Yoast Duplicate Post, WP Mail SMTP 4.10.0, WP Statistics 14.16.15, GTM4WP 2.0.5, Meta Pixel 5.2.2, LoftLoader 2.5.3 (ecranul de încărcare e **oprit** din Personalizare), Advanced Coupons, Auto Coupons, Abandoned Cart Reports, Payment Gateways by User Roles, When Last Login, Editor clasic, Piese clasice.

## Utilizatori (roluri)
Un singur administrator inițial, `a.min` (ID 1). Contul proprietarei (`g.ielelor`, ID 6) a trecut de la Manager magazin la **Administrator** pe 31.10.2026. Restul sunt clienți.

## Probleme observate (nu din vina pluginului nostru)
- WooCommerce → Checkout Fields (meniul temei WoodMart): „A fost o eroare critică pe acest site”, în administrare. Site-ul public merge. Vezi întrebarea 17 din `docs/decizii.md`.
- Anunțul vechi „Versiunea a fost actualizată cu succes!” de la Elementor rămâne în Panou: se poate ignora.
