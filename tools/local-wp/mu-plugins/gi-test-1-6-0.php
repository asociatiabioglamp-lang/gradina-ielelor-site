<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-6-0.js (codul QR al rezervării și pagina Recepție):
 *  - /?gi_test=comanda&stare=processing&nume=X&prenume=Y&tel=Z&sosire=aaaa-ll-zz&plecare=aaaa-ll-zz&adulti=2&copii=1[&metoda=cod]
 *        → creează o comandă de probă (produsul „Rezervare de probă”, câmpurile „Check in:”, „Check out:”, „Cantitate:”, „Adulți”, „Copii”), întoarce id, cheie și adresa paginii de mulțumire
 *        (cu &fara_cod=1: scoate codul pe care l-ar fi pus emailul de stare, ca la comenzile făcute înainte de 1.6.0)
 *  - /?gi_test=stare_comanda&id=N&stare=cancelled → schimbă starea comenzii
 *  - /?gi_test=cod_comanda&id=N → codul rezervării păstrat în comandă (gol dacă încă nu are)
 *  - /?gi_test=bloc_email&id=N&text=0|1&admin=0|1 → ce adaugă pluginul în emailul de confirmare (HTML sau text)
 *  - /?gi_test=sterge_comenzi → șterge comenzile de probă
 *  - /?gi_test=permisiuni → rolurile și drepturile Recepției (pentru verificare)
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_test_host = strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST));
if (!in_array($gi_test_host, array('127.0.0.1', 'localhost'), true)) {
    return;
}

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('' === $t || !in_array($t, array('comanda', 'stare_comanda', 'cod_comanda', 'bloc_email', 'sterge_comenzi', 'permisiuni'), true) || !function_exists('wc_create_order')) {
        return;
    }
    $q = function ($key, $default = '') {
        return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    };
    header('Content-Type: application/json');
    if ('comanda' === $t) {
        $product = get_page_by_path('rezervare-de-proba', OBJECT, 'product');
        $order = wc_create_order();
        $item = new WC_Order_Item_Product();
        $item->set_name('Glamping, rezervare de probă');
        $item->set_quantity(1);
        $item->set_total(450);
        if ($product) {
            $item->set_product_id($product->ID);
        }
        $fmt = function ($iso) {
            return gmdate('d-m-Y', strtotime($iso . ' 12:00:00 UTC'));
        };
        if ('' !== $q('sosire')) {
            $item->add_meta_data('Check in:', $fmt($q('sosire')));
        }
        if ('' !== $q('plecare')) {
            $item->add_meta_data('Check out:', $fmt($q('plecare')));
        }
        $item->add_meta_data('Cantitate:', '1');
        if ('' !== $q('adulti')) {
            $item->add_meta_data('Adulți:', $q('adulti'));
        }
        if ('' !== $q('copii')) {
            $item->add_meta_data('Copii:', $q('copii'));
        }
        $order->add_item($item);
        $order->set_billing_first_name($q('nume', 'Ana'));
        $order->set_billing_last_name($q('prenume', 'Testescu'));
        $order->set_billing_phone($q('tel', '0700000000'));
        $order->set_billing_email('client.test.' . wp_generate_password(6, false, false) . '@example.com');
        $order->set_billing_address_1('Strada Probei 1');
        $order->set_billing_city('Brașov');
        $order->set_billing_country('RO');
        $order->set_payment_method_title($q('plata', 'Card online'));
        if ('' !== $q('metoda')) {
            $order->set_payment_method($q('metoda'));
        }
        $order->set_total(450);
        $order->update_meta_data('_gi_test_comanda', '1');
        $order->save();
        $status = $q('stare', 'processing');
        $order->set_status($status);
        if ('processing' === $status || 'completed' === $status) {
            $order->set_date_paid(time());
        }
        $order->save();
        if ('1' === $q('fara_cod')) {
            // Comandă ca cele făcute înainte de 1.6.0: emailul de stare (oprit local) i-ar fi pus deja un cod.
            $order->delete_meta_data('_gi_cod_rezervare');
            $order->save();
        }
        echo wp_json_encode(array('id' => $order->get_id(), 'key' => $order->get_order_key(), 'url' => $order->get_checkout_order_received_url(), 'vezi' => $order->get_view_order_url(), 'email' => $order->get_billing_email()));
    } elseif ('stare_comanda' === $t) {
        $order = wc_get_order((int) $q('id'));
        if ($order) {
            $order->set_status($q('stare', 'cancelled'));
            $order->save();
        }
        echo wp_json_encode(array('ok' => (bool) $order));
    } elseif ('cod_comanda' === $t) {
        $order = wc_get_order((int) $q('id'));
        echo wp_json_encode(array('cod' => $order ? (string) $order->get_meta('_gi_cod_rezervare') : ''));
    } elseif ('bloc_email' === $t) {
        $order = wc_get_order((int) $q('id'));
        ob_start();
        if ($order) {
            GI_Rezervari::show_in_email($order, '1' === $q('admin'), '1' === $q('text'), null);
        }
        echo wp_json_encode(array('html' => ob_get_clean()));
    } elseif ('sterge_comenzi' === $t) {
        $n = 0;
        $ids = wc_get_orders(array('limit' => -1, 'status' => 'any', 'meta_key' => '_gi_test_comanda', 'meta_value' => '1', 'return' => 'ids')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        foreach ($ids as $id) {
            $o = wc_get_order($id);
            if ($o) {
                $o->delete(true);
                $n++;
            }
        }
        echo wp_json_encode(array('sterse' => $n));
    } elseif ('permisiuni' === $t) {
        $r = get_role('gi_receptie');
        $fise = array();
        foreach (wp_roles()->roles as $name => $role) {
            if (!empty($role['capabilities']['gi_fise_vezi'])) {
                $fise[] = $name;
            }
        }
        echo wp_json_encode(array('exista' => (bool) $r, 'caps' => $r ? array_keys(array_filter($r->capabilities)) : array(), 'fise_vezi_roluri' => $fise));
    }
    exit;
}, 20);
