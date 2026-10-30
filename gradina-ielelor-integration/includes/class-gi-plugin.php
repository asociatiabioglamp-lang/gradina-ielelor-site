<?php
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Plugin {
    public static function init() {
        add_filter('theme_page_templates', array(__CLASS__, 'register_template'), 10, 4);
        add_filter('template_include', array(__CLASS__, 'load_template'), 99);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('wp_head', array(__CLASS__, 'favicon'), 1);

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
        wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
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
