<?php
if (!defined('ABSPATH')) {
    exit;
}

function gi_default_options() {
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
    return array(
        'result_url' => $shop_url ? $shop_url : home_url('/shop/'),
        'category_camping_cort' => 'camping',
        'category_camping_rulota' => 'rulote',
        'category_glamping' => 'glamping',
        'category_bungalow' => 'bungalow',
        // 0 = redirecționări temporare (302) în săptămâna lansării, ca revenirea R1 să fie curată; 1 = permanente (301), de la D+7.
        'redirects_permanent' => 0,
        // Plata la locație: prețul de pe site + 10% (decizia proprietarului, 31.10.2026). Se pornește din setări, după ce se bifează metoda.
        'onsite_surcharge_enabled' => 0,
        'onsite_surcharge_percent' => 10,
        'onsite_surcharge_gateways' => array('cod'),
        // Sezonul afișat pe paginile ofertelor (text liber; gol = nu se afișează).
        'season_bungalow' => '1 mai – 1 octombrie',
        'season_glamping' => '1 mai – 1 octombrie',
        'season_camping' => '1 mai – 1 octombrie',
    );
}

function gi_options() {
    return wp_parse_args(get_option('gi_integration_options', array()), gi_default_options());
}

/**
 * Sezonul unei oferte (ex. „15 mai – 1 octombrie”), din Setări → Grădina Ielelor; '' dacă nu e completat.
 */
function gi_season($key) {
    $options = gi_options();
    return isset($options['season_' . $key]) ? trim((string) $options['season_' . $key]) : '';
}

function gi_asset_url($path) {
    return GI_URL . 'assets/' . ltrim($path, '/');
}

function gi_image_url($filename) {
    return gi_asset_url('images/' . rawurlencode($filename));
}

function gi_icon($name, $class = '') {
    $name = sanitize_key($name);
    $class = trim('gi-icon ' . $class);
    return '<svg class="' . esc_attr($class) . '" aria-hidden="true" focusable="false"><use href="#gi-icon-' . esc_attr($name) . '"></use></svg>';
}

function gi_brand($white = false, $extra_class = '') {
    $image = $white ? 'logo-gradina-ielelor-white.png' : 'logo-gradina-ielelor.png';
    $class = trim('official-brand ' . $extra_class);
    ?>
    <a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Grădina Ielelor — pagina principală">
        <img src="<?php echo esc_url(gi_asset_url($image)); ?>" alt="Grădina Ielelor" width="450" height="211">
    </a>
    <?php
}

function gi_clean_page_url($path) {
    $page = get_page_by_path(trim($path, '/'));
    return $page ? get_permalink($page) : home_url('/' . trim($path, '/') . '/');
}

/**
 * Linkurile legale din subsol: paginile site-ului care există (aceleași adrese ca pe site-ul actual)
 * și autoritățile pentru protecția consumatorului (aceleași linkuri ca pe site-ul actual; ODR: vezi docs/decizii.md).
 */
function gi_legal_links() {
    $terms_id = function_exists('wc_terms_and_conditions_page_id') ? (int) wc_terms_and_conditions_page_id() : 0;
    $terms_id = ($terms_id && 'publish' === get_post_status($terms_id)) ? $terms_id : 0;
    $pages = array(
        'Termeni și condiții' => $terms_id ? get_permalink($terms_id) : 'info-util/termeni-si-conditii',
        'Confidențialitate' => get_privacy_policy_url() ? get_privacy_policy_url() : 'info-util/politica-de-confidentialitate',
        'Cookies' => 'info-util/politica-cookies',
        'Politica de anulare' => 'info-util/politica-de-anulare',
    );
    $links = array();
    foreach ($pages as $label => $target) {
        if (0 !== strpos($target, 'http')) {
            $page = get_page_by_path($target);
            $target = ($page && 'publish' === $page->post_status) ? get_permalink($page) : '';
        }
        if ($target) {
            $links[$label] = $target;
        }
    }
    $links['ANPC'] = 'https://anpc.ro/';
    $links['ANPC — SAL'] = 'https://anpc.ro/categorie/1271/sal';
    $links['Litigii online (SOL)'] = 'https://ec.europa.eu/consumers/odr/main/index.cfm?event=main.home2.show&lng=RO';
    return apply_filters('gi_legal_links', $links);
}

