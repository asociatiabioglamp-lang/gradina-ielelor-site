<?php
/**
 * Plugin Name: Grădina Ielelor — Site + BRW
 * Plugin URI: https://gradina-ielelor.ro/
 * Description: Template-uri server-side pentru pagina principală și paginile dedicate celor cinci categorii (Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private), cu WooCommerce + OVABRW drept singur motor de rezervări. Nu include și nu pornește formularul React demonstrativ.
 * Version: 1.10.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Echipa Grădina Ielelor
 * Text Domain: gradina-ielelor
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GI_VERSION', '1.10.0');
define('GI_FILE', __FILE__);
define('GI_PATH', plugin_dir_path(__FILE__));
define('GI_URL', plugin_dir_url(__FILE__));
define('GI_TEMPLATE_SLUG', 'gi-one-page.php');

// Contract explicit: pachetul nu conține runtime-ul/formularul React demonstrativ.
define('GI_REACT_DEMO_ENABLED', false);

require_once GI_PATH . 'includes/content.php';
require_once GI_PATH . 'includes/pages.php';
require_once GI_PATH . 'includes/offer-pages.php';
require_once GI_PATH . 'includes/template-tags.php';
require_once GI_PATH . 'includes/class-gi-plugin.php';
require_once GI_PATH . 'includes/class-gi-seo.php';
require_once GI_PATH . 'includes/class-gi-security.php';
require_once GI_PATH . 'includes/class-gi-light.php';
require_once GI_PATH . 'includes/class-gi-translate.php';
require_once GI_PATH . 'includes/class-gi-settings.php';
require_once GI_PATH . 'includes/redirects.php';
require_once GI_PATH . 'includes/plata-la-locatie.php';
require_once GI_PATH . 'includes/acceptare-regulament.php';
require_once GI_PATH . 'includes/pregatire-pagini.php';
require_once GI_PATH . 'includes/class-gi-puncte.php';
require_once GI_PATH . 'includes/class-gi-istoric.php';
require_once GI_PATH . 'includes/class-gi-rezervari.php';
require_once GI_PATH . 'includes/class-gi-fise.php';
require_once GI_PATH . 'includes/class-gi-text.php';
require_once GI_PATH . 'includes/class-gi-harta.php';
require_once GI_PATH . 'includes/class-gi-vouchere.php';
require_once GI_PATH . 'includes/class-gi-zona.php';

add_action('before_woocommerce_init', function () {
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        // Comenzile se citesc și se scriu numai prin metodele WooCommerce (compatibil cu tabelele de comenzi noi).
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

GI_Plugin::init();
GI_SEO::init();
GI_Security::init();
GI_Light::init();
GI_Translate::init();
GI_Settings::init();
GI_Puncte::init();
GI_Istoric::init();
GI_Rezervari::init();
GI_Fise::init();
GI_Harta::init();
GI_Vouchere::init();
GI_Zona::init();
