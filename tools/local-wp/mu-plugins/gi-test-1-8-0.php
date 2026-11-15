<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-8-0.js (harta Grădinii, panouri informative, scriere simplă, PDF la locuri):
 *  - /?gi_test=h180_locuri  → locurile de probă (titlul începe cu „T180”, plus unul numit „Harta”): id, titlu, stare, adresă, poziția, tip, PDF (adresa și dacă fișierul există)
 *  - /?gi_test=h180_curata  → șterge locurile de probă (cu documentele lor) și planul Grădinii
 *  - /?gi_test=h180_plan    → starea planului (id imagine, fișierul există)
 *  - /?gi_test=h180_atas&id=N → există atașamentul N și fișierul lui?
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
    if (!in_array($t, array('h180_locuri', 'h180_curata', 'h180_plan', 'h180_atas'), true) || !class_exists('GI_Puncte')) {
        return;
    }
    header('Content-Type: application/json');
    $posts = get_posts(array('post_type' => GI_Puncte::TYPE, 'post_status' => 'any', 'numberposts' => 500));
    $posts = array_filter($posts, function ($p) {
        return 0 === strpos($p->post_title, 'T180') || 'Harta' === $p->post_title; // „Harta”: locul de probă pentru adresa rezervată
    });
    if ('h180_atas' === $t) { // există atașamentul și fișierul lui?
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $file = $id ? get_attached_file($id) : '';
        echo wp_json_encode(array('atas' => $id && 'attachment' === get_post_type($id), 'fisier' => $file ? file_exists($file) : false));
        exit;
    }
    if ('h180_locuri' === $t) {
        $out = array();
        foreach ($posts as $p) {
            $pdf = (int) get_post_meta($p->ID, '_gi_pdf', true);
            $file = $pdf ? get_attached_file($pdf) : '';
            $out[] = array('id' => $p->ID, 'titlu' => $p->post_title, 'stare' => $p->post_status, 'slug' => $p->post_name, 'url' => get_permalink($p), 'x' => get_post_meta($p->ID, '_gi_x', true), 'y' => get_post_meta($p->ID, '_gi_y', true), 'tip' => get_post_meta($p->ID, '_gi_tip', true), 'pdf' => $pdf, 'pdf_url' => $pdf ? wp_get_attachment_url($pdf) : '', 'pdf_exista' => $file ? file_exists($file) : false, 'continut' => $p->post_content);
        }
        echo wp_json_encode(array('locuri' => array_values($out)));
    } elseif ('h180_plan' === $t) {
        $s = GI_Harta::settings();
        $file = $s['img'] ? get_attached_file($s['img']) : '';
        echo wp_json_encode(array('img' => $s['img'], 'alt' => $s['alt'], 'exista' => $file ? file_exists($file) : false, 'url' => $s['img'] ? wp_get_attachment_url($s['img']) : ''));
    } else {
        foreach ($posts as $p) {
            $pdf = (int) get_post_meta($p->ID, '_gi_pdf', true);
            if ($pdf) {
                wp_delete_attachment($pdf, true);
            }
            wp_delete_post($p->ID, true);
        }
        $s = GI_Harta::settings();
        if ($s['img']) {
            wp_delete_attachment($s['img'], true);
        }
        delete_option(GI_Harta::OPT);
        echo wp_json_encode(array('ok' => true));
    }
    exit;
}, 20);
