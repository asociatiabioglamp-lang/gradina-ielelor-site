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
    $templates = array(
        GI_TEMPLATE_SLUG => array('key' => 'one-page', 'label' => 'Grădina Ielelor — Pagina principală (rezervare)', 'file' => 'one-page.php'),
    );
    foreach (gi_offers() as $key => $offer) {
        $templates[$offer['template']] = array('key' => $key, 'label' => 'Grădina Ielelor — ' . $offer['label'], 'file' => 'offer.php');
    }
    // Pagini scrise de proprietar în editorul WordPress (Despre noi, Regulament & politici, Contact).
    $templates['gi-content.php'] = array('key' => 'content', 'label' => 'Grădina Ielelor — Pagină simplă', 'file' => 'content-page.php');
    return $templates;
}

/**
 * Câmpul „Rezumat” (excerpt) în editorul paginilor: pe pagina simplă devine textul de sub titlu și descrierea SEO.
 */
function gi_page_excerpt_support() {
    add_post_type_support('page', 'excerpt');
}
add_action('init', 'gi_page_excerpt_support');

/**
 * Cele cinci categorii, în ordinea din meniu (decizia proprietarului din 31.10.2026).
 * `anchor` este secțiunea de pe pagina principală folosită cât timp pagina dedicată nu există
 * (Festivaluri & tabere și Evenimente private o împart pe cea de evenimente).
 * `slug` este adresa recomandată a paginii (docs/lansare.md); pagina se găsește oricum după template.
 */
function gi_offers() {
    return array(
        'bungalow' => array('label' => 'Bungalow', 'anchor' => 'bungalow', 'template' => 'gi-bungalow.php', 'slug' => 'bungalow'),
        'glamping' => array('label' => 'Glamping', 'anchor' => 'glamping', 'template' => 'gi-glamping.php', 'slug' => 'glamping'),
        'camping' => array('label' => 'Camping & rulote', 'anchor' => 'camping', 'template' => 'gi-camping.php', 'slug' => 'camping'),
        'festivaluri' => array('label' => 'Festivaluri & tabere', 'anchor' => 'evenimente', 'template' => 'gi-festivaluri.php', 'slug' => 'festivaluri-si-tabere'),
        'private' => array('label' => 'Evenimente private', 'anchor' => 'evenimente', 'template' => 'gi-evenimente-private.php', 'slug' => 'evenimente-private'),
    );
}

/**
 * Cheia ofertei a cărei pagină are adresa dată (ex. 'festivaluri-si-tabere' → 'festivaluri'), sau ''.
 */
function gi_offer_key_for_slug($slug) {
    foreach (gi_offers() as $key => $offer) {
        if ($slug === $key || $slug === $offer['slug']) {
            return $key;
        }
    }
    return '';
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
    $page = function_exists('gi_offer_page') ? gi_offer_page($key) : null;
    if ($page && !empty($page['seo'])) {
        return $page['seo'];
    }
    $defaults = array(
        'title' => 'Camping & Glamping Brașov | Grădina Ielelor Sâmbăta de Sus',
        'description' => 'Camping, glamping și bungalow în Sâmbăta de Sus, Brașov. Rezervă cazare la poalele Munților Făgăraș și descoperă calendarul evenimentelor 2027.',
        'image' => 'home-hero-lake-centered-1600.webp',
    );
    // Pagina simplă: titlul paginii + numele site-ului; descrierea vine din Rezumat (GI_SEO îl citește înaintea acestor valori).
    if ('content' === $key && is_singular('page')) {
        $title = get_the_title(get_queried_object_id());
        if ('' !== $title) {
            $defaults['title'] = $title . ' | ' . get_bloginfo('name');
        }
    }
    return $defaults;
}

/**
 * Butonul „Rezervă” din header: modulul de rezervări, cu categoria paginii curente preselectată.
 */
function gi_current_booking_url() {
    if (gi_is_one_page()) {
        return '#rezervare';
    }
    $page = gi_offer_page(gi_current_page_key());
    return gi_home_booking_url($page ? $page['booking_type'] : '');
}

/**
 * Acțiunea principală a paginii curente (ex. rezervare sau cerere de ofertă pentru festivaluri, tabere și evenimente private).
 */
function gi_current_cta_url() {
    $page = gi_is_one_page() ? null : gi_offer_page(gi_current_page_key());
    if ($page && !empty($page['cta_url'])) {
        return $page['cta_url'];
    }
    return gi_current_booking_url();
}
