<?php
/**
 * Regulamentul la finalizarea rezervării: bifă obligatorie, cu citire, și regulament de consultat numai pe site.
 *
 * Decizia proprietarului (31.10.2026, docs/decizii.md): înainte de finalizarea rezervării, clientul citește și bifează
 * regulamentul (ROI). Textul se traduce automat în limba aleasă pe site (GTranslate), pentru că e text în pagină, nu fișier.
 * Rezervarea nu se poate trimite fără bifă. Regulamentul nu se oferă ca fișier și nu se poate copia sau tipări de pe site
 * (protecția oprește doar gesturile obișnuite; o captură de ecran nu poate fi oprită).
 *
 * În fiecare comandă se salvează dovada acceptării: data, versiunea, limba, un cod al textului exact și faptul că a fost citit.
 * Textul acceptat se păstrează în arhivă (opțiunea `gi_roi_arhiva`), ca să poată fi arătat la nevoie.
 *
 * Funcționează cu pagina de finalizare clasică ([woocommerce_checkout]), cea folosită și de OVABRW pe site-ul actual.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pagina cu regulamentul (publicată), sau null. Slug-ul se poate schimba cu filtrul `gi_roi_page_slug`.
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
    return '' !== $version ? $version : '1';
}

/**
 * Textul regulamentului, ca HTML pentru casetă: conținutul paginii, cu toate secțiunile deschise și fără linkuri spre fișiere PDF.
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
    $html = (string) apply_filters('the_content', $page->post_content);
    $html = preg_replace('/<details\b(?![^>]*\bopen\b)/i', '<details open', $html);
    $html = preg_replace('/<a\b[^>]*href=(["\'])[^"\']*\.pdf(?:\?[^"\']*)?\1[^>]*>(.*?)<\/a>/is', '$2', $html);
    return $html;
}

/**
 * Textul simplu al regulamentului (pentru cod și arhivă): fără marcaj, spații normalizate.
 */
function gi_roi_text() {
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags(gi_roi_html()), ENT_QUOTES, 'UTF-8')));
}

function gi_roi_hash() {
    return hash('sha256', gi_roi_text());
}

/**
 * Limba aleasă pe site (cookie-ul GTranslate `googtrans`, de forma /ro/en); „ro” când nu s-a ales alta.
 */
function gi_roi_language() {
    $cookie = isset($_COOKIE['googtrans']) ? sanitize_text_field(wp_unslash($_COOKIE['googtrans'])) : '';
    if ($cookie && preg_match('~^/[a-z-]{2,8}/([a-z]{2,3}(?:-[A-Za-z]{2,4})?)$~', $cookie, $match)) {
        return strtolower($match[1]);
    }
    return 'ro';
}

/**
 * Adresa regulamentului, pentru linkul din bifă (se deschide în filă nouă, în aceeași limbă aleasă).
 */
function gi_roi_url() {
    $page = gi_roi_page();
    return $page ? get_permalink($page) : gi_clean_page_url('regulament-politici');
}

/**
 * Caseta și bifa, chiar deasupra butonului de finalizare. Starea (citit / bifat) se păstrează când pagina de plată se reîmprospătează.
 */
function gi_roi_render_checkout_box() {
    if (!gi_roi_required()) {
        return;
    }
    $html = gi_roi_html();
    $checked = function_exists('wc_get_post_data_by_key') && '1' === wc_get_post_data_by_key('gi_roi');
    $seen = ('' === $html) || (function_exists('wc_get_post_data_by_key') && '1' === wc_get_post_data_by_key('gi_roi_seen'));
    $label = apply_filters('gi_roi_checkbox_label', 'Am citit și accept Regulamentul de ordine interioară și condițiile de anulare.');
    ?>
    <div class="gi-roi<?php echo $seen ? ' gi-roi-citit' : ''; ?>" data-gi-roi>
        <h3 class="gi-roi-title">Regulamentul Grădinii</h3>
        <?php if ('' !== $html) : ?>
            <p class="gi-roi-hint" data-gi-roi-hint aria-live="polite"<?php echo $seen ? ' hidden' : ''; ?>>Citește regulamentul până jos, ca să poți bifa. Se traduce automat în limba aleasă pe site.</p>
            <div class="gi-roi-box gi-protejat" data-gi-protejat data-gi-roi-box tabindex="0" role="region" aria-label="Regulamentul Grădinii">
                <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- conținutul paginii, deja filtrat de WordPress. ?>
            </div>
        <?php endif; ?>
        <p class="form-row validate-required gi-roi-check">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="gi_roi" id="gi_roi" value="1"<?php echo $checked ? ' checked' : ''; ?>>
                <span><?php echo esc_html($label); ?> <abbr class="required" title="obligatoriu">*</abbr></span>
            </label>
            <input type="hidden" name="gi_roi_seen" value="<?php echo $seen ? '1' : '0'; ?>">
        </p>
        <p class="gi-roi-link"><a href="<?php echo esc_url(gi_roi_url()); ?>" target="_blank" rel="noopener">Deschide regulamentul într-o pagină nouă</a></p>
    </div>
    <?php
}
add_action('woocommerce_review_order_before_submit', 'gi_roi_render_checkout_box', 5);

