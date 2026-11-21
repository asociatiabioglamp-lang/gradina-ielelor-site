<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 * Pentru test-1-9-0.js (bannerul de cookie-uri, Tag Manager numai după acord, plasa de siguranță pentru codurile vechi):
 *  - orice pagină cu ?c190_vechi=1  → în <head> apar, exact ca pe paginile vechi de pe live, codurile fostului administrator (pixel Facebook, pixel TikTok,
 *    Google Analytics ×2, Tag Manager vechi) și, ca etalon, un script al asociației (care NU trebuie scos)
 *  - /?gi_test=c190_pagini → creează (o singură dată) pagina „Cookies” (info-util/politica-cookies) și o pagină de probă cu [gi_cookie_setari]; întoarce adresele
 *  - /?gi_test=c190_optiuni → întoarce opțiunile de cookie-uri (consent_enabled, gtm_id)
 *  - /?gi_test=c190_reset → pune opțiunile la valorile implicite
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_test_host = strtolower((string) wp_parse_url((string) get_option('home'), PHP_URL_HOST));
if (!in_array($gi_test_host, array('127.0.0.1', 'localhost'), true)) {
    return;
}

add_action('wp_head', function () {
    if (!isset($_GET['c190_vechi'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }
    ?>
<!-- GTM4WP simulat (cod vechi) -->
<script data-cfasync="false">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T5L2SC7K');</script>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '371463157145157');
fbq('track', 'PageView');
</script>
<noscript>
  <img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=371463157145157&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->
<!-- TikTok Pixel Code Start -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";n=document.createElement("script");n.async=!0;n.src=r+"?sdkid="+e+"&lib="+t;document.head.appendChild(n)};
  ttq.load('D7P8DJBC77U3CRQJR7P0');
  ttq.page();
}(window, document, 'ttq');
</script>
<!-- TikTok Pixel Code End -->
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-T0Y2TVCYBD"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-T0Y2TVCYBD');
</script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-T0Y2TVCYBD"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-T0Y2TVCYBD');
</script>
<script id="gi-test-etalon">window.__etalon_ramas = true;</script>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T5L2SC7K" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php
}, 5);

add_action('init', function () {
    $t = isset($_GET['gi_test']) ? sanitize_key(wp_unslash($_GET['gi_test'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!in_array($t, array('c190_pagini', 'c190_optiuni', 'c190_reset'), true)) {
        return;
    }
    header('Content-Type: application/json');
    if ('c190_pagini' === $t) {
        $parent = get_page_by_path('info-util');
        $parent_id = $parent ? $parent->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Info util', 'post_name' => 'info-util'));
        $policy = get_page_by_path('info-util/politica-cookies');
        $policy_id = $policy ? $policy->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Politica Cookies', 'post_name' => 'politica-cookies', 'post_parent' => $parent_id, 'post_content' => '<p>Text de probă pentru politica de cookie-uri.</p>'));
        $sc = get_page_by_path('test-cookie-shortcode');
        $sc_id = $sc ? $sc->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Test cookie shortcode', 'post_name' => 'test-cookie-shortcode', 'post_content' => '<p>Înainte.</p>[gi_cookie_setari]<p>După.</p>'));
        echo wp_json_encode(array('politica' => get_permalink($policy_id), 'shortcode' => get_permalink($sc_id)));
        exit;
    }
    if ('c190_reset' === $t) {
        delete_option('gi_integration_options');
    }
    $o = gi_options();
    echo wp_json_encode(array('consent_enabled' => (int) $o['consent_enabled'], 'gtm_id' => (string) $o['gtm_id']));
    exit;
}, 1);
