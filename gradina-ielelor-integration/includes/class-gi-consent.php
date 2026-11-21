<?php
/**
 * Cookie-uri și măsurare (1.9.0): bannerul de acord, Google Tag Manager încărcat numai după acord și plasa de siguranță pentru codurile străine.
 *
 * Cum funcționează (pe scurt):
 *  - Orice pagină a site-ului (și cele vechi, făcute cu vechea temă) primește, cât mai sus în <head>, starea implicită „refuzat” pentru Google Consent Mode.
 *  - Bannerul (Accept toate / Refuz / Alege) se afișează din JavaScript, pentru că paginile pot fi păstrate în memoria serverului (LiteSpeed): codul HTML e același
 *    pentru toți, iar alegerea fiecăruia stă numai în cookie-ul propriu `gi_consent` (versiune:statistici:marketing, ex. `1:1:0`).
 *  - Google Tag Manager (containerul din Setări → Grădina Ielelor) NU se încarcă până când vizitatorul nu acceptă statistici sau marketing. Modulul GTM4WP trebuie oprit
 *    (altfel își pune singur codul, fără acord); pagina de setări avertizează.
 *  - Plasa de siguranță: scriptele cu ID-urile vechi (Facebook, TikTok, Analytics și Tag Manager ale fostului administrator) se scot din pagină, indiferent de acord,
 *    pentru că vizitele nu trebuie să ajungă în conturi care nu sunt ale asociației.
 *
 * Textele bannerului sunt în română simplă, fără afirmații juridice. Ele, politica de cookie-uri (pagina „Cookies”) și durata păstrării alegerii (180 de zile)
 * trebuie arătate unui jurist; vezi docs/cookie-uri.md.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Consent {
    const COOKIE = 'gi_consent';
    /** Se schimbă când se schimbă categoriile sau sensul lor: toți vizitatorii sunt întrebați din nou. */
    const VERSION = 1;
    /** Cât se păstrează alegerea (zile). Apoi se întreabă din nou. */
    const DAYS = 180;

    public static function init() {
        add_action('wp_head', array(__CLASS__, 'head_defaults'), 0);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
        add_action('wp_footer', array(__CLASS__, 'render_banner'), 5); // înaintea scripturilor din subsol (prioritatea 20), ca scriptul să găsească bannerul
        add_action('template_redirect', array(__CLASS__, 'start_buffer'), 0);
        add_shortcode('gi_cookie_setari', array(__CLASS__, 'shortcode'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
    }

    /** Comutatorul din Setări → Grădina Ielelor. Oprit = nu apare nimic și nu se încarcă nimic (revenire la comportamentul de dinainte de 1.9.0). */
    public static function enabled() {
        $options = gi_options();
        return !empty($options['consent_enabled']);
    }

    /** Codul containerului Tag Manager (GTM-XXXX), validat; '' dacă lipsește sau arată greșit. */
    public static function gtm_id() {
        $options = gi_options();
        $id = isset($options['gtm_id']) ? strtoupper(trim((string) $options['gtm_id'])) : '';
        return 1 === preg_match('~^GTM-[A-Z0-9]{4,12}$~', $id) ? $id : '';
    }

    /** Zona echipei (conturi, fișe de cazare) nu are nici banner, nici măsurare. */
    private static function skip_request() {
        return is_admin() || (class_exists('GI_Zona') && GI_Zona::is_zona());
    }

    /** Adresa paginii cu politica de cookie-uri: „Cookies” (info-util/politica-cookies), apoi „Regulament & politici”. */
    public static function policy_url() {
        foreach (array('info-util/politica-cookies', 'regulament-politici') as $path) {
            $page = get_page_by_path($path);
            if ($page && 'publish' === $page->post_status) {
                return get_permalink($page);
            }
        }
        return '';
    }

    /** Starea implicită Google Consent Mode (totul refuzat) + alegerea deja făcută, din cookie. Cât mai sus în <head>, înaintea oricărui script de măsurare. */
    public static function head_defaults() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        $js = "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
            . "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'granted',security_storage:'granted'});"
            . "(function(){var m=document.cookie.match(/(?:^|; )" . self::COOKIE . "=([^;]*)/);if(!m)return;var r;try{r=decodeURIComponent(m[1]);}catch(e){return;}var p=r.split(':');"
            . "if(!/^\\d+:[01]:[01]$/.test(r)||p[0]!=='" . (int) self::VERSION . "')return;var s=p[1]==='1'?'granted':'denied',k=p[2]==='1'?'granted':'denied';"
            . "gtag('consent','update',{analytics_storage:s,ad_storage:k,ad_user_data:k,ad_personalization:k});})();";
        echo '<script id="gi-consent-default">' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text fix, fără date din exterior
    }

    public static function enqueue() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        wp_enqueue_style('gi-consent', gi_asset_url('css/consent.css'), array(), GI_VERSION);
        wp_enqueue_script('gi-consent', gi_asset_url('js/consent.js'), array(), GI_VERSION, true);
        wp_add_inline_script('gi-consent', 'window.GI_CONSENT=' . wp_json_encode(array(
            'gtm' => self::gtm_id(),
            'version' => (int) self::VERSION,
            'days' => (int) self::DAYS,
            'cookie' => self::COOKIE,
        )) . ';', 'before');
    }

    /** Bannerul: HTML ascuns, la fel pentru toți; JavaScript-ul îl arată numai cui nu a ales încă. */
    public static function render_banner() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        $policy = self::policy_url();
        $policy_link = $policy ? '<a href="' . esc_url($policy) . '">Politica de cookie-uri</a>' : '';
        ?>
        <div id="gi-cookie" class="gi-cookie" role="dialog" aria-modal="false" aria-labelledby="gi-cookie-title" tabindex="-1" hidden>
            <div class="gi-cookie__view" data-gi-view="main">
                <h2 id="gi-cookie-title" class="gi-cookie__title">Cookie-uri pe site</h2>
                <p class="gi-cookie__text">Folosim cookie-uri necesare, ca site-ul și rezervările să funcționeze. Cu acordul tău, folosim și cookie-uri de statistică (ce pagini sunt vizitate) și de marketing. Alegerea se poate schimba oricând, din „Setări cookie-uri”.</p>
                <div class="gi-cookie__actions">
                    <button type="button" class="gi-cookie__btn" data-gi-act="accept">Accept toate</button>
                    <button type="button" class="gi-cookie__btn" data-gi-act="reject">Refuz</button>
                    <button type="button" class="gi-cookie__btn gi-cookie__btn--ghost" data-gi-act="settings">Alege</button>
                </div>
                <?php if ($policy_link) : ?><p class="gi-cookie__more"><?php echo $policy_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?>
            </div>
            <div class="gi-cookie__view" data-gi-view="settings" hidden>
                <h2 class="gi-cookie__title">Alege ce cookie-uri accepți</h2>
                <ul class="gi-cookie__list">
                    <li class="gi-cookie__row">
                        <label for="gi-ck-need"><span class="gi-cookie__name">Necesare</span><span class="gi-cookie__desc">Mereu active: țin minte coșul de rezervare, autentificarea și alegerea ta de aici.</span></label>
                        <input type="checkbox" id="gi-ck-need" checked disabled>
                    </li>
                    <li class="gi-cookie__row">
                        <label for="gi-ck-stats"><span class="gi-cookie__name">Statistici</span><span class="gi-cookie__desc">Ne arată, fără să te identifice pe nume, ce pagini sunt vizitate și cum este folosit site-ul (Google Analytics).</span></label>
                        <input type="checkbox" id="gi-ck-stats" data-gi-cat="stats">
                    </li>
                    <li class="gi-cookie__row">
                        <label for="gi-ck-mkt"><span class="gi-cookie__name">Marketing</span><span class="gi-cookie__desc">Pentru reclame și măsurarea lor (de exemplu Facebook, TikTok, Google Ads).</span></label>
                        <input type="checkbox" id="gi-ck-mkt" data-gi-cat="mkt">
                    </li>
                </ul>
                <div class="gi-cookie__actions">
                    <button type="button" class="gi-cookie__btn" data-gi-act="save">Salvează alegerea</button>
                    <button type="button" class="gi-cookie__btn" data-gi-act="accept">Accept toate</button>
                    <button type="button" class="gi-cookie__btn" data-gi-act="reject">Refuz toate</button>
                </div>
                <p class="gi-cookie__more"><?php echo $policy_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><button type="button" class="gi-cookie__close" data-gi-act="close" hidden>Închide</button></p>
            </div>
        </div>
        <button type="button" class="gi-cookie-reopen" data-gi-cookie-open aria-label="Setări cookie-uri" title="Setări cookie-uri" hidden>
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-4-4 4 4 0 0 1-4-4 2 2 0 0 0-2-2Zm-3.5 9a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm3.5 6a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm4-3a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>
        </button>
        <?php
    }

    /** [gi_cookie_setari]: un buton „Setări cookie-uri”, pentru pagina de politici sau pentru orice pagină. */
    public static function shortcode() {
        if (!self::enabled()) {
            return '';
        }
        return '<button type="button" class="gi-cookie-link" data-gi-cookie-open>Setări cookie-uri</button>';
    }

    /* ---------------- plasa de siguranță: codurile fostului administrator ---------------- */

    /** ID-urile care nu sunt ale asociației (găsite în paginile vechi, 21.11.2026). Se pot completa cu filtrul `gi_blocked_tracker_ids`. */
    public static function blocked_ids() {
        $ids = apply_filters('gi_blocked_tracker_ids', array(
            '371463157145157',       // pixel Facebook
            'D7P8DJBC77U3CRQJR7P0',  // pixel TikTok
            'G-T0Y2TVCYBD',          // Google Analytics (proprietate veche)
            'GTM-T5L2SC7K',          // Tag Manager (container vechi)
        ));
        return array_values(array_filter(array_map('strval', (array) $ids), 'strlen'));
    }

    public static function start_buffer() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('is_embed') && is_embed())) {
            return;
        }
        ob_start(array(__CLASS__, 'strip_trackers'));
    }

    /** Scoate din pagină blocurile <script> și <noscript> care conțin un ID blocat. Orice altceva rămâne neatins; la orice eroare se întoarce pagina așa cum e. */
    public static function strip_trackers($html) {
        if (!is_string($html) || '' === $html || false === stripos($html, '<html')) {
            return $html;
        }
        $ids = self::blocked_ids();
        $hit = false;
        foreach ($ids as $id) {
            if (false !== strpos($html, $id)) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            return $html;
        }
        $pattern = '~' . implode('|', array_map(function ($id) {
            return preg_quote($id, '~');
        }, $ids)) . '~';
        $out = preg_replace_callback('~<script\b[^>]*>.*?</script>|<noscript\b[^>]*>.*?</noscript>~is', function ($match) use ($pattern) {
            return 1 === preg_match($pattern, $match[0]) ? '' : $match[0];
        }, $html);
        return is_string($out) ? $out : $html;
    }

    /* ---------------- avertisment în administrare ---------------- */

    /** Modulul GTM4WP încă activ = Tag Manager se încarcă fără acord. */
    public static function gtm4wp_active() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('duracelltomi-google-tag-manager/duracelltomi-google-tag-manager.php') || defined('GTM4WP_VERSION');
    }

    public static function admin_notice() {
        if (!self::enabled() || !current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array($screen->id, array('settings_page_gi-integration', 'plugins'), true)) {
            return;
        }
        if (self::gtm4wp_active()) {
            echo '<div class="notice notice-warning"><p><strong>Cookie-uri:</strong> modulul „GTM4WP — Google Tag Manager” este încă activ. El încarcă Tag Manager fără acordul vizitatorului. Dezactivează-l (Module → Module instalate → Dezactivează), ca bannerul să funcționeze: Tag Manager se încarcă acum din Grădina Ielelor, numai după acord.</p></div>';
        }
        if (!self::gtm_id()) {
            echo '<div class="notice notice-info"><p><strong>Cookie-uri:</strong> lipsește codul Tag Manager (Setări → Grădina Ielelor → Cookie-uri și măsurare). Până nu îl completezi, nu se măsoară nimic.</p></div>';
        }
    }
}
