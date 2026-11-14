<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-3-14.js:
 *  - pornește traducerea în română și local (limba locală e engleza);
 *  - /?gi_test=seo   → rezultatul filtrelor SEO pe date de probă (adresa dev24, schema, harta site-ului, numele site-ului);
 *  - /?gi_test=email → traducerea ÎNAINTE și DUPĂ începerea unui email (după, nu trebuie să traducă);
 *  - /?gi_test=email2 → în email nu se traduce, după trimiterea emailului se traduce din nou, în emailul „text simplu” nu se traduce;
 *  - /glamping/?gi_test=legacy  → simulează un titlu vechi din Yoast, cu greșeala „Sambata se Sus”;
 *  - /glamping/?gi_test=yoastok → simulează un titlu nou, corect, scris de mână în Yoast.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('gi_translate_active', '__return_true');

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('seo' === $t && class_exists('GI_SEO')) {
        add_filter('gi_seo_stale_hosts', function ($h) { $h[] = 'dev24.gradina-ielelor.ro'; return $h; });
        $graph = array(array('@id' => 'https://dev24.gradina-ielelor.ro/shop/#webpage', 'url' => 'https://dev24.gradina-ielelor.ro/shop/', 'nested' => array('x' => 'https://dev24.gradina-ielelor.ro/shop/#breadcrumb', 'ok' => 'https://exemplu.ro/pagina/')));
        header('Content-Type: application/json');
        echo wp_json_encode(array(
            'home' => home_url('/'),
            'canonical' => apply_filters('wpseo_canonical', 'https://dev24.gradina-ielelor.ro/shop/'),
            'canonical_ok' => apply_filters('wpseo_canonical', home_url('/glamping/')),
            'og_url' => apply_filters('wpseo_opengraph_url', 'https://dev24.gradina-ielelor.ro/blog/'),
            'graph' => apply_filters('wpseo_schema_graph', $graph),
            'org' => apply_filters('wpseo_schema_organization', array('@type' => 'Organization', 'name' => 'Grădina Ielelor')),
            'org_cu_telefon' => apply_filters('wpseo_schema_organization', array('@type' => 'Organization', 'telephone' => '+40000000000')),
            'exclude' => apply_filters('wpseo_exclude_from_sitemap_by_post_ids', array(7)),
            'entry_scos' => apply_filters('wpseo_sitemap_entry', array('loc' => 'x'), 'post', (object) array('ID' => (int) (isset($_GET['id']) ? $_GET['id'] : 0))), // phpcs:ignore
            'entry_pastrat' => apply_filters('wpseo_sitemap_entry', array('loc' => 'x'), 'post', (object) array('ID' => 999999)),
            'titlu_scurtat' => apply_filters('wpseo_title', 'Despre noi | ' . get_bloginfo('name')),
            'titlu_fara_nume' => apply_filters('wpseo_title', 'Locatie Glamping Romania - Camping, Brasov, Gradina Ielelor'),
            'entry_loc' => apply_filters('wpseo_sitemap_entry', array('loc' => 'https://dev24.gradina-ielelor.ro/x/'), 'post', (object) array('ID' => 999999)),
            'website' => apply_filters('wpseo_schema_website', array('@type' => 'WebSite', 'name' => get_bloginfo('name'))),
            'og_site' => apply_filters('wpseo_opengraph_site_name', get_bloginfo('name')),
            'host_lung' => apply_filters('wpseo_canonical', 'https://dev24.gradina-ielelor.ro.exemplu.com/x/'),
            'og_image' => apply_filters('wpseo_opengraph_image', 'https://dev24.gradina-ielelor.ro/wp-content/uploads/a.jpg'),
            'meniu' => (function () {
                $mk = function ($url) { $o = new stdClass(); $o->url = $url; $o->title = 'x'; return $o; };
                $items = array($mk(home_url('/cazare-bungalow/')), $mk(home_url('/tarife/')), $mk(home_url('/glamping/')), $mk('https://exemplu.ro/tarife/'), $mk(home_url('/tarife/?x=1')), $mk(home_url('/cazare-bungalow/#galerie')));
                return array_map(function ($i) { return $i->url; }, apply_filters('wp_nav_menu_objects', $items, new stdClass()));
            })(),
            'legale' => array_keys(function_exists('gi_legal_links') ? gi_legal_links() : array()),
            'pagina_cos' => function_exists('wc_get_page_id') ? array(wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('myaccount')) : array(),
        ));
        exit;
    }
    if ('email2' === $t) {
        do_action('woocommerce_email_header', 'x');
        $in_email = __('Order details', 'woocommerce');
        do_action('woocommerce_email_sent', true, 'x', null);
        $dupa_trimitere = __('Order details', 'woocommerce');
        do_action('woocommerce_email_before_order_table');
        $text_simplu = __('Order details', 'woocommerce');
        header('Content-Type: application/json');
        echo wp_json_encode(array('in_email' => $in_email, 'dupa_trimitere' => $dupa_trimitere, 'text_simplu' => $text_simplu));
        exit;
    }
    if ('brw' === $t) {
        add_filter('gettext', function ($tr, $text) { return 'No availability in the range' === $text ? 'Nu avem dispobibilitate în intervalul' : $tr; }, 5, 2);
        header('Content-Type: application/json');
        echo wp_json_encode(array(
            'obligatoriu' => __('This field is required.', 'ova-brw'),
            'termeni' => __('Please read and accept the terms and conditions to continue.', 'ova-brw'),
            'zi' => __("You can't book on this day!", 'ova-brw'),
            'tipar' => __('No availability in the range', 'ova-brw'),
            'neatins' => __('Some other string', 'ova-brw'),
        ));
        exit;
    }
    if ('email' === $t) {
        $inainte = __('Order details', 'woocommerce');
        do_action('woocommerce_email_header', 'Test');
        $dupa = __('Order details', 'woocommerce');
        header('Content-Type: application/json');
        echo wp_json_encode(array('inainte' => $inainte, 'dupa' => $dupa));
        exit;
    }
}, 1);

add_action('wp', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!in_array($t, array('legacy', 'yoastok'), true) || !is_singular('page')) {
        return;
    }
    $id = get_queried_object_id();
    $title = 'legacy' === $t ? 'Glamping in Romania - Gradina Ielelor - Glamping Sambata se Sus' : 'Glamping la Grădina Ielelor, sub munte';
    $desc = 'legacy' === $t ? 'Glamping in Romania - Brasov - Glamping Sambata se Sus - Facilitati All Inclusive' : 'Descriere nouă, scrisă de mână în Yoast.';
    update_post_meta($id, '_yoast_wpseo_title', $title);
    update_post_meta($id, '_yoast_wpseo_metadesc', $desc);
    register_shutdown_function(function () use ($id) {
        delete_post_meta($id, '_yoast_wpseo_title');
        delete_post_meta($id, '_yoast_wpseo_metadesc');
    });
    add_filter('pre_get_document_title', function () use ($title) { return $title; }, 15);
    add_filter('wpseo_metadesc', function () use ($desc) { return $desc; }, 5);
});
