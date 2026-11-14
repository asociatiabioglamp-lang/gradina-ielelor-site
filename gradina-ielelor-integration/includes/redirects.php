<?php
/**
 * Redirecționări de la adresele vechi ale site-ului spre paginile noi.
 *
 * Temporare (302) până când proprietarul bifează „Redirecționări permanente (301)” în Setări → Grădina Ielelor
 * (la D+7, docs/lansare.md): un 301 rămâne ținut minte de browsere și ar strica revenirea la site-ul vechi.
 *
 * Reguli:
 * - se redirecționează numai adresele din gi_redirect_map(), verificate pe site-ul live la 31.10.2026;
 * - numai dacă pagina nouă există și este publicată; până atunci adresa veche funcționează ca acum;
 * - nu se ating previzualizările administratorilor, paginile WooCommerce (magazin, produs, coș,
 *   finalizare, cont), căutările din modulul de rezervări (BRW), cererile POST, AJAX, REST sau cron;
 * - parametrii din adresă (ex. utm_*, fbclid, gclid, ref) trec neschimbați, ca sursa vizitei să nu se piardă.
 *
 * Motivul fiecărei redirecționări și lista de verificare: docs/seo-migrare.md.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adresa veche (cheie) → adresa nouă (valoare), ca drumuri față de pagina principală.
 *
 * Valori: '/' = pagina principală nouă (hub-ul de rezervare), activă numai după ce e setată ca prima pagină;
 * '/bungalow/', '/glamping/', '/camping/', '/festivaluri-si-tabere/', '/evenimente-private/' = paginile dedicate din gi_offers()
 * (se caută după template, deci funcționează și dacă pagina are alt slug);
 * orice alt drum = o pagină WordPress publicată cu acel drum (ex. '/despre-noi/').
 */
function gi_redirect_map() {
    $map = array(
        // Cazare: paginile vechi din meniul „Cazare” → paginile dedicate noi.
        // /glamping/ și /camping/ își păstrează adresa (pagina existentă primește template-ul nou), deci nu apar aici.
        '/cazare-bungalow/' => '/bungalow/',
        '/camping-cort/' => '/camping/',
        '/camping-rulota/' => '/camping/',
        // Rulote are acum aceeași pagină cu Camping (decizia din 31.10.2026).
        '/rulote/' => '/camping/',

        // Evenimente (decizia din 31.10.2026: „Festivaluri & evenimente” devine Festivaluri & tabere + Evenimente private).
        // Pagina veche din „Totul despre ea” vorbește despre nunți, botezuri, majorate, seminarii și teambuilding → Evenimente private.
        '/gradina-ielelor-totul-despre-ea/evenimente/' => '/evenimente-private/',
        // Adresa scurtă /evenimente/ (pe live arată azi pagina veche; în planul inițial era pagina „Festivaluri & evenimente”).
        '/evenimente/' => '/festivaluri-si-tabere/',

        // „Despre Noi” din meniul vechi → pagina nouă Despre noi (numai după ce e publicată).
        '/gradina-ielelor-totul-despre-ea/' => '/despre-noi/',

        // Tarifele: cele două pagini identice → pagina principală (cardurile au prețurile, rezervarea e sus).
        '/tarife/' => '/',
        '/tarife-2026/' => '/',

        // Pagina principală veche (Acasă2023), cu informații din 2024–2025.
        '/acasa2023/' => '/',

        // Paginile legale vechi -> pagina nouă Regulament & politici.
        // De activat numai după ce juristul aprobă textul nou și pagina e publicată (docs/pagini-continut.md):
        // '/info-util/' => '/regulament-politici/',
        // '/info-util/politica-de-anulare/' => '/regulament-politici/',
        // '/info-util/termeni-si-conditii/' => '/regulament-politici/',
        // '/info-util/politica-de-confidentialitate/' => '/regulament-politici/',
        // '/info-util/politica-cookies/' => '/regulament-politici/',

        // De activat numai după confirmarea proprietarului (vezi docs/seo-migrare.md, secțiunea 3):
        // '/gradina-ielelor-totul-despre-ea/timp-liber/' => '/despre-noi/',
        // '/info-util/info-covid-19/' => '/info-util/',
    );

    return apply_filters('gi_redirect_map', $map);
}

