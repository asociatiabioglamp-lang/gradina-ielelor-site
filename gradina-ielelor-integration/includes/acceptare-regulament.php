<?php
/**
 * Regulamentul la finalizarea rezervării: bifă obligatorie, cu derulare, și regulament de consultat numai pe site.
 *
 * Decizia proprietarului (31.10.2026, docs/decizii.md): înainte de finalizarea rezervării, clientul citește și bifează
 * regulamentul (ROI). Textul se traduce automat în limba aleasă pe site (GTranslate), pentru că e text în pagină, nu fișier.
 * Rezervarea nu se poate trimite fără bifă. Regulamentul nu se oferă ca fișier și nu se poate copia sau tipări din pagină
 * (protecția oprește doar gesturile obișnuite; o captură de ecran, sursa paginii sau DevTools nu pot fi oprite).
 *
 * Dovada din comandă (ce spune adevărul și nimic mai mult): data acceptării (UTC), versiunea, limba aleasă pe site,
 * codul textului exact (inclusiv eticheta bifei), „derulat până jos” (declarat de browser) și secundele de la prima afișare,
 * măsurate pe server. Textul acceptat se păstrează în arhivă (opțiunea `gi_roi_arhiva`). WooCommerce salvează deja
 * în comandă și adresa IP a clientului; politica de confidențialitate trebuie să o menționeze.
 *
 * Funcționează cu pagina de finalizare clasică ([woocommerce_checkout]), cea folosită și de OVABRW, și cu plata unei comenzi
 * existente (pagina „order-pay”). Cu pagina de finalizare cu blocuri WooCommerce nu se aplică (Setări avertizează).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pagina cu regulamentul (publicată, de nivel superior), sau null. Slug-ul se poate schimba cu filtrul `gi_roi_page_slug`.
 */
function gi_roi_page() {
    static $page = false;
    if (false === $page) {
        $found = get_page_by_path(apply_filters('gi_roi_page_slug', 'regulament-politici'));
        $page = ($found && 'publish' === $found->post_status) ? $found : null;
    }
    return $page;
}

function gi_roi_required() {
    $options = gi_options();
    return !empty($options['roi_required']);
}

function gi_roi_version() {
    $options = gi_options();
    $version = trim((string) $options['roi_version']);
    return '' !== $version ? mb_substr($version, 0, 60) : '1';
}

/**
 * Pe durata randării conținutului paginii pentru casetă, filtrul de protecție a paginii nu se aplică.
 */
function gi_roi_rendering($set = null) {
    static $on = false;
    if (null !== $set) {
        $on = (bool) $set;
    }
    return $on;
}

/**
 * Textul regulamentului, ca HTML pentru casetă: conținutul paginii, cu toate secțiunile deschise,
 * fără linkuri spre fișiere PDF și cu linkurile externe în fereastră nouă (ca să nu se piardă formularul de plată).
 */
function gi_roi_html() {
    static $html = null;
    if (null !== $html) {
        return $html;
    }
    $page = gi_roi_page();
    if (!$page) {
        $html = '';
        return $html;
    }
    global $post;
    $previous = isset($post) ? $post : null;
    gi_roi_rendering(true);
    $post = $page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- se restabilește mai jos.
    setup_postdata($page);
    $html = (string) apply_filters('the_content', $page->post_content);
    gi_roi_rendering(false);
    if ($previous) {
        $post = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($previous);
    } else {
        wp_reset_postdata();
    }

    $html = preg_replace('/<details\b(?![^>]*\bopen\b)/i', '<details open', $html);
    $html = preg_replace('/<a\b[^>]*href=(["\'])[^"\']*\.pdf(?:\?[^"\']*)?\1[^>]*>(.*?)<\/a>/is', '$2', $html);
    $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($match) {
        if (!preg_match('/\bhref=(["\'])https?:/i', $match[1]) || preg_match('/\btarget=/i', $match[1])) {
            return $match[0];
        }
        return '<a' . $match[1] . ' target="_blank" rel="noopener noreferrer">';
    }, $html);
    return $html;
}

