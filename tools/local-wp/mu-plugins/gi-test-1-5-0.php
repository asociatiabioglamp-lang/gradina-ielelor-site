<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-5-0.js (Zona echipei):
 *  - /?gi_test=emailuri         → câte emailuri a încercat WordPress să trimită de la ultima întrebare (le oprește, nu pleacă nimic)
 *  - /?gi_test=utilizator&login=X → rolurile, drepturile Zonei și numărul de sesiuni ale utilizatorului
 *  - /?gi_test=cupon&cod=X      → datele cuponului (valoare, tip, folosiri, expirare, cine l-a generat)
 *  - /?gi_test=sterge_utilizator&login=X → șterge utilizatorul de probă
 *  - /?gi_test=zona_reset       → readuce limitele și opțiunile Zonei la starea de pornire
 *  - /?gi_test=esecuri_login    → câte autentificări greșite a văzut un ascultător montat DUPĂ pluginul Grădinii (la init, prioritate 10); apoi se resetează
 *  - /?gi_test=cauta_optiuni&q=X → de câte ori apare textul X în valorile opțiunilor (parolele nu trebuie să rămână în baza de date)
 *  - /?gi_test=ini              → post_max_size și upload_max_filesize ale serverului local
 *  - /?gi_test=creeaza_client&login=X&email=Y&parola=Z → creează un cont de client (rol „customer”)
 *  - /?gi_test=curata_teste     → șterge conturile de probă rămase (colega.test.*, client.test.*) după o rulare întreruptă
 *  - /?gi_test=filtru_voucher&html=… → ce întoarce filtrul care înlocuiește propoziția despre folosirea parțială a voucherului
 */
if (!defined('ABSPATH')) {
    exit;
}

// Numai pe WordPress-ul local: pe orice alt domeniu nu face nimic (nu oprește emailurile, nu are adrese de test).
// Se verifică adresa din setările site-ului (nu antetul Host, pe care îl poate trimite oricine).
$gi_test_host = strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST));
if (!in_array($gi_test_host, array('127.0.0.1', 'localhost'), true)) {
    return;
}

// Ca un plugin de limitare a autentificărilor, montat DUPĂ pluginul Grădinii (la „init”), la prioritatea obișnuită 10:
// dacă Zona ar ieși din cerere înainte de prioritatea 10, acest ascultător n-ar mai vedea eșecul.
add_action('init', function () {
    add_action('wp_login_failed', function () {
        update_option('gi_test_esecuri_login', (int) get_option('gi_test_esecuri_login', 0) + 1, false);
    }, 10);
}, 20);

add_filter('pre_wp_mail', function ($null, $atts) {
    update_option('gi_test_emailuri', (int) get_option('gi_test_emailuri', 0) + 1, false);
    return true; // nu trimite nimic
}, 10, 2);

// /zona-echipei/…?gi_test_oversize=1 (POST): ca la o cerere mai mare decât post_max_size, PHP lasă $_POST gol, iar CONTENT_LENGTH rămâne mare.
add_action('init', function () {
    if (isset($_GET['gi_test_oversize']) && isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $_POST = array();
        $_FILES = array();
        $_SERVER['CONTENT_LENGTH'] = '99999999';
    }
}, 0);

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ('' === $t || !in_array($t, array('emailuri', 'utilizator', 'cupon', 'sterge_utilizator', 'zona_reset', 'esecuri_login', 'cauta_optiuni', 'ini', 'creeaza_client', 'filtru_voucher', 'curata_teste'), true)) {
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
            echo wp_json_encode(array('exists' => true, 'id' => $id, 'amount' => (float) $c->get_amount(), 'type' => $c->get_discount_type(), 'limit' => (int) $c->get_usage_limit(), 'expires' => $exp ? $exp->getTimestamp() : 0, 'creator' => (int) get_post_meta($id, '_gi_voucher_creator', true), 'individual' => (bool) $c->get_individual_use(), 'lot' => (string) get_post_meta($id, '_gi_voucher_lot', true), 'description' => $c->get_description()));
        }
    } elseif ('sterge_utilizator' === $t) {
        $login = isset($_GET['login']) ? sanitize_user(wp_unslash($_GET['login'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $u = get_user_by('login', $login);
        if ($u && $u->ID > 1) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($u->ID);
        }
        echo wp_json_encode(array('ok' => (bool) $u));
    } elseif ('esecuri_login' === $t) {
        $n = (int) get_option('gi_test_esecuri_login', 0);
        update_option('gi_test_esecuri_login', 0, false);
        echo wp_json_encode(array('count' => $n));
    } elseif ('cauta_optiuni' === $t) {
        global $wpdb;
        $q = isset($_GET['q']) ? (string) wp_unslash($_GET['q']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $n = '' === $q ? -1 : (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_value LIKE %s", '%' . $wpdb->esc_like($q) . '%'));
        echo wp_json_encode(array('count' => $n));
    } elseif ('ini' === $t) {
        echo wp_json_encode(array('post_max_size' => ini_get('post_max_size'), 'upload_max_filesize' => ini_get('upload_max_filesize'), 'wp_max_upload_size' => wp_max_upload_size()));
    } elseif ('creeaza_client' === $t) {
        $u = isset($_GET['login']) ? sanitize_user(wp_unslash($_GET['login'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $e = isset($_GET['email']) ? sanitize_email(wp_unslash($_GET['email'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $pw = isset($_GET['parola']) ? (string) wp_unslash($_GET['parola']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $id = ($u && $e && $pw) ? wp_insert_user(array('user_login' => $u, 'user_email' => $e, 'user_pass' => $pw, 'role' => 'customer')) : 0;
        echo wp_json_encode(array('ok' => $id && !is_wp_error($id), 'id' => is_wp_error($id) ? 0 : (int) $id));
    } elseif ('filtru_voucher' === $t) {
        $html = isset($_GET['html']) ? (string) wp_unslash($_GET['html']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo wp_json_encode(array('out' => GI_Plugin::voucher_whole_use($html, array())));
    } elseif ('curata_teste' === $t) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $n = 0;
        foreach (get_users(array('search' => '*.test.*', 'search_columns' => array('user_login'), 'fields' => array('ID', 'user_login'))) as $u) {
            if ($u->ID > 1 && (0 === strpos($u->user_login, 'colega.test.') || 0 === strpos($u->user_login, 'client.test.'))) {
                wp_delete_user($u->ID);
                $n++;
            }
        }
        echo wp_json_encode(array('sterse' => $n));
    } elseif ('zona_reset' === $t) {
        delete_option('gi_zona_limite');
        echo wp_json_encode(array('ok' => true));
    }
    exit;
}, 1);