/**
 * Harta cu drumurile aduse la aceeași formă (litere mici, cu „/” la început și la sfârșit).
 */
function gi_redirect_normalized_map() {
    static $normalized = null;
    if (null === $normalized) {
        $normalized = array();
        foreach (gi_redirect_map() as $old => $new) {
            $normalized[gi_redirect_normalize_path($old)] = (string) $new;
        }
    }
    return $normalized;
}

/**
 * „/Cazare-Bungalow” și „/cazare-bungalow/” devin aceeași cheie: „/cazare-bungalow/”.
 */
function gi_redirect_normalize_path($path) {
    $path = strtolower(trim(preg_replace('#/+#', '/', (string) $path), '/'));
    return '' === $path ? '/' : '/' . $path . '/';
}

/**
 * Drumul unei adrese față de pagina principală (contează dacă WordPress e instalat într-un subfolder).
 */
function gi_redirect_relative_path($url_path) {
    $path = rawurldecode((string) $url_path);
    $home_path = untrailingslashit((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
    if ('' !== $home_path && 0 === strpos($path, $home_path)) {
        $path = substr($path, strlen($home_path));
    }
    return gi_redirect_normalize_path($path);
}

/**
 * Drumul cererii curente, normalizat.
 */
function gi_redirect_request_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    return gi_redirect_relative_path((string) wp_parse_url((string) $uri, PHP_URL_PATH));
}

/**
 * Adresa completă a paginii noi sau șir gol dacă pagina nu există (încă).
 */
function gi_redirect_target_url($new_path) {
    $slug = trim(gi_redirect_normalize_path($new_path), '/');

    // Pagina principală nouă: numai după ce hub-ul de rezervare este publicat și setat ca prima pagină.
    if ('' === $slug) {
        $hub_id = gi_page_id_for_template(GI_TEMPLATE_SLUG);
        $is_front = 'page' === get_option('show_on_front') && (int) get_option('page_on_front') === $hub_id;
        return ($hub_id && $is_front) ? home_url('/') : '';
    }

    // Paginile dedicate ofertelor: după template, nu după slug (ex. '/festivaluri-si-tabere/' → pagina cu template-ul Festivaluri & tabere).
    $offer_key = gi_offer_key_for_slug($slug);
    if ('' !== $offer_key) {
        return gi_offer_has_page($offer_key) ? gi_offer_url($offer_key) : '';
    }

    $page = get_page_by_path($slug);
    if ($page && 'publish' === $page->post_status) {
        return get_permalink($page);
    }
    return '';
}

/**
 * Există în adresă cel puțin unul dintre parametrii dați?
 */
function gi_redirect_query_has($keys) {
    foreach ($keys as $key) {
        if (isset($_GET[$key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }
    }
    return false;
}

/**
 * Căutare sau preselectare din modulul de rezervări: parametrii formularului OVABRW (observați pe live)
 * și `tip`, folosit de plugin pentru oferta preselectată.
 */
function gi_redirect_is_booking_query() {
    foreach (array_keys($_GET) as $key) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (0 === strpos(strtolower((string) $key), 'ovabrw')) {
            return true;
        }
    }
    return gi_redirect_query_has(array('cat', 'pickup_date', 'dropoff_date', 'order', 'orderby', 'tip', 'product_cat'));
}

/**
 * Pagini și acțiuni WooCommerce: magazin, produs, categorii de produse, coș, finalizare, cont și endpoint-urile lor.
 */
function gi_redirect_is_woocommerce_request() {
    $checks = array('is_woocommerce', 'is_cart', 'is_checkout', 'is_account_page', 'is_wc_endpoint_url');
    foreach ($checks as $check) {
        if (function_exists($check) && call_user_func($check)) {
            return true;
        }
    }
    return gi_redirect_query_has(array('wc-ajax', 'wc-api', 'add-to-cart', 'remove_item', 'undo_item', 'removed_item', 'order-received', 'key'));
}

/**
 * Previzualizare: în editor, în Personalizare sau în Elementor, de către cineva care poate edita pagini.
 */
function gi_redirect_is_admin_preview() {
    if (is_preview() || is_customize_preview()) {
        return true;
    }
    if (!is_user_logged_in() || !current_user_can('edit_pages')) {
        return false;
    }
    return gi_redirect_query_has(array('preview', 'preview_id', 'preview_nonce', 'elementor-preview', 'customize_changeset_uuid', 'page_id', 'p'));
}

function gi_redirect_should_skip() {
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD']))) : 'GET';
    if (!in_array($method, array('GET', 'HEAD'), true)) {
        return true;
    }
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return true;
    }
    return gi_redirect_is_admin_preview() || gi_redirect_is_woocommerce_request() || gi_redirect_is_booking_query();
}

/**
 * Parametrii din adresa veche (ex. utm_source, fbclid, ref), transmiși mai departe.
 */
function gi_redirect_query_string() {
    $query = isset($_SERVER['QUERY_STRING']) ? wp_unslash($_SERVER['QUERY_STRING']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    return trim((string) $query, "&? \t\n\r\0\x0B");
}

/**
 * Redirecționare de la o adresă veche din hartă spre pagina nouă, dacă aceasta există.
 * Rulează înaintea redirecționărilor WordPress (prioritatea 1), ca vizitatorul să facă un singur salt.
 */
function gi_redirect_old_urls() {
    if (!function_exists('gi_offers') || !defined('GI_TEMPLATE_SLUG') || gi_redirect_should_skip()) {
        return;
    }

    $path = gi_redirect_request_path();
    $map = gi_redirect_normalized_map();
    if (!isset($map[$path])) {
        return;
    }

    $target = gi_redirect_target_url($map[$path]);
    if ('' === $target) {
        // Pagina nouă nu e publicată încă: adresa veche rămâne activă, fără 404.
        return;
    }

    // Fără bucle: dacă template-ul nou a fost pus chiar pe pagina veche, adresa veche e deja pagina nouă.
    if (gi_redirect_relative_path((string) wp_parse_url($target, PHP_URL_PATH)) === $path) {
        return;
    }

    $query = gi_redirect_query_string();
    if ('' !== $query) {
        $target .= (false === strpos($target, '?') ? '?' : '&') . $query;
    }

    $options = gi_options();
    if (empty($options['redirects_permanent'])) {
        nocache_headers();
        wp_safe_redirect($target, 302, 'Gradina Ielelor');
    } else {
        wp_safe_redirect($target, 301, 'Gradina Ielelor');
    }
    exit;
}

add_action('template_redirect', 'gi_redirect_old_urls', 1);

/**
 * Meniurile vechi (tema, Elementor) trimit încă la adresele vechi, care doar redirecționează.
 * Linkurile din meniu merg direct la pagina nouă: un pas mai puțin pentru vizitator și pentru Google.
 * Numai pentru adresele din gi_redirect_map() a căror pagină nouă e publicată; restul rămân neatinse.
 */
function gi_redirect_menu_items($items) {
    if (!is_array($items) || is_admin()) {
        return $items;
    }
    $map = gi_redirect_normalized_map();
    $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    foreach ($items as $item) {
        if (!is_object($item) || empty($item->url) || !is_string($item->url)) {
            continue;
        }
        $parts = wp_parse_url($item->url);
        if (!is_array($parts) || !empty($parts['query'])) {
            continue;
        }
        $host = isset($parts['host']) ? strtolower($parts['host']) : $home_host;
        if ($host !== $home_host && $host !== 'www.' . $home_host) {
            continue;
        }
        $path = gi_redirect_relative_path(isset($parts['path']) ? $parts['path'] : '/');
        if (!isset($map[$path])) {
            continue;
        }
        $target = gi_redirect_target_url($map[$path]);
        if ('' === $target || gi_redirect_relative_path((string) wp_parse_url($target, PHP_URL_PATH)) === $path) {
            continue;
        }
        $item->url = $target . (!empty($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }
    return $items;
}

add_filter('wp_nav_menu_objects', 'gi_redirect_menu_items', 20);
