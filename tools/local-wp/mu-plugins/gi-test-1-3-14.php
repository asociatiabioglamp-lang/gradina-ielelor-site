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

// /?gi_test=linkuri → rezultatul filtrului care scoate linkurile fără legătură (pluginul 1.3.15), pe texte de probă.
add_action('wp', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('linkuri' !== $t) {
        return;
    }
    $cases = array(
        'propozitie' => 'Nu lăsa <a href="https://salubritatetargoviste.ro/" target="_blank" rel="noopener">gunoaie</a> în urmă.',
        'cu_cale' => 'recomandat pentru <a href="https://jorjette.ro/tratament-ciclu-menstrual-neregulat/">ciclu menstrual neregulat</a>.',
        'www_apostrof_etichete' => "pe langa <a HREF='http://www.Cloud9Estate.ro' rel='follow'> facilitatile <strong>deosebite</strong> </a> de campare",
        'doua' => '<a href="https://elianadesign.ro/" target="_blank">casele</a> și <a href="//elianadesign.ro">Spatiul</a>',
        'pastrat_intern' => 'Vezi <a href="https://gradina-ielelor.ro/glamping/">Glamping</a>.',
        'pastrat_alt_site' => '<a href="https://jorjette.ro.exemplu.com/x">alt site</a> și <a href="https://exemplu.com/?u=https://jorjette.ro/">altul</a>',
        'pastrat_text' => 'Numele jorjette.ro scris în text, fără link.',
        'pastrat_data_href' => '<a data-href="https://jorjette.ro/a" href="https://gradina-ielelor.ro/">intern</a>',
        'semn_in_atribut' => 'x <a href="https://jorjette.ro/" title="a>b">text</a> y',
    );
    $out = array();
    foreach ($cases as $k => $v) {
        $out[$k] = GI_SEO::strip_unrelated_links($v);
    }
    $out['prin_the_content'] = trim(wp_strip_all_tags(apply_filters('the_content', $cases['propozitie']), true));
    $out['prin_the_content_html'] = apply_filters('the_content', $cases['propozitie']);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

// /?gi_test=typo → corectura „dispobibilitate” în ce afișează widgeturile Elementor (pluginul 1.3.16), pe texte de probă.
add_action('wp', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('typo' !== $t) {
        return;
    }
    $html = '<div class="ovabrw-label"> Nu avem dispobibilitate în intervalul </div>';
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode(array(
        'widget' => apply_filters('elementor/widget/render_content', $html, new stdClass()),
        'pagina' => apply_filters('elementor/frontend/the_content', '<p>' . $html . '</p>'),
        'corect_neschimbat' => apply_filters('elementor/widget/render_content', '<p>Nu avem disponibilitate.</p>', new stdClass()),
        'alt_text_neschimbat' => apply_filters('elementor/widget/render_content', '<p>Altceva.</p>', new stdClass()),
    ), JSON_UNESCAPED_UNICODE);
    exit;
});

// /?gi_test=nonce&n=<acțiune> → codul de siguranță al utilizatorului curent pentru o acțiune (pluginul 1.4.0; numai local, pentru testul de acces).
add_action('wp', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('nonce' !== $t || !is_user_logged_in()) {
        return;
    }
    $action = isset($_GET['n']) ? sanitize_key(wp_unslash($_GET['n'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    header('Content-Type: application/json');
    echo wp_json_encode(array('nonce' => wp_create_nonce($action)));
    exit;
});

// /?gi_test=galerie_reset → readuce pagina „Galerie foto” la starea de pe live (șablon implicit) și șterge marcajul „aplicată” (pluginul 1.4.0).
// /?gi_test=galerie_sablon=<șablon> → pune un șablon ales și șterge marcajul (pentru a verifica că o alegere făcută de mână nu se atinge).
add_action('wp', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('galerie_reset' !== $t) {
        return;
    }
    $page = get_page_by_path('galerie-foto');
    $template = isset($_GET['sablon']) ? sanitize_text_field(wp_unslash($_GET['sablon'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($page) {
        update_post_meta($page->ID, '_wp_page_template', $template ? $template : 'default');
        delete_post_meta($page->ID, '_gi_sablon_anterior');
        clean_post_cache($page->ID);
    }
    delete_option('gi_galerie_aplicata');
    header('Content-Type: application/json');
    echo wp_json_encode(array('ok' => (bool) $page, 'sablon' => $template ? $template : 'default'));
    exit;
});

