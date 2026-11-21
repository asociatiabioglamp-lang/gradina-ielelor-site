<?php
/**
 * „Pagini ușoare” (1.10.0): pe paginile făcute cu template-urile pluginului nu se mai încarcă fișierele altor module și ale temei vechi.
 *
 * De ce: pe live, fiecare pagină (și cele noi) făcea 125–145 de cereri: ~25 de fișiere ale temei vechi `giw` (WoodMart, Bootstrap), Elementor și Elementor Pro,
 * `hoteller-elementor`, `advanced-coupons`, formularul de rezervare (`ova-brw`), trei fonturi Google ale altor module, iar paginile nu foloseau nimic din ele
 * (măsurat pe 21.11.2026: primul conținut la ~6 s, pagina gata la 10–14 s; docs/viteza-2026-11-21.md). Paginile noastre au propriul CSS și propriul JS.
 *
 * Ce se scoate: orice stil sau script înregistrat de temă, de Elementor / Elementor Pro, de hoteller-elementor, de advanced-coupons (după locul fișierului și după numele
 * lui) și fonturile Google ale acestor module; BRW (`ova-brw`) numai unde pagina nu are formularul de rezervare; fișierele WooCommerce de coș și de plată (rămân cele de
 * atribuire a comenzilor, tratate de banner în 1.9.0). Din HTML se scot și `<link rel="modulepreload">` și scripturile-modul ale cuponelor.
 * Ce NU se atinge: paginile WooCommerce (magazin, produs, coș, plată, cont), paginile vechi făcute cu Elementor, Zona echipei, administrarea.
 *
 * Nivelul din Setări → Grădina Ielelor → „Pagini ușoare”: 0 = oprit (cum era), 1 = toate paginile noi, în afară de prima pagină (implicit; prima pagină are formularul
 * de rezervare real), 2 = și prima pagină (BRW rămâne, se scot restul). Orice schimbare e activă imediat (nu există memorie de pagină).
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Light {
    /** Pagina se afișează cu un template al pluginului? (stabilit la template_include) */
    private static $own = false;

    public static function init() {
        add_filter('template_include', array(__CLASS__, 'detect'), 100000);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'strip'), 100100);
        add_action('wp_head', array(__CLASS__, 'strip'), 7);   // înainte ca WordPress să afișeze stilurile (prioritatea 8)
        add_action('wp_footer', array(__CLASS__, 'strip'), 0); // înainte ca WordPress să afișeze scripturile din subsol (prioritatea 20)
        add_action('template_redirect', array(__CLASS__, 'start_buffer'), 1);
    }

    /** 0 = oprit, 1 = fără prima pagină (implicit), 2 = și prima pagină. */
    public static function level() {
        $options = gi_options();
        $level = isset($options['light_pages']) ? (int) $options['light_pages'] : 1;
        return max(0, min(2, $level));
    }

    public static function detect($template) {
        self::$own = is_string($template) && 0 === strpos(wp_normalize_path($template), wp_normalize_path(GI_PATH . 'templates/'));
        return $template;
    }

    /** Se aplică pe această pagină? */
    private static function applies() {
        $level = self::level();
        if ($level < 1 || !self::$own || is_admin() || wp_doing_ajax()) {
            return false;
        }
        if (class_exists('GI_Zona') && GI_Zona::is_zona()) {
            return false; // zona echipei: neatinsă
        }
        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return false;
        }
        if (self::is_hub() && $level < 2) {
            return false; // prima pagină are formularul real de rezervare: se atinge numai la nivelul 2
        }
        return true;
    }

    private static function is_hub() {
        return function_exists('gi_is_one_page') && gi_is_one_page();
    }

    /** Textul paginii (numai pentru pagini simple), ca să se vadă dacă are formular de rezervare sau blocuri WooCommerce. */
    private static function content() {
        if (!is_singular()) {
            return '';
        }
        $post = get_post(get_queried_object_id());
        return $post ? (string) $post->post_content : '';
    }

    private static function needs_booking() {
        if (self::is_hub()) {
            return true;
        }
        $content = self::content();
        return '' !== $content && 1 === preg_match('~\[(?:ovabrw_|gradina_ielelor_booking)~i', $content);
    }

    private static function needs_woocommerce() {
        $content = self::content();
        return '' !== $content && false !== stripos($content, 'woocommerce');
    }

    /** Fragmente din adresa fișierului care arată că vine de la un modul sau de la tema care nu are ce căuta pe paginile noastre. */
    private static function source_patterns($keep_booking) {
        $patterns = array(
            '/wp-content/themes/',
            '/wp-content/plugins/elementor/',
            '/wp-content/plugins/elementor-pro/',
            '/wp-content/plugins/hoteller-elementor/',
            '/wp-content/plugins/advanced-coupons-for-woocommerce-free/',
            '/wp-content/uploads/elementor/',
        );
        if (!$keep_booking) {
            $patterns[] = '/wp-content/plugins/s4p_ova-brw20/';
            $patterns[] = '/wp-content/plugins/ova-brw/';
        }
        return apply_filters('gi_light_drop_sources', $patterns, $keep_booking);
    }

    /** Începuturi de nume ale fișierelor generate în „uploads” sau încărcate din alte adrese (fonturi Google), care nu se văd după locul fișierului. */
    private static function handle_prefixes($keep_booking) {
        $prefixes = array('elementor', 'xts-', 'woodmart', 'wd-', 'hoteller', 'acfwf', 'google-fonts-');
        if (!$keep_booking) {
            $prefixes[] = 'ovabrw';
            $prefixes[] = 'ova-';
        }
        return apply_filters('gi_light_drop_handles', $prefixes, $keep_booking);
    }

    private static function matches($handle, $src, $sources, $prefixes) {
        $src = is_string($src) ? $src : '';
        foreach ($sources as $needle) {
            if ('' !== $src && false !== strpos($src, $needle)) {
                return true;
            }
        }
        foreach ($prefixes as $prefix) {
            if (0 === strpos($handle, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public static function strip() {
        if (!self::applies()) {
            return;
        }
        $keep_booking = self::needs_booking();
        $sources = self::source_patterns($keep_booking);
        $prefixes = self::handle_prefixes($keep_booking);

        $styles = wp_styles();
        foreach ((array) $styles->queue as $handle) {
            $src = isset($styles->registered[$handle]) ? $styles->registered[$handle]->src : '';
            if (self::matches($handle, $src, $sources, $prefixes)) {
                wp_dequeue_style($handle);
            }
        }
        $scripts = wp_scripts();
        foreach ((array) $scripts->queue as $handle) {
            $src = isset($scripts->registered[$handle]) ? $scripts->registered[$handle]->src : '';
            if (self::matches($handle, $src, $sources, $prefixes)) {
                wp_dequeue_script($handle);
            }
        }

        // WooCommerce: coșul, plata și fragmentele de coș nu sunt folosite pe paginile noastre (aceleași fișiere pe care le scoate deja GI_Plugin pe paginile ofertelor);
        // rămân sourcebuster-js și wc-order-attribution (rețin sursa clientului care face apoi comanda; le tratează bannerul de cookie-uri din 1.9.0).
        if (!self::is_hub() && !self::needs_woocommerce()) {
            foreach (array('woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general', 'woocommerce-blocktheme', 'wc-blocks-style', 'woocommerce-inline') as $handle) {
                wp_dequeue_style($handle);
            }
            foreach (array('wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'js-cookie', 'jquery-blockui') as $handle) {
                wp_dequeue_script($handle);
            }
        }
    }

    /* ---------------- modulele JavaScript ale cuponelor, scrise direct în pagină ---------------- */

    public static function start_buffer() {
        if (self::level() < 1 || is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('is_embed') && is_embed())) {
            return;
        }
        ob_start(array(__CLASS__, 'clean_html'));
    }

    /** Scoate <link rel="modulepreload"> și <script type="module"> ale cuponelor (WordPress le pune în pagină chiar dacă pagina nu are cupoane). */
    public static function clean_html($html) {
        if (!is_string($html) || '' === $html || !self::applies() || false === stripos($html, 'advanced-coupons-for-woocommerce-free')) {
            return $html;
        }
        $out = preg_replace_callback('~<link\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>|<script\b(?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+>\s*</script>~i', function ($match) {
            $tag = $match[0];
            if (false === stripos($tag, 'advanced-coupons-for-woocommerce-free')) {
                return $tag;
            }
            return (false !== stripos($tag, 'modulepreload') || false !== stripos($tag, 'type="module"') || false !== stripos($tag, "type='module'")) ? '' : $tag;
        }, $html);
        return is_string($out) ? $out : $html;
    }
}
