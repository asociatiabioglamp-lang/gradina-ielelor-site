<?php
/**
 * NUMAI LOCAL, niciodată pe staging sau live.
 *
 * Pe site-ul real, paginile noastre primesc și stilurile temei vechi și ale altor module (Elementor, „hoteller-elementor”),
 * încărcate DUPĂ ale noastre. WordPress-ul local nu le are, așa că un test local putea trece, iar pe live pagina să arate altfel
 * (așa a ajuns titlul paginilor Contact / Despre noi / Regulament sub siglă).
 *
 * Cu „?gi_test=stiluri” în adresă, pagina locală primește:
 *  - regulile străine care contează, copiate de pe live la 14.11.2026 (vezi docs/audit-2026-11-14.md, „Stiluri străine”);
 *  - clasele de pe <body> pe care le pune live (kitul Elementor, bara de jos a temei);
 *  - steagul de limbă GTranslate și bara de jos a temei, cu aceleași poziții ca pe live.
 * Folosit de tools/local-wp/test-1-3-15.js.
 */
if (!defined('ABSPATH')) {
    exit;
}

function gi_test_stiluri_straine_on() {
    return isset($_GET['gi_test']) && 'stiluri' === sanitize_key(wp_unslash($_GET['gi_test'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

add_filter('body_class', function ($classes) {
    if (gi_test_stiluri_straine_on()) {
        $classes[] = 'elementor-kit-2374';
        $classes[] = 'sticky-toolbar-on';
    }
    return $classes;
});

add_action('wp_footer', function () {
    if (!gi_test_stiluri_straine_on()) {
        return;
    }
    ?>
<style id="gi-test-stiluri-straine">
/* Ordinea e cea de pe live: stilurile noastre se încarcă primele, apoi kitul Elementor, „hoteller-elementor” și la urmă tema veche. */
/* 1. kitul Elementor: elementor/css/post-2374.css */
.elementor-kit-2374 a{color:#009014}
.elementor-kit-2374 a:hover{color:#FF0000}
.elementor-kit-2374 h1{font-family:"Lora",sans-serif;font-weight:700}
.elementor-kit-2374 h2{font-family:"Lora",sans-serif;font-weight:500}
/* 2. hoteller-elementor/assets/css/hoteller-elementor.css */
.container{width:960px;margin:auto}
@media (max-width:699px){.container{padding-right:40px;padding-left:40px}}
@media only screen and (min-width:1200px){.container{max-width:1425px;width:100%;padding:0 90px}}
@media only screen and (min-width:768px) and (max-width:960px){.container{width:100%;padding:0 30px}}
@media only screen and (max-width:767px){.container{width:100%;padding:0 30px}}
/* 3. tema veche (giw / WoodMart): bootstrap-light.min.css, parts/base.min.css, parts/opt-bottom-toolbar.min.css */
:root{--wd-container-w:1280px}
.container{width:100%;padding-right:15px;padding-left:15px;margin-right:auto;margin-left:auto}
.container{max-width:var(--wd-container-w)}
a{color:#009014}
li{margin-bottom:10px}
:is(.btn,.button,button,[type="submit"],[type="button"]){text-transform:uppercase}
/* efectul măsurat pe live la trecerea cu mouse-ul peste un buton fără regulă proprie (butonul de meniu) */
:is(.btn,.button,button,[type="submit"],[type="button"]):hover{color:#3E3E3E;background-color:#e0e0e0}
.wd-toolbar{position:fixed;right:0;bottom:0;left:0;z-index:350;display:flex;align-items:center;justify-content:space-around;height:55px;background:#fff;box-shadow:0 0 9px rgba(0,0,0,.12)}
@media (min-width:1025px){.wd-toolbar{display:none}}
@media (max-width:1024px){.sticky-toolbar-on{padding-bottom:55px}}
</style>
<div id="gt_float_wrapper" style="position:fixed;top:20px;left:20px;z-index:999999"><div class="gt_float_switcher" style="display:flex;align-items:center;justify-content:center;width:82px;height:32px;background:#fff;box-shadow:0 0 6px rgba(0,0,0,.25);font:700 12px sans-serif;color:#222">RO</div></div>
<div class="wd-toolbar"><a href="#">Contul Meu</a><a href="#">Coș</a><a href="#">Meniu</a></div>
    <?php
}, 999);
