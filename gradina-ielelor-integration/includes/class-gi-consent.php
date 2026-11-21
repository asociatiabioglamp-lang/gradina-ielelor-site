<?php
/**
 * Cookie-uri și măsurare (1.9.0): bannerul de acord, Google Tag Manager încărcat numai după acord și plasa de siguranță pentru codurile străine.
 *
 * Cum funcționează (pe scurt):
 *  - Orice pagină a site-ului (și cele vechi, făcute cu vechea temă) primește, cât mai sus în <head>, starea implicită „refuzat” pentru Google Consent Mode.
 *  - Bannerul (Accept toate / Refuz / Alege) se afișează din JavaScript, pentru că paginile pot fi păstrate în memoria serverului (LiteSpeed): codul HTML e același
 *    pentru toți, iar alegerea fiecăruia stă numai în cookie-ul propriu `gi_consent` (versiune:statistici:marketing, ex. `1:1:0`).
 *  - Google Tag Manager (containerul din Setări → Grădina Ielelor) NU se încarcă până când vizitatorul nu acceptă statistici sau marketing. Din HTML se scot toate
 *    codurile de măsurare scrise direct în pagină (Tag Manager, gtag, pixel Facebook, pixel TikTok): orice cod de acest fel din pagină ar rula fără acord.
 *  - Cookie-urile de atribuire ale WooCommerce (`sbjs_*`, „de unde a venit clientul”) sunt tratate ca statistici: scripturile lor pornesc numai după acord.
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

    /** Pașii (cod inline sau adresă de script) ai scripturilor WooCommerce de atribuire mutate sub acord; se pun în configurația bannerului. */
    private static $attribution_steps = array();

    public static function init() {
        add_action('wp_head', array(__CLASS__, 'head_defaults'), 0);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'defer_order_attribution'), 100000);
        add_action('wp_footer', array(__CLASS__, 'defer_order_attribution'), 1);
        add_action('wp_footer', array(__CLASS__, 'render_banner'), 5); // înaintea scripturilor din subsol (prioritatea 20), ca scriptul să găsească bannerul
        add_action('template_redirect', array(__CLASS__, 'start_buffer'), 0);
        add_shortcode('gi_cookie_setari', array(__CLASS__, 'shortcode'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        add_action('init', array(__CLASS__, 'purge_cache_after_upgrade'), 20);
        add_action('update_option_gi_integration_options', array(__CLASS__, 'purge_cache'));
        add_action('add_option_gi_integration_options', array(__CLASS__, 'purge_cache'));
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

    /** Zona echipei (conturi, fișe de cazare), previzualizările (Personalizare, Elementor) și administrarea nu au nici banner, nici măsurare. */
    private static function skip_request() {
        if (is_admin() || (class_exists('GI_Zona') && GI_Zona::is_zona())) {
            return true;
        }
        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return true;
        }
        return isset($_GET['elementor-preview']) || isset($_GET['elementor_library']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    /** Pagina cu politica de cookie-uri: „Cookies” (info-util/politica-cookies), apoi „Regulament & politici”. Întoarce adresa și textul linkului. */
    public static function policy() {
        $candidates = array('info-util/politica-cookies' => 'Politica de cookie-uri', 'regulament-politici' => 'Regulament și politici');
        foreach ($candidates as $path => $label) {
            $page = get_page_by_path($path);
            if ($page && 'publish' === $page->post_status) {
                return array('url' => get_permalink($page), 'label' => $label, 'dedicated' => 'info-util/politica-cookies' === $path);
            }
        }
        return array('url' => '', 'label' => '', 'dedicated' => false);
    }

    public static function policy_url() {
        $policy = self::policy();
        return $policy['url'];
    }

    /** Starea implicită Google Consent Mode (totul refuzat) + alegerea deja făcută, din cookie. Cât mai sus în <head>, înaintea oricărui script de măsurare. */
    public static function head_defaults() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        $js = "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
            . "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',personalization_storage:'denied',functionality_storage:'granted',security_storage:'granted'});"
            . "(function(){var m=document.cookie.match(/(?:^|; )" . self::COOKIE . "=([^;]*)/);if(!m)return;var r;try{r=decodeURIComponent(m[1]);}catch(e){return;}var p=r.split(':');"
            . "if(!/^\\d+:[01]:[01]$/.test(r)||p[0]!=='" . (int) self::VERSION . "')return;var s=p[1]==='1'?'granted':'denied',k=p[2]==='1'?'granted':'denied';"
            . "gtag('consent','update',{analytics_storage:s,ad_storage:k,ad_user_data:k,ad_personalization:k,personalization_storage:k});})();";
        echo '<script id="gi-consent-default">' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text fix, fără date din exterior
    }

    /**
     * Scripturile WooCommerce de atribuire a comenzilor (sourcebuster-js, wc-order-attribution) pun cookie-uri `sbjs_*` (sursă, campanie, pagina de intrare) încă de la
     * prima pagină. Sunt tratate ca statistici: se scot din pagină și se încarcă din consent.js numai după acord (cu aceleași date și în aceeași ordine).
     */
    public static function defer_order_attribution() {
        if (!self::enabled() || self::skip_request() || apply_filters('gi_keep_order_attribution', false)) {
            return;
        }
        $scripts = wp_scripts();
        $steps = array();
        $moving = array('sourcebuster-js', 'wc-order-attribution'); // se mută împreună: wc-order-attribution depinde de sourcebuster-js
        foreach ($moving as $handle) {
            if (!isset($scripts->registered[$handle]) || !wp_script_is($handle, 'enqueued')) {
                continue;
            }
            // Un script de care depinde altul pus în coadă (ex. integrarea WP Consent API) nu se mută: ar porni oricum, fără acord.
            $has_dependents = false;
            foreach ((array) $scripts->queue as $queued) {
                if (!in_array($queued, $moving, true) && isset($scripts->registered[$queued]) && in_array($handle, (array) $scripts->registered[$queued]->deps, true)) {
                    $has_dependents = true;
                    break;
                }
            }
            if ($has_dependents) {
                continue;
            }
            $reg = $scripts->registered[$handle];
            // ordinea din WordPress: datele (wp_localize_script), apoi „before”, scriptul, „after”
            $data = $scripts->get_data($handle, 'data');
            if (is_string($data) && '' !== trim($data)) {
                $steps[] = array('code' => $data);
            }
            $before = $scripts->get_data($handle, 'before');
            if (is_array($before)) {
                foreach ($before as $code) {
                    if (is_string($code) && '' !== trim($code)) {
                        $steps[] = array('code' => $code);
                    }
                }
            }
            $src = (string) $reg->src;
            if ('' !== $src) {
                if (!preg_match('~^(https?:)?//~i', $src)) {
                    $src = $scripts->base_url . $src;
                }
                $steps[] = array('src' => add_query_arg('ver', $reg->ver ? $reg->ver : false, $src));
            }
            $after = $scripts->get_data($handle, 'after');
            if (is_array($after)) {
                foreach ($after as $code) {
                    if (is_string($code) && '' !== trim($code)) {
                        $steps[] = array('code' => $code);
                    }
                }
            }
            wp_dequeue_script($handle);
        }
        if ($steps) {
            self::$attribution_steps = array_merge(self::$attribution_steps, $steps);
        }
    }

    public static function enqueue() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        wp_enqueue_style('gi-consent', gi_asset_url('css/consent.css'), array(), GI_VERSION);
        wp_enqueue_script('gi-consent', gi_asset_url('js/consent.js'), array(), GI_VERSION, true);
    }

    /** Configurația se pune în pagină târziu (după ce se știe ce scripturi WooCommerce au fost mutate), chiar înaintea scriptului. */
    private static function print_config() {
        wp_add_inline_script('gi-consent', 'window.GI_CONSENT=' . wp_json_encode(array(
            'gtm' => self::gtm_id(),
            'version' => (int) self::VERSION,
            'days' => (int) self::DAYS,
            'cookie' => self::COOKIE,
            'attr' => self::$attribution_steps,
        ), JSON_HEX_TAG | JSON_HEX_AMP) . ';', 'before');
    }

    /** Bannerul: HTML ascuns, la fel pentru toți; JavaScript-ul îl arată numai cui nu a ales încă. */
    public static function render_banner() {
        if (!self::enabled() || self::skip_request()) {
            return;
        }
        self::print_config();
        $policy = self::policy();
        $policy_link = $policy['url'] ? '<a href="' . esc_url($policy['url']) . '">' . esc_html($policy['label']) . '</a>' : '';
        ?>
        <div id="gi-cookie" class="gi-cookie" role="dialog" aria-modal="false" aria-labelledby="gi-cookie-title" tabindex="-1" hidden>
            <div class="gi-cookie__view" data-gi-view="main">
                <h2 id="gi-cookie-title" class="gi-cookie__title">Cookie-uri pe site</h2>
                <p class="gi-cookie__text">Folosim cookie-uri necesare, ca site-ul și rezervările să funcționeze. Cu acordul tău, folosim și cookie-uri de statistică (ce pagini sunt vizitate, de unde ai venit) și de marketing. Alegerea se poate schimba oricând, din „Setări cookie-uri”.</p>
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
                        <label for="gi-ck-need"><span class="gi-cookie__name">Necesare</span><span class="gi-cookie__desc">Mereu active: țin minte coșul de rezervare, autentificarea, limba aleasă și alegerea ta de aici.</span></label>
                        <input type="checkbox" id="gi-ck-need" checked disabled>
                    </li>
                    <li class="gi-cookie__row">
                        <label for="gi-ck-stats"><span class="gi-cookie__name">Statistici</span><span class="gi-cookie__desc">Ne arată, fără să te identifice pe nume, ce pagini sunt vizitate, de unde ai venit și cum este folosit site-ul (Google Analytics).</span></label>
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
        if (!self::enabled() || self::skip_request()) {
            return '';
        }
        return '<button type="button" class="gi-cookie-link" data-gi-cookie-open>Setări cookie-uri</button>';
    }

    /* ---------------- plasa de siguranță: coduri de măsurare scrise direct în pagină ---------------- */

    /** ID-urile care nu sunt ale asociației (găsite în paginile vechi, 21.11.2026). Se pot completa cu filtrul `gi_blocked_tracker_ids`. */
    public static function blocked_ids() {
        $ids = apply_filters('gi_blocked_tracker_ids', array(
            '371463157145157',       // pixel Facebook
            'D7P8DJBC77U3CRQJR7P0',  // pixel TikTok
            'G-T0Y2TVCYBD',          // Google Analytics (proprietate veche)
            'GTM-T5L2SC7K',          // Tag Manager (container vechi)
        ));
        $clean = array();
        foreach ((array) $ids as $id) {
            if (is_string($id) && strlen($id) >= 8) { // un ID mai scurt ar scoate scripturi care nu au legătură
                $clean[] = $id;
            }
        }
        return $clean;
    }

    /** Fragmente care arată că un bloc încarcă măsurare direct din pagină (deci înainte de acord). Numai când bannerul e pornit. */
    private static function loader_needles() {
        return array('googletagmanager.com/gtm.js', 'googletagmanager.com/gtag/js', 'googletagmanager.com/ns.html', 'connect.facebook.net', 'analytics.tiktok.com', 'facebook.com/tr?', 'facebook.com/tr/');
    }

    public static function start_buffer() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('is_embed') && is_embed())) {
            return;
        }
        if (class_exists('GI_Zona') && GI_Zona::is_zona()) { // exporturile și paginile Zonei se trimit așa cum sunt, fără memorie intermediară
            return;
        }
        ob_start(array(__CLASS__, 'strip_trackers'));
    }

    /**
     * Scoate din pagină blocurile <script>, <noscript> și <iframe> (și etichetele <link> și <img>) care conțin un ID blocat sau, cu bannerul pornit, un cod de încărcare a
     * măsurării. Orice altceva rămâne neatins. Se caută cu strpos (nu cu o singură expresie regulată), ca scripturile mari să nu depășească limitele PCRE; comentariile HTML
     * se sar (un <script> scris într-un comentariu nu se execută).
     */
    public static function strip_trackers($html) {
        if (!is_string($html) || '' === $html || false === stripos($html, '<html')) {
            return $html;
        }
        if (function_exists('headers_list')) { // numai pagini HTML (nu CSV, JSON etc.)
            foreach (headers_list() as $header) {
                if (0 === stripos($header, 'content-type:') && false === stripos($header, 'text/html')) {
                    return $html;
                }
            }
        }
        $needles = self::blocked_ids();
        if (self::enabled()) {
            $needles = array_merge($needles, self::loader_needles());
        }
        $hit = false;
        foreach ($needles as $needle) {
            if (false !== stripos($html, $needle)) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            return $html;
        }
        $out = self::strip_blocks($html, $needles);
        $simple = preg_replace_callback('~<(?:link|img)\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>~i', function ($match) use ($needles) {
            return self::contains_any($match[0], $needles) ? '' : $match[0];
        }, $out);
        return is_string($simple) ? $simple : $out;
    }

    private static function contains_any($text, $needles) {
        foreach ($needles as $needle) {
            if (false !== stripos($text, $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function strip_blocks($html, $needles) {
        $len = strlen($html);
        $pos = 0;
        $out = '';
        $comment = null; // poziția următorului comentariu (false = nu mai sunt); se recalculează numai când a rămas în urmă, ca scanarea să fie liniară
        $tag_at = null;
        $tag_name = '';
        while ($pos < $len) {
            if (null === $comment || (false !== $comment && $comment < $pos)) {
                $comment = strpos($html, '<!--', $pos);
            }
            if (null === $tag_at || (false !== $tag_at && $tag_at < $pos)) {
                if (preg_match('~<(script|noscript|iframe)(?=[\s/>])~i', $html, $m, PREG_OFFSET_CAPTURE, $pos)) {
                    $tag_at = $m[0][1];
                    $tag_name = strtolower($m[1][0]);
                } else {
                    $tag_at = false;
                }
            }
            if (false === $comment && false === $tag_at) {
                break;
            }
            if (false !== $comment && (false === $tag_at || $comment < $tag_at)) {
                if ('>' === substr($html, $comment + 4, 1)) { // <!--> este un comentariu gol
                    $end = $comment + 5;
                } elseif ('->' === substr($html, $comment + 4, 2)) { // <!--->
                    $end = $comment + 6;
                } else {
                    $end = strpos($html, '-->', $comment + 4);
                    $end = false === $end ? $len : $end + 3; // comentariu neînchis: restul paginii e comentariu și pentru browser
                }
                $out .= substr($html, $pos, $end - $pos);
                $pos = $end;
                continue;
            }
            // eticheta de închidere: „</script” urmat de spațiu, „/” sau „>” (ca în browser)
            if (!preg_match('~</' . $tag_name . '(?=[\s/>])~i', $html, $cm, PREG_OFFSET_CAPTURE, $tag_at + 1)) {
                break; // eticheta nu se închide: se lasă neatinsă
            }
            $gt = strpos($html, '>', $cm[0][1]);
            if (false === $gt) {
                break;
            }
            $block = substr($html, $tag_at, $gt + 1 - $tag_at);
            $out .= substr($html, $pos, $tag_at - $pos);
            if (!self::contains_any($block, $needles)) {
                $out .= $block;
            }
            $pos = $gt + 1;
        }
        return $out . substr($html, $pos);
    }

    /* ---------------- memoria serverului (LiteSpeed) ---------------- */

    /** Bannerul, starea implicită și codul containerului stau în HTML-ul păstrat de server: după orice schimbare de setări sau de versiune se șterge tot ce e păstrat. */
    public static function purge_cache() {
        do_action('litespeed_purge_all');
    }

    public static function purge_cache_after_upgrade() {
        if (get_option('gi_consent_cache_version') !== GI_VERSION) {
            update_option('gi_consent_cache_version', GI_VERSION, false);
            self::purge_cache();
        }
    }

    /* ---------------- avertisment în administrare ---------------- */

    /** Modulul GTM4WP încă activ: codul lui din pagină se scoate oricum de aici, dar nu mai are rost să rămână. */
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
            echo '<div class="notice notice-warning"><p><strong>Cookie-uri:</strong> modulul „GTM4WP — Google Tag Manager” este încă activ. Codul lui din pagină se scoate automat de către banner (Tag Manager pornește numai după acord, din Grădina Ielelor), deci nu strică nimic, dar nu mai are rost: dezactivează-l (Module → Module instalate → Dezactivează).</p></div>';
        }
        if (!self::gtm_id()) {
            echo '<div class="notice notice-info"><p><strong>Cookie-uri:</strong> lipsește codul Tag Manager (Setări → Grădina Ielelor → Cookie-uri și măsurare). Până nu îl completezi, nu se măsoară nimic.</p></div>';
        }
    }
}
