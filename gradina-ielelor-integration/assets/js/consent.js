/* Cookie-uri (1.9.0): bannerul de acord, alegerea în cookie-ul `gi_consent` (versiune:statistici:marketing) și încărcarea Google Tag Manager numai după acord.
 * Funcționează pe toate paginile site-ului (și pe cele vechi); nu depinde de alte scripturi sau de stilurile temei. */
(function () {
    'use strict';

    function boot() {

    var cfg = window.GI_CONSENT || {};
    var NAME = cfg.cookie || 'gi_consent';
    var VERSION = String(cfg.version || 1);
    var DAYS = cfg.days || 180;
    var box = document.getElementById('gi-cookie');
    var reopen = document.querySelector('.gi-cookie-reopen');
    var root = document.documentElement;
    var loaded = false;
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

    /* ---------- Google Consent Mode și Tag Manager ---------- */
    function gtag() {
        (window.dataLayer = window.dataLayer || []).push(arguments);
    }

    function pushConsent(stats, mkt) {
        var s = stats ? 'granted' : 'denied';
        var k = mkt ? 'granted' : 'denied';
        gtag('consent', 'update', { analytics_storage: s, ad_storage: k, ad_user_data: k, ad_personalization: k });
        window.dataLayer.push({ event: 'gi_consent_update', gi_consent_statistici: !!stats, gi_consent_marketing: !!mkt });
    }

    function loadGTM() {
        if (loaded || !cfg.gtm || !/^GTM-[A-Z0-9]{4,12}$/.test(cfg.gtm)) {
            return;
        }
        loaded = true;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(cfg.gtm);
        document.head.appendChild(s);
    }

    /* Când se retrage un acord: se șterg cookie-urile de măsurare (pe domeniu și pe domeniile-părinte) */
    function purgeTrackingCookies() {
        var re = /^(_ga|_gid|_gat|_gcl_|_gac_|_fbp|_fbc|_ttp|_tt_|ttclid|__utm)/;
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
            closeBtn.hidden = !readChoice();
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

    function openBox(view) {
        if (view === 'settings') {
            fillSettings();
        }
        showView(view);
        box.hidden = false;
        root.classList.add('gi-cookie-open');
        if (reopen) {
            reopen.hidden = true;
        }
        try {
            box.focus({ preventScroll: true });
        } catch (e) {
            box.focus();
        }
    }

    function closeBox() {
        box.hidden = true;
        root.classList.remove('gi-cookie-open');
        if (reopen && readChoice()) {
            reopen.hidden = false;
        }
        if (lastOpener && document.contains(lastOpener) && !lastOpener.hidden) {
            try {
                lastOpener.focus();
            } catch (e) { /* nimic */ }
        }
        lastOpener = null;
    }

    function choose(stats, mkt) {
        var prev = readChoice();
        var withdrew = !!prev && ((prev.stats && !stats) || (prev.mkt && !mkt));
        writeChoice(stats, mkt);
        pushConsent(stats, mkt);
        if (stats || mkt) {
            loadGTM();
        }
        closeBox();
        if (withdrew) {
            purgeTrackingCookies();
            if (loaded) {
                location.reload(); // scripturile deja pornite nu se pot opri; paginile se reîncarcă fără ele
            }
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
            openBox('settings');
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
        openBox('settings');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || box.hidden) {
            return;
        }
        if (readChoice()) {
            closeBox();
        } else if (!views().settings.hidden) {
            showView('main');
        }
    });

    /* ---------- pornire ---------- */
    var choice = readChoice();
    if (choice) {
        if (choice.stats || choice.mkt) {
            loadGTM();
        }
        if (reopen) {
            reopen.hidden = false;
        }
    } else {
        openBox('main');
    }
    if (location.hash === '#setari-cookie') {
        openBox('settings');
    }

    window.GICookie = {
        open: function () { openBox('settings'); },
        get: readChoice
    };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
