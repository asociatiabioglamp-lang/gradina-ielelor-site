<?php
/**
 * Plugin Name: Grădina Ielelor — One Page + BRW
 * Plugin URI: https://gradina-ielelor.ro/
 * Description: Template server-side pentru pagina principală, cu WooCommerce + OVABRW drept singur motor de rezervări. Nu include și nu pornește formularul React demonstrativ.
 * Version: 1.0.8
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Echipa Grădina Ielelor
 * Text Domain: gradina-ielelor
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GI_VERSION', '1.0.8');
define('GI_FILE', __FILE__);
define('GI_PATH', plugin_dir_path(__FILE__));
define('GI_URL', plugin_dir_url(__FILE__));
define('GI_TEMPLATE_SLUG', 'gi-one-page.php');

// Contract explicit: pachetul nu conține runtime-ul/formularul React demonstrativ.
define('GI_REACT_DEMO_ENABLED', false);

require_once GI_PATH . 'includes/content.php';
require_once GI_PATH . 'includes/template-tags.php';
require_once GI_PATH . 'includes/class-gi-plugin.php';
require_once GI_PATH . 'includes/class-gi-seo.php';
require_once GI_PATH . 'includes/class-gi-settings.php';

GI_Plugin::init();
GI_SEO::init();
GI_Settings::init();