/**
 * Textul simplu al regulamentului (pentru cod și arhivă): fără marcaj, câte un rând pentru fiecare paragraf, element de listă sau celulă.
 */
function gi_roi_text() {
    $html = preg_replace('#</(?:td|th|tr|p|li|summary|h[1-6]|div|details|ul|ol|table|blockquote)>|<br\s*/?>#i', "\n", gi_roi_html());
    $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
    $text = preg_replace('/ *\n[ \n]*/', "\n", $text);
    return trim($text);
}

function gi_roi_label() {
    return (string) apply_filters('gi_roi_checkbox_label', 'Am citit și accept Regulamentul de ordine interioară și condițiile de anulare.');
}

/**
 * Codul textului acceptat: eticheta bifei și textul regulamentului, laolaltă.
 */
function gi_roi_hash() {
    return hash('sha256', gi_roi_label() . "\n\n" . gi_roi_text());
}

/**
 * Se cere acceptarea numai când e pornită în setări și există textul de arătat (altfel nu s-ar putea dovedi nimic).
 */
function gi_roi_active() {
    return gi_roi_required() && '' !== gi_roi_html();
}

/**
 * Limba aleasă pe site (cookie-ul GTranslate `googtrans`, de forma /ro/en); „ro” când nu s-a ales alta.
 */
function gi_roi_language() {
    $cookie = isset($_COOKIE['googtrans']) ? sanitize_text_field(wp_unslash($_COOKIE['googtrans'])) : '';
    if ($cookie && preg_match('~^/[a-z-]{2,8}/([a-z]{2,3}(?:-[A-Za-z]{2,4})?)/?$~', $cookie, $match)) {
        return strtolower($match[1]);
    }
    return 'ro';
}

/**
 * Adresa regulamentului, pentru linkul de sub bifă (filă nouă, în aceeași limbă aleasă).
 */
function gi_roi_url() {
    $page = gi_roi_page();
    return $page ? get_permalink($page) : gi_clean_page_url('regulament-politici');
}

/**
 * Pagina de finalizare folosește blocurile noi WooCommerce? Atunci caseta nu se poate afișa.
 */
function gi_roi_checkout_uses_blocks() {
    static $blocks = null;
    if (null === $blocks) {
        $id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('checkout') : 0;
        $blocks = $id > 0 && function_exists('has_block') && has_block('woocommerce/checkout', get_post($id));
    }
    return $blocks;
}

/**
 * Momentul primei afișări a casetei în sesiunea clientului (pe server), ca dovadă că textul a fost pus în față.
 */
function gi_roi_mark_displayed() {
    if (function_exists('WC') && WC()->session && !WC()->session->get('gi_roi_afisat')) {
        WC()->session->set('gi_roi_afisat', time());
    }
}

function gi_roi_displayed_at() {
    return (function_exists('WC') && WC()->session) ? (int) WC()->session->get('gi_roi_afisat') : 0;
}

/**
 * Starea trimisă de client la reîmprospătarea plății. WooCommerce nu o pune în $_POST la `update_order_review`,
 * ci doar ca șir în `post_data`; o reținem din cârligul `woocommerce_checkout_update_order_review`.
 */
