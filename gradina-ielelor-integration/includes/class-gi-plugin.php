<?php
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Plugin {
    public static function init() {
        add_filter('theme_page_templates', array(__CLASS__, 'register_template'), 10, 4);
        add_filter('template_include', array(__CLASS__, 'load_template'), 99);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'trim_dedicated_assets'), 999);
        add_action('wp_footer', array(__CLASS__, 'trim_dedicated_assets'), 1); // wc-blocks-style se adaugă târziu.
        add_filter('render_block', array(__CLASS__, 'hide_editor_note'), 10, 2);
        add_filter('get_the_excerpt', array(__CLASS__, 'excerpt_without_notes'), 9, 2);
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('wp_head', array(__CLASS__, 'favicon'), 1);
        add_action('wp_head', array(__CLASS__, 'booking_arrival_script'), 3);

        // Navigare fluidă între paginile Grădinii: preîncărcare la hover (WordPress 6.8+).
        add_filter('wp_speculation_rules_configuration', array(__CLASS__, 'speculation_configuration'));
        add_filter('wp_speculation_rules_href_exclude_paths', array(__CLASS__, 'speculation_exclude_paths'), 10, 2);
    }

    public static function register_template($templates, $theme, $post, $post_type) {
        if ('page' === $post_type) {
            foreach (gi_page_templates() as $slug => $template) {
                $templates[$slug] = $template['label'];
            }
        }
        return $templates;
    }

    public static function load_template($template) {
        if (!is_singular('page')) {
            return $template;
        }
        $templates = gi_page_templates();
        $slug = get_page_template_slug(get_queried_object_id());
        if (isset($templates[$slug])) {
            return GI_PATH . 'templates/' . $templates[$slug]['file'];
        }
        return $template;
    }

    /**
     * Paginile ofertelor nu au coș și nici formularul de rezervare: fișierele WooCommerce doar le încetinesc
     * (aprox. 1 s pe telefon, docs/qa-performanta-accesibilitate.md). Pe prima pagină rămân toate, pentru BRW.
     * Rămân și sourcebuster-js / wc-order-attribution: rețin de unde a venit vizitatorul care face apoi comanda.
     */
    public static function trim_dedicated_assets() {
        // Pagina simplă poate primi blocuri WooCommerce de la proprietar: acolo nu se scoate nimic.
        if (!gi_is_dedicated_page() || 'content' === gi_current_page_key()) {
            return;
        }
        foreach (array('woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general', 'woocommerce-blocktheme', 'wc-blocks-style') as $handle) {
            wp_dequeue_style($handle);
        }
        foreach (array('wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'js-cookie', 'jquery-blockui') as $handle) {
            wp_dequeue_script($handle);
        }
    }

    /**
     * Plasă de siguranță: un bloc cu clasa `gi-nota-editor` nu ajunge la vizitatori (pagină, căutare, REST, flux).
     * Notele nu se pun în pagină (docs/pagini-continut.md); cine poate edita pagini le vede în continuare.
     */
    public static function hide_editor_note($content, $block) {
        if (empty($block['attrs']['className']) || current_user_can('edit_pages')) {
            return $content;
        }
        $classes = preg_split('/\s+/', (string) $block['attrs']['className']);
        return in_array('gi-nota-editor', $classes, true) ? '' : $content;
    }

    /**
     * Rezumatul automat (căutare, REST) se face din blocuri randate, deci fără note; rezumatul scris de mână rămâne.
     */
    public static function excerpt_without_notes($excerpt, $post = null) {
        if ('' !== trim((string) $excerpt) || !($post instanceof WP_Post) || false === strpos($post->post_content, 'gi-nota-editor') || current_user_can('edit_pages')) {
            return $excerpt;
        }
        $text = wp_strip_all_tags(do_blocks(strip_shortcodes($post->post_content)));
        return wp_trim_words($text, (int) apply_filters('excerpt_length', 55), apply_filters('excerpt_more', ' [&hellip;]'));
    }

    public static function enqueue_assets() {
        if (self::is_booking_page()) {
            wp_enqueue_style('gi-booking-pages', gi_asset_url('css/booking-pages.css'), array(), GI_VERSION);
            return;
        }

        if (!gi_is_site_page() && !gi_has_booking_shortcode()) {
            return;
        }

        wp_enqueue_style('gi-one-page', gi_asset_url('css/site.css'), array(), GI_VERSION);
        if (gi_is_site_page()) {
            wp_enqueue_style('gi-pages', gi_asset_url('css/pages.css'), array('gi-one-page'), GI_VERSION);
        }
        if (gi_is_one_page()) {
            wp_enqueue_style('gi-home', gi_asset_url('css/home.css'), array('gi-one-page'), GI_VERSION);
        }
        if ('content' === gi_current_page_key()) {
            wp_enqueue_style('gi-content-page', gi_asset_url('css/content-page.css'), array('gi-pages'), GI_VERSION);
        }
        wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
        if (gi_is_dedicated_page() && file_exists(GI_PATH . 'assets/js/pages.js')) {
            wp_enqueue_script('gi-pages', gi_asset_url('js/pages.js'), array('gi-one-page'), GI_VERSION, true);
        }
    }

    private static function is_booking_page() {
        $is_shop = function_exists('is_shop') && is_shop();
        $is_product = function_exists('is_product') && is_product();
        return $is_shop || $is_product;
    }

    public static function body_class($classes) {
        $key = gi_current_page_key();
        if ($key) {
            $classes[] = 'gi-one-page';
            $classes[] = 'gi-page-' . sanitize_html_class($key);
        }
        return $classes;
    }

    public static function favicon() {
        if (gi_is_site_page()) {
            echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(gi_asset_url('favicon.svg')) . '">' . "\n";
        }
    }

    /**
     * Pe pagina principală: la sosirea în modulul de rezervări (/#rezervare-<tip>) fotografia cardului
     * nu participă la tranziție, ca imaginea paginii dedicate să nu zboare spre un card din afara ecranului.
     * La orice sosire cu tranziție (pagereveal), cardurile din afara ecranului își pierd numele de tranziție.
     * Rulează în head, ca să prindă pagereveal (site.js se încarcă prea târziu pentru el).
     */
    public static function booking_arrival_script() {
        if (!gi_is_one_page()) {
            return;
        }
        echo "<script>(function(){var r=document.documentElement;function m(){r.classList.toggle('gi-booking-arrival',/^#rezervare/.test(location.hash));}m();addEventListener('pagereveal',function(e){m();if(!e.viewTransition)return;document.querySelectorAll('.gi-offer-card img').forEach(function(i){var b=i.getBoundingClientRect();i.style.viewTransitionName=(b.bottom>0&&b.top<innerHeight)?'':'none';});});addEventListener('hashchange',m);addEventListener('pageswap',function(){r.classList.remove('gi-booking-arrival');});}());</script>\n";
    }

    /**
     * Pe paginile Grădinii, linkurile interne se pregătesc când vizitatorul ține cursorul pe ele.
     * Pentru utilizatorii autentificați WordPress întoarce null; atunci nu schimbăm nimic.
     */
    public static function speculation_configuration($config) {
        if (!is_array($config) || !gi_is_site_page()) {
            return $config;
        }
        return array('mode' => 'prerender', 'eagerness' => 'moderate');
    }

    /**
     * Coșul, checkout-ul și contul nu se preîncarcă: au sesiune și efecte WooCommerce.
     */
    public static function speculation_exclude_paths($paths, $mode) {
        $urls = array();
        if (function_exists('wc_get_cart_url')) {
            $urls[] = wc_get_cart_url();
        }
        if (function_exists('wc_get_checkout_url')) {
            $urls[] = wc_get_checkout_url();
        }
        if (function_exists('wc_get_page_permalink')) {
            $urls[] = wc_get_page_permalink('myaccount');
        }
        // WordPress adaugă singur prefixul instalării; căile trebuie date relativ la pagina principală.
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        foreach ($urls as $url) {
            $path = (string) wp_parse_url($url, PHP_URL_PATH);
            if ($home_path && 0 === strpos($path, $home_path)) {
                $path = '/' . ltrim(substr($path, strlen($home_path)), '/');
            }
            if ('/' !== $path && '' !== $path) {
                $paths[] = trailingslashit($path) . '*';
            }
        }
        return $paths;
    }
}

function gi_is_one_page() {
    return 'one-page' === gi_current_page_key();
}

function gi_has_booking_shortcode() {
    if (!is_singular()) {
        return false;
    }
    $post = get_post(get_queried_object_id());
    return $post && has_shortcode($post->post_content, 'gradina_ielelor_booking');
}
