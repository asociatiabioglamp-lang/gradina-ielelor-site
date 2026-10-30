<?php
/**
 * Registrul paginilor Grădina Ielelor: pagina principală și paginile dedicate fiecărei oferte.
 * O pagină dedicată devine activă când o pagină WordPress publicată folosește template-ul ei;
 * până atunci, linkurile trimit la secțiunea echivalentă de pe pagina principală.
 */

if (!defined('ABSPATH')) {
    exit;
}

function gi_page_templates() {
    return array(
        GI_TEMPLATE_SLUG => array('key' => 'one-page', 'label' => 'Grădina Ielelor — One Page + BRW', 'file' => 'one-page.php'),
        'gi-glamping.php' => array('key' => 'glamping', 'label' => 'Grădina Ielelor — Glamping', 'file' => 'glamping.php'),
    );
}

/**
 * Cele patru oferte principale, în ordinea din meniu.
 * `anchor` este secțiunea de pe pagina principală folosită cât timp pagina dedicată nu există.
 */
function gi_offers() {
    return array(
        'glamping' => array('label' => 'Glamping', 'anchor' => 'glamping', 'template' => 'gi-glamping.php'),
        'camping' => array('label' => 'Camping & rulote', 'anchor' => 'camping', 'template' => ''),
        'bungalow' => array('label' => 'Bungalow', 'anchor' => 'bungalow', 'template' => ''),
        'evenimente' => array('label' => 'Festivaluri & evenimente', 'anchor' => 'evenimente', 'template' => ''),
    );
}

function gi_current_page_key() {
    if (!is_singular('page')) {
        return '';
    }
    $templates = gi_page_templates();
    $slug = get_page_template_slug(get_queried_object_id());
    return isset($templates[$slug]) ? $templates[$slug]['key'] : '';
}

function gi_is_site_page() {
    return '' !== gi_current_page_key();
}

function gi_is_dedicated_page() {
    $key = gi_current_page_key();
    return '' !== $key && 'one-page' !== $key;
}

/**
 * Prima pagină publicată care folosește template-ul dat, sau 0.
 */
function gi_page_id_for_template($template) {
    static $cache = array();
    if (!$template) {
        return 0;
    }
    if (!isset($cache[$template])) {
        $ids = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'orderby' => 'menu_order ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_key' => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        ));
        $cache[$template] = $ids ? (int) $ids[0] : 0;
    }
    return $cache[$template];
}

function gi_offer_has_page($key) {
    $offers = gi_offers();
    return isset($offers[$key]) && gi_page_id_for_template($offers[$key]['template']) > 0;
}

/**
 * URL-ul unei oferte: pagina dedicată dacă există, altfel secțiunea de pe pagina principală.
 */
function gi_offer_url($key) {
    $offers = gi_offers();
    if (!isset($offers[$key])) {
        return home_url('/');
    }
    $page_id = gi_page_id_for_template($offers[$key]['template']);
    if ($page_id) {
        return get_permalink($page_id);
    }
    return gi_home_section_url($offers[$key]['anchor']);
}

function gi_home_section_url($anchor) {
    return home_url('/') . '#' . rawurlencode($anchor);
}

/**
 * Pagina produsului WooCommerce/BRW pentru o ofertă, cu fallback la URL-ul public observat.
 */
function gi_product_url($slug) {
    $slug = sanitize_title($slug);
    $product = get_page_by_path($slug, OBJECT, 'product');
    if ($product && 'publish' === $product->post_status) {
        return get_permalink($product);
    }
    return home_url('/produs/' . $slug . '/');
}

/**
 * Valori SEO implicite pentru fiecare template; câmpurile din pagină și Yoast/Rank Math au prioritate.
 */
function gi_page_seo_defaults($key) {
    $defaults = array(
        'one-page' => array(
            'title' => 'Camping & Glamping Brașov | Grădina Ielelor Sâmbăta de Sus',
            'description' => 'Camping, glamping și bungalow în Sâmbăta de Sus, Brașov. Rezervă cazare la poalele Munților Făgăraș și descoperă calendarul evenimentelor 2027.',
            'image' => 'home-hero-lake-centered-1600.webp',
        ),
        'glamping' => array(
            'title' => 'Glamping Sâmbăta de Sus, Brașov | Grădina Ielelor',
            'description' => 'Descoperă corturile de glamping din Grădina Ielelor, la poalele Munților Făgăraș. Vezi facilitățile, fotografiile și disponibilitatea și rezervă online.',
            'image' => 'super-glamping-1600.webp',
        ),
    );
    return isset($defaults[$key]) ? $defaults[$key] : $defaults['one-page'];
}
