<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-10-0.js („pagini ușoare”): pune în orice pagină fișiere de probă, cu aceleași nume și aceleași locuri ca pe live (tema giw, Elementor,
 * hoteller-elementor, cupoane, BRW, fonturi Google) și un fișier „de control” al unui alt modul, care trebuie să rămână. Fișierele nu există (404), contează doar ce
 * se scoate din pagină.
 *  - /?gi_test=c1100_nivel&n=0|1|2 → pune „Pagini ușoare” pe nivelul n (fără n: numai citește); întoarce nivelul
 *  - /?gi_test=c1100_pagini → creează (o singură dată) pagina cu formular de rezervare ([ovabrw_search]) și pagina cu blocuri WooCommerce; întoarce adresele
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_test_host = strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST));
if (!in_array($gi_test_host, array('127.0.0.1', 'localhost'), true)) {
    return;
}

add_action('wp_enqueue_scripts', function () {
    $u = content_url();
    $styles = array(
        'bootstrap' => '/themes/giw/css/bootstrap.min.css',
        'woodmart-style' => '/themes/giw/css/style.min.css',
        'wd-header-cart' => '/themes/giw/css/parts/header-cart.min.css',
        'elementor-frontend' => '/uploads/elementor/css/custom-frontend.min.css',
        'elementor-post-2374' => '/uploads/elementor/css/post-2374.css',
        'elementor-icons' => '/plugins/elementor/assets/lib/eicons/css/elementor-icons.min.css',
        'elementor-pro' => '/uploads/elementor/css/custom-pro-frontend.min.css',
        'hoteller-elementor' => '/plugins/hoteller-elementor/assets/css/style.css',
        'acfwf-wc-cart-block-integration' => '/plugins/advanced-coupons-for-woocommerce-free/dist/cart.css',
        'ovabrw-frontend' => '/plugins/s4p_ova-brw20/assets/css/frontend.css',
        'ova-timepicker' => '/plugins/s4p_ova-brw20/assets/css/timepicker.css',
        'xts-style-theme_settings_default' => '/uploads/2026/11/xts-theme_settings_default-1.css',
        'control-alt-modul' => '/plugins/alt-modul/css/control.css',
    );
    foreach ($styles as $handle => $path) {
        wp_enqueue_style($handle, $u . $path, array(), '1');
    }
    foreach (array('ovabrw-google-font' => '//fonts.googleapis.com/css?family=Poppins', 'xts-google-fonts' => '//fonts.googleapis.com/css?family=Mulish', 'google-fonts-1' => '//fonts.googleapis.com/css?family=Lora') as $handle => $url) {
        wp_enqueue_style($handle, $url, array(), null);
    }
    $scripts = array(
        'woodmart-theme' => '/themes/giw/js/woodmart.js',
        'elementor-frontend' => '/plugins/elementor/assets/js/frontend.min.js',
        'elementor-pro-frontend' => '/plugins/elementor-pro/assets/js/frontend.min.js',
        'hoteller-elementor' => '/plugins/hoteller-elementor/assets/js/script.js',
        'ovabrw-frontend' => '/plugins/s4p_ova-brw20/assets/js/frontend.js',
        'ova-calendar' => '/plugins/s4p_ova-brw20/assets/js/calendar.js',
        'control-alt-modul' => '/plugins/alt-modul/js/control.js',
        'gtm4wp-woocommerce' => '/plugins/duracelltomi-google-tag-manager/build/gtm4wp-woocommerce.js',
        'gtm4wp-ecommerce-generic' => '/plugins/duracelltomi-google-tag-manager/build/gtm4wp-ecommerce-generic.js',
        'gtm4wp-contact-form-7-tracker' => '/plugins/duracelltomi-google-tag-manager/build/gtm4wp-contact-form-7-tracker.js',
    );
    foreach ($scripts as $handle => $path) {
        wp_enqueue_script($handle, $u . $path, array(), '1', true);
    }
    wp_add_inline_script('elementor-frontend', 'window.__elementor_inline = 1;', 'before');
    wp_add_inline_script('control-alt-modul', 'window.__control_inline = 1;', 'before');
}, 5);

// fișiere puse în coadă TÂRZIU (ca tema veche, care își pune scriptul de autentificare în subsol, după wp_footer) și un lanț de dependențe: wd-login-sidebar depinde
// de woodmart-theme, care depinde de jQuery
add_action('wp_head', function () {
    wp_enqueue_style('swiper', content_url('/plugins/elementor/assets/lib/swiper/v8/css/swiper.min.css'), array(), '1');
}, 4);
add_action('wp_footer', function () {
    wp_enqueue_script('wd-login-sidebar', content_url('/themes/giw/js/login-sidebar.js'), array('woodmart-theme'), '1', true);
    wp_enqueue_style('wd-bottom-toolbar', content_url('/themes/giw/css/bottom-toolbar.css'), array(), '1');
}, 3);
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('woodmart-theme');
    wp_register_script('woodmart-theme', content_url('/themes/giw/js/woodmart.js'), array('jquery'), '1', true);
    wp_enqueue_script('woodmart-theme');
}, 6);

// fișiere în „stil modul” (WordPress le pune în pagină cu <link rel="modulepreload">)
add_action('wp_head', function () {
    $u = content_url();
    echo '<link rel="modulepreload" href="' . esc_url($u . '/plugins/advanced-coupons-for-woocommerce-free/dist/common/NoticesPlugin.js') . '" />' . "\n";
    echo '<script type="module" src="' . esc_url($u . '/plugins/advanced-coupons-for-woocommerce-free/dist/cart.js') . '"></script>' . "\n";
    echo '<link rel="modulepreload" href="' . esc_url($u . '/plugins/alt-modul/js/control-modul.js') . '" />' . "\n";
}, 6);

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!in_array($t, array('c1100_nivel', 'c1100_pagini'), true)) {
        return;
    }
    header('Content-Type: application/json');
    if ('c1100_nivel' === $t) {
        if (isset($_GET['n']) && 'reset' === $_GET['n']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $o = (array) get_option('gi_integration_options', array());
            unset($o['light_pages']);
            update_option('gi_integration_options', $o);
        } elseif (isset($_GET['n']) && is_numeric($_GET['n'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $o = (array) get_option('gi_integration_options', array());
            $o['light_pages'] = max(0, min(2, (int) $_GET['n'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            update_option('gi_integration_options', $o);
        } // fără n: numai citește nivelul
        echo wp_json_encode(array('nivel' => (int) GI_Light::level()));
        exit;
    }
    $mk = function ($slug, $title, $content) {
        $p = get_page_by_path($slug);
        $id = $p ? $p->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content));
        update_post_meta($id, '_wp_page_template', 'gi-content.php');
        return get_permalink($id);
    };
    $punct = get_posts(array('post_type' => GI_Puncte::TYPE, 'post_status' => 'publish', 'numberposts' => 1, 's' => 'T1100'));
    $punct_id = $punct ? $punct[0]->ID : wp_insert_post(array('post_type' => GI_Puncte::TYPE, 'post_status' => 'publish', 'post_title' => 'T1100 Loc de probă', 'post_content' => 'Text de probă.'));
    echo wp_json_encode(array(
        'brw' => $mk('c1100-cu-formular', 'Cu formular', '<p>Text.</p>[ovabrw_search]'),
        'woo' => $mk('c1100-cu-woo', 'Cu WooCommerce', '<p>Text.</p>[woocommerce_cart]'),
        'woo2' => $mk('c1100-cu-produse', 'Cu produse', '<p>Text.</p>[products limit="2"]'),
        'punct' => get_permalink($punct_id),
    ));
    exit;
}, 1);