function gi_blog_url() {
    $page_id = (int) get_option('page_for_posts');
    return $page_id ? get_permalink($page_id) : gi_home_section_url('blog');
}

function gi_booking_url($type = '') {
    $base = is_singular() ? get_permalink(get_queried_object_id()) : home_url('/');
    $allowed = array('camping-cort', 'camping-rulota', 'glamping', 'bungalow');
    if (in_array($type, $allowed, true)) {
        $base = add_query_arg('tip', $type, $base);
    }
    return $base . '#rezervare';
}

function gi_requested_category() {
    if (!isset($_GET['tip'])) {
        return '';
    }

    return gi_category_for_type(sanitize_key(wp_unslash($_GET['tip'])));
}

/**
 * Slug-ul categoriei BRW configurate pentru un tip de cazare, sau șir gol pentru tipuri necunoscute.
 */
function gi_category_for_type($type) {
    $map = array(
        'camping-cort' => 'category_camping_cort',
        'camping-rulota' => 'category_camping_rulota',
        'glamping' => 'category_glamping',
        'bungalow' => 'category_bungalow',
    );

    if (!isset($map[$type])) {
        return '';
    }

    $options = gi_options();
    return sanitize_title($options[$map[$type]]);
}

function gi_render_gallery($images, $label) {
    ?>
    <div class="home-photo-gallery" role="group" aria-label="<?php echo esc_attr($label); ?>">
        <?php foreach ($images as $index => $image) : ?>
            <button type="button" class="gi-gallery-trigger" data-gallery-index="<?php echo esc_attr($index); ?>" aria-label="Mărește fotografia: <?php echo esc_attr($image['caption']); ?>">
                <img src="<?php echo esc_url(gi_image_url($image['src'])); ?>" alt="<?php echo esc_attr($image['alt']); ?>" width="960" height="720" loading="lazy" decoding="async" data-caption="<?php echo esc_attr($image['caption']); ?>">
                <span><?php echo esc_html($image['caption']); ?><?php echo gi_icon('arrow-up-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </button>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * BRW 2.0.0 nu are atributul result_url în shortcode-ul de căutare.
 * Ajustăm action-ul și câmpul folosit de scriptul BRW; query-ul și motorul rămân neschimbate.
 */
function gi_brw_search_compatibility($html, $options) {
    if (empty($options['result_url'])) {
        return $html;
    }

    $result_url = esc_url($options['result_url']);
    $html = preg_replace_callback(
        '/<form\b[^>]*\bclass=(["\'])[^"\']*\bgi-ovabrw-search\b[^"\']*\1[^>]*>/i',
        function ($opening_form) use ($result_url) {
            return preg_replace(
                '/\saction=(["\'])[^"\']*\1/i',
                ' action="' . $result_url . '"',
                $opening_form[0],
                1
            );
        },
        $html,
        1
    );

    return preg_replace_callback(
        '/<input\b[^>]*\bname=(["\'])ovabrw_search_url\1[^>]*>/i',
        function ($input) use ($result_url) {
            return preg_replace(
                '/\svalue=(["\'])[^"\']*\1/i',
                ' value="' . $result_url . '"',
                $input[0],
                1
            );
        },
        $html,
        1
    );
}

/**
 * Textele formularului BRW, pe limba vizitatorului: butonul „Caută disponibilitatea”, „Alege cazarea”
 * în loc de „Selecteză Tip Cazare” și „Alege data” în loc de formatul tehnic „d-m-Y”. Se schimbă doar textul afișat;
 * numele câmpurilor și valorile trimise la căutare rămân ale BRW.
 */
function gi_brw_search_texts($html) {
    $html = preg_replace('/(<button\b[^>]*\bovabrw_btn_submit\b[^>]*>)\s*Caută\s*(<\/button>)/u', '$1Caută disponibilitatea$2', $html, 1);
    $html = preg_replace('/(<select\b[^>]*\bname=(["\'])cat\2[^>]*>\s*<option\b[^>]*\bvalue=(["\'])\3[^>]*>)[^<]*(<\/option>)/u', '$1Alege cazarea$4', $html, 1);
    return preg_replace('/(<input\b[^>]*\bovabrw-datepicker-(?:start|end)\b[^>]*\bplaceholder=)(["\'])d-m-Y\2/u', '$1$2Alege data$2', $html);
}

/**
 * Singurul punct de randare a rezervării: shortcode-ul real OVABRW.
 * Nu există handler de submit, calcul de preț sau disponibilitate în acest plugin.
 */
function gi_render_booking($default_type = '', $show_trust = true) {
    $preselect = gi_requested_category();
    if (!$preselect && $default_type) {
        $preselect = gi_category_for_type($default_type);
    }
    ?>
    <div class="gi-real-booking" data-gi-booking-engine="ovabrw" data-gi-react-demo="disabled" data-gi-categories="<?php echo esc_attr(wp_json_encode(gi_booking_categories())); ?>"<?php echo $preselect ? ' data-gi-preselect-category="' . esc_attr($preselect) . '"' : ''; ?>>
        <?php if (shortcode_exists('ovabrw_search') && class_exists('WooCommerce')) : ?>
            <?php
            $options = gi_options();
            $attributes = array(
                'class' => 'gi-ovabrw-search',
                'show_cat' => 'yes',
                'show_pickup_date' => 'yes',
                'show_dropoff_date' => 'yes',
                'column' => '3',
            );
            if (!empty($options['result_url'])) {
                $attributes['result_url'] = $options['result_url'];
            }

            $shortcode = '[ovabrw_search';
            foreach ($attributes as $key => $value) {
                $shortcode .= ' ' . sanitize_key($key) . '="' . esc_attr($value) . '"';
            }
            $shortcode .= ']';

            $html = gi_brw_search_texts(do_shortcode($shortcode));
            echo gi_brw_search_compatibility($html, $options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML BRW, cu action escapat.
            ?>
        <?php else : ?>
            <div class="gi-booking-unavailable" role="status">
                <strong>Rezervările online nu pot fi afișate momentan.</strong>
                <p>Ne poți contacta la <a href="tel:+40721333164">+40 721 333 164</a> sau <a href="mailto:rezervari@gradina-ielelor.ro">rezervari@gradina-ielelor.ro</a>.</p>
                <?php if (current_user_can('manage_options')) : ?>
                    <p><strong>Diagnostic administrator:</strong> activează WooCommerce și OVABRW; shortcode-ul necesar este <code>[ovabrw_search]</code>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!$show_trust) {
        return;
    } ?>
    <div class="gi-booking-trust">
        <img src="<?php echo esc_url(gi_asset_url('logo-gradina-ielelor.png')); ?>" alt="" width="120" height="56" loading="lazy">
        <p>Disponibilitatea, tarifele finale, opțiunile, comenzile și plățile sunt furnizate exclusiv de WooCommerce + BRW.</p>
    </div>
    <?php
}

add_shortcode('gradina_ielelor_booking', 'gi_booking_shortcode');
function gi_booking_shortcode() {
    if (GI_REACT_DEMO_ENABLED) {
        return '';
    }
    ob_start();
    gi_render_booking();
    return ob_get_clean();
}

/**
 * Încarcă o parte comună din templates/parts/, cu argumente disponibile în $args.
 */
function gi_part($name, $args = array()) {
    load_template(GI_PATH . 'templates/parts/' . sanitize_file_name($name) . '.php', false, $args);
}

/**
 * URL-ul modulului de rezervări de pe pagina principală, cu oferta preselectată prin fragment
 * (ex. /#rezervare-glamping). Fragmentul nu schimbă URL-ul cerut, deci pagina principală
 * preîncărcată în fundal poate fi refolosită de toate butoanele de rezervare.
 */
function gi_home_booking_url($type = '') {
    $page_id = gi_page_id_for_template(GI_TEMPLATE_SLUG);
    $base = $page_id ? get_permalink($page_id) : home_url('/');
    $allowed = array('camping-cort', 'camping-rulota', 'glamping', 'bungalow');
    return $base . '#rezervare' . (in_array($type, $allowed, true) ? '-' . $type : '');
}

/**
 * Corespondența tip → categorie BRW, pentru preselectarea din fragmentul URL-ului.
 */
function gi_booking_categories() {
    $categories = array();
    foreach (array('camping-cort', 'camping-rulota', 'glamping', 'bungalow') as $type) {
        $categories[$type] = gi_category_for_type($type);
    }
    return $categories;
}
