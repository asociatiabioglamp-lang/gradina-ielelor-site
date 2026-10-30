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
    }

    public static function register_template($templates, $theme, $post, $post_type) {
        if ('page' === $post_type) {
            $templates[GI_TEMPLATE_SLUG] = 'Grădina Ielelor — One Page + BRW';
        }
        return $templates;
    }

    public static function load_template($template) {
        if (gi_is_one_page()) {
            return GI_PATH . 'templates/one-page.php';
        }
        return $template;
    }

    public static function enqueue_assets() {
        if (self::is_booking_page()) {
            wp_enqueue_style('gi-booking-pages', gi_asset_url('css/booking-pages.css'), array(), GI_VERSION);
            return;
        }

        if (!gi_is_one_page() && !gi_has_booking_shortcode()) {
            return;
        }

        wp_enqueue_style('gi-one-page', gi_asset_url('css/site.css'), array(), GI_VERSION);
        wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
    }

    private static function is_booking_page() {
        $is_shop = function_exists('is_shop') && is_shop();
        $is_product = function_exists('is_product') && is_product();
        return $is_shop || $is_product;
    }

    public static function body_class($classes) {
        if (gi_is_one_page()) {
            $classes[] = 'gi-one-page';
        }
        return $classes;
    }

    public static function favicon() {
        if (gi_is_one_page()) {
            echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(gi_asset_url('favicon.svg')) . '">' . "\n";
        }
    }
}

function gi_is_one_page() {
    if (!is_singular('page')) {
        return false;
    }
    return GI_TEMPLATE_SLUG === get_page_template_slug(get_queried_object_id());
}

function gi_has_booking_shortcode() {
    if (!is_singular()) {
        return false;
    }
    $post = get_post(get_queried_object_id());
    return $post && has_shortcode($post->post_content, 'gradina_ielelor_booking');
}