function gi_roi_posted_state($set = null) {
    static $state = null;
    if (is_array($set)) {
        $state = $set;
    }
    if (null === $state) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $state = array(
            'checked' => isset($_POST['gi_roi']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi'])),
            'seen' => isset($_POST['gi_roi_seen']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi_seen'])),
        );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }
    return $state;
}

function gi_roi_capture_posted($post_data) {
    parse_str((string) $post_data, $data);
    gi_roi_posted_state(array(
        'checked' => isset($data['gi_roi']) && is_string($data['gi_roi']) && '1' === $data['gi_roi'],
        'seen' => isset($data['gi_roi_seen']) && is_string($data['gi_roi_seen']) && '1' === $data['gi_roi_seen'],
    ));
}
add_action('woocommerce_checkout_update_order_review', 'gi_roi_capture_posted');

/**
 * Caseta și bifa, chiar deasupra butonului de plată (finalizare și plata unei comenzi existente).
 * Starea (derulat / bifat) se păstrează când pagina de plată se reîmprospătează.
 */
function gi_roi_render_box() {
    if (!gi_roi_active()) {
        return;
    }
    gi_roi_mark_displayed();
    $state = gi_roi_posted_state();
    $html = gi_roi_html();
    $seen = !empty($state['seen']);
    $checked = !empty($state['checked']);
    ?>
    <div class="gi-roi<?php echo $seen ? ' gi-roi-citit' : ''; ?>" data-gi-roi>
        <h3 class="gi-roi-title">Regulamentul Grădinii</h3>
        <p class="gi-roi-hint" id="gi-roi-hint" data-gi-roi-hint role="status" aria-live="polite"><?php echo $seen ? 'Ai ajuns la sfârșitul regulamentului. Poți bifa.' : 'Citește regulamentul până jos, ca să poți bifa (cu tastatura: apasă pe casetă, apoi tasta End).'; ?></p>
        <div class="gi-roi-box gi-protejat" data-gi-protejat data-gi-roi-box tabindex="0" role="region" aria-label="Regulamentul Grădinii">
            <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- conținutul paginii, deja filtrat de WordPress. ?>
        </div>
        <p class="gi-roi-note">Se traduce automat în limba aleasă pe site. Versiunea în limba română este cea valabilă.</p>
        <p class="form-row validate-required gi-roi-check">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="gi_roi" id="gi_roi" value="1" aria-describedby="gi-roi-hint"<?php echo $checked ? ' checked' : ''; ?>>
                <span><?php echo esc_html(gi_roi_label()); ?> <abbr class="required" title="obligatoriu">*</abbr></span>
            </label>
            <input type="hidden" name="gi_roi_seen" value="<?php echo $seen ? '1' : '0'; ?>">
            <noscript><input type="hidden" name="gi_roi_seen" value="1"></noscript>
        </p>
        <p class="gi-roi-link"><a href="<?php echo esc_url(gi_roi_url()); ?>" target="_blank" rel="noopener">Deschide regulamentul într-o pagină nouă</a></p>
    </div>
    <?php
}
add_action('woocommerce_review_order_before_submit', 'gi_roi_render_box', 5);
add_action('woocommerce_pay_order_before_submit', 'gi_roi_render_box', 5);

/**
 * Mesajul de eroare când acceptarea lipsește, sau '' când totul e în regulă.
 * „Derulat” e declarația browserului; pe server se cere doar ca caseta să fi fost afișată în sesiune.
 */
function gi_roi_acceptance_error() {
    // Nonce-ul îl verifică WooCommerce înainte de aceste cârlige.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    $accepted = isset($_POST['gi_roi']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi']));
    $seen = isset($_POST['gi_roi_seen']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi_seen']));
    // phpcs:enable WordPress.Security.NonceVerification.Missing
    if (!$accepted || !$seen || !gi_roi_displayed_at()) {
        return 'Ca să finalizezi rezervarea, citește regulamentul până la capăt și bifează că l-ai citit și îl accepți.';
    }
    return '';
}

function gi_roi_validate_checkout() {
    if (!gi_roi_active()) {
        return;
    }
    $error = gi_roi_acceptance_error();
    if ('' !== $error) {
        wc_add_notice($error, 'error');
    }
}
add_action('woocommerce_checkout_process', 'gi_roi_validate_checkout');

/**
 * Dovada acceptării, pusă în comandă.
 */
function gi_roi_write_acceptance($order) {
    $hash = gi_roi_hash();
    $shown = gi_roi_displayed_at();
    $order->update_meta_data('_gi_roi_acceptat', gmdate('c'));
    $order->update_meta_data('_gi_roi_versiune', gi_roi_version());
    $order->update_meta_data('_gi_roi_cod', $hash);
    $order->update_meta_data('_gi_roi_limba', gi_roi_language());
    $order->update_meta_data('_gi_roi_derulat', 'da');
    $order->update_meta_data('_gi_roi_durata_s', $shown ? max(0, time() - $shown) : 0);
    gi_roi_archive($hash, gi_roi_version(), gi_roi_label(), gi_roi_text());
}

function gi_roi_save_acceptance($order) {
    if ($order instanceof WC_Order && gi_roi_active()) {
        gi_roi_write_acceptance($order);
    }
}
add_action('woocommerce_checkout_create_order', 'gi_roi_save_acceptance');

/**
 * Plata unei comenzi existente (link de plată): aceeași cerință, aceeași dovadă.
 */
function gi_roi_before_pay($order) {
    if (!$order instanceof WC_Order || !gi_roi_active()) {
        return;
    }
    $error = gi_roi_acceptance_error();
    if ('' !== $error) {
        wc_add_notice($error, 'error');
        return;
    }
    gi_roi_write_acceptance($order);
    $order->save();
    gi_roi_add_order_note($order);
}
add_action('woocommerce_before_pay_action', 'gi_roi_before_pay');

/**
 * O singură notă pe comandă pentru fiecare variantă acceptată (o comandă reluată nu adaugă note în plus).
 */
function gi_roi_add_order_note($order) {
    if (!$order instanceof WC_Order || !$order->get_meta('_gi_roi_acceptat')) {
        return;
    }
    $code = (string) $order->get_meta('_gi_roi_cod');
    if ($order->get_meta('_gi_roi_nota') === $code) {
        return;
    }
    $order->add_order_note(sprintf('Regulament acceptat: versiunea %s, limba %s, cod %s, derulat până jos (declarat de browser), după %s s de la afișare.', $order->get_meta('_gi_roi_versiune'), $order->get_meta('_gi_roi_limba'), substr($code, 0, 12), $order->get_meta('_gi_roi_durata_s')));
    $order->update_meta_data('_gi_roi_nota', $code);
    $order->save();
}
add_action('woocommerce_checkout_order_created', 'gi_roi_add_order_note');

/**
 * Păstrează textul exact acceptat, o singură dată pentru fiecare variantă (după cod). Nu se șterge niciodată.
 */
function gi_roi_archive($hash, $version, $label, $text) {
    $archive = get_option('gi_roi_arhiva', array());
    if (!is_array($archive)) {
        $archive = array();
    }
    if (isset($archive[$hash]) || '' === $text) {
        return;
    }
    $archive[$hash] = array('versiune' => $version, 'data' => gmdate('c'), 'eticheta' => $label, 'text' => $text);
    update_option('gi_roi_arhiva', $archive, false);
}

/**
 * În pagina comenzii din administrare: ce a acceptat clientul.
 */
function gi_roi_admin_order_box($order) {
    $when = $order->get_meta('_gi_roi_acceptat');
    if (!$when) {
        return;
    }
    ?>
    <p class="form-field form-field-wide"><strong>Regulament acceptat:</strong><br>
        <?php echo esc_html(sprintf('%s UTC · versiunea %s · limba %s · derulat până jos (declarat de browser): %s · după %s s de la afișare · cod %s', str_replace('T', ' ', substr($when, 0, 19)), $order->get_meta('_gi_roi_versiune'), $order->get_meta('_gi_roi_limba'), $order->get_meta('_gi_roi_derulat'), $order->get_meta('_gi_roi_durata_s'), substr((string) $order->get_meta('_gi_roi_cod'), 0, 12))); ?></p>
    <?php
}
add_action('woocommerce_admin_order_data_after_order_details', 'gi_roi_admin_order_box');

/**
 * Cererile directe către interfața de plată Store API (folosită de pagina cu blocuri) ocoleau bifa:
 * cu pagina clasică, finalizarea se face numai din formular.
 */
function gi_roi_block_store_api($result, $server, $request) {
    if (null !== $result || !gi_roi_active() || gi_roi_checkout_uses_blocks()) {
        return $result;
    }
    if (in_array($request->get_method(), array('POST', 'PUT', 'PATCH'), true) && preg_match('#^/wc/store(?:/v\d+)?/checkout(?:/|$)#', $request->get_route())) {
        return new WP_Error('gi_roi_required', 'Rezervarea se finalizează numai din pagina de plată, după ce citești și bifezi regulamentul.', array('status' => 403));
    }
    return $result;
}
add_filter('rest_pre_dispatch', 'gi_roi_block_store_api', 10, 3);

/**
 * Textul regulamentului nu se mai oferă nici prin interfața REST publică a WordPress-ului (cititorii obișnuiți nu au nevoie de el acolo).
 */
function gi_roi_hide_in_rest($response, $post) {
    $page = gi_roi_page();
    if ($page && $post instanceof WP_Post && $post->ID === $page->ID && !current_user_can('edit_pages')) {
        $data = $response->get_data();
        foreach (array('content', 'excerpt') as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field]['rendered'] = '';
                unset($data[$field]['raw']);
            }
        }
        $response->set_data($data);
    }
    return $response;
}
add_filter('rest_prepare_page', 'gi_roi_hide_in_rest', 10, 2);