/**
 * Fără bifă (și fără citire), rezervarea nu se trimite.
 */
function gi_roi_validate_checkout() {
    if (!gi_roi_required()) {
        return;
    }
    // Nonce-ul îl verifică WooCommerce înainte de acest filtru.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    $accepted = isset($_POST['gi_roi']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi']));
    $seen = isset($_POST['gi_roi_seen']) && '1' === sanitize_text_field(wp_unslash($_POST['gi_roi_seen']));
    // phpcs:enable WordPress.Security.NonceVerification.Missing
    if ('' === gi_roi_html()) {
        $seen = true; // nu există text de citit; rămâne doar bifa
    }
    if (!$accepted || !$seen) {
        wc_add_notice('Ca să finalizezi rezervarea, citește regulamentul până la capăt și bifează că l-ai citit și îl accepți.', 'error');
    }
}
add_action('woocommerce_checkout_process', 'gi_roi_validate_checkout');

/**
 * Dovada acceptării, salvată în comandă.
 */
function gi_roi_save_acceptance($order) {
    if (!gi_roi_required() || !$order instanceof WC_Order) {
        return;
    }
    $hash = gi_roi_hash();
    $order->update_meta_data('_gi_roi_acceptat', gmdate('c'));
    $order->update_meta_data('_gi_roi_versiune', gi_roi_version());
    $order->update_meta_data('_gi_roi_cod', $hash);
    $order->update_meta_data('_gi_roi_limba', gi_roi_language());
    $order->update_meta_data('_gi_roi_citit', 'da');
    gi_roi_archive($hash, gi_roi_version(), gi_roi_text());
}
add_action('woocommerce_checkout_create_order', 'gi_roi_save_acceptance');

function gi_roi_add_order_note($order) {
    if ($order instanceof WC_Order && $order->get_meta('_gi_roi_acceptat')) {
        $order->add_order_note(sprintf('Regulament acceptat și citit: versiunea %s, limba %s, cod %s.', $order->get_meta('_gi_roi_versiune'), $order->get_meta('_gi_roi_limba'), substr((string) $order->get_meta('_gi_roi_cod'), 0, 12)));
    }
}
add_action('woocommerce_checkout_order_created', 'gi_roi_add_order_note');

/**
 * Păstrează textul exact acceptat, o singură dată pentru fiecare variantă (după cod).
 */
function gi_roi_archive($hash, $version, $text) {
    $archive = get_option('gi_roi_arhiva', array());
    if (!is_array($archive)) {
        $archive = array();
    }
    if (isset($archive[$hash]) || '' === $text) {
        return;
    }
    $archive[$hash] = array('versiune' => $version, 'data' => gmdate('c'), 'text' => $text);
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
        <?php echo esc_html(sprintf('%s UTC · versiunea %s · limba %s · citit: %s · cod %s', str_replace('T', ' ', substr($when, 0, 19)), $order->get_meta('_gi_roi_versiune'), $order->get_meta('_gi_roi_limba'), $order->get_meta('_gi_roi_citit'), substr((string) $order->get_meta('_gi_roi_cod'), 0, 12))); ?></p>
    <?php
}
add_action('woocommerce_admin_order_data_after_order_details', 'gi_roi_admin_order_box');

/**
 * Pagina regulamentului: conținutul e „doar de consultat” (fără selectare, copiere, tipărire).
 */
function gi_roi_protect_page_content($content) {
    static $busy = false;
    $page = gi_roi_page();
    if ($busy || !$page || !is_singular('page') || !in_the_loop() || get_the_ID() !== $page->ID) {
        return $content;
    }
    $busy = true;
    $note = '<p class="gi-protejat-nota">Regulamentul se poate citi numai pe site-ul gradina-ielelor.ro.</p>';
    $out = '<div class="gi-protejat" data-gi-protejat>' . $content . '</div>' . $note;
    $busy = false;
    return $out;
}
add_filter('the_content', 'gi_roi_protect_page_content', 20);

/**
 * Stil și script mici, numai unde sunt necesare: pagina de finalizare și pagina regulamentului.
 */
function gi_roi_enqueue() {
    $on_checkout = function_exists('is_checkout') && is_checkout() && !(function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'));
    $page = gi_roi_page();
    $on_page = $page && is_singular('page') && get_queried_object_id() === $page->ID;
    if (!$on_checkout && !$on_page) {
        return;
    }
    wp_enqueue_style('gi-roi', gi_asset_url('css/roi.css'), array(), GI_VERSION);
    wp_enqueue_script('gi-roi', gi_asset_url('js/roi.js'), array(), GI_VERSION, true);
}
add_action('wp_enqueue_scripts', 'gi_roi_enqueue', 30);
