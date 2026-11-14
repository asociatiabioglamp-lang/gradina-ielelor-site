<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-securitate.js: arată antetele pe care le-ar trimite GI_Security când cererea vine prin HTTPS
 * (serverul local e HTTP, deci HSTS nu apare în mod normal).
 * Adresă: /?gi_test=hsts
 *  - https_domeniu_oarecare: antetele pe un domeniu care NU e în lista live (cum e copia de test) → fără HSTS;
 *  - https_domeniu_live: aceleași antete după ce domeniul curent e adăugat în lista live → cu HSTS.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    if (!isset($_GET['gi_test']) || 'hsts' !== $_GET['gi_test'] || !class_exists('GI_Security')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }
    $_SERVER['HTTPS'] = 'on';
    $oarecare = GI_Security::headers();
    add_filter('gi_security_hsts_hosts', function ($hosts) {
        $hosts[] = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        return $hosts;
    });
    $live = GI_Security::headers();
    header('Content-Type: application/json');
    echo wp_json_encode(array('ssl' => is_ssl(), 'https_domeniu_oarecare' => $oarecare, 'https_domeniu_live' => $live));
    exit;
}, 1);
