/* Cookie-uri (1.9.0): bannerul de acord, alegerea în cookie-ul `gi_consent` (versiune:statistici:marketing), încărcarea Google Tag Manager și a scripturilor
 * WooCommerce de atribuire numai după acord, ștergerea cookie-urilor pe categorii.
 * Funcționează pe toate paginile site-ului (și pe cele vechi); nu depinde de alte scripturi sau de stilurile temei. */
(function () {
    'use strict';

    function boot() {
        var cfg = window.GI_CONSENT || {};
        var NAME = cfg.cookie || 'gi_consent';
        var VERSION = String(cfg.version || 1);
        var DAYS = cfg.days || 180;
        if (window.GICookie) {
            return; // scriptul a fost inclus de două ori (ex. un modul care combină fișierele)
        }
        var box = document.getElementById('gi-cookie');
        var reopen = document.querySelector('.gi-cookie-reopen');
        var footerLink = document.querySelector('.footer-legal [data-gi-cookie-open]');
        if (footerLink && reopen && reopen.parentNode) {
            // paginile noi au linkul „Setări cookie-uri” în subsol: butonul mic de deasupra paginii ar acoperi, pe telefon, căsuța „Administrare” de la sfârșit
            reopen.parentNode.removeChild(reopen);
            reopen = null;
        }
        var root = document.documentElement;
        var loaded = false;
        var attrLoaded = false;
        var decidedNow = false; // a ales pe această pagină (chiar dacă browserul nu păstrează cookie-uri)
        var current = null; // alegerea în vigoare pe această pagină ({stats, mkt}); nu depinde de cookie-uri
        var lastOpener = null;

        if (!box) {
            return;
        }

        /* ---------- cookie-ul propriu ---------- */
        function readChoice() {
            var m = document.cookie.match(new RegExp('(?:^|; )' + NAME + '=([^;]*)'));
            if (!m) {
                return null;
            }
            var raw;
            try {
                raw = decodeURIComponent(m[1]);
            } catch (e) {
                return null;
            }
            var p = raw.split(':');
            if (!/^\d+:[01]:[01]$/.test(raw) || p[0] !== VERSION) {
                return null; // cookie stricat, alte categorii sau altă versiune: se întreabă din nou
            }
            return { stats: p[1] === '1', mkt: p[2] === '1' };
        }

        function writeChoice(stats, mkt) {
            var parts = [
                NAME + '=' + encodeURIComponent([VERSION, stats ? 1 : 0, mkt ? 1 : 0].join(':')),
                'path=/',
                'max-age=' + (DAYS * 86400),
                'SameSite=Lax'
            ];
            if (location.protocol === 'https:') {
                parts.push('Secure');
            }
            document.cookie = parts.join('; ');
        }

        /* ---------- Google Consent Mode, Tag Manager, scripturile WooCommerce de atribuire ---------- */
        function gtag() {
            (window.dataLayer = window.dataLayer || []).push(arguments);
        }

        function pushConsent(stats, mkt) {
            var s = stats ? 'granted' : 'denied';
            var k = mkt ? 'granted' : 'denied';
            gtag('consent', 'update', { analytics_storage: s, ad_storage: k, ad_user_data: k, ad_personalization: k, personalization_storage: k });
            window.dataLayer.push({ event: 'gi_consent_update', gi_consent_statistici: !!stats, gi_consent_marketing: !!mkt });
        }

        function gtmPresent(id) {
            return !!(window.google_tag_manager && window.google_tag_manager[id]) || !!document.querySelector('script[src*="googletagmanager.com/gtm.js?id=' + id + '"]');
        }

        function loadGTM() {
            if (loaded || !cfg.gtm || !/^GTM-[A-Z0-9]{4,12}$/.test(cfg.gtm)) {
                return;
            }
            loaded = true;
            if (gtmPresent(cfg.gtm)) {
                return; // deja pornit de altcineva: nu se încarcă a doua oară
            }
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(cfg.gtm);
            document.head.appendChild(s);
        }

        /* Atribuirea comenzilor (WooCommerce): scripturile scoase din pagină de server pornesc numai după acord pentru statistici, în aceeași ordine */
        function loadAttribution() {
            if (attrLoaded || !cfg.attr || !cfg.attr.length) {
                return;
            }
            attrLoaded = true;
            var i = 0;
            (function next() {
                if (i >= cfg.attr.length) {
                    return;
                }
                var item = cfg.attr[i++];
                var s = document.createElement('script');
                if (item.src) {
                    s.src = item.src;
                    s.onload = next;
                    s.onerror = next;
                    document.head.appendChild(s);
                } else {
                    s.text = item.code;
                    document.head.appendChild(s);
                    next();
                }
            })();
        }

        function loadAllowed(choice) {
            if (choice && (choice.stats || choice.mkt)) {
                loadGTM();
            }
            if (choice && choice.stats) {
                loadAttribution();
            }
        }

        /* ---------- ștergerea cookie-urilor, pe categorii ---------- */
        var GROUPS = {
            stats: /^(_ga(_.*)?|_gid|_gat(_.*)?|__utm[a-z]+|sbjs_.*)$/,
            mkt: /^(_gcl_.*|_gac_.*|_fbp|_fbc|_ttp|_tt_.*|ttclid)$/
        };

        function purge(re) {
            var names = document.cookie.split(';').map(function (c) {
                return c.split('=')[0].replace(/^\s+/, '');
            }).filter(function (n) {
                return re.test(n);
            });
            var host = location.hostname.split('.');
            var domains = [''];
            for (var i = 0; i < host.length - 1; i++) {
                domains.push('.' + host.slice(i).join('.'));
            }
            names.forEach(function (n) {
                domains.forEach(function (d) {
                    document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : '');
                });
            });
        }

        /* Categoriile neacordate nu au voie să lase cookie-uri (nici cele rămase de la vizite mai vechi) */
        function purgeUngranted(choice) {
            if (!choice || !choice.stats) {
                purge(GROUPS.stats);
            }
            if (!choice || !choice.mkt) {
                purge(GROUPS.mkt);
            }
        }

        /* ---------- bannerul ---------- */
        function views() {
            return {
                main: box.querySelector('[data-gi-view="main"]'),
                settings: box.querySelector('[data-gi-view="settings"]')
            };
        }

        function showView(name) {
            var v = views();
            v.main.hidden = name !== 'main';
            v.settings.hidden = name !== 'settings';
            var closeBtn = box.querySelector('[data-gi-act="close"]');
            if (closeBtn) {
                closeBtn.hidden = !(readChoice() || decidedNow);
            }
        }

        function fillSettings() {
            var c = readChoice() || { stats: false, mkt: false };
            var st = box.querySelector('[data-gi-cat="stats"]');
            var mk = box.querySelector('[data-gi-cat="mkt"]');
            if (st) {
                st.checked = !!c.stats;
            }
            if (mk) {
                mk.checked = !!c.mkt;
            }
        }

        function focusEl(el) {
            if (!el) {
                return;
            }
            try {
                el.focus({ preventScroll: true });
            } catch (e) {
                el.focus();
            }
        }

        function openBox(view, byUser) {
            if (view === 'settings') {
                fillSettings();
            }
            showView(view);
            box.hidden = false;
            root.classList.add('gi-cookie-open');
            if (reopen) {
                reopen.hidden = true;
            }
            // pe prima vizită, focusul se mută pe dialog numai dacă nu e deja pe un element al paginii
            if (byUser || !document.activeElement || document.activeElement === document.body) {
                focusEl(box);
            }
        }

        function closeBox() {
            box.hidden = true;
            root.classList.remove('gi-cookie-open');
            var show = !!(reopen && (readChoice() || decidedNow));
            if (reopen) {
                reopen.hidden = !show;
            }
            var target = (lastOpener && document.contains(lastOpener) && !lastOpener.hidden) ? lastOpener : (show ? reopen : footerLink);
            lastOpener = null;
            focusEl(target);
        }

        function choose(stats, mkt) {
            var prev = current || readChoice();
            var withdrew = !!prev && ((prev.stats && !stats) || (prev.mkt && !mkt));
            writeChoice(stats, mkt);
            decidedNow = true;
            current = { stats: stats, mkt: mkt };
            pushConsent(stats, mkt);
            loadAllowed({ stats: stats, mkt: mkt });
            purgeUngranted({ stats: stats, mkt: mkt });
            closeBox();
            if (withdrew && (loaded || attrLoaded)) {
                location.reload(); // scripturile deja pornite nu se pot opri; pagina se reîncarcă fără ele
            }
        }

        box.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-gi-act]') : null;
            if (!btn) {
                return;
            }
            var act = btn.getAttribute('data-gi-act');
            if (act === 'accept') {
                choose(true, true);
            } else if (act === 'reject') {
                choose(false, false);
            } else if (act === 'settings') {
                openBox('settings', true);
                focusEl(box.querySelector('[data-gi-cat="stats"]'));
            } else if (act === 'save') {
                var st = box.querySelector('[data-gi-cat="stats"]');
                var mk = box.querySelector('[data-gi-cat="mkt"]');
                choose(!!(st && st.checked), !!(mk && mk.checked));
            } else if (act === 'close') {
                closeBox();
            }
        });

        document.addEventListener('click', function (e) {
            var opener = e.target.closest ? e.target.closest('[data-gi-cookie-open]') : null;
            if (!opener) {
                return;
            }
            e.preventDefault();
            lastOpener = opener;
            openBox('settings', true);
        });

        document.addEventListener('keydown', function (e) {
            // Escape se ține în banner: nu atinge lightbox-urile sau meniurile paginii
            if (e.key !== 'Escape' || box.hidden || !box.contains(document.activeElement)) {
                return;
            }
            if (readChoice() || decidedNow) {
                closeBox();
            } else if (!views().settings.hidden) {
                showView('main');
                focusEl(views().main.querySelector('[data-gi-act="settings"]'));
            }
        });

        /* O pagină readusă din memoria browserului (butonul Înapoi) nu a văzut alegerea făcută între timp */
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) {
                return;
            }
            var c = readChoice();
            var anyGranted = !!(c && (c.stats || c.mkt));
            if ((loaded && !anyGranted) || (attrLoaded && !(c && c.stats))) {
                location.reload();
            } else if (!c && box.hidden) {
                openBox('main');
            } else if (c && !box.hidden) {
                current = c;
                loadAllowed(c);
                closeBox();
            }
        });

        /* ---------- pornire ---------- */
        var choice = readChoice();
        current = choice;
        purgeUngranted(choice);
        if (choice) {
            loadAllowed(choice);
            if (reopen) {
                reopen.hidden = false;
            }
        } else {
            openBox('main', false);
        }
        if (location.hash === '#setari-cookie') {
            openBox('settings', true);
        }

        window.GICookie = {
            open: function () { openBox('settings', true); },
            get: readChoice
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