/**
 * Pagina regulamentului: conținutul e „doar de consultat” (fără selectare, copiere, tipărire).
 */
function gi_roi_protect_page_content($content) {
    $page = gi_roi_page();
    if (gi_roi_rendering() || !$page || !is_singular('page') || !in_the_loop() || get_the_ID() !== $page->ID) {
        return $content;
    }
    $note = '<p class="gi-protejat-nota">Regulamentul se poate citi numai pe site-ul gradina-ielelor.ro.</p>';
    return '<div class="gi-protejat" data-gi-protejat>' . $content . '</div>' . $note;
}
add_filter('the_content', 'gi_roi_protect_page_content', 20);

/**
 * Avertisment în administrare când acceptarea e cerută, dar nu poate funcționa.
 */
function gi_roi_admin_notice() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, array('dashboard', 'woocommerce_page_wc-orders', 'edit-shop_order'), true) || !current_user_can('manage_options') || !gi_roi_required()) {
        return;
    }
    $problem = '';
    if (!gi_roi_page()) {
        $problem = 'pagina „Regulament & politici” nu e publicată, așa că la plată nu apare caseta cu regulamentul și comenzile nu păstrează dovada acceptării.';
    } elseif (gi_roi_checkout_uses_blocks()) {
        $problem = 'pagina de finalizare folosește blocurile noi WooCommerce, așa că la plată nu apare caseta cu regulamentul. Pagina trebuie să fie cea clasică, cu [woocommerce_checkout].';
    }
    if ('' !== $problem) {
        echo '<div class="notice notice-error"><p><strong>Grădina Ielelor — regulamentul la plată:</strong> ' . esc_html($problem) . '</p></div>';
    }
}
add_action('admin_notices', 'gi_roi_admin_notice');

/**
 * Stil și script mici, numai unde sunt necesare: pagina de finalizare/plată și pagina regulamentului.
 */
function gi_roi_enqueue() {
    $on_checkout = function_exists('is_checkout') && is_checkout() && !(function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'));
    $page = gi_roi_page();
    $on_page = $page && is_singular('page') && get_queried_object_id() === $page->ID;
    if (!$on_checkout && !$on_page) {
        return;
    }
    wp_enqueue_style('gi-roi', gi_asset_url('css/roi.css'), array(), GI_VERSION);
    wp_enqueue_script('gi-roi', gi_asset_url('js/roi.js'), $on_checkout ? array('jquery') : array(), GI_VERSION, true);
}
add_action('wp_enqueue_scripts', 'gi_roi_enqueue', 30);
