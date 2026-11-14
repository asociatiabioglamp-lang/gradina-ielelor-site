<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-securitate.js: arată antetele pe care le-ar trimite GI_Security când cererea vine prin HTTPS
 * (serverul local e HTTP, deci HSTS nu apare în mod normal).
 * Adresă: /?gi_test=hsts
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    if (!isset($_GET['gi_test']) || 'hsts' !== $_GET['gi_test'] || !class_exists('GI_Security')) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }
    $_SERVER['HTTPS'] = 'on';
    header('Content-Type: application/json');
    echo wp_json_encode(array('https' => GI_Security::headers(), 'ssl' => is_ssl()));
    exit;
}, 1);
