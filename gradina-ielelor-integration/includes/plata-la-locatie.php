<?php
/**
 * Plata la locație: prețul de pe site + un procent (implicit 10%), adăugat automat la finalizarea rezervării.
 *
 * Tarifele de pe site sunt pentru plata online (NETOPIA, inclusiv cardurile de vacanță). Dacă clientul alege
 * o metodă de plată la locație (ex. „Plată la livrare”, id `cod`), coșul primește o linie în plus, vizibilă înainte
 * de plată și salvată în comandă. Plata online rămâne la prețul afișat.
 *
 * Regula și formularea: decizia proprietarului din 31.10.2026 (docs/decizii.md). Tarifele de pe site sunt pentru plata
 * online, cu rezervarea garantată; la plata în locație tariful e mai mare, iar rezervarea nu e garantată.
 * Se pornește din Setări → Grădina Ielelor, bifând metoda de plată la locație de pe site.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Setările, din opțiunile pluginului: pornit / oprit, procentul și metodele de plată la locație.
 */
function gi_onsite_surcharge_settings() {
    $options = gi_options();
    return array(
        'enabled' => !empty($options['onsite_surcharge_enabled']),
        'percent' => max(0, min(100, (float) $options['onsite_surcharge_percent'])),
        'gateways' => array_values(array_filter((array) $options['onsite_surcharge_gateways'])),
    );
}

/**
 * Adaugă majorarea în coș când metoda de plată aleasă e una „la locație”.
 * Baza: totalul cazării după reduceri și vouchere, cu TVA inclus (exact suma pe care o vede clientul).
 */
function gi_onsite_surcharge_fee($cart) {
    if ((is_admin() && !wp_doing_ajax()) || !function_exists('WC') || !WC()->session) {
        return;
    }
    $settings = gi_onsite_surcharge_settings();
    if (!$settings['enabled'] || $settings['percent'] <= 0) {
        return;
    }
    if (!in_array(WC()->session->get('chosen_payment_method'), $settings['gateways'], true)) {
        return;
    }

    $base = (float) $cart->get_cart_contents_total() + (float) $cart->get_cart_contents_tax();
    $fee = round($base * $settings['percent'] / 100, wc_get_price_decimals());
    if ($fee <= 0) {
        return;
    }
    $label = sprintf('Tarif pentru plata la locație (+%s%%)', wc_format_localized_decimal($settings['percent']));
    $cart->add_fee($label, $fee, false);
}
add_action('woocommerce_cart_calculate_fees', 'gi_onsite_surcharge_fee');

/**
 * Pe pagina de finalizare (clasică): la schimbarea metodei de plată, totalul se recalculează pe loc,
 * ca clientul să vadă majorarea înainte să apese „Plasează comanda”.
 */
function gi_onsite_surcharge_refresh_script() {
    $settings = gi_onsite_surcharge_settings();
    if (!$settings['enabled'] || !function_exists('is_checkout') || !is_checkout() || !wp_script_is('wc-checkout', 'enqueued')) {
        return;
    }
    wp_add_inline_script('wc-checkout', 'jQuery(function($){$("form.checkout").on("change","input[name=payment_method]",function(){$(document.body).trigger("update_checkout");});});');
}
add_action('wp_enqueue_scripts', 'gi_onsite_surcharge_refresh_script', 20);

/**
 * Textul pentru vizitatori, lângă prețuri și lângă rezervare; gol când majorarea e oprită.
 */
function gi_onsite_surcharge_notice() {
    $settings = gi_onsite_surcharge_settings();
    if (!$settings['enabled'] || $settings['percent'] <= 0 || !$settings['gateways']) {
        return '';
    }
    return sprintf('Prețurile sunt pentru plata online, cu rezervarea garantată. Dacă alegi plata în locație, tariful e cu %s%% mai mare, iar rezervarea nu e garantată.', wc_format_localized_decimal($settings['percent']));
}
