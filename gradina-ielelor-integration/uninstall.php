<?php
// Șterge exclusiv opțiunile acestui plugin; nu atinge paginile, produsele sau comenzile.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('gi_integration_options');
