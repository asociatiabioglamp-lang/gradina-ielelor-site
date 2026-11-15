<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-5-0.js (Zona echipei):
 *  - /?gi_test=emailuri         → câte emailuri a încercat WordPress să trimită de la ultima întrebare (le oprește, nu pleacă nimic)
 *  - /?gi_test=utilizator&login=X → rolurile, drepturile Zonei și numărul de sesiuni ale utilizatorului
 *  - /?gi_test=cupon&cod=X      → datele cuponului (valoare, tip, folosiri, expirare, cine l-a generat)
 *  - /?gi_test=sterge_utilizator&login=X → șterge utilizatorul de probă
 *  - /?gi_test=zona_reset       → readuce limitele și opțiunile Zonei la starea de pornire
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('pre_wp_mail', function ($null, $atts) {
    update_option('gi_test_emailuri', (int) get_option('gi_test_emailuri', 0) + 1, false);
    return true; // nu trimite nimic
}, 10, 2);

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('' === $t || !in_array($t, array('emailuri', 'utilizator', 'cupon', 'sterge_utilizator', 'zona_reset'), true)) {
        return;
    }
    header('Content-Type: application/json');
    if ('emailuri' === $t) {
        $n = (int) get_option('gi_test_emailuri', 0);
        update_option('gi_test_emailuri', 0, false);
        echo wp_json_encode(array('count' => $n));
    } elseif ('utilizator' === $t) {
        $login = isset($_GET['login']) ? sanitize_user(wp_unslash($_GET['login'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $u = get_user_by('login', $login);
        if (!$u) {
            echo wp_json_encode(array('exists' => false));
        } else {
            $caps = array();
            foreach (array('gi_zona', 'gi_vouchere', 'gi_vouchere_toate', 'gi_copaci', 'gi_echipa', 'edit_posts', 'upload_files', 'manage_woocommerce', 'read') as $c) {
                $caps[$c] = $u->has_cap($c);
            }
            echo wp_json_encode(array('exists' => true, 'id' => $u->ID, 'roles' => array_values($u->roles), 'caps' => $caps, 'sessions' => count(WP_Session_Tokens::get_instance($u->ID)->get_all()), 'email' => $u->user_email, 'name' => $u->display_name));
        }
    } elseif ('cupon' === $t) {
        $code = isset($_GET['cod']) ? wc_format_coupon_code(wp_unslash($_GET['cod'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $id = $code ? wc_get_coupon_id_by_code($code) : 0;
        if (!$id) {
            echo wp_json_encode(array('exists' => false));
        } else {
            $c = new WC_Coupon($id);
            $exp = $c->get_date_expires();
            echo wp_json_encode(array('exists' => true, 'id' => $id, 'amount' => (float) $c->get_amount(), 'type' => $c->get_discount_type(), 'limit' => (int) $c->get_usage_limit(), 'expires' => $exp ? $exp->getTimestamp() : 0, 'creator' => (int) get_post_meta($id, '_gi_voucher_creator', true), 'lot' => (string) get_post_meta($id, '_gi_voucher_lot', true), 'description' => $c->get_description()));
        }
    } elseif ('sterge_utilizator' === $t) {
        $login = isset($_GET['login']) ? sanitize_user(wp_unslash($_GET['login'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $u = get_user_by('login', $login);
        if ($u && $u->ID > 1) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($u->ID);
        }
        echo wp_json_encode(array('ok' => (bool) $u));
    } elseif ('zona_reset' === $t) {
        delete_option('gi_zona_limite');
        echo wp_json_encode(array('ok' => true));
    }
    exit;
}, 1);
