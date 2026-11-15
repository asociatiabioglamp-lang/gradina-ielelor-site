<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-7-0.js (fișa de cazare și registrul):
 *  - /?gi_test=fise_randuri         → toate fișele din tabel (fără imaginile semnăturilor, doar lungimea lor)
 *  - /?gi_test=fise_curata          → șterge toate fișele și repune setările fișei la valorile de pornire
 *  - /?gi_test=gd                   → are PHP-ul extensia GD (pentru verificarea semnăturilor goale)
 *  - /?gi_test=fise_vechi&id=N&luni=13 → mută data înscrierii fișei N cu atâtea luni în urmă (pentru termenul de păstrare)
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
    if (!in_array($t, array('fise_randuri', 'fise_curata', 'fise_vechi', 'gd'), true) || !class_exists('GI_Fise')) {
        return;
    }
    global $wpdb;
    $table = GI_Fise::table();
    header('Content-Type: application/json');
    if ('gd' === $t) {
        echo wp_json_encode(array('gd' => function_exists('imagecreatefromstring') && function_exists('imagecolorat')));
        exit;
    }
    if ('fise_randuri' === $t) {
        $rows = $wpdb->get_results("SELECT id, an, nr, serie, creat, creat_de, receptioner, rezervare, comanda, adult_nr, nume, prenume, nastere, sex, loc_nastere, cetatenie, tip_act, serie_act, act_valabil, cnp, domiciliu, telefon, sosire, plecare, gdpr_text, unitate, LENGTH(semn_turist) AS semn_t, LENGTH(semn_receptie) AS semn_r, LEFT(semn_turist, 22) AS semn_t_start FROM {$table} ORDER BY id ASC", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        echo wp_json_encode(array('rows' => $rows ? $rows : array()));
    } elseif ('fise_curata' === $t) {
        $wpdb->query("DELETE FROM {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gi\\_fise\\_ultim\\_%'"); // contoarele seriei (altfel seria nu mai pornește de la 1)
        delete_option(GI_Fise::SETTINGS);
        echo wp_json_encode(array('ok' => true));
    } else {
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $months = isset($_GET['luni']) ? absint(wp_unslash($_GET['luni'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $when = gmdate('Y-m-d H:i:s', strtotime('-' . $months . ' months', time()));
        $wpdb->update($table, array('creat' => $when), array('id' => $id));
        echo wp_json_encode(array('ok' => true, 'creat' => $when));
    }
    exit;
}, 20);
